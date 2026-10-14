@props(['view' => 'tiles', 'count' => 3])

{{--
    The grey shapes of project cards, shimmering while the list reloads or
    grows (resources/css/ui/project-card.css): tiles in the same grid as the
    list, or, for rows, one photo and one block of text side by side.
--}}
@if ($view === 'tiles')
    <div {{ $attributes->class('project-tiles') }}>
        @for ($i = 0; $i < $count; $i++)
            <div class="project-skeleton__shape project-skeleton__card">
                <div class="project-skeleton__image"></div>
                <div class="project-skeleton__line project-skeleton__line--long"></div>
                <div class="project-skeleton__line project-skeleton__line--medium"></div>
                <div class="project-skeleton__line project-skeleton__line--short"></div>
            </div>
        @endfor
    </div>
@else
    <div {{ $attributes->class('project-rows') }}>
        @for ($i = 0; $i < ($count > 3 ? 2 : 1); $i++)
            <div @class(['project-skeleton__row', 'is-flipped' => $i % 2])>
                <div class="project-skeleton__shape"><div class="project-skeleton__image project-skeleton__image--tall"></div></div>
                <div class="project-skeleton__shape project-skeleton__shape--text">
                    <div class="project-skeleton__line project-skeleton__line--medium"></div>
                    <br>
                    <div class="project-skeleton__line project-skeleton__line--short"></div>
                    <div class="project-skeleton__line project-skeleton__line--medium"></div>
                    <div class="project-skeleton__line project-skeleton__line--short"></div>
                    <br>
                    <div class="project-skeleton__line project-skeleton__line--long"></div>
                    <div class="project-skeleton__line project-skeleton__line--long"></div>
                </div>
            </div>
        @endfor
    </div>
@endif
