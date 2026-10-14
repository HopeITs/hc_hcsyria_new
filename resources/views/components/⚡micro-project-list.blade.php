<?php

use App\Queries\MemberProjectQuery;
use App\Queries\ProjectSearch;
use App\Support\Device;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use Livewire\Attributes\Url;
use Livewire\Component;

/*
 * What a signed-in member sees on /projects/micro-projects in place of the
 * gate: the filters, the projects as tiles or rows and a map of the filtered
 * set, with the details window (micro-project-details) alongside. Ported from
 * App\Livewire\MicroProjects and its view at legacy commit 6d45ef9; what each project card says and shows is in
 * components/site/project-*.
 *
 * The filter bar edits a draft in the browser and sends it when Apply is
 * pressed, as legacy's "temp" fields did. The map and the suggestions for
 * the search box are fetched by the browser (markers(), suggestions()) so a
 * page of tiles never carries the whole list.
 */
new class extends Component
{
    /** How many projects each "Load More" adds. */
    private const STEP = 15;

    private const SEARCH_LENGTH = 100;

    /*
     * The applied filters and the view live in the address (?city=homs&donor=caritas-poland&video=1&search=...&view=grid), cities and donors by slug,
     * so a filtered list can be bookmarked or shared and opens already filtered, and
     * each Apply is a step the back button undoes.
     */
    #[Url(history: true, except: '')]
    public string $city = '';

    #[Url(history: true, except: '')]
    public string $donor = '';

    #[Url(as: 'video', history: true, except: false)]
    public bool $hasVideo = false;

    #[Url(history: true, except: '')]
    public string $search = '';

    public int $limit = self::STEP;

    /** 'tiles' or 'grid' (the list of rows). */
    #[Url(except: 'tiles')]
    public string $view = 'tiles';

    /** How many projects match the applied filters. */
    #[Locked]
    public int $count = 0;

    /** How many projects the public list has at all. */
    #[Locked]
    public int $total = 0;

    /** Legacy chose the rows' layout on the server from the User-Agent (tablets included). */
    #[Locked]
    public bool $mobile = false;

    /** Every request, not just the first: a session that has ended gets nothing more. */
    public function boot(): void
    {
        Gate::authorize('view-micro-projects');
    }

    public function mount(): void
    {
        // What came in the address is no more to be trusted than what Apply sends.
        $this->city = $this->knownCity($this->city);
        $this->donor = $this->knownDonor($this->donor);
        $this->search = Str::limit(trim($this->search), self::SEARCH_LENGTH, '');
        $this->view = $this->view === 'grid' ? 'grid' : 'tiles';

        $this->mobile = Device::isMobile(request());
        $this->total = MemberProjectQuery::total();
        $this->count = $this->query()->builder()->count();
    }

    /** Apply: the draft becomes the filter, and the list starts again from its first page. */
    public function apply(string $city, string $donor, bool $hasVideo, string $search): void
    {
        $this->city = $this->knownCity($city);
        $this->donor = $this->knownDonor($donor);
        $this->hasVideo = $hasVideo;
        $this->search = Str::limit(trim($search), self::SEARCH_LENGTH, '');
        $this->limit = self::STEP;
        $this->count = $this->query()->builder()->count();

        $this->dispatch('projects-filtered');
    }

    /** The address changed under the list (the back button): count again from the first page. */
    public function updated(string $name): void
    {
        if (in_array($name, ['city', 'donor', 'hasVideo', 'search'], true)) {
            $this->city = $this->knownCity($this->city);
            $this->donor = $this->knownDonor($this->donor);
            $this->search = Str::limit(trim($this->search), self::SEARCH_LENGTH, '');
            $this->limit = self::STEP;
            $this->count = $this->query()->builder()->count();

            $this->dispatch('projects-filtered');
        }
    }

    public function loadMore(): void
    {
        $this->limit += self::STEP;
    }

    public function show(string $view): void
    {
        $this->view = $view === 'grid' ? 'grid' : 'tiles';
    }

    /** @return array<int, array{lat: string, lng: string, name: ?string, beneficiary: string}> */
    #[Renderless]
    public function markers(): array
    {
        return MemberProjectQuery::markers($this->query());
    }

    /** @return array{projects: array<int, string>, beneficiaries: array<int, string>} */
    #[Renderless]
    public function suggestions(): array
    {
        return MemberProjectQuery::suggestions();
    }

    /** A city slug as the address or Apply sent it: kept when it is one of the filter's, else no city. */
    private function knownCity(string $slug): string
    {
        return MemberProjectQuery::cityId($slug) === null ? '' : $slug;
    }

    private function knownDonor(string $slug): string
    {
        return MemberProjectQuery::donorId($slug) === null ? '' : $slug;
    }

    private function query(): ProjectSearch
    {
        return ProjectSearch::make()
            ->search($this->search)
            ->forDonor(MemberProjectQuery::donorId($this->donor))
            ->inCity(MemberProjectQuery::cityId($this->city))
            ->onlyWithVideo($this->hasVideo);
    }

    public function with(): array
    {
        $projects = MemberProjectQuery::page($this->query(), $this->limit);

        return [
            'projects' => $projects,
            'hasMore' => $this->count > $projects->count(),
            'cities' => MemberProjectQuery::cities(),
            'donors' => MemberProjectQuery::donors(),
        ];
    }
};
?>

<div class="member-projects">
    <x-site.project-filters :$cities :$donors :view="$view" :applied="['city' => $city, 'donor' => $donor, 'hasVideo' => $hasVideo, 'search' => $search]"/>

    <div class="page-margin">
        <div class="page-margin">
            {{-- Placeholders for the list while Apply is thinking. --}}
            <div wire:loading.block wire:target="apply">
                <x-site.project-skeleton :$view :count="6"/>
            </div>
        </div>

        <div class="page-margin">
            <div class="page-margin" wire:loading.remove wire:target="apply">
                @if ($projects->isNotEmpty())
                    @if ($view === 'tiles')
                        <div id="tiles-section" class="project-tiles">
                            @foreach ($projects as $project)
                                <x-site.project-tile :$project wire:key="tile-{{ $project->id }}"/>
                            @endforeach
                        </div>
                    @else
                        <div id="grid-section" class="project-rows">
                            @foreach ($projects as $project)
                                <x-site.project-row :$project :$mobile :flip="! $mobile && $loop->even" wire:key="row-{{ $project->id }}"/>
                            @endforeach
                        </div>
                    @endif

                    <div wire:loading.block wire:target="loadMore">
                        <x-site.project-skeleton :$view :count="3" class="project-skeleton--flush"/>
                    </div>

                    @if ($hasMore)
                        <div class="project-loadmore">
                            <button type="button" class="project-button project-button--outline" wire:click="loadMore">Load More</button>
                        </div>
                    @endif
                @else
                    <div class="project-none">No Projects Found</div>
                @endif

                <x-ui.red-rule short class="project-rule"/>
                <x-site.project-map/>
            </div>
        </div>
    </div>

    {{-- The details window is a component of its own, so opening it does not draw this list again. --}}
    <livewire:micro-project-details/>
</div>
