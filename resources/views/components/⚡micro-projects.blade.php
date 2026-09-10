<?php

use App\Queries\MicroProjectStatsQuery;
use App\Queries\PartnerQuery;
use App\Queries\ProjectSearch;
use Livewire\Attributes\Computed;
use Livewire\Component;

/*
 * Ported from App\Livewire\MicroProjects in the legacy application.
 *
 * Behaviour is kept as it is there: filters are staged in temp* properties and
 * only take effect when Apply is pressed, the list grows by 15 on Load More,
 * and the view toggles between tiles and grid. Querying is delegated to
 * app/Queries\ProjectSearch, which is verified against the legacy query.
 */
new class extends Component
{
    /** Staged filter values, applied on Apply. */
    public string $tempSearch = '';
    public string $tempCity = '';
    public string $tempDonor = '';
    public bool $tempHasVideo = false;

    /** Applied filter values, which drive the query. */
    public string $search = '';
    public string $city = '';
    public string $donor = '';
    public bool $hasVideo = false;

    public string $activeView = 'tiles';

    public int $limit = 15;

    private const PAGE_SIZE = 15;

    public function applyFilters(): void
    {
        $this->search   = $this->tempSearch;
        $this->city     = $this->tempCity;
        $this->donor    = $this->tempDonor;
        $this->hasVideo = $this->tempHasVideo;

        $this->limit = self::PAGE_SIZE;
    }

    public function loadMore(): void
    {
        $this->limit += self::PAGE_SIZE;
    }

    private function query(): ProjectSearch
    {
        return ProjectSearch::make()
            ->search($this->search)
            ->forDonor($this->donor)
            ->inCity($this->city)
            ->onlyWithVideo($this->hasVideo);
    }

    #[Computed]
    public function projects()
    {
        return $this->query()->builder()
            ->with(['content', 'mPEDonor', 'mPECity', 'images'])
            ->limit($this->limit)
            ->get();
    }

    /** Projects matching the current filters. */
    #[Computed]
    public function totalCount(): int
    {
        return $this->query()->builder()->toBase()->getCountForPagination();
    }

    /** Every published project, shown in brackets beside the filtered count. */
    #[Computed]
    public function totalProjectsCount(): int
    {
        return ProjectSearch::make()->builder()->toBase()->getCountForPagination();
    }

    #[Computed]
    public function hasMore(): bool
    {
        return $this->projects->count() < $this->totalCount;
    }

    #[Computed]
    public function cities()
    {
        return MicroProjectStatsQuery::make()->countsByCity()
            ->keys()
            ->map(fn($id) => (object) ['id' => (int) $id, 'name' => PartnerQuery::placeName($id)])
            ->filter(fn($c) => $c->name !== null)
            ->values();
    }

    #[Computed]
    public function donors()
    {
        return PartnerQuery::microDonors();
    }

    #[Computed]
    public function cityName(): string
    {
        return $this->city === ''
            ? 'All Cities'
            : (PartnerQuery::placeName($this->city) ?? 'All Cities');
    }

    #[Computed]
    public function donorName(): string
    {
        return $this->donor === ''
            ? 'All Donors'
            : ($this->donors->firstWhere('id', (int) $this->donor)->name ?? 'All Donors');
    }
};
?>

