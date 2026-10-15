<?php

use App\Models\Initiative;
use App\Queries\InitiativeListQuery;
use App\Queries\InitiativeTitleListQuery;
use App\Queries\MemberProjectQuery;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as Support;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use Livewire\Attributes\Url;
use Livewire\Component;

/*
 * A programme's activities (Study Zone, DISC): the filter bar of the
 * micro-projects list (a city, a donor, a video, the search box and Apply),
 * the activities as tiles or rows, and Load More. It replaces the tabs of
 * paged lists, which reloaded the page for every step; here the filters and
 * the view are in the address
 * (?city=homs&donor=caritas-poland&search=...&view=grid) and the list changes
 * in place.
 *
 * Both layouts are on the page and the switch shows one (see x-ui.view-switch);
 * Apply fetches the first six again, Load More adds six. The bar offers a
 * donor only where the programme's activities have one.
 *
 * $towns is the city's slug => ['label', 'places' (ids), 'page' (legacy's page
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
    public ?int $subCategory = null;

    /** The programme's address segment: disc in /projects/disc/1-title. */
    #[Locked]
    public string $type = '';

    /** @var array<string, array{label: string, places: list<int>, page: string, byDate: bool, empty: array{0: string, 1: ?string}}> */
    #[Locked]
    public array $towns = [];

    #[Locked]
    public string $title = '';

    #[Locked]
    public bool $phone = false;

    /** The applied city's slug; empty is every city. Legacy had its towns as ?tab=. */
    #[Url(history: true, except: '')]
    public string $city = '';

    #[Url(history: true, except: '')]
    public string $donor = '';

    #[Url(as: 'video', history: true, except: false)]
    public bool $hasVideo = false;

    #[Url(history: true, except: '')]
    public string $search = '';

    /** 'tiles' or 'grid' (the rows). */
    #[Url(except: 'tiles')]
    public string $view = 'tiles';

    public int $limit = self::STEP;

    /** @var Support<int, \App\Models\Donor>|null */
    private ?Support $donorList = null;

    /** How many activities match the applied filters. */
    #[Locked]
    public int $count = 0;

    /** How many activities the programme has at all. */
    #[Locked]
    public int $total = 0;

    /**
     * @param  array<string, array<string, mixed>>  $towns
     */
    public function mount(int $category, ?int $subCategory, string $type, array $towns, string $title, bool $phone = false): void
    {
        $this->category = $category;
        $this->subCategory = $subCategory;
        $this->type = $type;
        $this->towns = $towns;
        $this->title = $title;
        $this->phone = $phone;

        // Legacy opened the town whose page number was in the address (?ini_H=2).
        if ($this->city === '') {
            foreach ($towns as $key => $town) {
                if (request()->has($town['page'])) {
                    $this->city = $key;
                    break;
                }
            }
        }

        $this->normalise();
        $this->total = InitiativeListQuery::upTo($category, $subCategory, $this->allPlaces(), 0)[1];
        $this->count = $this->fetch(0)[1];
    }

    /** Apply: the draft becomes the filter, and the list starts again from its first page. */
    public function apply(string $city, string $donor, bool $hasVideo, string $search): void
    {
        $this->city = $city;
        $this->donor = $donor;
        $this->hasVideo = $hasVideo;
        $this->search = $search;

        $this->restart();
    }

    /** The address changed under the list (the back button): start again. */
    public function updated(string $name): void
    {
        if (in_array($name, ['city', 'donor', 'hasVideo', 'search'], true)) {
            $this->restart();
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

    private function restart(): void
    {
        $this->normalise();
        $this->limit = self::STEP;
        $this->count = $this->fetch(0)[1];

        $this->dispatch('projects-filtered');
    }

    /** What the address or Apply sent, as one of the cities and donors, a short search and a known view. */
    private function normalise(): void
    {
        $this->city = array_key_exists($this->city, $this->towns) ? $this->city : '';
        $this->donor = $this->donors()->contains('slug', $this->donor) ? $this->donor : '';
        $this->search = Str::limit(trim($this->search), self::SEARCH_LENGTH, '');
        $this->view = $this->view === 'grid' ? 'grid' : 'tiles';
    }

    /** @return list<int> */
    private function allPlaces(): array
    {
        return collect($this->towns)->flatMap(fn (array $town) => $town['places'])->all();
    }

    /**
     * The activities of the applied filters, up to $limit, and how many match.
     *
     * @return array{0: Collection<int, Initiative>, 1: int}
     */
    private function fetch(int $limit): array
    {
        $town = $this->towns[$this->city] ?? null;
        $donor = $this->donors()->firstWhere('slug', $this->donor);

        return InitiativeListQuery::upTo($this->category, $this->subCategory, $town['places'] ?? $this->allPlaces(), $limit,
            $town['byDate'] ?? false, $this->search, $donor?->id, $this->hasVideo);
    }

    /** The donors of this programme's activities, each with the slug that stands for it in an address. */
    private function donors(): Support
    {
        return $this->donorList ??= MemberProjectQuery::donors()
            ->whereIn('id', InitiativeListQuery::donorIds($this->category, $this->subCategory))->values();
    }

    public function with(): array
    {
        [$activities] = $this->fetch($this->limit);
        $town = $this->towns[$this->city] ?? null;
        $filtered = $this->search !== '' || $this->donor !== '' || $this->hasVideo;

        return [
            'activities' => $activities,
            'hasMore' => $this->count > $activities->count(),
            'cities' => collect($this->towns)->map(fn (array $town, string $slug) => (object) ['slug' => $slug, 'name' => $town['label']])->values(),
            'donors' => $this->donors(),
            'emptyTitle' => $town && ! $filtered ? $town['empty'][0] : "Sorry, we couldn't find any results",
            'emptyHint' => $town && ! $filtered ? $town['empty'][1] : __('projects.sorryMessage2'),
        ];
    }
};
?>

<div id="initiative-section" class="section-gap">
    {{-- The bar sticks under the navigation while the list scrolls past, and is inset as on the micro-projects page. --}}
    <x-site.project-filters class="mx-3" :$cities :$donors :$view :title="$title" unit="Activities" :headings="['activities']"
                            :applied="['city' => $city, 'donor' => $donor, 'hasVideo' => $hasVideo, 'search' => $search]"/>

    <x-ui.reveal :ms="800">
        <div class="h-[3vw] bg-white"></div>

        <div class="relative bg-white">
            <div wire:loading.block wire:target="apply">
                <div x-show="$wire.view === 'tiles'" @if ($view !== 'tiles') style="display: none" @endif>
                    <x-site.activity-skeleton view="tiles"/>
                </div>
                <div x-show="$wire.view === 'grid'" @if ($view !== 'grid') style="display: none" @endif>
                    <x-site.activity-skeleton view="rows" :count="3"/>
                </div>
            </div>

            <div wire:loading.remove wire:target="apply">
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

                @if ($hasMore)
                    <x-ui.load-more/>
                @endif
            </div>

            <div wire:loading.block wire:target="loadMore">
                <div x-show="$wire.view === 'tiles'" @if ($view !== 'tiles') style="display: none" @endif><x-site.activity-skeleton view="tiles" class="card-skeleton--flush"/></div>
                <div x-show="$wire.view === 'grid'" @if ($view !== 'grid') style="display: none" @endif><x-site.activity-skeleton view="rows" :count="2"/></div>
            </div>
        </div>
    </x-ui.reveal>
</div>
