<?php

namespace App\Http\Responses;

use App\Queries\AdminAccessQuery;
use App\Support\SafeRedirect;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

/* After signing in: back to the page the form was on, else the dashboard (staff) or the site's home. */
class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $target = SafeRedirect::target($request->input('redirect'));

        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->to($target ?? $this->landing($request));
    }

    /** Staff land on the dashboard; everyone else on the site's home. */
    private function landing(Request $request): string
    {
        $user = $request->user();

        return $user !== null && AdminAccessQuery::mayEnter($user) ? route('admin.dashboard') : config('fortify.home');
    }
}
