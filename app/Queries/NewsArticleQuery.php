<?php

namespace App\Queries;

use App\Models\News;

/*
 * One news story's page, /our-news/{id}-{city}-{date}: the story that is
 * online with its English text, its place and the photos of its gallery.
 *
 * Legacy PublicController::show_article(). The story is found by its id
 * alone: the city and the date in the address only ever fed the share
 * address, so an address with any other city or date shows the same story.
 */
final class NewsArticleQuery
{
    /** The sizetype of a gallery photo (the others are the card photos and the cover). */
    public const GALLERY = 3;

    public static function find(int $id): ?News
    {
        return News::query()
            ->where('online', 1)
            ->with([
                'content',
                'place:id,name',
                'newsImages' => fn ($images) => $images
                    ->where('sizetype', self::GALLERY)
                    ->orderBy('image_order')
                    ->orderBy('id'),
            ])
            ->find($id);
    }
}
