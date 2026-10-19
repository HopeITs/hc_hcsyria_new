<?php

namespace App\Actions\Admin;

use App\Enums\ProjectTransition;
use App\Exceptions\AdminActionRefused;
use App\Models\Project;
use App\Models\User;
use App\Support\Admin\ProjectReadiness;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/*
 * Publish, take offline, hide, unhide, or mark English checked: the five state
 * changes of projectsController, with the same columns changed and the same
 * log text, in one place instead of five methods.
 *
 *  - publish: online, no longer hidden, publish date now (Damascus time), funded
 *  - unpublish: back to the pending list, publish date now
 *  - hide: offline and out of the pending list
 *  - unhide: back to the pending list (it stays offline)
 *
 * Publishing is refused until the project is ready (ProjectReadiness). The
 * caller has already checked the policy; this does not look at who asks.
 */
class ChangeProjectState
{
    public function __construct(private readonly WriteActivityLog $log) {}

    /** @throws AdminActionRefused */
    public function handle(Project $project, ProjectTransition $transition, User $by): void
    {
        if ($transition === ProjectTransition::Publish) {
            $this->requireReady($project);
        }

        DB::transaction(function () use ($project, $transition, $by): void {
            match ($transition) {
                ProjectTransition::Publish => $project->forceFill([
                    'MPE_Published' => '1',
                    'hidden' => false,
                    'MPE_PublishDate' => $this->now(),
                    'MPE_Done' => '1',
                ]),
                ProjectTransition::Unpublish => $project->forceFill([
                    'MPE_Published' => '0',
                    'MPE_PublishDate' => $this->now(),
                ]),
                ProjectTransition::Hide => $project->forceFill(['MPE_Published' => '0', 'hidden' => true]),
                ProjectTransition::Unhide => $project->forceFill(['hidden' => false]),
                ProjectTransition::CheckEnglish => $project->forceFill(['english_check' => 1]),
            };

            $project->save();
            $this->log->project($by, $project->getKey(), $transition->logOperation());
        });
    }

    private function requireReady(Project $project): void
    {
        $facts = (object) [
            'english_check' => $project->english_check,
            'photos' => $project->images()->count(),
            'donor_id' => $project->MPE_Donor,
        ];

        $blockers = ProjectReadiness::blockers($facts);

        if ($blockers !== []) {
            throw new AdminActionRefused('not_ready', [
                'number' => $project->MicroProject_ID,
                'missing' => ProjectReadiness::describe($blockers),
            ]);
        }
    }

    private function now(): Carbon
    {
        return Carbon::now('Asia/Damascus');
    }
}
