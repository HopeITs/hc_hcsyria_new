@props(['partners'])

{{--
    Family to Family's partners (the churches that help the families): a band
    split in two, the partner's logo, name and a few words of its story on a
    grey panel beside its photograph, both changing together every five
    seconds (resources/css/ui/partner-showcase.css). The panel's own arrows
    and dots are hidden; the photograph's drive both. The photographs are
    legacy's p1.jpg, p2.jpg ... in the partners' order. The story is plain
    text in the database; tags are stripped. A "Read More" link appears once
    the story runs past forty characters, and a video, when there is one,
    opens from the small icon by the name.
--}}
@php
    $count = $partners->count();
@endphp

<x-ui.reveal :ms="600" id="partner-section" class="partner-showcase section-gap">
    <div class="flex w-full flex-col items-center justify-center">
        <x-ui.section-title class="mt-4">Partners</x-ui.section-title>
    </div>
    <x-ui.red-rule short/>

    <div class="partner-showcase__band px-3">
        <x-ui.reveal :ms="1000" class="-mx-3">
            <x-ui.carousel :count="$count" :interval="5000" :pause="false" keys :pane="false">
                <div class="flex flex-wrap">
                    <div class="partner-showcase__half partner-showcase__half--text w-full sm:w-1/2">
                        <x-ui.carousel :scope="false">
                            <x-ui.carousel.track class="partner-showcase__track partner-showcase__track--text">
                                @foreach ($partners as $i => $partner)
                                    @php
                                        $title = ucfirst((string) $partner->content?->title);
                                        $text = strip_tags((string) $partner->content?->content);
                                        $long = strlen((string) $partner->content?->content) > 40;
                                        $excerpt = $long ? implode(' ', array_slice(explode(' ', $text), 0, 40)).'...' : $text;
                                    @endphp
                                    <x-ui.carousel.item :i="$i">
                                        <div class="partner-showcase__panel">
                                            <div class="partner-showcase__logo-row fade-in-up" style="animation-delay: .1s">
                                                <img class="partner-showcase__logo" src="{{ ucfirst($partner->logo) }}" alt="">
                                            </div>
                                            <div class="fade-in-up flex items-center" style="animation-delay: .8s">
                                                <div class="partner-showcase__title">{{ $title }}</div>
                                                @if ($partner->video !== '0')
                                                    <a class="ml-auto flex grow justify-end" href="{{ $partner->video }}" data-lightbox="partner-video-{{ $partner->id }}">
                                                        <img class="partner-showcase__video" src="/images/ico/video-ini-icon.svg" alt="Video">
                                                    </a>
                                                @endif
                                            </div>
                                            <div class="partner-showcase__rule fade-in-up" style="animation-delay: .8s"></div>
                                            <div class="partner-showcase__story fade-in-up" style="animation-delay: 1s">
                                                <p></p>
                                                <p>{{ html_entity_decode($excerpt) }}</p>
                                            </div>
                                            @if ($long)
                                                <a class="partner-showcase__more fade-in-up" style="animation-delay: 1.2s"
                                                   href="/projects/family-to-family/{{ $partner->id }}-{{ Str::slug(strtolower((string) $partner->content?->title)) }}">{{ __('main.readMore') }}</a>
                                            @endif
                                        </div>
                                    </x-ui.carousel.item>
                                @endforeach
                            </x-ui.carousel.track>
                        </x-ui.carousel>
                    </div>

                    <div class="partner-showcase__half w-full sm:w-1/2">
                        <x-ui.carousel :scope="false">
                            <x-ui.carousel.track class="partner-showcase__track">
                                @foreach ($partners as $i => $partner)
                                    <x-ui.carousel.item :i="$i" slide class="relative">
                                        <img class="partner-showcase__photo" src="/images/initiatives/Family to Family/p{{ $i + 1 }}.jpg" alt="">
                                        <img class="torn-edge" src="/images/home/image-mask.png" alt="">
                                    </x-ui.carousel.item>
                                @endforeach

                                <x-ui.carousel.controls/>
                            </x-ui.carousel.track>

                            <x-ui.carousel.dots :count="$count"/>
                        </x-ui.carousel>
                    </div>
                </div>
            </x-ui.carousel>
        </x-ui.reveal>
    </div>
</x-ui.reveal>
