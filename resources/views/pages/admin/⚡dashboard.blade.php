<?php

use App\Enums\AdminCapability;
use App\Livewire\Concerns\ManagesContent;
use App\Queries\AdminAccessQuery;
use App\Queries\AdminDashboardQuery;
use App\Queries\AdminProjectIndexQuery;
use App\Support\Admin\WorkQueue;
use Illuminate\Support\Facades\Auth;
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

    public function with(): array
    {
        $user = Auth::user();
        $places = AdminAccessQuery::placeIds($user);
        $counts = AdminProjectIndexQuery::make()->limitedTo($places)->counts();
        $dashboard = new AdminDashboardQuery($places);
        $queue = WorkQueue::for($user);
        $may = fn (AdminCapability $capability): bool => AdminAccessQuery::can($user, $capability);

        return [
            'queue' => $queue,
            'queueRows' => AdminProjectIndexQuery::make()->limitedTo($places)->tab($queue['tab'])->paginate(self::QUEUE_SIZE)->items(),
            'queueTotal' => $counts[$queue['tab']],
            'counts' => $counts,
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
</div>
