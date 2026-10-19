{{--
    The sign-in page (legacy new/auth/login.blade.php): a standalone screen,
    with no site header or footer. The form posts to Fortify's POST /login
    (app/Providers/FortifyServiceProvider). Styles: resources/css/pages/login.css.

    Not ported: the page's own jQuery/Bootstrap bundle, the Font Awesome font
    (four glyphs are drawn by x-ui.icon) and the inline togglePasswordVisibility()
    handler, which the content security policy rules out (resources/js/password-toggle.js).
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Login | Hope Center</title>

    <link rel="icon" href="/favicon.ico">
    <link rel="preload" href="{{ Vite::asset('resources/fonts/lato/lato-400-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="login">
    <main class="login-page">
        <section class="login-shell" aria-label="Hope Center login">
            <aside class="brand-panel">
                <div class="brand-mark">
                    <img src="/images/logos/001_HC_W.svg" alt="Hope Center">
                </div>

                <div class="brand-copy">
                    <h1>HC Syria<br>Dashboard</h1>
                </div>

                <div class="brand-footer">Copyright © Christian Hope Center {{ date('Y') }}</div>
            </aside>

            <section class="auth-panel">
                <form class="auth-card" method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="auth-top">
                        <h2 class="auth-title">Log in to continue</h2>
                    </div>

                    <div class="auth-fields">
                        <div class="field">
                            <label for="email" class="field-label">Email</label>
                            <div class="input-wrap">
                                <x-ui.icon name="fa-envelope" class="input-icon"/>
                                <input id="email" type="email" @class(['auth-input', 'is-invalid' => $errors->has('email')]) placeholder="name@hcsyria.org" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                            </div>

                            @if ($errors->has('email'))
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $errors->first('email') }}</strong>
                                </span>
                            @endif
                        </div>

                        <div class="field" x-data="passwordToggle">
                            <label for="password" class="field-label">Password</label>
                            <div class="input-wrap">
                                <x-ui.icon name="fa-lock" class="input-icon"/>
                                <input id="password" type="password" x-bind:type="shown ? 'text' : 'password'" @class(['auth-input', 'password-input', 'is-invalid' => $errors->has('password')]) placeholder="Enter your password" name="password" autocomplete="current-password" required>
                                <button type="button" class="password-toggle" aria-label="Show password" x-bind:aria-label="shown ? 'Hide password' : 'Show password'" aria-pressed="false" x-bind:aria-pressed="shown">
                                    <x-ui.icon name="fa-eye-slash" x-show="! shown"/>
                                    <x-ui.icon name="fa-eye" x-show="shown" x-cloak/>
                                </button>
                            </div>

                            @if ($errors->has('password'))
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $errors->first('password') }}</strong>
                                </span>
                            @endif
                        </div>

                        <div class="form-options">
                            <label class="remember">
                                <input type="checkbox" name="remember" @checked(old('remember'))>
                                <span class="remember-box">
                                    <x-ui.icon name="fa-check"/>
                                </span>
                                <span>Remember Me</span>
                            </label>
                        </div>
                    </div>

                    <div class="auth-actions">
                        <button type="submit" class="login-button">
                            <span>Login</span>
                            <x-ui.icon name="fa-arrow-right"/>
                        </button>
                    </div>
                </form>
            </section>
        </section>
    </main>

    @livewireScripts
</body>
</html>
