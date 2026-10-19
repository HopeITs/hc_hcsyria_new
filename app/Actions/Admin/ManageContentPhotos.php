<?php

namespace App\Actions\Admin;

use App\Enums\ContentKind;
use App\Enums\ContentTransition;
use App\Models\User;
use App\Support\Admin\PhotoFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

/*
 * Adds, removes and orders the gallery photos of a project or an initiative.
 * Replaces projectsController::store_image(), delete_images() and order_images()
 * and their initiative twins.
 *
 * Kept from legacy: the log says "N Images stored" and "Image Deleted"; and a
 * project that is online goes back to the pending list when its photos change
 * (ContentKind::photosChangeTakesOffline; initiatives were left online). Changed:
 * one upload becomes both file sizes (PhotoFiles), and the order is one run of
 * 1, 2, 3... (legacy left every new photo at 0, so the cover was whichever the
 * database returned first). The caller has already checked managePhotos.
 */
class ManageContentPhotos
{
    public function __construct(
        private readonly WriteActivityLog $log,
        private readonly ChangeContentState $state,
    ) {}

    /**
     * @param  list<UploadedFile>  $files
     * @return array{added: int, offline: bool}
     */
    public function add(Model $item, array $files, User $by): array
    {
        $kind = ContentKind::of($item);
        $photos = $kind->photoTable();
        $sizes = $kind->photoSizes();
        $directory = $kind->photoDirectory($item);
        $stored = [];

        try {
            $order = (int) DB::table($photos['table'])->where($photos['owner'], $item->getKey())->max('image_order');

            DB::transaction(function () use ($item, $kind, $photos, $sizes, $directory, $files, &$stored, &$order): void {
                foreach ($files as $file) {
                    $path = PhotoFiles::storePair((string) $file->getRealPath(), $directory, $sizes['small'], $sizes['big']);
                    $stored[] = $path;

                    DB::table($photos['table'])->insert([
                        $photos['path'] => $path,
                        $photos['owner'] => $item->getKey(),
                        'image_order' => ++$order,
                        ...$kind->photoExtras($item),
                    ]);
                }
            });
        } catch (Throwable $failure) {
            array_map(PhotoFiles::delete(...), $stored);

            throw $failure;
        }

        $this->log->item($by, $kind, $item->getKey(), count($files).' Images stored');

        return ['added' => count($files), 'offline' => $this->takeOffline($item, $by)];
    }

    /** Returns whether the item went back to the pending list. */
    public function remove(Model $item, int $imageId, User $by): bool
    {
        $kind = ContentKind::of($item);
        $photos = $kind->photoTable();
        $row = DB::table($photos['table'])->where($photos['owner'], $item->getKey())->where('id', $imageId)->first();

        if ($row === null) {
            return false;
        }

        $path = (string) $row->{$photos['path']};
        DB::transaction(function () use ($item, $kind, $photos, $imageId): void {
            DB::table($photos['table'])->where('id', $imageId)->delete();
            $this->number($kind, $this->orderedIds($item));
        });
        PhotoFiles::delete($path);

        $this->log->item($by, $kind, $item->getKey(), 'Image Deleted');

        return $this->takeOffline($item, $by);
    }

    /** Move a photo one place earlier (-1) or later (+1). */
    public function move(Model $item, int $imageId, int $offset): void
    {
        $ids = $this->orderedIds($item);
        $from = array_search($imageId, $ids, true);
        $to = $from === false ? false : $from + $offset;

        if ($from === false || $to < 0 || $to >= count($ids)) {
            return;
        }

        [$ids[$from], $ids[$to]] = [$ids[$to], $ids[$from]];
        $this->number(ContentKind::of($item), $ids);
    }

    public function makeCover(Model $item, int $imageId): void
    {
        $ids = $this->orderedIds($item);

        if (in_array($imageId, $ids, true)) {
            $this->number(ContentKind::of($item), [$imageId, ...array_values(array_diff($ids, [$imageId]))]);
        }
    }

    private function takeOffline(Model $item, User $by): bool
    {
        if (! ContentKind::of($item)->photosChangeTakesOffline()) {
            return false;
        }

        $item->refresh();

        if (! ContentTransition::Unpublish->appliesTo($item)) {
            return false;
        }

        $this->state->handle($item, ContentTransition::Unpublish, $by);

        return true;
    }

    /** @return array<int, int> */
    private function orderedIds(Model $item): array
    {
        $photos = ContentKind::of($item)->photoTable();

        return DB::table($photos['table'])->where($photos['owner'], $item->getKey())->orderBy('image_order')->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();
    }

    /** @param array<int, int> $ids in the order they should show */
    private function number(ContentKind $kind, array $ids): void
    {
        foreach ($ids as $position => $id) {
            DB::table($kind->photoTable()['table'])->where('id', $id)->update(['image_order' => $position + 1]);
        }
    }
}
