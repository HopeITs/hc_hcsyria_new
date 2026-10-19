<?php

namespace App\Actions\Admin;

use App\Enums\ContentKind;
use App\Exceptions\AdminActionRefused;
use App\Models\News;
use App\Models\User;
use App\Support\Admin\PhotoFiles;
use App\Support\Admin\PhotoFolders;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/*
 * The three single pictures of a newsletter item, kept beside its gallery in
 * news_images (the `sizetype` column says which): the thumbnail (0) on its small card,
 * the Important picture (1) of the lead story, and the Pinned picture (2) of the wide
 * card. A new one replaces the old one of its kind. Replaces the picture fields of
 * NewsImagesController::store() and destroy(). The gallery (sizetype 3) is
 * ManageContentPhotos'.
 *
 * Kept from legacy: where each is kept and named, the folder of the item's number, the
 * log wording ("1 Images stored", "Image Deleted"), and that removing the Important or
 * Pinned picture of an item that has that status makes it Regular again. Changed: every
 * upload is cut to its card's proportions (a square thumbnail, 3:2 Important, 2:1 Pinned,
 * like the existing ones) so cards line up whatever was uploaded, and a replaced or removed
 * file is really deleted (legacy asked File::delete() for public_path() of the stored
 * path, which is not where the files are). The caller has already checked the policy.
 */
class ManageNewsPictures
{
    public const THUMBNAIL = 0;

    public const IMPORTANT = 1;

    public const PINNED = 2;

    /** sizetype => [what it is called, the file-name ending, the box it is cut to]. */
    public const TYPES = [
        self::THUMBNAIL => ['Thumbnail', '_thumb', [600, 600]],
        self::IMPORTANT => ['Important picture', '_important', [1200, 800]],
        self::PINNED => ['Pinned picture', '_pinned', [1200, 600]],
    ];

    public function __construct(
        private readonly WriteActivityLog $log,
        private readonly SetNewsPriority $priority,
    ) {}

    /** @throws AdminActionRefused */
    public function replace(News $news, int $sizetype, UploadedFile $file, User $by): void
    {
        [, $suffix, $box] = $this->type($sizetype);

        $path = PhotoFiles::storeOne((string) $file->getRealPath(), PhotoFolders::news((int) $news->getKey()), $box, cover: true, suffix: $suffix);

        if (strlen($path) > 150) {
            PhotoFiles::delete($path);

            throw new AdminActionRefused('path_too_long');
        }

        $old = DB::transaction(function () use ($news, $sizetype, $path): array {
            $old = DB::table('news_images')->where('news_id', $news->getKey())->where('sizetype', $sizetype)->pluck('path')->all();
            DB::table('news_images')->where('news_id', $news->getKey())->where('sizetype', $sizetype)->delete();
            DB::table('news_images')->insert(['news_id' => $news->getKey(), 'path' => $path, 'image_order' => 0, 'sizetype' => $sizetype, 'created_at' => now(), 'updated_at' => now()]);

            return $old;
        });

        array_map(PhotoFiles::delete(...), $old);

        $this->log->item($by, ContentKind::News, $news->getKey(), '1 Images stored');
    }

    public function remove(News $news, int $sizetype, User $by): void
    {
        $this->type($sizetype);

        $paths = DB::table('news_images')->where('news_id', $news->getKey())->where('sizetype', $sizetype)->pluck('path')->all();

        if ($paths === []) {
            return;
        }

        DB::table('news_images')->where('news_id', $news->getKey())->where('sizetype', $sizetype)->delete();
        array_map(PhotoFiles::delete(...), $paths);

        $this->log->item($by, ContentKind::News, $news->getKey(), 'Image Deleted');

        // An item whose Important or Pinned picture is gone is no longer that.
        if (($sizetype === self::IMPORTANT && (int) $news->priority_status === SetNewsPriority::IMPORTANT)
            || ($sizetype === self::PINNED && (int) $news->priority_status === SetNewsPriority::PINNED)) {
            $this->priority->handle($news, SetNewsPriority::REGULAR, $by);
        }
    }

    /** @return array{0: string, 1: string, 2: array{0: int<1, max>, 1: int<1, max>}} */
    private function type(int $sizetype): array
    {
        return self::TYPES[$sizetype] ?? throw new InvalidArgumentException("$sizetype is not a single picture of a news item.");
    }
}
