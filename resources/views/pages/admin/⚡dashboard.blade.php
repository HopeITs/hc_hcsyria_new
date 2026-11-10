<?php

use App\Enums\AdminCapability;
use App\Livewire\Concerns\ManagesContent;
use App\Models\Initiative;
use App\Models\News;
use App\Models\Project;
use App\Models\RootsOfHope;
use App\Models\Success;
use App\Queries\AdminAccessQuery;
use App\Queries\AdminDashboardQuery;
use App\Queries\AdminInitiativeIndexQuery;
use App\Queries\AdminNewsIndexQuery;
use App\Queries\AdminProjectIndexQuery;
use App\Queries\AdminRootsQuery;
use App\Queries\AdminStatisticsQuery;
use App\Queries\AdminSuccessIndexQuery;
use App\Support\Admin\WorkQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/*
 * /admin: what needs this person now, and how the micro-projects stand. The
 * queue and the figures follow the account's role and cities (WorkQueue,
 * AdminAccessQuery); the buttons are the shared ones (ManagesContent).
 * Replaces the legacy admin home, which listed every pending project in three
 * city tables.
 *
 * The page is read in three beats: the band at the top says what needs this
 * person, the tiles say what is stuck, and the work area holds the queue and the
 * other sections. The impact figures come last, for those who may see them.
 */
