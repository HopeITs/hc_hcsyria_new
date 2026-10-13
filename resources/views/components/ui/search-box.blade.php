@props(['suggestions' => [], 'value' => '', 'placeholder' => 'Search Activities'])

{{--
    The search box under a list's title: a button and a field that suggests
    titles as you type (resources/css/ui/search-box.css, resources/js/search-box.js).
    Searching reloads the page with ?search=. $suggestions are the titles to
    suggest from.
--}}
<div {{ $attributes->class('search-box') }} x-data="searchBox(@js(['suggestions' => array_values($suggestions), 'value' => $value]))" @click.outside="open = false">
    <button type="button" class="search-box__button" @click="go()" aria-label="Search">
        <img src="/images/ico/search-blue-icon.png" alt="">
    </button>

    <div class="search-box__field">
        <input class="search-box__input" type="text" name="search" placeholder="{{ $placeholder }}" autocomplete="off"
               x-model="query" @input="open = true; cursor = -1" @focus="open = true"
               @keydown.enter.prevent="enter()" @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)" @keydown.escape="open = false">

        <div class="search-box__menu" x-show="open && matches.length" x-cloak>
            <h5 class="search-box__heading">activities</h5>
            <template x-for="(title, i) in matches" :key="title">
                <div class="search-box__suggestion" :class="{ 'is-current': i === cursor }" @click="go(title)" @mouseenter="cursor = i">
                    <template x-for="part in parts(title)"><span :class="{ 'font-bold': part.match }" x-text="part.text"></span></template>
                </div>
            </template>
        </div>
    </div>
</div>
