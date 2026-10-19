<?php

namespace App\Actions\Admin;

use App\Models\Initiative;
use App\Support\Admin\ProjectPhotoFiles;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/*
 * Deletes an initiative, its text, donors, testimonies and photos, and the files
 * of its logo and photos. Replaces InitiativesController::destroy(), which
 * deleted the rows one table at a time without a transaction and then asked
 * File::delete() for paths like "/images/..." that do not exist on disk, so no
 * file was ever removed.
 *
 * The database cascades the rows (initiatives_english, _french, _donors,
 * _images, _images_rehabilitation, testimonies); the paths are read first, and the
 * files go once the rows have.
 */
class DeleteInitiative
{
    /** @return int how many files were removed */
    public function handle(int $initiativeId): int
    {
        $initiative = Initiative::query()->find($initiativeId);

        if ($initiative === null) {
            throw new RuntimeException("Initiative $initiativeId does not exist.");
        }

        $paths = collect([$initiative->logo])
            ->merge(DB::table('initiatives_images')->where('initiative_id', $initiativeId)->pluck('path'))
            ->merge(DB::table('initiatives_images_rehabilitation')->where('initiative_id', $initiativeId)->pluck('path_1'))
            ->merge(DB::table('initiatives_images_rehabilitation')->where('initiative_id', $initiativeId)->pluck('path_2'))
            ->filter(fn ($path): bool => is_string($path) && $path !== '0' && $path !== '')
            ->unique();

        DB::transaction(fn () => DB::table('initiatives')->where('id', $initiativeId)->delete());

        return $paths->sum(fn (string $path): int => ProjectPhotoFiles::delete($path));
    }
}
