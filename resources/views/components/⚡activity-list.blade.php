<?php

use App\Queries\InitiativeListQuery;
use App\Queries\InitiativeTitleListQuery;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use Livewire\Attributes\Url;
use Livewire\Component;

/*
 * A programme's activities (Study Zone, DISC): a title, a search box where the
 * programme has one, a tab for each town, the activities as tiles or rows, and
 * Load More. It replaces the tabs of paged lists, which reloaded the page for
 * every step; here the town, the search and the view are in the address
 * (?tab=homs&search=...&view=grid) and the list changes in place.
 *
 * Both layouts are on the page and the switch shows one (see x-ui.view-switch);
 * a town or a search fetches the first six again, Load More adds six.
 *
 * $towns is town key => ['label', 'places' (ids), 'page' (legacy's page
 * parameter, ini_A), 'byDate', 'empty' (what an empty list says)].
 */
new class extends Component
{
    /** How many activities each "Load More" adds. */
    private const STEP = 6;

    private const SEARCH_LENGTH = 100;

    #[Locked]
    public int $category = 0;

    #[Locked]
    public int $subCategory = 0;

    /** The programme's address segment: disc in /projects/disc/1-title. */
    #[Locked]
    public string $type = '';

    /** @var array<string, array{label: string, places: list<int>, page: string, byDate: bool, empty: array{0: string, 1: ?string}}> */
    #[Locked]
    public array $towns = [];

    #[Locked]
    public string $title = '';

    #[Locked]
    public bool $searchable = false;

    #[Locked]
    public bool $phone = false;

    /** The open town's key; empty is the first. Legacy had it as ?tab=. */
    #[Url(as: 'tab', history: true, except: '')]
    public string $town = '';

    #[Url(history: true, except: '')]
    public string $search = '';

    /** 'tiles' or 'grid' (the rows). */
    #[Url(except: 'tiles')]
    public string $view = 'tiles';

    public int $limit = self::STEP;

    /**
     * @param  array<string, array<string, mixed>>  $towns
     */
    public function mount(int $category, int $subCategory, string $type, array $towns, string $title, bool $searchable = false, bool $phone = false): void
    {
        $this->category = $category;
        $this->subCategory = $subCategory;
        $this->type = $type;
        $this->towns = $towns;
        $this->title = $title;
        $this->searchable = $searchable;
        $this->phone = $phone;

        // Legacy opened the town whose page number was in the address (?ini_H=2).
        if ($this->town === '') {
            foreach ($towns as $key => $town) {
                if (request()->has($town['page'])) {
                    $this->town = $key;
                    break;
                }
            }
        }

        $this->normalise();
    }

    /** The town or search changed (a tab, the box, the back button): start the list again. */
    public function updated(string $name): void
    {
        if ($name === 'town' || $name === 'search') {
            $this->normalise();
            $this->limit = self::STEP;
        }
    }

    public function loadMore(): void
    {
        $this->limit += self::STEP;
    }

    /** @return array{0: array<int, string>} the titles the search box suggests */
    #[Renderless]
    public function suggestions(): array
    {
        $titles = InitiativeTitleListQuery::make()->forCategory($this->category)->titles()
            ->map(fn ($title) => html_entity_decode($title, ENT_QUOTES))->all();

        return [$titles];
    }

    /** What the address or the browser sent, as one of the towns, a short search and a known view. */
    private function normalise(): void
    {
        $this->town = array_key_exists($this->town, $this->towns) ? $this->town : '';
        $this->search = $this->searchable ? Str::limit(trim($this->search), self::SEARCH_LENGTH, '') : '';
        $this->view = $this->view === 'grid' ? 'grid' : 'tiles';
    }

    public function with(): array
    {
        $first = array_key_first($this->towns);
        $town = $this->towns[$this->town ?: $first];

        [$activities, $total] = InitiativeListQuery::upTo($this->category, $this->subCategory, $town['places'], $this->limit, $town['byDate'], $this->search);

        return [
            'first' => $first,
            'activities' => $activities,
            'hasMore' => $total > $activities->count(),
            'emptyTitle' => $this->search === '' ? $town['empty'][0] : "Sorry, we couldn't find any results",
            'emptyHint' => $this->search === '' ? $town['empty'][1] : __('projects.sorryMessage2'),
        ];
    }
};
?>

