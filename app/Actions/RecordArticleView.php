<?php

namespace App\Actions;

use App\Models\News;
use Illuminate\Contracts\Session\Session;

/*
 * Counts a visit to a news story (legacy PublicController::show_article):
 * every visit adds to total_views, and view_count, the count of visitors,
 * grows once per session. The page shows total_views, with this visit in it.
 *
 * Like RecordPageView it does nothing unless COUNT_PAGE_VIEWS is on, as
 * staging reads and writes the live database.
 */
final class RecordArticleView
{
    public function __construct(private Session $session) {}

    public function __invoke(News $news): void
    {
        if (! config('hcsyria.count_page_views')) {
            return;
        }

        $news->increment('total_views');

        $key = "viewed_article_{$news->id}";

        if (! $this->session->has($key)) {
            $news->increment('view_count');
            $this->session->put($key, true);
        }
    }
}
