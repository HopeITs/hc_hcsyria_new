<?php

namespace App\Actions;

use App\Models\PageView;
use Illuminate\Contracts\Session\Session;

/*
 * Counts a visit to a public page, once per session, in the shared
 * `page_views` table that the admin dashboard reads (legacy
 * PublicController, 13 pages, all with this same shape).
 *
 * $page is the row's page_name and must be kept exactly as the legacy site
 * wrote it — including its typos ('initiatives_micro_porjects') — or the
 * dashboard would start a second row for the same page.
 *
 * It does nothing unless COUNT_PAGE_VIEWS is on. Staging reads and writes
 * the live database, so every test visit there would be counted as a real
 * one; only production turns it on.
 */
final class RecordPageView
{
    public function __construct(private Session $session) {}

    public function __invoke(string $page): void
    {
        if (! config('hcsyria.count_page_views')) {
            return;
        }

        $key = "has_viewed_{$page}";

        if ($this->session->has($key)) {
            return;
        }

        PageView::firstOrCreate(['page_name' => $page], ['views' => 0])->increment('views');

        $this->session->put($key, true);
    }
}
