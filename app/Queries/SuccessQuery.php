<?php

namespace App\Queries;

use App\Models\Success;
use Illuminate\Database\Eloquent\Collection;

/*
 * Published success stories and testimonies of one programme, newest first,
 * with their text. Replaces the Success::with(...) lines in the programme
 * methods of PublicController.
 */
final class SuccessQuery
{
    /** @return Collection<int, Success> */
    public static function latest(int $categoryId, int $subCategoryId, int $limit): Collection
    {
        return Success::query()
            ->with('content')
            ->where('what_category_id', $categoryId)
            ->where('Sub_what_category_id', $subCategoryId)
            ->where('online', '1')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }
}
