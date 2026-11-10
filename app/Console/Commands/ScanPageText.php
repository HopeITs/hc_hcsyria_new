<?php

namespace App\Console\Commands;

use App\Support\Site\PageText;
use App\Support\Site\SiteContent;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

/*
 * Reads a public page the way a visitor's browser gets it, and prints the lines of text on
 * it as JSON, for the dashboard's Words list. It is a command, not a request made from
 * inside the dashboard, so the page is rendered with a session of its own and the staff
 * member's stays as it is. Changes made in the dashboard are left out: the list is of the
 * original words.
 */
class ScanPageText extends Command
{
    protected $signature = 'site:scan {path : the page, such as /home}';

    protected $description = 'List the lines of text on a public page, as JSON';

    public function handle(Kernel $kernel): int
    {
        $path = '/'.trim((string) $this->argument('path'), '/');

        if (! collect((array) config('site_content.pages'))->contains(fn (array $page): bool => $page['url'] === $path)) {
            $this->error('Not a page of the site content list.');

            return self::FAILURE;
        }

        SiteContent::$scanning = true;
        $request = Request::create($path, 'GET', server: ['HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120 Safari/537.36']);
        try {
            $response = $kernel->handle($request);
        } finally {
            SiteContent::$scanning = false;
        }

        if ($response->getStatusCode() !== 200) {
            $this->error('The page answered '.$response->getStatusCode().'.');

            return self::FAILURE;
        }

        $this->line((string) json_encode(PageText::lines((string) $response->getContent()), JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
