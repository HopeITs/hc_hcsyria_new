{{--
    The members-only gate that stands in for the project list on
    /projects/micro-projects (resources/css/ui/locked-gate.css): a faded
    photo, the notice, and a sign-in card.

    The card posts to /login, which arrives with the sign-in phase (Fortify,
    upgrade plan §5.2); until then it is the page's look only. Signed-in
    members, and the "not authorised" notice for other accounts, come with it.
--}}
<div class="locked-gate">
    <img class="locked-gate__image" src="/images/micro/locked-projects-bg.png" alt="">
    <div class="locked-gate__content">
        <p class="locked-gate__notice">
            <span>This section contains private information</span>
            <span>and is available to authorized members only.</span>
        </p>
        <p class="locked-gate__hint">Please sign in with your email Address or username and password to continue.</p>

        <form class="gate-login" method="POST" action="/login">
            @csrf
            <input type="hidden" name="redirect" value="/projects/micro-projects">
            <h2 class="gate-login__title">Log in</h2>
            @if ($errors->any())
                <div class="gate-login__error">{{ $errors->first() }}</div>
            @endif
            <input class="gate-login__input" type="email" name="email" placeholder="Email Address" value="{{ old('email') }}"
                   autocomplete="username" required>
            <input class="gate-login__input" type="password" name="password" placeholder="Password"
                   autocomplete="current-password" required>
            <button class="gate-login__button" type="submit">Log in</button>
            <label class="gate-login__remember">
                <input type="checkbox" name="remember" checked>
                <span class="gate-login__check"><x-ui.icon name="fa-check"/></span>
                Remember Me
            </label>
        </form>
    </div>
</div>
