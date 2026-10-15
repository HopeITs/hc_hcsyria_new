<?php

use App\Queries\MemberProjectQuery;
use App\Support\ProjectPresenter;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

/*
 * The details window of a project on /projects/micro-projects, a component
 * of its own so that opening it fetches and draws one project, not the whole
 * list again. The list's cards announce the project to open (the
 * 'project-open' event); the window is also what the address names, as
 * ?projectDetailsModal=3033-pick-up-truck, which legacy's links had as a
 * bare id (still understood, and rewritten).
 */
new class extends Component
{
    #[Url(as: 'projectDetailsModal', history: true, except: '')]
    public string $details = '';

    /** Whether the window was opened by a link to it, which keeps it open until the cross is used. */
    #[Locked]
    public bool $linked = false;

    /** Every request, not just the first: a session that has ended gets nothing more. */
    public function boot(): void
    {
        Gate::authorize('view-micro-projects');
    }

    public function mount(): void
    {
        $this->details = $this->canonical($this->details);
        $this->linked = $this->details !== '';
    }

    #[On('project-open')]
    public function open(int $id): void
    {
        $this->details = $this->canonical((string) $id);
        $this->linked = false;
    }

    public function close(): void
    {
        $this->details = '';
    }

    /** The project id a details link names: the digits it starts with, whatever follows. */
    private function projectId(string $link): ?int
    {
        return preg_match('/^(\d+)(-|$)/', $link, $found) ? (int) $found[1] : null;
    }

    /** A details link as the address should carry it: id and name of a listed project, else nothing. */
    private function canonical(string $link): string
    {
        $project = ($id = $this->projectId($link)) ? MemberProjectQuery::find($id) : null;

        return $project ? (new ProjectPresenter($project))->linkKey() : '';
    }

    public function with(): array
    {
        return ['project' => ($id = $this->projectId($this->details)) ? MemberProjectQuery::find($id) : null];
    }
};
?>

{{--
    Up at once, before the project has come: a card's click shows the frame
    (project-opening) and the window replaces it (project-opened).
--}}
<div x-data="{ opening: false, timer: null }"
     @project-opening.window="opening = true; clearTimeout(timer); timer = setTimeout(() => opening = false, 10000)"
     @project-opened.window="opening = false">
    <div x-show="opening" x-cloak class="project-modal project-modal--opening" aria-hidden="true">
        <div class="project-modal__backdrop"></div>
        <div class="project-modal__dialog">
            <div class="project-modal__content">
                <div class="card-skeleton__shape project-modal__shape"></div>
            </div>
        </div>
    </div>

    @if ($project)
        <x-site.project-modal :$project :locked="$linked" wire:key="modal-{{ $project->id }}"/>
    @endif
</div>
