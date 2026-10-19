<?php

use App\Queries\ActivitySearch;
use App\Queries\InitiativeTitleListQuery;
use App\Queries\MemberProjectQuery;
use App\Queries\ProjectSearch;
use App\Support\Device;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use Livewire\Attributes\Url;
use Livewire\Component;

/*
 * The site-wide search, /search?search=. Ported from
 * new/public/search.blade.php and PublicController::search() at legacy
 * commit 6d45ef9, and upgraded on request: the Projects and Initiatives tabs
 * (and the pager, and the Bootstrap, fancybox and jQuery scripts behind
 * them) are replaced by the filter bar of the other lists (city, donor,
 * video, search, Apply, tiles or rows) over two groups of results that each
 * grow with Load More. It follows the address like they do
 * (?search=...&city=homs&donor=caritas-poland&video=1&view=grid).
 *
 * As in legacy, a visit with no search term goes back to the page it came
 * from, and the projects (the members' micro-projects) are only searched,
 * counted and listed for those who pass 'view-micro-projects'.
 *
 * The red mark over each matching word is kept, but built by
 * App\Support\Highlight (legacy made it with an unescaped regular expression
 * from the visitor's text and printed the result as HTML). Not ported: the
 * centre's name as something to match; the English/French switch. What is
 * matched is in ActivitySearch and ProjectSearch (:matchingDonorAndCity).
 */
