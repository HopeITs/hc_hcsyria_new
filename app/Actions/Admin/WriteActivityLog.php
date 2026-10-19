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

    public function project(User $by, int $projectId, string $operation, mixed $previous = null, mixed $current = null): void
    {
        $this->item($by, ContentKind::Project, $projectId, $operation, $previous, $current);
    }

    public function item(User $by, ContentKind $kind, int $id, string $operation, mixed $previous = null, mixed $current = null): void
    {
        Log::query()->create([
            'category' => $kind->logCategory(),
            'project_id' => $id,
            'operation' => $operation,
            'user_id' => $by->getKey(),
            'seen' => 0,
            'previous_data' => $previous === null ? null : (string) $previous,
            'current_data' => $current === null ? null : (string) $current,
        ]);
    }
}
