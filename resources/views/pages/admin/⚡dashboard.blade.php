<?php

use App\Enums\AdminCapability;
use App\Livewire\Concerns\ManagesContent;
use App\Queries\AdminAccessQuery;
use App\Models\Initiative;
use App\Models\News;
use App\Models\Success;
use App\Queries\AdminDashboardQuery;
use App\Queries\AdminInitiativeIndexQuery;
use App\Queries\AdminNewsIndexQuery;
use App\Queries\AdminProjectIndexQuery;
use App\Queries\AdminStatisticsQuery;
use App\Queries\AdminSuccessIndexQuery;
use App\Support\Admin\WorkQueue;
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
 */
new #[Layout('layouts::admin', ['title' => 'Dashboard'])] class extends Component
{
    use ManagesContent;

    private const QUEUE_SIZE = 6;

    private const SECTION_QUEUE_SIZE = 4;

    /**
     * The other sections' queues: what waits for this person in each, on the tab the micro
     * projects' queue uses. Photos and "latest" mean nothing outside projects, so those
     * accounts see the latest items. Only the sections the account may open.
     *
     * @param  array{tab: string, title: string, action: string}  $queue
     * @return list<array{title: string, count: int, rows: list<object>, section: string, tab: string}>
     */
    private function sectionQueues(array $queue): array
    {
        $user = Auth::user();
        $tab = in_array($queue['tab'], ['pending', 'english'], true) ? $queue['tab'] : 'all';
        $when = ['pending' => 'waiting to be published', 'english' => 'waiting for the English check'];
        $sections = [
            ['Initiatives', Initiative::class, 'admin.initiatives', fn () => AdminInitiativeIndexQuery::make()],
            ['Successes', Success::class, 'admin.successes', fn () => AdminSuccessIndexQuery::make()],
            ['Newsletter items', News::class, 'admin.news', fn () => AdminNewsIndexQuery::make()],
        ];

        $queues = [];

        foreach ($sections as [$name, $model, $route, $make]) {
            if (! Gate::forUser($user)->allows('viewAny', $model)) {
                continue;
            }

            $queues[] = [
                'title' => $tab === 'all' ? 'Latest '.lcfirst($name) : $name.' '.$when[$tab],
                'count' => $make()->counts()[$tab],
                'rows' => $make()->tab($tab)->paginate(self::SECTION_QUEUE_SIZE)->items(),
                'section' => $route,
                'tab' => $tab,
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

        return [
            // Work that is stuck, for everyone; money, people and readers only for those who see reports.
            'attention' => $stats->attention(),
            'reports' => $reports,
            'funding' => $reports ? $stats->funding() : null,
            'fundedPerMonth' => $reports ? $stats->fundedPerMonth() : collect(),
            'beneficiaries' => $reports ? $stats->beneficiaries() : null,
            'subscribers' => $reports ? $stats->subscribers() : null,
            'topPages' => $reports ? $stats->topPages() : collect(),
            'topArticles' => $reports ? $stats->topArticles() : collect(),
            'team' => $may(AdminCapability::ViewLog) ? $stats->teamActivity() : collect(),
            'currency' => (string) config('hcsyria.currency'),
            'queue' => $queue,
            'queueRows' => AdminProjectIndexQuery::make()->limitedTo($places)->tab($queue['tab'])->paginate(self::QUEUE_SIZE)->items(),
            'queueTotal' => $counts[$queue['tab']],
            'counts' => $counts,
            'sectionQueues' => $this->sectionQueues($queue),
            'cities' => $dashboard->onlineByCity(),
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

<div class="stack">
    <x-admin.page-header title="Dashboard" :subtitle="$queueTotal === 0 ? 'Nothing is waiting for you.' : $queueTotal.' '.\Illuminate\Support\Str::plural('project', $queueTotal).' need you.'">
        <x-slot:actions>
            @if ($may['create'])
                <a href="{{ route('admin.projects.create') }}" wire:navigate class="btn btn-p"><x-admin.icon name="plus" />New project</a>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <div class="tiles">
        <x-admin.stat-tile label="Funded, not published" :value="number_format($attention['unpublished'])" icon="folder" :tone="$attention['unpublished'] > 0 ? 'warn' : 'good'" :hint="$attention['oldest_days'] !== null ? 'Oldest has waited '.number_format($attention['oldest_days']).' days' : 'Nothing is waiting'" :href="route('admin.projects.index', ['tab' => 'pending'])" />
        <x-admin.stat-tile label="Waiting for English check" :value="number_format($attention['english'])" icon="edit" :tone="$attention['english'] > 0 ? 'warn' : 'good'" :href="route('admin.projects.index', ['tab' => 'english'])" />
        <x-admin.stat-tile label="Online without photos" :value="number_format($attention['photos'])" icon="warn" :tone="$attention['photos'] > 0 ? 'crit' : 'good'" :href="route('admin.projects.index', ['tab' => 'nophoto'])" />
        <x-admin.stat-tile label="Hidden projects" :value="number_format($attention['hidden'])" icon="log" :href="route('admin.projects.index', ['tab' => 'hidden'])" />
    </div>

    @if ($reports)
        <div class="tiles">
            <x-admin.stat-tile label="Money donated" :value="number_format($funding['donated'])" :hint="$currency.' · '.number_format($funding['share'], 0).'% of '.number_format($funding['needed'])" icon="trophy" tone="good" />
            <x-admin.stat-tile label="Still missing" :value="number_format($funding['missing'])" :hint="$currency" icon="warn" :tone="$funding['missing'] > 0 ? 'warn' : 'good'" />
            <x-admin.stat-tile label="People reached" :value="number_format($beneficiaries['people'])" :hint="number_format($beneficiaries['families']).' families · '.$beneficiaries['average_family'].' per family'" icon="users" />
            <x-admin.stat-tile label="Days, funded to online" :value="$funding['avg_days_to_publish'] === null ? 'No data' : number_format($funding['avg_days_to_publish'], 0)" hint="Average, published projects" icon="spark" />
        </div>

        <div class="cols" style="grid-template-columns: repeat(auto-fit, minmax(min(100%, 22rem), 1fr))">
            <x-admin.panel title="Projects funded, last 12 months">
                <x-admin.bar-list :rows="$fundedPerMonth" label="month" value="projects" empty="No funded projects in the last year." />
            </x-admin.panel>
            <x-admin.panel title="Readers of the site">
                <x-admin.bar-list :rows="$topPages" label="page" value="views" empty="No page views are counted yet." />
                <div class="mt" style="padding: 0 16px 14px">
                    <span class="muted">Newsletter: <b class="tnum">{{ number_format($subscribers['active']) }}</b> active · <b class="tnum">{{ number_format($subscribers['new_week']) }}</b> new this week ({{ number_format($subscribers['previous_week']) }} the week before)</span>
                </div>
            </x-admin.panel>
            <x-admin.panel title="Most read newsletter items">
                <x-admin.bar-list :rows="$topArticles" label="title" value="views" suffix=" views" empty="No newsletter item has been read yet." />
            </x-admin.panel>
        </div>

        <p style="margin: 0"><a href="{{ route('admin.reports') }}" wire:navigate class="btn btn-o s"><x-admin.icon name="log" />Open the reports</a> <span class="muted">for funding by city, donor and category, beneficiaries, and CSV downloads.</span></p>
    @endif

    @if ($team->isNotEmpty())
        <x-admin.panel title="Team activity, last 7 days">
            <x-admin.bar-list :rows="$team" label="name" value="actions" suffix=" actions" />
        </x-admin.panel>
    @endif

    <x-admin.panel title="Online micro projects by city" :count="number_format($counts['online'])">
        <div class="cities">
            @foreach ($cities as $city)
                @php $share = $counts['online'] > 0 ? $city->total / $counts['online'] * 100 : 0; @endphp
                <div class="city">
                    <div class="n tnum">{{ number_format($city->total) }}</div>
                    <div class="trk"><i style="width: {{ round($share) }}%"></i></div>
                    <small class="muted"><b style="color: var(--ink)">{{ $city->city }}</b> · {{ number_format($share, 1) }}%</small>
                </div>
            @endforeach
        </div>
    </x-admin.panel>

    <x-admin.panel :title="$queue['title']" :count="number_format($queueTotal)">
        <x-slot:toolbar>
            <a href="{{ route('admin.projects.index', ['tab' => $queue['tab']]) }}" wire:navigate class="btn btn-g s">Open the list</a>
        </x-slot:toolbar>

        @forelse ($queueRows as $project)
            <div class="q" wire:key="queue-{{ $project->id }}">
                <a href="{{ route('admin.projects.show', $project->id) }}" wire:navigate>
                    <div class="nm">{{ $project->name }}</div>
                    <div class="mt">
                        <span class="tnum">#{{ $project->number }}</span>
                        <span>{{ $project->city }}</span>
                        @if ($project->funded)<span>funded {{ \Illuminate\Support\Carbon::parse($project->funded)->diffForHumans() }}</span>@endif
                        @if ($queue['action'] === 'pending' && ($missing = \App\Support\Admin\ProjectReadiness::blockers($project)))
                            <span style="color: var(--warn)"><x-admin.icon name="warn" class="i16" /> Needs: {{ \App\Support\Admin\ProjectReadiness::describe($missing) }}</span>
                        @endif
                    </div>
                </a>
                <x-admin.content-actions :item="$project" />
            </div>
        @empty
            <x-admin.empty-state icon="check" title="All caught up">Nothing is waiting here.</x-admin.empty-state>
        @endforelse
    </x-admin.panel>

    <div class="cols" style="grid-template-columns: repeat(auto-fit, minmax(min(100%, 22rem), 1fr))">
        @foreach ($sectionQueues as $section)
            <x-admin.section-queue :title="$section['title']" :count="$section['count']" :rows="$section['rows']" :section="$section['section']" :tab="$section['tab']" />
        @endforeach
    </div>
</div>
