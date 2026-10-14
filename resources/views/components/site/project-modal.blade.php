@props(['project', 'locked' => false])

{{--
    A project's details in a window over the page (resources/css/ui/project-modal.css,
    resources/js/project-list.js): its photos, facts, donor and whole story.
    Keeps the keyboard inside while it is open and gives it back to the card that opened it. Closes with the cross, Escape, a click on the dark behind it (not when
    it was opened by a link, as the legacy deep link did), or a swipe up on a
    touch screen.
--}}
@php($presenter = new \App\Support\ProjectPresenter($project))
<div {{ $attributes->class('project-modal') }} x-data="projectModal(@js(['locked' => $locked]))"
     @keydown.window.escape="$event.defaultPrevented || close()" @keydown.tab.window="trap($event)" role="dialog" aria-modal="true" aria-label="{{ $presenter->heading() }}">
    <div class="project-modal__backdrop" @click="backdrop()"></div>

    <div class="project-modal__dialog" tabindex="-1" x-ref="dialog">
        <div class="project-modal__content">
            <button type="button" class="project-modal__close" @click="close()" aria-label="Close">&times;</button>

            <div class="project-row__head">
                <div class="card-title">{{ $presenter->heading() }}</div>
                <div class="project-row__icons">
                    <x-site.project-actions :$presenter layout="modal"/>
                </div>
            </div>
            <div class="card-title-section"></div>

            <x-site.project-photos :$presenter :group="'details-'.$project->id" dots class="project-modal__photos"/>

            <div class="project-modal__swipe" x-ref="swipe">
                <div class="project-row__facts project-modal__facts">
                    <div>
                        <x-site.project-facts :$presenter skills/>
                    </div>
                    <div class="project-row__side"><x-site.project-logo :$presenter/></div>
                </div>
                <div class="card-content project-modal__story">{!! $presenter->story() !!}</div>

                <div class="project-modal__hint" x-show="touch" x-cloak>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5.4l-6.6 6.6 1.4 1.4L12 8.2l5.2 5.2 1.4-1.4zM12 10.4l-6.6 6.6 1.4 1.4 5.2-5.2 5.2 5.2 1.4-1.4z"/></svg>
                    <p>{{ __('main.swipeUp') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
