<?php

namespace App\Livewire\Concerns;

use App\Actions\Admin\ChangeProjectState;
use App\Actions\Admin\WriteActivityLog;
use App\Actions\DeleteProject;
use App\Enums\ProjectTransition;
use App\Exceptions\AdminActionRefused;
use App\Models\Project;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/*
 * The things a staff member does to a project from a list, the dashboard queue
 * or the project's own page: move it between states, or delete it. Written once
 * here so every page offers them by the same rules: each call finds the project,
 * asks ProjectPolicy (the page's buttons are only a convenience, the call is
 * what is enforced), does the work through the actions, and answers with an alert.
 */
trait ManagesProjects
{
    use SendsAlerts;

    public function moveProject(int $id, string $name): void
    {
        $project = Project::query()->find($id);

        if ($project === null) {
            $this->alert('gone', type: 'error');

            return;
        }

        $transition = ProjectTransition::tryFrom($name);

        if ($transition === null || ! $this->mayDo($transition, $project)) {
            $this->alert('forbidden', type: 'error');

            return;
        }

        if (! $transition->appliesTo($project)) {
            return;
        }

        try {
            app(ChangeProjectState::class)->handle($project, $transition, Auth::user());
        } catch (AdminActionRefused $refused) {
            $this->alert($refused->alertKey(), $refused->params, 'error');

            return;
        }

        $inverse = $transition->inverse();
        $this->alert(
            'state.'.$transition->value,
            ['number' => $project->MicroProject_ID],
            undo: $inverse === null ? null : ['moveProject', $id, $inverse->value],
        );
    }

    /**
     * The same move on several projects (the list's selection). Those that are not
     * allowed, not ready or already there are skipped, and counted in the alert.
     *
     * @param  list<int|string>  $ids
     */
    public function transitionMany(array $ids, string $name): void
    {
        $transition = ProjectTransition::tryFrom($name);
        $projects = Project::query()->whereKey(array_map('intval', $ids))->get();
        $done = 0;

        foreach ($projects as $project) {
            if ($transition === null || ! $this->mayDo($transition, $project) || ! $transition->appliesTo($project)) {
                continue;
            }

            try {
                app(ChangeProjectState::class)->handle($project, $transition, Auth::user());
                $done++;
            } catch (AdminActionRefused) {
                // Counted as skipped.
            }
        }

        $count = count($ids);
        $skipped = $count - $done;

        match (true) {
            $done === 0 => $this->alert('state_many.none', ['count' => $count], 'error'),
            $skipped === 0 => $this->alert('state_many.done', ['count' => $done]),
            default => $this->alert('state_many.partly', ['done' => $done, 'count' => $count, 'skipped' => $skipped], 'warning'),
        };
    }

    public function deleteProject(int $id): void
    {
        $project = Project::query()->find($id);

        if ($project === null) {
            $this->alert('gone', type: 'error');

            return;
        }

        if (Auth::user()->cannot('delete', $project)) {
            $this->alert('forbidden', type: 'error');

            return;
        }

        try {
            app(DeleteProject::class)->handle($id);
        } catch (RuntimeException) {
            $this->alert('refusals.has_successes', ['number' => $project->MicroProject_ID], 'error');

            return;
        }

        app(WriteActivityLog::class)->project(Auth::user(), $id, 'Project Deleted');

        $this->afterDeleted($project);
    }

    /** What a page does once a project is gone: the list just shows an alert, the project's own page leaves. */
    protected function afterDeleted(Project $project): void
    {
        $this->alert('deleted', ['number' => $project->MicroProject_ID]);
    }

    private function mayDo(ProjectTransition $transition, Project $project): bool
    {
        try {
            return Auth::user()->can($transition->ability(), $project);
        } catch (AuthorizationException) {
            return false;
        }
    }
}
