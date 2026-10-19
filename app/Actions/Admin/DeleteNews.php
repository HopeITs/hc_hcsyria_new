<?php

namespace App\Actions\Admin;

use App\Models\News;
use App\Support\Admin\PhotoFiles;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/*
 * Deletes a news item, its text, tags and pictures, and the files of its pictures
 * and its attachment. Replaces NewsController::destroy(), which deleted the rows one
 * table at a time without a transaction and then removed a folder by a path built from
 * public_path() of a stored "/images/..." path, which is not where the files are.
 *
 * The database cascades the English and French text and the picture rows, but not
 * the tag links (news_tags), which are removed here. The paths are read first, and the
 * files go once the rows have.
 */
class DeleteNews
{
    /** @return int how many files were removed */
    public function handle(int $newsId): int
    {
        $news = News::query()->find($newsId);

        if ($news === null) {
            throw new RuntimeException("News $newsId does not exist.");
        }

        $paths = collect([$news->file])
            ->merge(DB::table('news_images')->where('news_id', $newsId)->pluck('path'))
            ->filter(fn (string $path): bool => $path !== '0' && $path !== '')
            ->unique();

        DB::transaction(function () use ($newsId): void {
            DB::table('news_tags')->where('news_id', $newsId)->delete();
            DB::table('news')->where('id', $newsId)->delete();
        });

        return $paths->sum(fn (string $path): int => PhotoFiles::delete($path));
    }
}
