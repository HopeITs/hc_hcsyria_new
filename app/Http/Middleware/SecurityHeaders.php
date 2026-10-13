<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds the browser-hardening response headers to every response.
 *
 * Ported from the Tracking app (hc-tracking). Prepended to the global stack,
 * so even an error response carries the headers. Headers a response already
 * set are left untouched.
 *
 * The CSP keeps 'unsafe-inline' and 'unsafe-eval' for scripts because Livewire
 * and Alpine need them (Livewire's csp_safe build only accepts simple Alpine
 * expressions; revisit once the interactive public pages are ported).
 * Everything else is locked to this origin, which still blocks framing,
 * plugins, base-tag and form hijacking, and requests to third-party hosts.
 *
 * Third-party origins are allowed one at a time, only where a ported page
 * needs them, each with the reason next to it.
 *
 * HSTS is sent only over HTTPS in production, so a local http:// copy can
 * never be locked out. Its max-age comes from config('app.hsts_max_age');
 * 0 tells browsers to forget an earlier HSTS answer, which is the rollback.
 */
class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Content-Security-Policy' => $this->contentSecurityPolicy($request),
        ];

        foreach ($headers as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        if ($request->isSecure() && app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age='.max(0, (int) config('app.hsts_max_age')));
        }

        // PHP adds X-Powered-By itself (expose_php), outside the response object.
        $response->headers->remove('X-Powered-By');
        if (! headers_sent()) {
            header_remove('X-Powered-By');
        }

        return $response;
    }

    private function contentSecurityPolicy(Request $request): string
    {
        $devServer = $this->viteDevServer();

        $directives = [
            'default-src' => ["'self'"],
            'script-src' => array_filter(["'self'", "'unsafe-inline'", "'unsafe-eval'", $devServer]),
            'style-src' => array_filter(["'self'", "'unsafe-inline'", $devServer]),
            // Map tiles come from one server (config/hcsyria.php).
            'img-src' => ["'self'", 'data:', 'blob:', config('hcsyria.map_tiles.origin')],
            'font-src' => array_filter(["'self'", 'data:', $devServer]),
            'connect-src' => array_filter(["'self'", $devServer, $this->webSocketOrigin($devServer)]),
            // Programme videos, embedded from YouTube's cookieless host.
            'frame-src' => ["'self'", 'https://www.youtube-nocookie.com'],
            'worker-src' => ["'self'"],
            'manifest-src' => ["'self'"],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'self'"],
        ];

        $policy = collect($directives)
            ->map(fn (array $sources, string $directive): string => $directive.' '.implode(' ', $sources))
            ->implode('; ');

        return $request->isSecure() ? $policy.'; upgrade-insecure-requests' : $policy;
    }

    /**
     * The Vite dev server origin while `npm run dev` is running, else null.
     */
    private function viteDevServer(): ?string
    {
        if (! Vite::isRunningHot()) {
            return null;
        }

        $origin = trim((string) @file_get_contents(Vite::hotFile()));

        return $origin === '' ? null : $origin;
    }

    /**
     * The ws:// twin of the Vite dev server origin, for its hot-reload socket.
     */
    private function webSocketOrigin(?string $devServer): ?string
    {
        return $devServer === null ? null : preg_replace('/^http/', 'ws', $devServer);
    }
}
