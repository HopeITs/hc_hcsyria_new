<?php

namespace App\Http\Responses;

use App\Support\SafeRedirect;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

/* After signing out: back to the page the button was on, else the home page. */
class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        $target = SafeRedirect::target($request->input('redirect'));

        return $request->wantsJson()
            ? response()->noContent()
            : redirect()->to($target ?? '/home');
    }
}
