{{--
    Family to Family's opening: the verse the programme rests on, broken into
    short centred lines, beside a video (resources/css/ui/verse-video.css).
    Wide screens break the verse into nine lines; phones into three longer
    ones, as legacy did. The video is from YouTube's cookieless host.
--}}
@php
    // The quotation marks are drawn in their own font (see the stylesheet), so each sits in a span.
    $mark = '<span>“</span>';
    $wide = [$mark.'And the King shall', 'answer and say', 'unto them, Verily I', 'say unto you, In as', 'much as ye have', 'done it unto one of', 'the least of these my', 'brethren, ye have', 'done it unto me'.$mark];
    $narrow = [$mark.'And the King shall answer and say unto them,', 'Verily I say unto you, In as much as ye have done it unto', 'one of the least of these my brethren, ye have done it unto me '.$mark];
@endphp

<section id="about-section" class="verse-video">
    <x-ui.red-rule class="verse-video__rule max-sm:hidden"/>

    <x-ui.reveal :ms="1000" class="verse-video__body">
        <div class="px-3">
            <div class="-mx-3 flex flex-wrap">
                <div class="w-full px-3 sm:w-1/2">
                    <div class="verse-video__verse">
                        <div class="verse-video__quote max-sm:hidden">
                            @foreach ($wide as $line)
                                <x-ui.reveal :ms="800">{!! $line !!}</x-ui.reveal>
                            @endforeach
                            <x-ui.reveal :ms="1000" class="verse-video__source">Matthew 25:40</x-ui.reveal>
                        </div>

                        {{-- On phones the reference stands outside the verse, so the verse's own bottom margin shows above it. --}}
                        <div class="flex flex-col items-center sm:hidden">
                            <div class="verse-video__quote verse-video__quote--phone">
                                @foreach ($narrow as $line)
                                    <x-ui.reveal :ms="800">{!! $line !!}</x-ui.reveal>
                                @endforeach
                            </div>
                            <x-ui.reveal :ms="1000" class="verse-video__source">Matthew 25:40</x-ui.reveal>
                        </div>
                    </div>
                </div>

                <div class="verse-video__media flex w-full justify-start sm:w-1/2">
                    <x-ui.reveal :ms="600" class="verse-video__frame">
                        <iframe class="verse-video__video" src="https://www.youtube-nocookie.com/embed/ApyHAvgggcE" title="Family to Family" loading="lazy"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                allowfullscreen></iframe>
                        <img class="torn-edge verse-video__edge" src="/images/home/image-mask.png" alt="">
                    </x-ui.reveal>
                </div>
            </div>
        </div>
    </x-ui.reveal>
</section>
