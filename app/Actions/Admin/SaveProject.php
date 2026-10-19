<?php

namespace App\Actions\Admin;

use App\Exceptions\AdminActionRefused;
use App\Models\Place;
use App\Models\Project;
use App\Models\ProjectContent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/*
 * Creates or updates a micro-project and its English text. Replaces
 * projectsController::store() and update().
 *
 * Kept from legacy: a new project starts funded and waiting to be published;
 * a field left empty is stored as the text "0" (the public pages test for it);
 * the activity log gets one row per changed field, with the old and new value;
 * and moving a project to cancelled takes it off the public list (published 2),
 * back to funded restores it (published 1).
 *
 * Fixed from legacy: the family-members change was logged as "DOB Edited"; the
 * sub-category change was never logged (it read a column that does not exist);
 * the check for an existing number was built by string concatenation and the
 * donor check compared against the wrong id. The story is cut to the tags the
 * editor makes (SafeHtml) before it is stored.
 *
 * $data is what ProjectForm validated.
 */
class SaveProject
{
    /** data key => [projects column, wording of the log row]; the text "0" stands for "none". */
    private const COLUMNS = [
        'beneficiary' => ['MPE_BeneficiaryName', 'Beneficiary Name Edited', false],
        'partner' => ['MPE_PartnerName', 'Partner Name Edited', true],
        'partner2' => ['MPE_PartnerName2', 'Second Partner Name Edited', true],
        'funded' => ['MPE_FundedDate', 'Funded Date Edited', false],
        'birth_year' => ['DOB', 'DOB Edited', false],
        'family_members' => ['FamilyMembers', 'Family Members Edited', false],
        'latitude' => ['latitude', 'Location Latitude Edited', true],
        'longitude' => ['longtitude', 'Location Longtitude Edited', true],
        'donor_id' => ['MPE_Donor', 'Donor Edited', false],
        'kind_id' => ['projecttype_id', 'Project Type Edited', false],
        'sub_category_id' => ['SubCategory_ID', 'Project Sub Type Edited', false],
        'mobile' => ['Mobile', 'Mobile Edited', true],
        'video' => ['MPE_Video', 'Video Edited', true],
    ];

    /** content key => [projects_english column, log wording] */
    private const CONTENT = [
        'name' => ['MPE_Name', 'Project Name Edited'],
        'story' => ['MPE_Suffering', 'Story Edited'],
        'skills' => ['MPE_ExpSkill', 'Exp & Skill Edited'],
    ];

    public function __construct(private readonly WriteActivityLog $log) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws AdminActionRefused
     */
    public function handle(array $data, User $by, ?Project $project = null): Project
    {
        $this->requireFreeNumber((int) $data['number'], (int) $data['city_id'], $project);

        return DB::transaction(fn (): Project => $project === null
            ? $this->create($data, $by)
            : $this->update($project, $data, $by));
    }

    /** @param array<string, mixed> $data */
    private function create(array $data, User $by): Project
    {
        $project = new Project;
        $project->forceFill([
            'MicroProject_ID' => $data['number'],
            'MPE_City' => $data['city_id'],
            'english_check' => 0,
            'MPE_French' => '0',
            'MPE_Done' => '1',
            'MPE_Published' => '0',
            'hidden' => false,
            'MPE_PublishDate' => $data['funded'] ?? null,
            'MPE_AllMoneyNeeded' => 0,
            'MPE_MoneyDonated' => 0,
            'NeedConversion' => '0',
        ]);
        $this->assign($project, $data, log: false);
        $project->save();

        ProjectContent::query()->create([
            'project_id' => $project->getKey(),
            'MicroProject_ID' => $data['number'],
            'MPE_SuccessStory' => '0',
            'MPE_Name' => $data['name'],
            'MPE_Suffering' => $data['story'],
            'MPE_ExpSkill' => $data['skills'] ?? '',
        ]);

        $this->log->project($by, $project->getKey(), 'Project has been Created');

        return $project;
    }

    /** @param array<string, mixed> $data */
    private function update(Project $project, array $data, User $by): Project
    {
        $project->MicroProject_ID = $data['number'];
        $project->MPE_City = $data['city_id'];

        $this->assign($project, $data, log: true, by: $by);
        $this->applyStatus($project, $data['status'] ?? null, $by);
        $project->save();

        $content = ProjectContent::query()->where('project_id', $project->getKey())->first()
            ?? new ProjectContent(['project_id' => $project->getKey(), 'MicroProject_ID' => $data['number'], 'MPE_SuccessStory' => '0']);

        foreach (self::CONTENT as $key => [$column, $operation]) {
            $new = (string) ($data[$key] ?? '');

            if ($this->textChanged($content->{$column}, $new)) {
                $this->log->project($by, $project->getKey(), $operation, $content->{$column}, $new);
                $content->{$column} = $new;
            }
        }

        $content->MicroProject_ID = $data['number'];
        $content->save();

        return $project;
    }

    /** @param array<string, mixed> $data */
    private function assign(Project $project, array $data, bool $log, ?User $by = null): void
    {
        foreach (self::COLUMNS as $key => [$column, $operation, $zeroWhenEmpty]) {
            $value = $data[$key] ?? null;
            $value = $zeroWhenEmpty && ($value === null || $value === '') ? '0' : $value;

            $project->{$column} = $value;

            if ($log && $by !== null && $project->isDirty($column)) {
                $this->log->project($by, $project->getKey(), $operation, $project->getOriginal($column), $project->{$column});
            }
        }
    }

    private function applyStatus(Project $project, ?string $status, User $by): void
    {
        $done = match ($status) {
            'funded' => '1',
            'cancelled' => '2',
            default => null,
        };

        if ($done === null || $done === (string) $project->MPE_Done) {
            return;
        }

        $cancelling = $done === '2';
        $project->MPE_Done = $done;
        $project->MPE_Published = $cancelling ? '2' : '1';

        $this->log->project($by, $project->getKey(), 'Status Edited', $cancelling ? 'Funded' : 'Cancelled', $cancelling ? 'Cancelled' : 'Funded');
    }

    /** @throws AdminActionRefused */
    private function requireFreeNumber(int $number, int $cityId, ?Project $project): void
    {
        $taken = Project::query()
            ->where('MicroProject_ID', $number)
            ->where('MPE_City', $cityId)
            ->when($project !== null, fn ($query) => $query->whereKeyNot($project->getKey()))
            ->exists();

        if ($taken) {
            throw new AdminActionRefused('duplicate', [
                'number' => $number,
                'city' => (string) Place::query()->whereKey($cityId)->value('name'),
            ]);
        }
    }

    /** Line endings and surrounding space do not count (the rich-text box re-writes them). */
    private function textChanged(mixed $old, string $new): bool
    {
        $normalise = fn (mixed $text): string => trim(str_replace(["\r\n", "\r"], "\n", (string) $text));

        return $normalise($old) !== $normalise($new);
    }
}
