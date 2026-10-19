{{--
    The sign-in page, in the Tracking app's sign-in design (its pages/auth/login
    and layouts/auth/simple): a standalone screen with no site header or footer.
    The form posts to Fortify's POST /login (app/Providers/FortifyServiceProvider).
    Styles: resources/css/pages/login.css; the eye button: resources/js/password-toggle.js.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Sign In - Hope Center</title>

    <link rel="icon" href="/favicon.ico">
    <link rel="preload" href="{{ Vite::asset('resources/fonts/somar-sans/SomarSans-Regular.woff2') }}" as="font" type="font/woff2" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="login">
    <main class="login-page">
        <div class="login-column">
            <a href="/" class="login-logo">
                <img src="/images/logos/hc.svg" alt="">
                <span class="sr-only">Hope Center</span>
            </a>

            <div class="login-card">
                <header class="login-header">
                    <h1>Sign In</h1>
                    <p>Please enter your credentials to sign in</p>
                </header>

                <form method="POST" action="{{ route('login') }}" class="login-form">
                    @csrf

                    <div>
                        <label for="login-email" class="login-label">Email address <span>*</span></label>
                        <input id="login-email" type="email" name="email" value="{{ old('email') }}" placeholder="email@example.com" autocomplete="email" required autofocus class="login-input">
                        @error('email')
                            <p class="login-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-data="passwordToggle">
                        <label for="login-password" class="login-label">Password <span>*</span></label>
                        <div class="login-password">
                            <input id="login-password" type="password" x-bind:type="shown ? 'text' : 'password'" name="password" placeholder="Password" autocomplete="current-password" required class="login-input">
                            <button type="button" class="login-eye" aria-label="Toggle password visibility" x-on:click="shown = ! shown">
                                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" x-show="shown" x-cloak>
                                    <path fill-rule="evenodd" d="M3.28 2.22a.75.75 0 0 0-1.06 1.06l14.5 14.5a.75.75 0 1 0 1.06-1.06l-1.745-1.745a10.029 10.029 0 0 0 3.3-4.38 1.651 1.651 0 0 0 0-1.185A10.004 10.004 0 0 0 9.999 3a9.956 9.956 0 0 0-4.744 1.194L3.28 2.22ZM7.752 6.69l1.092 1.092a2.5 2.5 0 0 1 3.374 3.373l1.091 1.092a4 4 0 0 0-5.557-5.557Z" clip-rule="evenodd"/>
                                    <path d="m10.748 13.93 2.523 2.523a9.987 9.987 0 0 1-3.27.547c-4.258 0-7.894-2.66-9.337-6.41a1.651 1.651 0 0 1 0-1.186A10.007 10.007 0 0 1 2.839 6.02L6.07 9.252a4 4 0 0 0 4.678 4.678Z"/>
                                </svg>
                                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" x-show="! shown">
                                    <path d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/>
                                    <path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 0 1 0-1.186A10.004 10.004 0 0 1 10 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0 1 10 17c-4.257 0-7.893-2.66-9.336-6.41ZM14 10a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="login-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="login-remember">
                        <input type="checkbox" name="remember" @checked(old('remember'))>
                        <span class="login-remember__box">
                            <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 0 1 .208 1.04l-5 7.5a.75.75 0 0 1-1.154.114l-3-3a.75.75 0 0 1 1.06-1.06l2.353 2.353 4.493-6.74a.75.75 0 0 1 1.04-.207Z" clip-rule="evenodd"/>
                            </svg>
                        </span>
                        <span>Remember me</span>
                    </label>

                    <button type="submit" class="login-submit">Sign In</button>
                </form>
            </div>

            <p class="login-copyright">© {{ date('Y') }} - Christian Hope Center</p>
        </div>
    </main>

    @livewireScripts
</body>
</html>
