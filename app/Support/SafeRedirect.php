<?php

namespace App\Support;

use Illuminate\Support\Str;

/*
 * Where a sign-in or sign-out may send a visitor back to: the page they came
 * from, named in a `redirect` field. Only this site's own addresses count, so
 * a crafted form cannot send anyone to another host. Legacy
 * LoginController::safeRedirectTarget().
 */
final class SafeRedirect
{
    public static function target(mixed $redirect): ?string
    {
        if (! is_string($redirect) || $redirect === '') {
            return null;
        }

        // A path on this site starts with a single slash.
        if (Str::startsWith($redirect, '/') && ! Str::startsWith($redirect, ['//', '/\\'])) {
            return $redirect;
        }

        // A full address only if it names this host.
        if (Str::startsWith($redirect, ['http://', 'https://']) && parse_url($redirect, PHP_URL_HOST) === parse_url(url('/'), PHP_URL_HOST)) {
            return $redirect;
        }

        return null;
    }
}
