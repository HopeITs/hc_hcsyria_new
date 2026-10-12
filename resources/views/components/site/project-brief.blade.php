@props(['project'])

{{--
    A programme's brief (legacy components/project-brief-section): its logo,
    "Project Brief" and the description on the left, its photos sliding on
    the right, with the red bar running down between them
    (resources/css/ui/project-brief.css).

    The description is plain text in the database; tags are stripped rather
    than trusted, so an editor's stray markup cannot reach the page.

    Not ported yet: the YouTube variant (a programme whose media is a video)
    and Family to Family's mirrored layout, which come with those pages.
--}}
@php
    $photos = $project->hopeProjectMedia->where('mediaType', 'image')->values();
@endphp

<section class="project-brief">
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

                <div class="project-brief__media flex w-full justify-start sm:w-1/2">
                    <x-ui.reveal :ms="600" class="project-brief__frame">
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

                        <img class="torn-edge project-brief__edge" src="/images/home/image-mask.png" alt="">
                    </x-ui.reveal>
                </div>
            </div>
        </div>
    </x-ui.reveal>
</section>
