<?php

namespace App\Providers;

use App\Http\Responses\LoginResponse;
use App\Http\Responses\LogoutResponse;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);
    }

    public function boot(): void
    {
        // The account must exist, match its password and be switched on (legacy `active` flag).
        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::query()->where('email', $request->input(Fortify::username()))->first();

            // Compare against something even when there is no such account, so a miss takes as long as a hit.
            $matches = Hash::check((string) $request->input('password'), $user ? $user->password : '$2y$12$'.str_repeat('a', 53));

            if (! $user || ! $matches) {
                return null;
            }

            if (! $user->active) {
                throw ValidationException::withMessages([
                    'password' => 'Your account is not activated, Please contact the website administrator',
                ]);
            }

            return $user;
        });

        // Per account and address; legacy allowed three a minute, which a shared office address would hit too easily.
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(Str::transliterate(Str::lower((string) $request->input(Fortify::username())).'|'.$request->ip())));
    }
}
