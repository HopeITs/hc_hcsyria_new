@props(['categories', 'intro'])

{{--
    A wide photo with a row of round buttons over its bottom edge, one per
    category (resources/css/ui/category-showcase.css, resources/js/category-showcase.js).
    Pointing at a button darkens the photo and shows the category's title and
    text over it.

    Each category is ['label', 'title', 'image', 'lines'], the label being
    the button's text (| marks a line break) and lines the text, each with
    the milliseconds it takes to rise into view. $intro is what the panel
    says before any button is used, for wide and narrow screens:
    ['wide' => ['title', 'lines'], 'narrow' => ['title', 'lines']].
--}}
<section id="categories-section" {{ $attributes->class('category-showcase') }}
         x-data="categoryShowcase(@js(['categories' => $categories, 'intro' => $intro]))">
    <img class="category-showcase__photo" :src="categories[selected].image" src="{{ $categories[0]['image'] }}" alt="">
    <img class="torn-edge category-showcase__edge" src="/images/home/image-mask.png" alt="">

    <div class="category-showcase__panel">
        <div class="category-showcase__heading">Essential Programs</div>

        @foreach (['wide', 'narrow'] as $size)
            <div class="category-showcase__text category-showcase__text--{{ $size }}">
                <div class="px-3">
                    <div class="-mx-3 flex">
                        <div @class(['flex w-full flex-col justify-center px-3', 'items-start' => $size === 'wide', 'items-center' => $size === 'narrow'])>
                            <div class="section-title category-showcase__title pt-2 pb-0"
                                 x-text="panel('{{ $size }}').title">{{ $intro[$size]['title'] }}</div>
                            <div class="section-title-desc category-showcase__desc">
                                <div x-show="! used">
                                    @foreach ($intro[$size]['lines'] as $line)
                                        <div>{{ $line['text'] }}</div>
                                    @endforeach
                                </div>
                                <template x-if="used">
                                    <div>
                                        <template x-for="line in categories[selected].lines" :key="selected + line.text">
                                            <div class="reveal" x-reveal :style="`--reveal-ms: ${line.ms}ms`" x-text="line.text"></div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="category-showcase__buttons">
        <ul>
            @foreach ($categories as $i => $category)
                <li class="category-showcase__item" :class="{ 'is-selected': selected === {{ $i }} }"
                    @mouseenter="select({{ $i }})" @click="select({{ $i }})">
                    <div class="category-showcase__button">{!! str_replace('|', '<br>', e($category['label'])) !!}</div>
                </li>
            @endforeach
        </ul>
    </div>
</section>
