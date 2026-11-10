<?php

namespace App\Http\Middleware;

use App\Support\Site\PageText;
use App\Support\Site\SiteContent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/*
 * Puts the words staff rewrote in the dashboard (Site content, Words) into a public
 * page on its way out. With nothing rewritten for the page, which is nearly always,
 * the response is not touched at all.
 */
class ApplySiteText
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethod('GET') || $response->getStatusCode() !== 200 || SiteContent::$scanning
            || ! str_contains((string) $response->headers->get('Content-Type'), 'text/html')
            || $request->is('admin', 'admin/*', 'livewire*', 'livewire-*/*')) {
            return $response;
        }

        $page = '/'.trim($request->path(), '/');
        $changes = SiteContent::textFor($page);

        if ($changes !== []) {
            $response->setContent(PageText::rewrite((string) $response->getContent(), $changes, $page));
        }

        return $response;
    }
}
