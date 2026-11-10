<?php

namespace App\Support\Site;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;

/*
 * The lines of text on a public page, for the Words list: asked of the site:scan
 * command (its own process, so the dashboard's session is never touched) and kept for
 * an hour, since reading a page costs as much as showing it. "Read again" forgets it.
 */
final class PageScan
{
    /** @return list<array{text: string, wide: bool}>|null null when the page could not be read */
    public static function lines(string $path, bool $fresh = false): ?array
    {
        $key = 'site-scan:'.$path;

        if ($fresh) {
            Cache::forget($key);
        }

        $cached = Cache::get($key);

        if (is_array($cached)) {
            return $cached;
        }

        $run = Process::path(base_path())->timeout(60)->run([PHP_BINARY, 'artisan', 'site:scan', $path]);
        $lines = $run->successful() ? json_decode(trim($run->output()), true) : null;

        if (! is_array($lines)) {
            return null;
        }

        Cache::put($key, $lines, 3600);

        return $lines;
    }
}
