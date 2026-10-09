<?php

namespace App\Queries;

use App\Models\Cover;
use Illuminate\Support\Collection;

/*
 * The cover photos of a page: rows of `backgrounds`, one set per page,
 * identified by a what_category_id the admin assigns (20 is /who-we-are).
 * Replaces the same Cover::where(...)->orderBy('image_order') line each
 * controller method repeated.
 */
final class CoverQuery
{
    /** @return Collection<int, Cover> */
    public static function images(int $categoryId): Collection
    {
        return Cover::query()
            ->where('what_category_id', $categoryId)
            ->orderBy('image_order')
            // image_order is not unique; id keeps tied rows in a fixed order.
            ->orderBy('id')
            ->get(['id', 'image_path']);
    }
}
