@props(['cities', 'donors', 'view', 'applied'])

{{--
    The filter bar above the project list (resources/css/ui/project-filters.css,
    resources/js/project-filters.js): city and donor, the view, projects with
    a video, the search box and Apply. It works on a draft that Apply sends to
    the list, and sticks under the navigation while the list scrolls past.
    It starts from the filters the list already has (the address may have set
    them) and follows them when they change under it (the back button). The
    list's own state comes through $wire.
--}}
<div class="project-filters" wire:ignore
     x-data="projectFilters(@js(['cities' => $cities->pluck('name', 'id'), 'donors' => $donors->pluck('name', 'id'), 'applied' => $applied]))">
    <div class="project-filters__row">
        <div class="project-filters__col project-filters__col--end">
            <x-ui.dropdown>
                <x-slot:trigger><span x-text="'City-' + (cities[draft.city] ?? 'All')"></span></x-slot:trigger>
                <li><a class="dropdown__item" href="#" @click.prevent="draft.city = ''">All</a></li>
                @foreach ($cities as $city)
                    <li><a class="dropdown__item" href="#" @click.prevent="draft.city = '{{ $city->id }}'">{{ $city->name }}</a></li>
                @endforeach
            </x-ui.dropdown>
        </div>
        <div class="project-filters__gap"></div>
        <div class="project-filters__col project-filters__col--start">
            <x-ui.dropdown>
                <x-slot:trigger><span x-text="'Donor-' + (donors[draft.donor] ?? 'All')"></span></x-slot:trigger>
                <li><a class="dropdown__item" href="#" @click.prevent="draft.donor = ''">All</a></li>
                @foreach ($donors as $donor)
                    <li><a class="dropdown__item" href="#" @click.prevent="draft.donor = '{{ $donor->id }}'">{{ $donor->name }}</a></li>
                @endforeach
            </x-ui.dropdown>
        </div>
    </div>

    <div class="project-filters__title">Projects</div>

    <div class="project-filters__row project-filters__row--tools">
        <div class="project-filters__col project-filters__col--end">
            <div class="project-view">
                <label>View</label>
                <img :src="`/images/ico/${$wire.view === 'tiles' ? 'tiles-active' : 'tiles'}.png`" src="/images/ico/{{ $view === 'tiles' ? 'tiles-active' : 'tiles' }}.png" alt="Tiles" role="button" class="project-view__tiles" @click="$wire.show('tiles')">
                <img :src="`/images/ico/${$wire.view === 'grid' ? 'grid-active' : 'grid'}.png`" src="/images/ico/{{ $view === 'grid' ? 'grid-active' : 'grid' }}.png" alt="List" role="button" class="project-view__grid" @click="$wire.show('grid')">
            </div>
            <div class="project-video">
                <label class="project-video__switch" :class="{ 'is-on': draft.hasVideo }">
                    <input type="checkbox" x-model="draft.hasVideo" aria-label="Project with Video"
                           @mouseenter="tip($el, 'project with video')">
                    <img class="project-video__icon" :src="draft.hasVideo ? '/images/ico/reverst-video.svg' : '/images/ico/search-video-icon.svg'" alt="">
                </label>
            </div>
        </div>
        <div class="project-filters__gap"></div>
        <div class="project-filters__col project-filters__col--bottom" @search-box="draft.search = $event.detail">
            <x-ui.search-box filter placeholder="Search" :button="false" :value="$applied['search']"/>
            <button type="button" class="project-button" @click="apply()">Apply</button>
        </div>
    </div>

    <div class="project-filters__row project-filters__row--result">
        <div class="project-filters__col project-filters__col--end">
            <div class="project-result">
                Filter Result: <span x-text="result"></span>
            </div>
        </div>
        <div class="project-filters__gap"></div>
        <div class="project-filters__col"></div>
    </div>
</div>
