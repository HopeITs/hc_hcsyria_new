<?php

namespace App\Http\Responses;

use App\Support\SafeRedirect;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

/* After signing in: back to the page the form was on, else the site's home. */
class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $target = SafeRedirect::target($request->input('redirect'));

        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->to($target ?? config('fortify.home'));
    }
}
