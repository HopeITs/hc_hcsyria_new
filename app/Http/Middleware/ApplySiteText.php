<?php

namespace App\Http\Middleware;

use App\Enums\AdminCapability;
use App\Support\Site\PageText;
use App\Support\Site\SiteContent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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

        if (! $request->isMethod('GET') || $response->getStatusCode() !== 200 || SiteContent::$scanning || $request->hasHeader('X-Site-Scan')
            || ! str_contains((string) $response->headers->get('Content-Type'), 'text/html')
            || $request->is('admin', 'admin/*', 'livewire*', 'livewire-*/*')) {
            return $response;
        }

        $page = '/'.trim($request->path(), '/');
        $changes = SiteContent::textFor($page);

        // The visual editor: staff who may change the site get the page marked up, with the editor inside it.
        if ($request->boolean('sc-edit') && Gate::allows('admin.'.AdminCapability::ManageSite->value)) {
            $html = PageText::decorate((string) $response->getContent(), $changes, $page, SiteContent::slotAddresses());
            $editor = view('site.editor')->render();
            $html = str_contains($html, '</body>') ? str_replace('</body>', $editor.'</body>', $html) : $html.$editor;

            return $response->setContent($html)->withHeaders(['Cache-Control' => 'no-store, private']);
        }

        if ($changes !== []) {
            $response->setContent(PageText::rewrite((string) $response->getContent(), $changes, $page));
        }

        return $response;
    }
}
