@props(['project', 'mirror' => false])

{{--
    A programme's brief (legacy components/project-brief-section): its logo,
    "Project Brief" and the description on the left, its photos sliding on
    the right, with the red bar running down between them
    (resources/css/ui/project-brief.css).

    The description is plain text in the database; tags are stripped rather
    than trusted, so an editor's stray markup cannot reach the page.

    A programme whose media includes a video shows the video instead of the
    photos, from YouTube's cookieless host (the address in the database is
    youtube.com; the page it is allowed to frame is the privacy one).

    :mirror puts the photos or video on the left and the text on the right, as
    Family to Family's page does; on phones the photos come first either way.
--}}
@php
    $photos = $project->hopeProjectMedia->where('mediaType', 'image')->values();
    $video = $project->hopeProjectMedia->firstWhere('mediaType', 'video')?->mediaURL;
    $video = $video ? str_replace('://www.youtube.com/', '://www.youtube-nocookie.com/', $video) : null;
@endphp

<section @class(['project-brief', 'project-brief--mirror' => $mirror])>
    <x-ui.red-rule class="project-brief__rule"/>

    <x-ui.reveal :ms="1000" class="project-brief__body">
        <div class="px-3">
            <div class="-mx-3 flex flex-wrap">
                <div class="w-full px-3 sm:w-1/2">
                    <div class="project-brief__text">
                        <div class="-mx-3 flex flex-wrap">
                            <x-ui.reveal :ms="600" class="flex w-full justify-start px-3">
                                <img class="project-brief__logo" src="{{ $project->logo }}" alt="{{ $project->name }}">
                            </x-ui.reveal>
                        </div>
                        <div class="project-brief__copy -mx-3 flex flex-wrap">
                            <div class="flex flex-1 flex-col justify-end px-3">
                                <x-ui.reveal :ms="800" class="project-brief__heading">Project Brief</x-ui.reveal>
                                <x-ui.reveal :ms="1000" class="project-brief__desc">{!! strip_tags($project->description) !!}</x-ui.reveal>
                            </div>
                        </div>
                    </div>
                </div>

                <div @class(['project-brief__media flex w-full justify-start sm:w-1/2', 'order-first' => $mirror])>
                    <x-ui.reveal :ms="600" class="project-brief__frame">
                        @if ($video)
                            <iframe class="project-brief__video" src="{{ $video }}" title="{{ $project->name }}" loading="lazy"
                                    allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                    allowfullscreen></iframe>
                        @else
                            <x-ui.carousel :count="$photos->count()" :interval="5000">
                                <x-ui.carousel.track class="project-brief__track">
                                    @foreach ($photos as $i => $photo)
                                        <x-ui.carousel.item :i="$i" slide>
                                            <img class="block h-full w-full object-cover" src="{{ $photo->mediaURL }}" alt="">
                                        </x-ui.carousel.item>
                                    @endforeach

                                    <x-ui.carousel.controls/>
                                </x-ui.carousel.track>

                                <x-ui.carousel.dots :count="$photos->count()"/>
                            </x-ui.carousel>
                        @endif

                        <img class="torn-edge project-brief__edge" src="/images/home/image-mask.png" alt="">
                    </x-ui.reveal>
                </div>
            </div>
        </div>
    </x-ui.reveal>
</section>
