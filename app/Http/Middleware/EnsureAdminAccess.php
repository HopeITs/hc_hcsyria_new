<?php

namespace App\Http\Middleware;

use App\Queries\AdminAccessQuery;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/*
 * The /admin area: a signed-in, active account that holds a role the admin
 * gives something to do. Anyone else gets a 403 (the signed-out are sent to
 * the sign-in page by `auth`, which runs first).
 */
class EnsureAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user !== null && AdminAccessQuery::mayEnter($user), 403);

        return $next($request);
    }
}
