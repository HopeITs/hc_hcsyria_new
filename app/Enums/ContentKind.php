<?php

namespace App\Enums;

use App\Actions\Admin\DeleteInitiative;
use App\Actions\DeleteProject;
use App\Models\Initiative;
use App\Models\Project;
use App\Support\Admin\ProjectReadiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/*
 * The kinds of content the staff dashboard manages the same way: what each is
 * called, where its pages are, how its state is stored (legacy used different
 * columns for each), what its activity-log rows say, and what it still needs
 * before it can go online. The shared pieces (the state changes, the actions
 * menu, the alerts) ask the kind instead of knowing about any one table.
 */
enum ContentKind: string
{
    case Project = 'project';
    case Initiative = 'initiative';

    public static function of(Model $item): self
    {
        return $item instanceof Initiative ? self::Initiative : self::Project;
    }

    /** @return class-string<Model> */
    public function modelClass(): string
    {
        return match ($this) {
            self::Project => Project::class,
            self::Initiative => Initiative::class,
        };
    }

    /** The word used in sentences: "project #3071 is online". */
    public function noun(): string
    {
        return $this->value;
    }

    public function nouns(): string
    {
        return Str::plural($this->value);
    }

    /** The route of one of its pages: index, show, create, edit. */
    public function route(string $page): string
    {
        return 'admin.'.$this->nouns().'.'.$page;
    }

    /** Legacy's category in the activity log (`logs.category`). */
    public function logCategory(): int
    {
        return match ($this) {
            self::Project => 0,
            self::Initiative => 1,
        };
    }

    /** The action that deletes one and its files; its handle() takes the id. @return class-string */
    public function deleteAction(): string
    {
        return match ($this) {
            self::Project => DeleteProject::class,
            self::Initiative => DeleteInitiative::class,
        };
    }

    /** Legacy's log text for a deletion. */
    public function deleteOperation(): string
    {
        return match ($this) {
            self::Project => 'Project Deleted',
            self::Initiative => 'Initiative deleted',
        };
    }

    /** What people call it by: a project's own number, an initiative's id. */
    public function number(Model $item): int
    {
        return (int) ($this === self::Project ? $item->MicroProject_ID : $item->getKey());
    }

    /** Change the state columns for a move (the caller saves). */
    public function apply(Model $item, ContentTransition $transition, Carbon $now): void
    {
        $item->forceFill(match ($this) {
            self::Project => match ($transition) {
                ContentTransition::Publish => ['MPE_Published' => '1', 'hidden' => false, 'MPE_PublishDate' => $now, 'MPE_Done' => '1'],
                ContentTransition::Unpublish => ['MPE_Published' => '0', 'MPE_PublishDate' => $now],
                ContentTransition::Hide => ['MPE_Published' => '0', 'hidden' => true],
                ContentTransition::Unhide => ['hidden' => false],
                ContentTransition::CheckEnglish => ['english_check' => 1],
            },
            self::Initiative => match ($transition) {
                ContentTransition::Publish => ['online' => 1, 'hidden' => false],
                ContentTransition::Unpublish => ['online' => 0],
                ContentTransition::Hide => ['online' => 0, 'hidden' => true],
                ContentTransition::Unhide => ['hidden' => false],
                ContentTransition::CheckEnglish => ['english_check' => 1],
            },
        });
    }

    /** The text legacy wrote to the activity log for the same move. */
    public function operation(ContentTransition $transition): string
    {
        return match ($this) {
            self::Project => match ($transition) {
                ContentTransition::Publish => 'Made Online',
                ContentTransition::Unpublish => 'Made Offline',
                ContentTransition::Hide => 'Made Hidden',
                ContentTransition::Unhide => 'Made Pending to publish',
                ContentTransition::CheckEnglish => 'English Checked',
            },
            self::Initiative => match ($transition) {
                ContentTransition::Publish => 'Initiative Status: Online',
                ContentTransition::Unpublish => 'Initiative Status: Offline',
                ContentTransition::Hide => 'Initiative Status: Hidden',
                ContentTransition::Unhide => 'Initiative Status: Pending to publish',
                ContentTransition::CheckEnglish => 'Initiative English Checked',
            },
        };
    }

    /**
     * What must be done before it may be published: keys of admin.needs.
     *
     * @return list<string>
     */
    public function blockers(Model $item): array
    {
        return match ($this) {
            self::Project => ProjectReadiness::blockers((object) [
                'english_check' => $item->english_check,
                'photos' => $item->images()->count(),
                'donor_id' => $item->MPE_Donor,
            ]),
            self::Initiative => (int) $item->english_check === 1 ? [] : ['english'],
        };
    }

    /**
     * The same, for a list row (columns english_check, photos, donor_id).
     *
     * @return list<string>
     */
    public function rowBlockers(object $row): array
    {
        return match ($this) {
            self::Project => ProjectReadiness::blockers($row),
            self::Initiative => (int) $row->english_check === 1 ? [] : ['english'],
        };
    }
}
