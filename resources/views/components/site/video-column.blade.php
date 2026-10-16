@props(['src', 'title'])

{{--
    The right half of a row that opens a page: a YouTube video in the
    programme photo frame, with the torn edge along its bottom
    (resources/css/ui/video-column.css). Used by the verse beside a video
    (Family to Family) and by the Christian Youth page. The video is from
    YouTube's cookieless host.
--}}
<div {{ $attributes->class('video-column flex w-full justify-start sm:w-1/2') }}>
    <x-ui.reveal :ms="600" class="video-column__frame">
        <iframe class="video-column__video" src="{{ $src }}" title="{{ $title }}" loading="lazy"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                allowfullscreen></iframe>
        <img class="torn-edge video-column__edge" src="/images/home/image-mask.png" alt="">
    </x-ui.reveal>
</div>
