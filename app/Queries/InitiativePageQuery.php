<?php

namespace App\Queries;

use App\Models\Initiative;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/*
 * One activity's page, /projects/{category}/{id}-{title}: the activity with its
 * text, photos, funders and before-and-after pairs, and three others of its
 * category to point to.
 *
 * Legacy PublicController::initiative(). The activity is the one the address
 * names only when the whole address is right: it is online, it is not one of
 * the Family to Family stories (sub-category 2, which have their own page),
 * and both the category and the title in the address are the slugs of its own.
 */
final class InitiativePageQuery
{
    /** The sub-category of the Family to Family stories, which this page does not show. */
    private const STORIES = 2;

    public static function find(string $type, int $id, string $slug): ?Initiative
    {
        $activity = self::visible(Initiative::query())
            ->with([
                'content',
                'whatCategory',
                'initiativeImages' => fn ($images) => $images->orderBy('image_order')->orderBy('id'),
                'initiativeImageRehabilitations',
                'initiativeDonors' => fn ($donors) => $donors->orderBy('id'),
                'initiativeDonors.donor',
            ])
            ->find($id);

        if ($activity === null || $activity->content === null || $activity->whatCategory === null) {
            return null;
        }

        $right = $type === Str::slug(strtolower($activity->whatCategory->name))
            && $slug === Str::slug(strtolower($activity->content->title));

        return $right ? $activity : null;
    }

    /**
     * Three other activities of the same category, chosen at random on every
     * visit as legacy did, each with the address it lives at and its first
     * photo (a rehabilitation project with none shows its first "before").
     *
     * @return list<array{href: string, image: ?string, title: string}>
     */
    public static function related(Initiative $activity): array
    {
        $others = self::visible(Initiative::query())
            ->with([
                'content',
                'whatCategory',
                'initiativeImages' => fn ($images) => $images->orderBy('image_order')->orderBy('id'),
                'initiativeImageRehabilitations',
            ])
            ->whereKeyNot($activity->id)
            ->where('what_category_id', $activity->what_category_id)
            ->inRandomOrder()
            ->limit(3)
            ->get();

        return array_values($others
            ->filter(fn (Initiative $other) => $other->content !== null && $other->whatCategory !== null)
            ->values()
            ->map(fn (Initiative $other) => [
                'href' => '/projects/'.Str::slug(strtolower($other->whatCategory->name)).'/'.$other->id.'-'.Str::slug(strtolower($other->content->title)),
                'image' => self::firstPhoto($other),
                'title' => ucfirst($other->content->title),
            ])
            ->all());
    }

    private static function firstPhoto(Initiative $activity): ?string
    {
        return $activity->initiativeImages->pluck('path')->first()
            ?? $activity->initiativeImageRehabilitations->pluck('path_1')->first();
    }

    /**
     * @param  Builder<Initiative>  $query
     * @return Builder<Initiative>
     */
    private static function visible(Builder $query): Builder
    {
        return $query
            ->where('online', '1')
            ->where(fn ($inner) => $inner->where('sub_what_category_id', '<>', self::STORIES)->orWhereNull('sub_what_category_id'));
    }
}
