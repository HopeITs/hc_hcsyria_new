<?php

namespace App\Queries;

use App\Models\RootsOfHope;
use Illuminate\Support\Collection;

/*
 * The editions of the Roots of Hope magazine that are online, oldest first.
 * The page shows the last one large and all of them below. Legacy
 * PublicController::roots() ran RootsOfHope::where('online', 1)->get() twice,
 * once to list them and once to take the last.
 */
final class RootsOfHopeQuery
{
    /** @return Collection<int, RootsOfHope> */
    public static function editions(): Collection
    {
        return RootsOfHope::query()
            ->where('online', 1)
            // The table's key order is what legacy's unordered get() returned.
            ->orderBy('id')
            ->get(['id', 'title', 'image_path', 'file_path', 'url']);
    }
}
