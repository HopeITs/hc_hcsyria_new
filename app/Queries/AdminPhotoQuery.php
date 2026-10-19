<?php

namespace App\Queries;

use App\Enums\ContentKind;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/*
 * The gallery photos of a project or an initiative, in their order: the first is
 * the cover. The table and its columns are ContentKind's.
 */
final class AdminPhotoQuery
{
    /** @return Collection<int, object{id: int, path: string}> */
    public static function gallery(ContentKind $kind, int $itemId): Collection
    {
        $photos = $kind->photoTable();

        return DB::table($photos['table'])
            ->where($photos['owner'], $itemId)
            ->where($kind->photoConstraints())
            ->orderBy('image_order')
            ->orderBy('id')
            ->get(['id', $photos['path'].' as path']);
    }
}