new #[Layout('layouts::admin', ['title' => 'Dashboard'])] class extends Component
{
    use ManagesContent;

    private const QUEUE_SIZE = 6;

    private const SECTION_QUEUE_SIZE = 3;

    /**
     * The other sections' queues: what waits for this person in each, on the tab the micro
     * projects' queue uses. Photos and "latest" mean nothing outside projects, so those
     * accounts see the latest items. Only the sections the account may open.
     *
     * Each section says which of its own tabs stands for the queue's tab: the magazine
     * has no English check, so the English queue falls back to its latest editions.
     *
     * @param  array{tab: string, title: string, action: string}  $queue
     * @return list<array{title: string, count: int, rows: list<object>, section: string, tab: string, icon: string}>
     */
    private function sectionQueues(array $queue): array
    {
        $user = Auth::user();
        $tab = in_array($queue['tab'], ['pending', 'english'], true) ? $queue['tab'] : 'all';
        $when = ['pending' => 'waiting to be published', 'english' => 'waiting for the English check'];
        $sections = [
            ['name' => 'Initiatives', 'icon' => 'spark', 'model' => Initiative::class, 'route' => 'admin.initiatives', 'tabs' => ['pending' => 'pending', 'english' => 'english', 'all' => 'all'], 'make' => fn () => AdminInitiativeIndexQuery::make()],
            ['name' => 'Successes', 'icon' => 'trophy', 'model' => Success::class, 'route' => 'admin.successes', 'tabs' => ['pending' => 'pending', 'english' => 'english', 'all' => 'all'], 'make' => fn () => AdminSuccessIndexQuery::make()],
            ['name' => 'Newsletter items', 'icon' => 'news', 'model' => News::class, 'route' => 'admin.news', 'tabs' => ['pending' => 'pending', 'english' => 'english', 'all' => 'all'], 'make' => fn () => AdminNewsIndexQuery::make()],
            ['name' => 'Roots of Hope', 'icon' => 'book', 'model' => RootsOfHope::class, 'route' => 'admin.roots', 'tabs' => ['pending' => 'pending', 'english' => 'all', 'all' => 'all'], 'make' => fn () => AdminRootsQuery::make()],
        ];

        $queues = [];

        foreach ($sections as $section) {
            if (! Gate::forUser($user)->allows('viewAny', $section['model'])) {
                continue;
            }

            $sectionTab = $section['tabs'][$tab];

            $queues[] = [
                'title' => $sectionTab === 'all' ? 'Latest '.lcfirst($section['name']) : $section['name'].' '.$when[$sectionTab],
                'count' => $section['make']()->counts()[$sectionTab],
                'rows' => $section['make']()->tab($sectionTab)->paginate(self::SECTION_QUEUE_SIZE)->items(),
                'section' => $section['route'],
                'tab' => $sectionTab,
                'icon' => $section['icon'],
            ];
        }

        return $queues;
    }

    public function with(): array
    {
        $user = Auth::user();
        $places = AdminAccessQuery::placeIds($user);
        $counts = AdminProjectIndexQuery::make()->limitedTo($places)->counts();
        $dashboard = new AdminDashboardQuery($places);
        $queue = WorkQueue::for($user);
        $may = fn (AdminCapability $capability): bool => AdminAccessQuery::can($user, $capability);
        $stats = new AdminStatisticsQuery($places);
        $reports = $may(AdminCapability::ViewReports);
        $log = $may(AdminCapability::ViewLog);

        return [
            // Work that is stuck, for everyone; money, people and readers only for those who see reports.
            'attention' => $stats->attention(),
            'reports' => $reports,
            'funding' => $reports ? $stats->funding() : null,
            'fundedPerMonth' => $reports
                ? $stats->fundedPerMonth()->map(fn ($row): array => [
                    'label' => Carbon::parse($row->month.'-01')->format('M'),
                    'value' => (int) $row->projects,
                    'title' => Carbon::parse($row->month.'-01')->format('F Y'),
                ])->values()
                : collect(),
            'beneficiaries' => $reports ? $stats->beneficiaries() : null,
            'subscribers' => $reports ? $stats->subscribers() : null,
            'topPages' => $reports ? $stats->topPages() : collect(),
            'topArticles' => $reports ? $stats->topArticles() : collect(),
            'team' => $log ? $stats->teamActivity() : collect(),
            'recent' => $log ? $dashboard->recentActivity() : collect(),
            'currency' => (string) config('hcsyria.currency'),
            'queue' => $queue,
            'queueRows' => AdminProjectIndexQuery::make()->limitedTo($places)->tab($queue['tab'])->paginate(self::QUEUE_SIZE)->items(),
            'queueTotal' => $counts[$queue['tab']],
            'counts' => $counts,
            'sectionQueues' => $this->sectionQueues($queue),
            'cities' => $dashboard->onlineByCity(),
            'canCreate' => [
                'project' => Gate::forUser($user)->allows('create', Project::class),
                'initiative' => Gate::forUser($user)->allows('create', Initiative::class),
                'success' => Gate::forUser($user)->allows('create', Success::class),
                'news' => Gate::forUser($user)->allows('create', News::class),
                'roots' => Gate::forUser($user)->allows('create', RootsOfHope::class),
            ],
            'may' => [
                'publish' => $may(AdminCapability::Publish) || $may(AdminCapability::CheckEnglish),
                'english' => $may(AdminCapability::CheckEnglish) || $may(AdminCapability::Edit),
                'photos' => $may(AdminCapability::ManagePhotos),
                'edit' => $may(AdminCapability::Edit),
                'create' => $may(AdminCapability::Create),
            ],
        ];
    }
};
?>

@php
    $user = Auth::user();
    $firstName = \Illuminate\Support\Str::of($user->name)->before(' ')->value();
    $hour = (int) now()->format('G');
    $greeting = match (true) {
        $hour < 12 => 'Good morning',
        $hour < 18 => 'Good afternoon',
        default => 'Good evening',
    };

    $waiting = $queueTotal > 0;
    $headline = match (true) {
        ! $waiting => 'Nothing is waiting for you',
        $queue['tab'] === 'all' => 'The latest micro projects',
        default => $queueTotal.' '.\Illuminate\Support\Str::plural('micro project', $queueTotal).' need you',
    };
    $detail = match (true) {
        ! $waiting => 'Your queue is clear. Here is how the work stands.',
        $queue['tab'] === 'all' => 'Newest first, across the cities you can see.',
        $queue['tab'] === 'pending' && $attention['oldest_days'] !== null => 'Waiting to be published · the oldest has waited '.\Illuminate\Support\Str::plural('day', $attention['oldest_days']),
        default => $queue['title'],
    };
    $urgent = $waiting && $queue['tab'] !== 'all';
@endphp

