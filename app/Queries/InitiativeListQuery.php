<?php

namespace App\Queries;

use App\Models\Initiative;
use Illuminate\Pagination\LengthAwarePaginator;

/*
 * A programme's activities in one town, a page at a time, newest first, with
 * each activity's text and photos. Replaces the three near-identical
 * Initiative::with(...)->paginate(5, ['*'], 'ini_X') blocks (and the three
 * more for the search box) in PublicController::disc() and its siblings.
 *
 * Aleppo's list was ordered by creation date and the other towns' by id;
 * both are kept, though for new rows they come to the same thing.
 */
final class InitiativeListQuery
{
    public const PER_PAGE = 5;

    /**
     * @param  string  $pageName  the query-string key of this list's page number
     * @return LengthAwarePaginator<int, Initiative>
     */
    public static function forPlace(int $categoryId, int $subCategoryId, int $placeId, string $pageName, bool $byDate = false): LengthAwarePaginator
    {
        return Initiative::query()
            ->with([
                'content',
                'initiativeImages' => fn ($images) => $images->orderBy('image_order')->orderBy('id'),
            ])
            ->where('what_category_id', $categoryId)
            ->where('sub_what_category_id', $subCategoryId)
            ->where('place_id', $placeId)
            ->where('online', '1')
            ->when($byDate, fn ($query) => $query->orderByDesc('created_at'), fn ($query) => $query->orderByDesc('id'))
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE, ['*'], $pageName);
    }
}
