<?php

namespace App\Enums;

/*
 * The five moves a staff member can make on a project's state, as legacy had
 * them (publish, unpublish "Offline", hide, unhide, English checked). One place
 * says, for each: which policy ability allows it, when it applies, its log text,
 * and how to take it back.
 *
 * $project is anything with the columns the queries select: published, hidden,
 * english_check (the Project model and the list's rows both have them).
 */
enum ProjectTransition: string
{
    case Publish = 'publish';
    case Unpublish = 'unpublish';
    case Hide = 'hide';
    case Unhide = 'unhide';
    case CheckEnglish = 'check-english';

    /** The ProjectPolicy method that allows it. */
    public function ability(): string
    {
        return match ($this) {
            self::Publish => 'publish',
            self::Unpublish => 'unpublish',
            self::Hide, self::Unhide => 'hide',
            self::CheckEnglish => 'checkEnglish',
        };
    }

    /** Is there anything to do to this project? */
    public function appliesTo(object $project): bool
    {
        $published = (int) ($project->published ?? $project->MPE_Published);
        $hidden = (bool) $project->hidden;

        return match ($this) {
            self::Publish => $published === 0 && ! $hidden,
            self::Unpublish => $published === 1,
            self::Hide => ! $hidden,
            self::Unhide => $hidden,
            self::CheckEnglish => (int) $project->english_check === 0,
        };
    }

    /** The move that takes this one back, if there is one. */
    public function inverse(): ?self
    {
        return match ($this) {
            self::Publish => self::Unpublish,
            self::Unpublish => self::Publish,
            self::Hide => self::Unhide,
            self::Unhide => self::Hide,
            self::CheckEnglish => null,
        };
    }

    /** The text legacy wrote to the activity log for the same move. */
    public function logOperation(): string
    {
        return match ($this) {
            self::Publish => 'Made Online',
            self::Unpublish => 'Made Offline',
            self::Hide => 'Made Hidden',
            self::Unhide => 'Made Pending to publish',
            self::CheckEnglish => 'English Checked',
        };
    }

    public function label(): string
    {
        return __('admin.transitions.'.$this->value);
    }

    public function icon(): string
    {
        return match ($this) {
            self::Publish => 'check',
            self::Unpublish => 'out',
            self::Hide => 'eyeoff',
            self::Unhide => 'eye',
            self::CheckEnglish => 'globe',
        };
    }
}