new #[Layout('layouts::app', [
    'title' => 'Hope Center Search',
    'keywords' => 'aleppo syria, hope, center, search, projects,news,initiatives',
    'preloader' => true,
])] class extends Component
{
    /** How many activities, and how many projects, each "Load More" adds. */
    private const ACTIVITY_STEP = 6;

    private const PROJECT_STEP = 15;

    private const SEARCH_LENGTH = 100;

    /** What was searched for; legacy's own parameter name. */
    #[Url(history: true, except: '')]
    public string $search = '';

    #[Url(history: true, except: '')]
    public string $city = '';

    #[Url(history: true, except: '')]
    public string $donor = '';

    #[Url(as: 'video', history: true, except: false)]
    public bool $hasVideo = false;

    /** 'tiles' or 'grid' (the rows). */
    #[Url(except: 'tiles')]
    public string $view = 'tiles';

    public int $activityLimit = self::ACTIVITY_STEP;

    public int $projectLimit = self::PROJECT_STEP;

    /** What the filters leave: all of it, counted for the bar, and for each group's heading. */
    #[Locked]
    public int $count = 0;

    #[Locked]
    public int $activityCount = 0;

    #[Locked]
    public int $projectCount = 0;

    /** What the search finds with no filter but its words; the bar shows it beside the count. */
    #[Locked]
    public int $total = 0;

    #[Locked]
    public bool $phone = false;

    #[Locked]
    public bool $mobile = false;

    public function mount(): mixed
    {
        // Legacy: no search term, back to where the visitor was.
        if (! request()->filled('search')) {
            return $this->redirect(url()->previous('/'));
        }

        $this->phone = Device::isPhone(request());
        $this->mobile = Device::isMobile(request());
        $this->normalise();
        $this->recount();

        return null;
    }

    /** Apply: the draft becomes the filter, and both lists start again from their first page. */
    public function apply(string $city, string $donor, bool $hasVideo, string $search): void
    {
        $this->city = $city;
        $this->donor = $donor;
        $this->hasVideo = $hasVideo;
        $this->search = $search;

        $this->restart();
    }

    /** The address changed under the lists (the back button): start again. */
    public function updated(string $name): void
    {
        if (in_array($name, ['city', 'donor', 'hasVideo', 'search'], true)) {
            $this->restart();
        }
    }

    /** @param  'activities'|'projects'  $group */
    public function loadMore(string $group): void
    {
        if ($group === 'projects') {
            $this->projectLimit += self::PROJECT_STEP;
        } else {
            $this->activityLimit += self::ACTIVITY_STEP;
        }
    }

    /**
     * What the search box suggests: activity titles, then, for those who may see them, the
     * names of the projects and of their beneficiaries.
     *
     * @return list<array<int, string>>
     */
    #[Renderless]
    public function suggestions(): array
    {
        $titles = InitiativeTitleListQuery::make()->onlyOnline()->titles()
            ->map(fn ($title) => html_entity_decode($title, ENT_QUOTES))->all();

        if (! $this->mayViewProjects()) {
            return [$titles];
        }

        $projects = MemberProjectQuery::suggestions();

        return [$titles, $projects['projects'], $projects['beneficiaries']];
    }

    private function restart(): void
    {
        $this->normalise();
        $this->activityLimit = self::ACTIVITY_STEP;
        $this->projectLimit = self::PROJECT_STEP;
        $this->recount();

        $this->dispatch('projects-filtered');
    }

    /** What the address or Apply sent, as one of the cities and donors, a short search and a known view. */
    private function normalise(): void
    {
        $this->city = MemberProjectQuery::cityId($this->city) === null ? '' : $this->city;
        $this->donor = $this->donors()->contains('slug', $this->donor) ? $this->donor : '';
        $this->search = Str::limit(trim($this->search), self::SEARCH_LENGTH, '');
        $this->view = $this->view === 'grid' ? 'grid' : 'tiles';
    }

    private function recount(): void
    {
        $this->activityCount = $this->activities()->builder()->count();
        $this->projectCount = $this->mayViewProjects() ? $this->projects()->builder()->count() : 0;
        $this->count = $this->activityCount + $this->projectCount;

        $this->total = ActivitySearch::make()->search($this->search)->builder()->count()
            + ($this->mayViewProjects() ? $this->projects(filtered: false)->builder()->count() : 0);
    }

    private function mayViewProjects(): bool
    {
        return Gate::allows('view-micro-projects');
    }

    private function activities(): ActivitySearch
    {
        return ActivitySearch::make()
            ->search($this->search)
            ->inCity(MemberProjectQuery::cityId($this->city))
            ->forDonor(MemberProjectQuery::donorId($this->donor))
            ->onlyWithVideo($this->hasVideo);
    }

    private function projects(bool $filtered = true): ProjectSearch
    {
        $search = ProjectSearch::make()->search($this->search)->matchingDonorAndCity();

        return $filtered
            ? $search
                ->forDonor(MemberProjectQuery::donorId($this->donor))
                ->inCity(MemberProjectQuery::cityId($this->city))
                ->onlyWithVideo($this->hasVideo)
            : $search;
    }

    /** The donors that fund something listed here (an activity, or a project for those who may see it). */
    private function donors()
    {
        $funders = collect(ActivitySearch::donorIds());

        if ($this->mayViewProjects()) {
            $funders = $funders->merge(ProjectSearch::make()->builder()->reorder()->distinct()->pluck('MPE_Donor')->map(fn ($id) => (int) $id));
        }

        return MemberProjectQuery::donors()->whereIn('id', $funders->unique()->all())->values();
    }

    public function with(): array
    {
        $mayViewProjects = $this->mayViewProjects();
        $activities = $this->activities()->page($this->activityLimit);
        $projects = $mayViewProjects ? MemberProjectQuery::page($this->projects(), $this->projectLimit) : collect();

        return [
            'activities' => $activities,
            'projects' => $projects,
            'mayViewProjects' => $mayViewProjects,
            'moreActivities' => $this->activityCount > $activities->count(),
            'moreProjects' => $this->projectCount > $projects->count(),
            'cities' => MemberProjectQuery::cities(),
            'donors' => $this->donors(),
        ];
    }
};
?>

