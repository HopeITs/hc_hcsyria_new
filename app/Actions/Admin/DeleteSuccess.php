<?php

namespace App\Actions\Admin;

use App\Models\Success;
use App\Support\Admin\PhotoFiles;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/*
 * Deletes a success story, its text, and the files of its pictures. Replaces
 * StudyController::success_destroy() (and the copies in DiscController and
 * SuccessController), which deleted the rows one table at a time and then asked
 * File::delete() for paths like "/images/..." that do not exist on disk, so no
 * file was ever removed.
 *
 * The database cascades the English and French rows; the paths are read first,
 * and the files go once the rows have.
 */
class DeleteSuccess
{
    /** @return int how many files were removed */
    public function handle(int $successId): int
    {
        $success = Success::query()->find($successId);

        if ($success === null) {
            throw new RuntimeException("Success $successId does not exist.");
        }

        $paths = collect([$success->image_path, $success->inner_image_path, $success->inner_big_image_path])
            ->filter(fn (string $path): bool => $path !== '0' && $path !== '')
            ->unique();

        DB::transaction(fn () => DB::table('successes')->where('id', $successId)->delete());

        return $paths->sum(fn (string $path): int => PhotoFiles::delete($path));
    }
}
