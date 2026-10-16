@props(['code', 'status' => null])

{{--
    One error page for every status code, in the design of the legacy
    new/errors/404.blade.php. Styles: resources/css/site/error-page.css.

    It must render with nothing working behind it — no database, no session —
    because a 500 or 503 is often caused by exactly that. The legacy 500 view
    crashed on an undefined $edition and visitors got Symfony's bare page.
    Nothing here, in the layout, the header or the footer reads the database.

    $code picks the wording: a status (404) or a fallback group ('4xx',
    '5xx'). $status, when given, is the real status shown in large type.
--}}
@php($text = __("errors.$code"))

{{-- The legacy 404 lacks the script that makes the menus open instantly, so they slide. --}}
<x-layouts::app :title="$text['title']" menu="slide" fill>
    <div class="error-page">
        <div class="error-page__container">
            <div class="error-page__box">
                <div class="error-page__code">{{ $status ?? $code }}</div>
                <div @class(['error-page__title', 'error-page__title--last' => ! $text['message']])>{{ $text['heading'] }}</div>
                @if ($text['message'])
                    <div class="error-page__message">{{ $text['message'] }}</div>
                @endif
                <a href="/home" class="error-page__action">
                    <x-ui.icon name="bi-house-door-fill"/> @lang('errors.back_home')
                </a>
            </div>
        </div>
    </div>
</x-layouts::app>
