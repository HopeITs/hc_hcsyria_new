@props(['stories', 'title' => 'Testemonies', 'spaced' => false])

{{--
    A programme's testimonies: a title and a grey card per story, with the
    speaker's round photo over its top edge, one at a time on a five-second
    timer (resources/css/ui/testimonials.css). Pausing on hover, dots under
    the card. $stories are Success rows with their text; the text is HTML an
    editor wrote, so tags other than line breaks and emphasis are dropped.
--}}
@php
    $count = $stories->count();
@endphp

<div id="testimonies-section" class="testimonials__title">
    <div class="flex flex-col items-center justify-center">
        <x-ui.section-title :class="$spaced ? 'mt-4' : ''">{{ $title }}</x-ui.section-title>
    </div>
</div>
<x-ui.red-rule short :ms="800"/>

<div class="page-margin">
    <x-ui.reveal :ms="800" id="testimonies-body" class="px-3">
        <div class="-mx-3 flex justify-center">
            <div class="grow px-3">
                <x-ui.carousel :count="$count" :interval="5000" id="carouselTestemonies" class="testimonials">
                    <x-ui.carousel.track class="testimonials__track">
                        @foreach ($stories as $i => $story)
                            <x-ui.carousel.item :i="$i">
                                <div class="relative text-center">
                                    <div class="testimonials__profile">
                                        <img src="{{ $story->image_path }}" alt="{{ $story->content?->title }}">
                                    </div>
                                    <div class="absolute top-1/2 flex justify-center">
                                        <div class="testimonials__card">
                                            <img class="testimonials__quote" src="/images/home/quotation.svg" alt="">
                                            <div class="testimonials__name fade-in-up" style="animation-delay: .1s">{{ $story->content?->title }}</div>
                                            <div class="testimonials__text fade-in-up" style="animation-delay: .8s">{!! strip_tags((string) $story->content?->text, '<br><b><strong><em><i><u><p>') !!}</div>
                                        </div>
                                    </div>
                                </div>
                            </x-ui.carousel.item>
                        @endforeach
                    </x-ui.carousel.track>

                    <x-ui.carousel.dots :count="$count"/>
                </x-ui.carousel>
            </div>
        </div>
    </x-ui.reveal>
</div>
