<?php

namespace App\Queries;

use App\Models\Initiative;
use Illuminate\Database\Eloquent\Collection;
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
     * @param  int|list<int>  $placeId  one town, or several shown as one (Homs and its countryside)
     * @param  string  $pageName  the query-string key of this list's page number
     * @param  string|null  $search  only activities whose title, text or summary contain this
     * @return LengthAwarePaginator<int, Initiative>
     */
    public static function forPlace(int $categoryId, int $subCategoryId, int|array $placeId, string $pageName, bool $byDate = false, ?string $search = null): LengthAwarePaginator
    {
        return Initiative::query()
            ->with([
                'content',
                'initiativeImages' => fn ($images) => $images->orderBy('image_order')->orderBy('id'),
            ])
            ->where('what_category_id', $categoryId)
            ->where('sub_what_category_id', $subCategoryId)
            ->whereIn('place_id', (array) $placeId)
            ->where('online', '1')
            ->when(filled($search), function ($query) use ($search) {
                $like = '%'.self::escapeLike(mb_strtolower($search)).'%';

                // One group, so the three alternatives stay inside the relation's own constraint.
                $query->whereHas('content', fn ($content) => $content->where(fn ($any) => $any
                    ->where('title', 'like', $like)
                    ->orWhere('content', 'like', $like)
                    ->orWhere('description', 'like', $like)));
            })
            ->when($byDate, fn ($query) => $query->orderByDesc('created_at'), fn ($query) => $query->orderByDesc('id'))
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE, ['*'], $pageName);
    }

    /**
     * Every online activity of a programme (or sub-category) that has no towns or tabs, in the
     * order its editors ranked them (School to School).
     *
     * @return Collection<int, Initiative>
     */
    public static function ranked(?int $categoryId, ?int $subCategoryId = null): Collection
    {
        return Initiative::query()
            ->with([
                'content',
                'initiativeImages' => fn ($images) => $images->orderBy('image_order')->orderBy('id'),
            ])
            ->when($categoryId !== null, fn ($query) => $query->where('what_category_id', $categoryId))
            ->when($subCategoryId !== null, fn ($query) => $query->where('sub_what_category_id', $subCategoryId))
            ->where('online', '1')
            ->orderBy('rank')
            ->get();
    }

    /** A search term as a LIKE pattern body: the wildcards a visitor types mean themselves. */
    private static function escapeLike(string $term): string
    {
        return addcslashes($term, '\%_');
    }
}
