<?php

namespace App\Support\Site;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\PhpExecutableFinder;
use Throwable;

/*
 * The lines of text on a public page, for the Words list, kept for an hour since
 * reading a page costs as much as showing it ("Read page again" forgets it).
 *
 * First choice: the site:scan command in a process of its own, so the dashboard's
 * session is never touched. It needs the command-line PHP, which is not the program
 * serving the dashboard (that is often php-cgi or php-fpm), so the finder looks for
 * it. If the server cannot start a process, the page is fetched over HTTP like a
 * visitor would. What went wrong, if both fail, is kept in $error for the page to show.
 */
final class PageScan
{
    /** Why the last read failed. */
    public static ?string $error = null;

    /** @return list<array{text: string, wide: bool}>|null null when the page could not be read (see $error) */
    public static function lines(string $path, bool $fresh = false): ?array
    {
        $key = 'site-scan:'.$path;
        self::$error = null;

        if ($fresh) {
            Cache::forget($key);
        }

        $cached = Cache::get($key);

        if (is_array($cached)) {
            return $cached;
        }

        $lines = self::byCommand($path) ?? self::byHttp($path);

        if ($lines === null) {
            return null;
        }

        Cache::put($key, $lines, 3600);

        return $lines;
    }

    /** @return list<array{text: string, wide: bool}>|null */
    private static function byCommand(string $path): ?array
    {
        $php = (new PhpExecutableFinder)->find();

        if ($php === false) {
            self::$error = 'The command-line PHP was not found on the server.';

            return null;
        }

        try {
            $run = Process::path(base_path())->timeout(90)->run([$php, 'artisan', 'site:scan', $path]);
        } catch (Throwable $failed) {
            self::$error = 'The server could not start a process: '.$failed->getMessage();

            return null;
        }

        $lines = $run->successful() ? json_decode(self::json($run->output()), true) : null;

        if (! is_array($lines)) {
            self::$error = trim(strip_tags((string) preg_replace('/\e\[[\d;]*m/', '', $run->errorOutput().' '.$run->output()))) ?: 'The reading command stopped without saying why.';
            self::$error = mb_substr((string) preg_replace('/\s+/', ' ', self::$error), 0, 300);

            return null;
        }

        return $lines;
    }

    /** @return list<array{text: string, wide: bool}>|null */
    private static function byHttp(string $path): ?array
    {
        $first = self::$error;

        try {
            $response = Http::timeout(30)->withHeaders(['Accept' => 'text/html', 'X-Site-Scan' => '1'])->get(url($path));
        } catch (Throwable $failed) {
            self::$error = $first.' Fetching the page over HTTP failed too: '.mb_substr($failed->getMessage(), 0, 150);

            return null;
        }

        if (! $response->successful()) {
            self::$error = $first.' The page itself answered '.$response->status().'.';

            return null;
        }

        self::$error = null;

        return PageText::lines($response->body());
    }

    /** The command prints one JSON line; ignore any notice a server's PHP writes around it. */
    private static function json(string $output): string
    {
        foreach (array_reverse(preg_split('/\R/', trim($output)) ?: []) as $line) {
            if (str_starts_with($line, '[')) {
                return $line;
            }
        }

        return '';
    }
}
