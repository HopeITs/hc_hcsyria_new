@props(['suggestions' => [], 'value' => '', 'placeholder' => 'Search Activities', 'filter' => false, 'button' => true, 'live' => false, 'heading' => 'activities'])

{{--
    A search box with suggestions as you type (resources/css/ui/search-box.css,
    resources/js/search-box.js). By default it sits under a list's title, with
    a button, and searching reloads the page with ?search=; $suggestions are
    the titles to suggest from. As a :filter it belongs to a filter bar: it
    announces what is typed, fetches its own suggestions from the list around
    it, and shows them under a heading for names and one for beneficiaries.
--}}
<div {{ $attributes->class(['search-box', 'search-box--filter' => $filter]) }}
     x-data="searchBox(@js(match (true) {
         $filter => ['filter' => true, 'value' => $value, 'headings' => [__('projects.projectsNames'), __('projects.beneficiariesNames')]],
         $live => ['live' => true, 'value' => $value, 'headings' => [$heading]],
         default => ['suggestions' => array_values($suggestions), 'value' => $value],
     }))"
     @click.outside="open = false"
     @if ($filter) @search-set.window="query = $event.detail" @endif>
    @if ($button)
        <button type="button" class="search-box__button" @click="go()" aria-label="Search">
            <img src="/images/ico/search-blue-icon.png" alt="">
        </button>
    @endif

    <div class="search-box__field">
        <input class="search-box__input" type="text" name="search" placeholder="{{ $placeholder }}" autocomplete="off"
               x-model="query" @input="open = true; cursor = -1" @focus="open = true; load()"
               @keydown.enter.prevent="enter()" @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)" @keydown.escape="open = false">

        <div class="search-box__menu" x-show="open && matches.length" x-cloak>
            <template x-for="group in shown" :key="group.heading">
                <div>
                    <h5 class="search-box__heading" x-text="group.heading"></h5>
                    <template x-for="item in group.items" :key="group.heading + item.title">
                        <div class="search-box__suggestion" :class="{ 'is-current': item.i === cursor }" @click="go(item.title)" @mouseenter="cursor = item.i">
                            <template x-for="part in parts(item.title)"><span :class="{ 'font-bold': part.match }" x-text="part.text"></span></template>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>
</div>
