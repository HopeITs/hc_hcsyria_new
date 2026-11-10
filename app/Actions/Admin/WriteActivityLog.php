<?php

namespace App\Actions\Admin;

use App\Enums\ContentKind;
use App\Models\Log;
use App\Models\User;

/*
 * One row in the activity log (`logs`), as legacy Controller::saveToLog() and
 * saveToLogWithData() wrote it: the notifications and the Log page read these.
 * Every admin write goes through here, so the wording and the shape are kept in
 * one place. The category says what the row is about (ContentKind::logCategory).
 */
class WriteActivityLog
{
    /** Legacy's category for micro-projects. */
    public const PROJECT = 0;

    /** Legacy's categories for the newsletter's own lists. */
    public const EDITIONS = 4;

    public const SUBSCRIBERS = 6;

    public const TAGS = 35;

    /** New: legacy did not log changes to accounts. */
    public const USERS = 12;

    /** New: changes to the pictures and words of the public pages (Site content). */
    public const SITE = 40;

    public function project(User $by, int $projectId, string $operation, mixed $previous = null, mixed $current = null): void
    {
        $this->item($by, ContentKind::Project, $projectId, $operation, $previous, $current);
    }

    public function item(User $by, ContentKind $kind, int $id, string $operation, mixed $previous = null, mixed $current = null): void
    {
        $this->record($by, $kind->logCategory(), $id, $operation, $previous, $current);
    }

    /** A row for something that is not one item (a tag, an edition, a subscriber): the category is legacy's. */
    public function record(User $by, int $category, int $id, string $operation, mixed $previous = null, mixed $current = null): void
    {
        Log::query()->create([
            'category' => $category,
            'project_id' => $id,
            'operation' => $operation,
            'user_id' => $by->getKey(),
            'seen' => 0,
            'previous_data' => $previous === null ? null : (string) $previous,
            'current_data' => $current === null ? null : (string) $current,
        ]);
    }
}
