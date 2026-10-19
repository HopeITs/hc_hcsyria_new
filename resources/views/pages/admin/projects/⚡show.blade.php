<?php

use App\Enums\ProjectStatus;
use App\Livewire\Concerns\ManagesContent;
use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use App\Queries\AdminProjectQuery;
use App\Support\Admin\ProjectReadiness;
use App\Support\SafeHtml;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/*
 * /admin/projects/{id}: one micro-project, everything the staff need to judge
 * it: its details and story, its photos, what it still needs before it can go
 * online (the same checks the Publish button enforces) and what was done to it.
 * Replaces legacy's project-view. The buttons are the shared ones.
 */
new #[Layout('layouts::admin', ['title' => 'Micro project'])] class extends Component
{
    use ManagesContent;

    #[Locked]
    public int $projectId;

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);
        $this->projectId = $project->getKey();
    }

    /** The photos panel changed them: this page's checklist reads them again on its next render. */
    #[On('photos-changed')]
    public function photosChanged(): void {}

    /** The project is gone: back to the list, with the alert waiting there. */
    protected function afterDeleted(Model $item): void
    {
        $this->flashAlert('deleted', ['number' => $this->contentKind()->number($item)]);
        $this->redirectRoute('admin.projects.index', navigate: true);
    }

    public function with(): array
    {
        $project = AdminProjectQuery::find($this->projectId);

        if ($project === null) {
            $this->flashAlert('gone', type: 'error');
            $this->redirectRoute('admin.projects.index', navigate: true);

            return ['project' => null];
        }

        return [
            'project' => $project,
            'status' => ProjectStatus::of($project->published, $project->done, $project->hidden),
            'checks' => ProjectReadiness::checks($project),
            'blocking' => ProjectReadiness::BLOCKING,
            'history' => AdminProjectQuery::history($this->projectId),
        ];
    }
};
?>

<div class="stack">
    @if ($project)
        <x-admin.page-header :title="$project->name" :crumbs="['Dashboard' => route('admin.dashboard'), 'Micro projects' => route('admin.projects.index'), '#'.$project->number => null]">
            <x-slot:actions>
                <a href="{{ route('admin.projects.index') }}" wire:navigate class="btn btn-o"><x-admin.icon name="chev" class="i16" />Back</a>
                @if ($status === ProjectStatus::Online)
                    <a href="/projects/micro-projects?projectDetailsModal={{ $project->id }}" target="_blank" rel="noopener" class="btn btn-o"><x-admin.icon name="ext" />View on the site</a>
                @endif
                <x-admin.content-actions :item="$project" size="" />
            </x-slot:actions>
        </x-admin.page-header>

        <div class="row-s">
            <x-admin.status-pill :status="$status" />
            <span class="muted">{{ $project->kind }} · {{ $project->city }} · {{ $project->category }} / {{ $project->sub_category }}</span>
        </div>

        <div class="cols">
            <div class="stack">
                <x-admin.panel title="Details">
                    <dl class="facts" style="margin: 0">
                        @foreach ([
                            'Project number' => '#'.$project->number,
                            'Beneficiary' => $project->beneficiary,
                            'Year of birth' => $project->birth_year,
                            'Family members' => $project->family_members,
                            'Fund date' => $project->funded ? \Illuminate\Support\Carbon::parse($project->funded)->format('j M Y') : '—',
                            'Donor' => $project->donor ?? '—',
                            'Partner' => $project->partner !== '0' ? $project->partner : '—',
                            'Second partner' => $project->partner2 !== '0' ? $project->partner2 : '—',
                            'Mobile' => $project->mobile !== '0' ? $project->mobile : '—',
                            'Video' => $project->video !== '0' ? $project->video : '—',
                            'Location' => $checks['location'] ? $project->latitude.', '.$project->longitude : 'Not set',
                            'Last published' => $project->published_at ? \Illuminate\Support\Carbon::parse($project->published_at)->format('j M Y') : '—',
                        ] as $label => $value)
                            <div class="fact"><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
                        @endforeach
                    </dl>
                </x-admin.panel>

                <x-admin.panel title="Suffering and need">
                    <div class="story">{!! SafeHtml::text($project->story) !!}</div>
                    @if (trim((string) $project->skills) !== '')
                        <div class="story" style="border-top: 1px solid var(--line)"><b>Skills and experience.</b> {{ $project->skills }}</div>
                    @endif
                </x-admin.panel>

                <livewire:admin.content-photos kind="project" :item-id="$project->id" :key="'photos-'.$project->id" />
            </div>

            <aside class="stack" aria-label="Status">
                <x-admin.panel title="Ready to go online?">
                    @php $blockers = ProjectReadiness::blockers($project); @endphp
                    <x-slot:toolbar><x-admin.status-pill :tone="$blockers === [] ? 'good' : 'warn'" :label="$blockers === [] ? 'Ready' : count($blockers).' to fix'" /></x-slot:toolbar>
                    <ul class="chk">
                        @foreach ($checks as $key => $met)
                            <li class="{{ $met ? 'ok' : 'no' }}">
                                <span class="m"><x-admin.icon :name="$met ? 'check' : 'warn'" /></span>
                                <span>{{ __('admin.checks.'.$key) }}@if (! in_array($key, $blocking, true)) <span class="faint">(optional)</span>@endif</span>
                            </li>
                        @endforeach
                    </ul>
                </x-admin.panel>

                <x-admin.panel title="History">
                    <x-admin.history :entries="$history" />
                </x-admin.panel>
            </aside>
        </div>
    @endif
</div>
