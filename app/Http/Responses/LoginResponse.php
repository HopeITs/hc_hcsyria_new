<?php

namespace App\Http\Responses;

use App\Queries\AdminAccessQuery;
use App\Support\SafeRedirect;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

/* After signing in: the dashboard for staff, else back to the page the form was on or the site's home. */
class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        $user = $request->user();

        // Staff always land on the dashboard (/admin/home), wherever the form was.
        if ($user !== null && AdminAccessQuery::mayEnter($user)) {
            return redirect()->to(route('admin.dashboard'));
        }

        return redirect()->to(SafeRedirect::target($request->input('redirect')) ?? config('fortify.home'));
    }
}
