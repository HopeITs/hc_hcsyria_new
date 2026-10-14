@props(['rows' => false, 'count' => 2])

{{-- The grey shapes of a project card, shimmering while the list reloads. --}}
@if ($rows)
    @for ($i = 0; $i < $count; $i++)
        <div class="project-skeleton__row {{ $i % 2 ? 'is-flipped' : '' }}">
            <div class="project-skeleton__shape project-skeleton__shape--photo"><div class="project-skeleton__image project-skeleton__image--tall"></div></div>
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
@else
    <div class="project-skeleton__shape project-skeleton__card">
        <div class="project-skeleton__image"></div>
        <div class="project-skeleton__line project-skeleton__line--long"></div>
        <div class="project-skeleton__line project-skeleton__line--medium"></div>
        <div class="project-skeleton__line project-skeleton__line--short"></div>
    </div>
@endif
