<?php

namespace App\Actions\Admin;

use App\Models\RootsOfHope;
use App\Support\Admin\PhotoFiles;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/*
 * Deletes a magazine edition and the files of its cover and its PDF. Replaces
 * RootsController::destroy(), which removed the row and left both files on the server
 * (and asked File::delete() for paths that are not on disk). The paths are read first,
 * and the files go once the row has.
 */
class DeleteMagazine
{
    /** @return int how many files were removed */
    public function handle(int $id): int
    {
        $edition = RootsOfHope::query()->find($id);

        if ($edition === null) {
            throw new RuntimeException("Magazine $id does not exist.");
        }

        $paths = collect([$edition->image_path, $edition->file_path])
            ->filter(fn (string $path): bool => $path !== '0' && $path !== '')
            ->unique();

        DB::transaction(fn () => DB::table('rootsofhope')->where('id', $id)->delete());

        return $paths->sum(fn (string $path): int => PhotoFiles::delete($path));
    }
}
