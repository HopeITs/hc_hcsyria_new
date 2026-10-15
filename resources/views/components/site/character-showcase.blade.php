@props(['subjects', 'title'])

{{--
    A wide photo with a title picture over its top and a row of round
    buttons, one per subject, each showing a cartoon character, over its
    bottom edge (resources/css/ui/character-showcase.css). Pointing at a
    button, or tapping it, shows that subject's name and text on the photo.
    Children University's subjects; DISC and Study Zone's programmes are the
    same idea in components/site/category-showcase.

    $subjects is a list of ['name', 'image', 'lines'], the image being the
    character on the button and lines the text, each with the milliseconds it
    takes to rise into view. Narrow screens show the lines as one paragraph.
    $title is the picture over the top of the photo. The first subject is the
    one shown until a button is used.
--}}
@php
    $texts = collect($subjects)->map(fn ($subject) => [
        'title' => $subject['name'],
        'lines' => $subject['lines'],
        'narrow' => [['ms' => 300, 'text' => collect($subject['lines'])->pluck('text')->map(fn ($text) => trim($text))->implode(' ')]],
    ]);
    $first = $texts->first();
    $intro = ['wide' => $first, 'narrow' => ['title' => $first['title'], 'lines' => $first['narrow']]];
@endphp

<section id="categories-section" {{ $attributes->class('character-showcase') }}
         x-data="categoryShowcase(@js(['categories' => $texts, 'intro' => $intro]))">
    <img class="character-showcase__photo" src="/images/home/children.png" alt="">
    <img class="torn-edge character-showcase__edge" src="/images/home/image-mask.png" alt="">

    <div class="character-showcase__title">
        <div class="px-3">
            <div class="-mx-3 flex justify-center">
                <div class="w-1/4 px-3"><img class="reveal w-full" style="--reveal-ms: 3000ms" x-reveal src="{{ $title }}" alt=""></div>
            </div>
        </div>
    </div>

    <div class="character-showcase__buttons">
        @foreach ($subjects as $i => $subject)
            <button type="button" class="character-showcase__button" :class="{ 'is-selected': selected === {{ $i }} }"
                    aria-label="{{ $subject['name'] }}" @mouseenter="select({{ $i }})" @click="select({{ $i }})">
                <img src="{{ $subject['image'] }}" alt="">
            </button>
        @endforeach
    </div>

    @foreach (['wide', 'narrow'] as $size)
        <div class="character-showcase__text character-showcase__text--{{ $size }}">
            <div class="px-3">
                <div class="-mx-3 flex">
                    <div class="flex w-full flex-col items-center justify-center px-3">
                        <div class="section-title character-showcase__name pt-2 pb-0" x-text="panel('{{ $size }}').title">{{ $intro[$size]['title'] }}</div>
                        <div class="-mx-3 flex w-full justify-center text-center">
                            <div @class(['px-3', 'w-3/4 lg:w-2/3' => $size === 'wide', 'w-11/12 sm:w-2/3' => $size === 'narrow'])>
                                <div class="section-title-desc">
                                    <div x-show="! used">
                                        @foreach ($intro[$size]['lines'] as $line)
                                            <div>{{ $line['text'] }}</div>
                                        @endforeach
                                    </div>
                                    <template x-if="used">
                                        <div>
                                            <template x-for="line in panel('{{ $size }}').lines" :key="selected + line.text">
                                                <div class="reveal" x-reveal :style="`--reveal-ms: ${line.ms}ms`" x-text="line.text"></div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</section>
