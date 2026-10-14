@props(['project', 'mobile' => false, 'flip' => false])

{{--
    A project as a row: its photos moving on by themselves beside a card
    with the name, facts, donor, an opening of the story and a button for
    the rest. Rows alternate sides on a desktop (:flip puts the card first,
    and, as legacy's second kind of row did, shows more of the story).
--}}
@php($presenter = new \App\Support\ProjectPresenter($project))
<div {{ $attributes->class('project-row') }}>
    <div @class(['project-row__inner', 'is-flipped' => $flip])>
        <x-site.project-photos :$presenter :group="'row-'.$project->id" class="project-row__photos"/>

        <div class="project-row__card">
            <div class="project-row__head">
                <div class="card-title">{{ $presenter->heading() }}</div>
                <div class="project-row__icons">
                    <x-site.project-actions :$presenter layout="row"/>
                </div>
            </div>
            <div class="card-title-section"></div>

            <div class="project-row__facts">
                <div>
                    <x-site.project-facts :$presenter/>
                </div>
                <div class="project-row__side"><x-site.project-logo :$presenter/></div>
            </div>

            @if ($presenter->skills() !== '')
                <div class="project-fact"><strong>{{ __('projects.skills') }}</strong> <span>{{ $presenter->skills() }}</span></div>
            @endif
            <div class="card-content">
                <p>{!! $presenter->excerpt($flip || $mobile ? 15 : 8) !!}</p>
            </div>

            @if ($presenter->hasDetails())
                <div class="project-row__more">
                    <button type="button" class="project-more" @click="$dispatch('project-opening'); Livewire.dispatch('project-open', { id: {{ $project->id }} })">{{ __('main.readMore') }}</button>
                </div>
            @endif
        </div>
    </div>
</div>