<div id="Search" class="page-start">
    <x-site.page-title solo subtitle="Search Results"/>

    <div class="mt-[3vw]">
        <x-site.project-filters class="mx-3" :$cities :$donors :$view title="Results" unit="Results"
                                :headings="$mayViewProjects ? ['activities', __('projects.projectsNames'), __('projects.beneficiariesNames')] : ['activities']"
                                :applied="['city' => $city, 'donor' => $donor, 'hasVideo' => $hasVideo, 'search' => $search]"/>

        <x-ui.reveal :ms="800">
            <div class="relative bg-white">
                {{-- Placeholders while Apply is thinking. --}}
                <div wire:loading.block wire:target="apply">
                    <div x-show="$wire.view === 'tiles'" @if ($view !== 'tiles') style="display: none" @endif>
                        <x-site.activity-skeleton view="tiles"/>
                    </div>
                    <div x-show="$wire.view === 'grid'" @if ($view !== 'grid') style="display: none" @endif>
                        <x-site.activity-skeleton view="rows" :count="3"/>
                    </div>
                </div>

                <div wire:loading.remove wire:target="apply">
                    @if ($mayViewProjects)
                        <div id="projects-results">
                            <x-ui.section-title class="pb-0 text-center">Projects ({{ $projectCount }})</x-ui.section-title>

                            @if ($projects->isNotEmpty())
                                <div class="member-projects">
                                    <div class="page-margin">
                                        <div class="card-grid" x-show="$wire.view === 'tiles'" @if ($view !== 'tiles') style="display: none" @endif>
                                            @foreach ($projects as $project)
                                                <x-site.project-tile :$project :highlight="$search" wire:key="project-tile-{{ $project->id }}"/>
                                            @endforeach
                                        </div>
                                        <div class="project-rows" x-show="$wire.view === 'grid'" @if ($view !== 'grid') style="display: none" @endif>
                                            @foreach ($projects as $project)
                                                <x-site.project-row :$project :$mobile :highlight="$search" :flip="! $mobile && $loop->even" wire:key="project-row-{{ $project->id }}"/>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                @if ($moreProjects)
                                    <x-ui.load-more method="loadMore('projects')"/>
                                @endif
                            @else
                                <div class="list-empty">No Projects Found</div>
                            @endif
                        </div>
                    @endif

                    <div id="activities-results">
                        <x-ui.section-title class="pb-0 text-center">Initiatives ({{ $activityCount }})</x-ui.section-title>

                        @if ($activities->isNotEmpty())
                            {{-- Both layouts are drawn and the view switch only shows one of them. --}}
                            <div class="card-grid activity-tiles" x-show="$wire.view === 'tiles'" @if ($view !== 'tiles') style="display: none" @endif>
                                @foreach ($activities as $activity)
                                    <x-site.activity-tile :$activity :highlight="$search" :type="Str::slug(strtolower($activity->whatCategory->name))" wire:key="tile-{{ $activity->id }}"/>
                                @endforeach
                            </div>
                            <div x-show="$wire.view === 'grid'" @if ($view !== 'grid') style="display: none" @endif>
                                @foreach ($activities as $activity)
                                    <x-site.activity-card :$activity :highlight="$search" :type="Str::slug(strtolower($activity->whatCategory->name))" :flip="! $phone && $loop->even" wire:key="card-{{ $activity->id }}"/>
                                @endforeach
                            </div>

                            @if ($moreActivities)
                                <x-ui.load-more method="loadMore('activities')"/>
                            @endif
                        @else
                            <div class="activity-card">
                                <div class="activity-card__inner">
                                    <div class="-mx-3 flex">
                                        <div class="activity-tabs__empty flex w-full flex-col justify-end px-3 pr-1">
                                            <div class="text-center">Sorry, we couldn't find any results</div>
                                            <div>{{ __('projects.sorryMessage2') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </x-ui.reveal>
    </div>

    <x-ui.red-rule class="red-rule--end"/>

    @if ($mayViewProjects)
        {{-- The details window of a project, a component of its own so opening it does not draw the lists again. --}}
        <livewire:micro-project-details/>
    @endif
</div>