<div class="stack dash">
    <x-admin.page-header title="Dashboard" subtitle="What needs you now, and how the micro projects stand." />

    <x-admin.focus-band
        :eyebrow="$greeting.', '.$firstName"
        :headline="$headline"
        :detail="$detail"
        :figure="$waiting ? number_format($queueTotal) : null"
        :figure-label="$queue['tab'] === 'all' ? 'projects' : 'waiting'"
        :tone="$urgent ? 'action' : 'calm'">
        <x-slot:actions>
            <a href="{{ route('admin.projects.index', ['tab' => $queue['tab']]) }}" wire:navigate class="btn btn-p"><x-admin.icon name="list" />Open the list</a>
        </x-slot:actions>
    </x-admin.focus-band>

    <section class="sec">
        <h2 class="sec-h">Needs attention</h2>
        <div class="tiles">
            <x-admin.stat-tile label="Funded, not published" :value="number_format($attention['unpublished'])" icon="folder" :tone="$attention['unpublished'] > 0 ? 'warn' : 'good'" :hint="$attention['oldest_days'] !== null ? 'Oldest has waited '.number_format($attention['oldest_days']).' days' : 'Nothing is waiting'" :href="route('admin.projects.index', ['tab' => 'pending'])" />
            <x-admin.stat-tile label="Waiting for English check" :value="number_format($attention['english'])" icon="edit" :tone="$attention['english'] > 0 ? 'warn' : 'good'" :href="route('admin.projects.index', ['tab' => 'english'])" />
            <x-admin.stat-tile label="Online without photos" :value="number_format($attention['photos'])" icon="warn" :tone="$attention['photos'] > 0 ? 'crit' : 'good'" :href="route('admin.projects.index', ['tab' => 'nophoto'])" />
            <x-admin.stat-tile label="Hidden projects" :value="number_format($attention['hidden'])" icon="log" :href="route('admin.projects.index', ['tab' => 'hidden'])" />
        </div>
    </section>

    <div class="cols">
        <div class="stack">
            <x-admin.panel :title="$queue['title']" :count="number_format($queueTotal)" icon="folder">
                <x-slot:toolbar>
                    <a href="{{ route('admin.projects.index', ['tab' => $queue['tab']]) }}" wire:navigate class="btn btn-g s">Open the list</a>
                </x-slot:toolbar>

                @forelse ($queueRows as $project)
                    @php
                        $age = $project->funded ? now()->startOfDay()->diffInDays(Carbon::parse($project->funded)->startOfDay(), true) : null;
                        $missing = $queue['action'] === 'pending' ? \App\Support\Admin\ProjectReadiness::blockers($project) : null;
                    @endphp
                    <div class="q{{ $age !== null && $age >= 30 ? ' aged' : '' }}" wire:key="queue-{{ $project->id }}">
                        <a href="{{ route('admin.projects.show', $project->id) }}" wire:navigate>
                            <div class="nm">{{ $project->name }}</div>
                            <div class="mt">
                                <span class="tnum">#{{ $project->number }}</span>
                                <span>{{ $project->city }}</span>
                                @if ($project->funded)<span>funded {{ Carbon::parse($project->funded)->diffForHumans() }}</span>@endif
                                @if ($age !== null && $age >= 30)<span class="age">{{ $age }} days waiting</span>@endif
                                @if ($missing)
                                    <span class="age warn"><x-admin.icon name="warn" class="i16" /> Needs: {{ \App\Support\Admin\ProjectReadiness::describe($missing) }}</span>
                                @endif
                            </div>
                        </a>
                        <x-admin.content-actions :item="$project" />
                    </div>
                @empty
                    <x-admin.empty-state icon="check" title="All caught up">Nothing is waiting here.</x-admin.empty-state>
                @endforelse
            </x-admin.panel>
        </div>

        <div class="stack">
            @if (array_filter($canCreate))
                <x-admin.panel title="Create" icon="plus">
                    <div class="create-grid">
                        @if ($canCreate['project'])
                            <a href="{{ route('admin.projects.create') }}" wire:navigate class="create-btn"><x-admin.icon name="folder" />New project</a>
                        @endif
                        @if ($canCreate['initiative'])
                            <a href="{{ route('admin.initiatives.create') }}" wire:navigate class="create-btn"><x-admin.icon name="spark" />New initiative</a>
                        @endif
                        @if ($canCreate['success'])
                            <a href="{{ route('admin.successes.create') }}" wire:navigate class="create-btn"><x-admin.icon name="trophy" />New success</a>
                        @endif
                        @if ($canCreate['news'])
                            <a href="{{ route('admin.news.create') }}" wire:navigate class="create-btn"><x-admin.icon name="news" />New newsletter item</a>
                        @endif
                        @if ($canCreate['roots'])
                            <a href="{{ route('admin.roots.create') }}" wire:navigate class="create-btn"><x-admin.icon name="book" />New magazine edition</a>
                        @endif
                    </div>
                </x-admin.panel>
            @endif

            <x-admin.panel title="Online micro projects by city" icon="pin" :count="number_format($counts['online'])">
                <x-admin.rank-list :rows="$cities" label="city" value="total" :total="$counts['online']" empty="No project is online yet." />
            </x-admin.panel>

            @if ($team->isNotEmpty())
                <x-admin.panel title="Team activity, last 7 days" icon="users">
                    <x-admin.bar-list :rows="$team" label="name" value="actions" suffix=" actions" />
                </x-admin.panel>
            @endif

            @if ($recent->isNotEmpty())
                <x-admin.panel title="Recent changes" icon="log">
                    <x-admin.activity-feed :rows="$recent" />
                </x-admin.panel>
            @endif
        </div>
    </div>

    @if ($sectionQueues !== [])
        <section class="sec">
            <h2 class="sec-h">The other sections</h2>
            <div class="sec-cards">
                @foreach ($sectionQueues as $section)
                    <x-admin.section-queue :title="$section['title']" :count="$section['count']" :rows="$section['rows']" :section="$section['section']" :tab="$section['tab']" :icon="$section['icon']" />
                @endforeach
            </div>
        </section>
    @endif

    @if ($reports)
        <section class="sec">
            <h2 class="sec-h">
                <span>Impact</span>
                <a href="{{ route('admin.reports') }}" wire:navigate class="sec-link">Open the reports<x-admin.icon name="ext" class="i16" /></a>
            </h2>

            <div class="tiles">
                <x-admin.stat-tile label="Money donated" :value="number_format($funding['donated'])" :hint="$currency.' · '.number_format($funding['share'], 0).'% of '.number_format($funding['needed'])" icon="trophy" tone="good" :meter="$funding['share']" />
                <x-admin.stat-tile label="Still missing" :value="number_format($funding['missing'])" :hint="$currency" icon="warn" :tone="$funding['missing'] > 0 ? 'warn' : 'good'" />
                <x-admin.stat-tile label="People reached" :value="number_format($beneficiaries['people'])" :hint="number_format($beneficiaries['families']).' families · '.$beneficiaries['average_family'].' per family'" icon="users" />
                <x-admin.stat-tile label="Days, funded to online" :value="$funding['avg_days_to_publish'] === null ? 'No data' : number_format($funding['avg_days_to_publish'], 0)" hint="Average, published projects" icon="spark" />
            </div>

            <div class="cols">
                <x-admin.panel title="Projects funded, last 12 months" icon="chart">
                    <x-admin.column-chart :rows="$fundedPerMonth" :height="160" empty="No funded projects in the last year." />
                </x-admin.panel>

                <x-admin.panel title="Readers of the site" icon="eye">
                    <x-admin.bar-list :rows="$topPages" label="page" value="views" empty="No page views are counted yet." />
                    <div class="note-row">
                        <span class="muted">Newsletter: <b class="tnum">{{ number_format($subscribers['active']) }}</b> active · <b class="tnum">{{ number_format($subscribers['new_week']) }}</b> new this week ({{ number_format($subscribers['previous_week']) }} the week before)</span>
                    </div>
                </x-admin.panel>
            </div>

            <x-admin.panel title="Most read newsletter items" icon="news">
                <x-admin.bar-list :rows="$topArticles" label="title" value="views" suffix=" views" empty="No newsletter item has been read yet." />
            </x-admin.panel>
        </section>
    @endif
</div>
