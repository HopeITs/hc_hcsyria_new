@props(['view' => 'tiles', 'count' => 3])

{{--
    The grey shapes of project cards, shimmering while the list reloads or
    grows (resources/css/ui/card-skeleton.css): tiles in the same grid as the
    list, or, for rows, one photo and one block of text side by side.
--}}
@if ($view === 'tiles')
    <div {{ $attributes->class('card-grid') }}>
        @for ($i = 0; $i < $count; $i++)
            <div class="card-skeleton__shape card-skeleton__card">
                <div class="card-skeleton__image"></div>
                <div class="card-skeleton__line card-skeleton__line--long"></div>
                <div class="card-skeleton__line card-skeleton__line--medium"></div>
                <div class="card-skeleton__line card-skeleton__line--short"></div>
            </div>
        @endfor
    </div>
@else
    <div {{ $attributes->class('project-rows') }}>
        @for ($i = 0; $i < ($count > 3 ? 2 : 1); $i++)
            <div @class(['card-skeleton__row', 'is-flipped' => $i % 2])>
                <div class="card-skeleton__shape"><div class="card-skeleton__image card-skeleton__image--tall"></div></div>
                <div class="card-skeleton__shape card-skeleton__shape--text">
                    <div class="card-skeleton__line card-skeleton__line--medium"></div>
                    <br>
                    <div class="card-skeleton__line card-skeleton__line--short"></div>
                    <div class="card-skeleton__line card-skeleton__line--medium"></div>
                    <div class="card-skeleton__line card-skeleton__line--short"></div>
                    <br>
                    <div class="card-skeleton__line card-skeleton__line--long"></div>
                    <div class="card-skeleton__line card-skeleton__line--long"></div>
                </div>
            </div>
        @endfor
    </div>
@endif
