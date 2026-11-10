<?php

namespace App\Http\Middleware;

use App\Enums\AdminCapability;
use App\Support\Site\PageText;
use App\Support\Site\SiteContent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        // Staff who may change the site, on a page that can be changed, are offered the editor. A visitor, or a
        // member who cannot, gets the page exactly as it is.
        $staff = Auth::check() && SiteContent::editablePage($page) && Gate::allows('admin.'.AdminCapability::ManageSite->value);
        $html = (string) $response->getContent();

        if ($staff && $request->boolean('sc-edit')) {
            $html = PageText::decorate($html, $changes, $page, SiteContent::slotAddresses());
            $html = $this->before('</body>', $html, view('site.editor', ['endpoint' => url('/site-edit'), 'token' => csrf_token(), 'leave' => url($page)])->render());

            return $response->setContent($html)->withHeaders(['Cache-Control' => 'no-store, private']);
        }

        if ($changes !== []) {
            $html = PageText::rewrite($html, $changes, $page);
        }

        if ($staff) {
            $html = $this->before('</body>', $html, view('site.edit-pill', ['href' => url($page).'?sc-edit=1'])->render());
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        if ($staff || $changes !== []) {
            $response->setContent($html);
        }

        return $response;
    }

    /** Put markup before the last $tag, or at the end if there is none. */
    private function before(string $tag, string $html, string $markup): string
    {
        $at = strripos($html, $tag);

        return $at === false ? $html.$markup : substr($html, 0, $at).$markup.substr($html, $at);
    }
}