<div class="mx-auto max-w-[1400px] px-4 sm:px-6">
    {{-- Filters --}}
    <div class="filters-section">
        <div class="flex flex-wrap items-center justify-center gap-4 sm:gap-10">
            <x-ui.dropdown :label="'City-' . ($tempCity ? (\App\Queries\PartnerQuery::placeName($tempCity) ?? 'All') : 'All')">
                <button type="button" wire:click="$set('tempCity', '')" class="dropdown-item">All</button>
                @foreach ($this->cities as $option)
                    <button type="button" wire:click="$set('tempCity', '{{ $option->id }}')" class="dropdown-item">
                        {{ $option->name }}
                    </button>
                @endforeach
            </x-ui.dropdown>

            <x-ui.dropdown :label="'Donor-' . ($tempDonor ? ($this->donors->firstWhere('id', (int) $tempDonor)->name ?? 'All') : 'All')">
                <button type="button" wire:click="$set('tempDonor', '')" class="dropdown-item">All</button>
                @foreach ($this->donors as $option)
                    <button type="button" wire:click="$set('tempDonor', '{{ $option->id }}')" class="dropdown-item">
                        {{ $option->name }}
                    </button>
                @endforeach
            </x-ui.dropdown>
        </div>

        <h2 class="section-title mt-6 text-center">Projects</h2>

        <div class="mt-4 flex flex-wrap items-center justify-center gap-4 sm:gap-10">
            <div class="flex items-center gap-3">
                <span class="nav-link normal-case">View</span>

                <button type="button" wire:click="$set('activeView', 'tiles')"
                        aria-pressed="{{ $activeView === 'tiles' ? 'true' : 'false' }}" class="p-1">
                    <span class="sr-only">Tiles view</span>
                    <img src="{{ $activeView === 'tiles' ? '/images/ico/tiles-active.png' : '/images/ico/tiles.png' }}"
                         alt="" class="h-5 w-auto">
                </button>

                <button type="button" wire:click="$set('activeView', 'grid')"
                        aria-pressed="{{ $activeView === 'grid' ? 'true' : 'false' }}" class="p-1">
                    <span class="sr-only">Grid view</span>
                    <img src="{{ $activeView === 'grid' ? '/images/ico/grid-active.png' : '/images/ico/grid.png' }}"
                         alt="" class="h-5 w-auto">
                </button>

                <label class="ml-2 flex cursor-pointer items-center rounded px-2 py-1"
                       style="background: {{ $tempHasVideo ? '#BC1818' : '#f7f7f7' }}">
                    <input type="checkbox" wire:model.live="tempHasVideo" class="sr-only"
                           aria-label="Project with Video">
                    <img src="{{ $tempHasVideo ? '/images/ico/reverst-video.svg' : '/images/ico/search-video-icon.svg' }}"
                         alt="" class="h-5 w-auto">
                </label>
            </div>

            <div class="flex items-end gap-2">
                <label for="search-input" class="sr-only">Search</label>
                <input id="search-input" type="text" placeholder="Search" wire:model="tempSearch"
                       wire:keydown.enter="applyFilters"
                       class="border border-ink-200 px-3 py-1.5 text-[1vw] focus:border-brand-600 focus:outline-none">
                <button type="button" wire:click="applyFilters" class="apply-btn">Apply</button>
            </div>
        </div>

        <p class="result-line mt-4 text-center" aria-live="polite">
            Filter Result:
            <span class="font-semibold">
                {{ number_format($this->totalCount) }} ({{ number_format($this->totalProjectsCount) }}) Projects
                | {{ $this->cityName }}
                | {{ $this->donorName }}
                @if ($search) | {{ $search }} @endif
                @if ($hasVideo) | With Video @endif
            </span>
        </p>
    </div>

    {{-- Results --}}
    <div class="mt-8 grid gap-6 {{ $activeView === 'grid' ? 'sm:grid-cols-3 lg:grid-cols-4' : 'sm:grid-cols-2 lg:grid-cols-3' }}">
        @foreach ($this->projects as $project)
            <x-project.card :$project wire:key="project-{{ $project->id }}"/>
        @endforeach
    </div>

    @if ($this->projects->isEmpty())
        <p class="result-line mt-10 text-center">No projects match these filters.</p>
    @endif

    @if ($this->hasMore)
        <div class="mt-10 flex justify-center">
            <button type="button" wire:click="loadMore" class="apply-btn loadmore">
                <span wire:loading.remove wire:target="loadMore">Load More</span>
                <span wire:loading wire:target="loadMore">Load More</span>
            </button>
        </div>
    @endif
</div>
