@props(['eyebrow', 'headline', 'detail' => null, 'figure' => null, 'figureLabel' => null, 'tone' => 'action'])

{{--
    The dashboard's first thing: what needs this person now. The tone says how
    urgent it is — "action" (the brand red) while something waits, "calm" (green)
    when everything is caught up.
--}}
<section @class(['focus', $tone])>
    <div class="focus-main">
        <div class="focus-eyebrow"><span class="fx-dot"></span>{{ $eyebrow }}</div>
        <h2 class="focus-h">{{ $headline }}</h2>
        @if ($detail)
            <p class="focus-d">{{ $detail }}</p>
        @endif
    </div>

    @if ($figure !== null)
        <div class="focus-fig">
            <b class="tnum">{{ $figure }}</b>
            @if ($figureLabel)<span>{{ $figureLabel }}</span>@endif
        </div>
    @endif

    @isset($actions)
        <div class="focus-acts">{{ $actions }}</div>
    @endisset
</section>
