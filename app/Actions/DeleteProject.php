<?php

namespace App\Actions;

use App\Exceptions\AdminActionRefused;
use App\Models\Project;
use App\Support\Admin\PhotoFiles;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/*
 * Deletes a micro-project and its image files.
 *
 * Replaces projectsController::delete(), which had three defects that
 * compounded into one silent failure:
 *
 *  1. It deleted the `projects` row first, then queried `images` for the paths
 *     to unlink. But images.project_id is ON DELETE CASCADE, so those rows were
 *     already gone and the query returned nothing — meaning the loop never ran
 *     and **no image file was ever removed from disk**.
 *  2. Even had it run, it called File::delete($path, $thumbnail). That method
 *     takes a single argument, so the second was silently ignored and the
 *     thumbnail would have been left behind regardless.
 *  3. There was no transaction, and `successes.project_id` is ON DELETE
 *     RESTRICT — so deleting a project that has a success story fails with a
 *     raw database error after other work has already happened.
 *
 * Paths are collected before the delete, the cascade is left to the database
 * rather than duplicated in PHP, and the RESTRICT case is reported clearly.
 */
class DeleteProject
{
    /**
     * @return array{deleted_files: int, missing_files: int}
     */
    public function handle(int $projectId): array
    {
        $project = Project::query()->find($projectId);

        if ($project === null) {
            throw new RuntimeException("Project $projectId does not exist.");
        }

        if (DB::table('successes')->where('project_id', $projectId)->exists()) {
            throw new AdminActionRefused('has_successes');
        }

        // Collected before the delete: the foreign keys cascade, so afterwards
        // there is nothing left to read the paths from.
        $paths = DB::table('images')
            ->where('project_id', $projectId)
            ->pluck('MPI_Path')
            ->filter()
            ->all();

        DB::transaction(function () use ($projectId) {
            /*
             * projects_english, projects_french and images all cascade from
             * this one delete. The legacy code deleted them explicitly as well,
             * which was redundant.
             */
            DB::table('projects')->where('id', $projectId)->delete();
        });

        // Both files of each photo, found under MEDIA_ROOT (a stored path is "/images/...", not a disk path).
        $deleted = 0;
        $missing = 0;

        foreach ($paths as $path) {
            $removed = PhotoFiles::delete($path);
            $deleted += $removed;
            $missing += 2 - $removed;
        }

        return ['deleted_files' => $deleted, 'missing_files' => $missing];
    }
}
