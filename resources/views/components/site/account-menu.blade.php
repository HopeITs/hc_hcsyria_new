{{--
    A signed-in member's round badge in the corner of every page: their
    initial, opening a small card with their name and a Sign out button
    (resources/css/site/account-menu.css). Sign out posts to /logout (Fortify)
    and brings the member back to the page they were on.
--}}
@php($initial = mb_strtoupper(mb_substr((string) auth()->user()->name, 0, 1)))
<div class="account-menu" x-data="{ open: false }" :class="{ 'is-open': open }" @click.outside="open = false" @keydown.escape.window="open = false">
    <div class="account-menu__panel">
        <div class="account-menu__head">
            <span class="account-menu__initial">{{ $initial }}</span>
            <div>
                <div class="account-menu__name">{{ auth()->user()->name }}</div>
                <span class="account-menu__status">Logged in</span>
            </div>
        </div>
        <form method="POST" action="/logout">
            @csrf
            <input type="hidden" name="redirect" value="{{ request()->path() === '/' ? '/' : '/'.request()->path() }}">
            <button type="submit" class="account-menu__signout"><x-ui.icon name="fa-sign-out"/> Sign out</button>
        </form>
    </div>
    <button type="button" class="account-menu__toggle" @click.stop="open = ! open" :aria-expanded="open.toString()" aria-label="Account menu">
        <span>{{ $initial }}</span>
    </button>
</div>
