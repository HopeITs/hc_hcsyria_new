<?php

namespace App\Queries;

use App\Models\Initiative;
use App\Models\InitiativeDonor;
use Illuminate\Database\Eloquent\Builder;
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
        return self::ordered(self::base($categoryId, $subCategoryId, (array) $placeId, $search), $byDate)
            ->paginate(self::PER_PAGE, ['*'], $pageName);
    }

    /**
     * The first $limit activities of a town and how many it has in all: what a
     * list that grows with "Load More" shows, instead of a page.
     *
     * @param  list<int>  $placeIds
     * @return array{0: Collection<int, Initiative>, 1: int}
     */
    public static function upTo(int $categoryId, int $subCategoryId, array $placeIds, int $limit, bool $byDate = false, ?string $search = null, ?int $donorId = null, bool $onlyWithVideo = false): array
    {
        $query = self::base($categoryId, $subCategoryId, $placeIds, $search, $donorId, $onlyWithVideo);

        return [self::ordered(clone $query, $byDate)->limit($limit)->get(), $query->count()];
    }

    /**
     * The donors that have a say in a programme's activities, for its filter.
     *
     * @return list<int>
     */
    public static function donorIds(int $categoryId, int $subCategoryId): array
    {
        $ids = InitiativeDonor::query()
            ->whereIn('initiative_id', self::base($categoryId, $subCategoryId, null, null)->select('initiatives.id'))
            ->distinct()
            ->pluck('donor_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values($ids);
    }

    /** Whether any of a programme's activities has a video, which is when its filter offers one. */
    public static function hasVideos(int $categoryId, int $subCategoryId): bool
    {
        return self::base($categoryId, $subCategoryId, null, null, null, true)->exists();
    }

    /**
     * @param  list<int>|null  $placeIds  null: every town
     * @return Builder<Initiative>
     */
    private static function base(int $categoryId, int $subCategoryId, ?array $placeIds, ?string $search, ?int $donorId = null, bool $onlyWithVideo = false): Builder
    {
        return Initiative::query()
            ->with([
                'content',
                'initiativeImages' => fn ($images) => $images->orderBy('image_order')->orderBy('id'),
            ])
            ->where('what_category_id', $categoryId)
            ->where('sub_what_category_id', $subCategoryId)
            ->when($placeIds !== null, fn ($query) => $query->whereIn('place_id', $placeIds))
            ->where('online', '1')
            ->when($donorId !== null, fn ($query) => $query->whereHas('initiativeDonors', fn ($donors) => $donors->where('donor_id', $donorId)))
            // The column holds '0' (or nothing) where there is no video.
            ->when($onlyWithVideo, fn ($query) => $query->where('video', '!=', '0')->where('video', '!=', ''))
            ->when(filled($search), function ($query) use ($search) {
                $like = '%'.self::escapeLike(mb_strtolower((string) $search)).'%';

                // One group, so the three alternatives stay inside the relation's own constraint.
                $query->whereHas('content', fn ($content) => $content->where(fn ($any) => $any
                    ->where('title', 'like', $like)
                    ->orWhere('content', 'like', $like)
                    ->orWhere('description', 'like', $like)));
            });
    }

    /**
     * @param  Builder<Initiative>  $query
     * @return Builder<Initiative>
     */
    private static function ordered(Builder $query, bool $byDate): Builder
    {
        return $query
            ->when($byDate, fn ($query) => $query->orderByDesc('created_at'), fn ($query) => $query->orderByDesc('id'))
            ->orderByDesc('id');
    }

    /**
     * Every online activity of a programme that has no towns or tabs, in the
     * order its editors ranked them (School to School).
     *
     * @return Collection<int, Initiative>
     */
    public static function ranked(int $categoryId): Collection
    {
        return Initiative::query()
            ->with([
                'content',
                'initiativeImages' => fn ($images) => $images->orderBy('image_order')->orderBy('id'),
            ])
            ->where('what_category_id', $categoryId)
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