<div @search-box-submit.window="$wire.$set('search', $event.detail)">
    <div id="initiative-section" class="section-gap">
        <div class="flow-root px-3">
            <x-ui.reveal :ms="600" class="mb-2 flex flex-col items-center justify-center">
                <x-ui.section-title class="mt-4">{{ $title }}</x-ui.section-title>
                <div class="activity-tools">
                    @if ($searchable)
                        <x-ui.search-box :live="true" :value="$search"/>
                    @endif
                    <x-ui.view-switch :$view/>
                </div>
            </x-ui.reveal>
        </div>
    </div>
    <x-ui.red-rule short :ms="800" style="z-index: -1"/>

    <x-ui.reveal :ms="800">
        <ul class="activity-tabs" role="tablist">
            @foreach ($towns as $key => $town)
                <li class="activity-tabs__item" role="presentation">
                    <button type="button" class="activity-tabs__tab" :class="{ 'is-active': ($wire.town || '{{ $first }}') === '{{ $key }}' }" role="tab"
                            :aria-selected="($wire.town || '{{ $first }}') === '{{ $key }}'" @click="$wire.$set('town', '{{ $key === $first ? '' : $key }}')">{{ $town['label'] }}</button>
                </li>
            @endforeach
        </ul>
        <div class="h-[3vw] bg-white"></div>

        <div class="relative bg-white">
            <div wire:loading.block wire:target="town, search">
                <div x-show="$wire.view === 'tiles'" @if ($view !== 'tiles') style="display: none" @endif>
                    <x-ui.card-skeleton view="tiles" :count="3" class="activity-tiles card-skeleton--flush"/>
                </div>
                <div x-show="$wire.view === 'grid'" @if ($view !== 'grid') style="display: none" @endif>
                    <x-ui.card-skeleton view="grid" :count="1" class="card-skeleton--flush"/>
                </div>
            </div>

            <div wire:loading.remove wire:target="town, search">
                @if ($activities->isNotEmpty())
                    {{-- Both layouts are drawn and the view switch only shows one of them. --}}
                    <div class="card-grid activity-tiles" x-show="$wire.view === 'tiles'" @if ($view !== 'tiles') style="display: none" @endif>
                        @foreach ($activities as $activity)
                            <x-site.activity-tile :$activity :$type wire:key="tile-{{ $activity->id }}"/>
                        @endforeach
                    </div>
                    <div x-show="$wire.view === 'grid'" @if ($view !== 'grid') style="display: none" @endif>
                        @foreach ($activities as $activity)
                            <x-site.activity-card :$activity :$type :flip="! $phone && $loop->even" wire:key="card-{{ $activity->id }}"/>
                        @endforeach
                    </div>
                @else
                    <div class="activity-card">
                        <div class="activity-card__inner">
                            <div class="-mx-3 flex">
                                <div class="activity-tabs__empty flex w-full flex-col justify-end px-3 pr-1">
                                    <div class="text-center">{{ $emptyTitle }}</div>
                                    @if ($emptyHint)
                                        <div>{{ $emptyHint }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <x-ui.red-rule short :ms="600"/>
                @if ($hasMore)
                    <x-ui.load-more/>
                @endif
            </div>

            <div wire:loading.block wire:target="loadMore">
                <div x-show="$wire.view === 'tiles'" @if ($view !== 'tiles') style="display: none" @endif><x-ui.card-skeleton view="tiles" :count="3" class="activity-tiles card-skeleton--flush"/></div>
                <div x-show="$wire.view === 'grid'" @if ($view !== 'grid') style="display: none" @endif><x-ui.card-skeleton view="grid" :count="1" class="card-skeleton--flush"/></div>
            </div>
        </div>
    </x-ui.reveal>
</div>
