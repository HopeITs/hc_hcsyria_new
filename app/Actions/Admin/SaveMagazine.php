<?php

namespace App\Actions\Admin;

use App\Enums\ContentKind;
use App\Models\RootsOfHope;
use App\Models\User;
use App\Support\Admin\DocumentFiles;
use App\Support\Admin\PhotoFiles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

/*
 * Creates or updates a Roots of Hope edition, with its cover and its PDF. Replaces
 * RootsController::store() and update().
 *
 * Kept from legacy: a new edition is offline; an empty cover or PDF is the text "0"; the
 * log says "Magazine Created". Changed: the cover is re-encoded and scaled down to a page
 * (legacy kept the upload as it came, some are 4961 x 7016 pixels); both files are stored
 * under names that cannot replace another; a replaced or removed file is really deleted;
 * an edit is logged field by field (legacy logged nothing); saving a change to an online
 * edition does not take it offline (legacy's update left the state alone as well).
 *
 * The columns that keep the paths hold 100 characters, so the PDF's name is cut shorter
 * than for the newsletter's attachments.
 */
class SaveMagazine
{
    private const COVER_DIRECTORY = '/images/rootsofhope';

    private const PDF_DIRECTORY = '/files/rootsofhope';

    private const COVER_BOX = [900, 1200];

    private const PDF_STEM = 40;

    public function __construct(private readonly WriteActivityLog $log) {}

    /**
     * @param  array{title: string, url: string}  $data
     * @param  bool  $removeFile  take the current PDF away (edit only)
     */
    public function handle(array $data, User $by, ?UploadedFile $cover, ?UploadedFile $pdf, bool $removeFile = false, ?RootsOfHope $edition = null): RootsOfHope
    {
        $stored = [];

        try {
            $coverPath = $cover === null ? null : $stored[] = PhotoFiles::storeOne((string) $cover->getRealPath(), self::COVER_DIRECTORY, self::COVER_BOX, cover: false);
            $pdfPath = $pdf === null ? null : $stored[] = DocumentFiles::store($pdf, self::PDF_DIRECTORY, self::PDF_STEM);

            return DB::transaction(fn (): RootsOfHope => $edition === null
                ? $this->create($data, $by, $coverPath, $pdfPath)
                : $this->update($edition, $data, $by, $coverPath, $pdfPath, $removeFile));
        } catch (Throwable $failure) {
            array_map(PhotoFiles::delete(...), $stored);

            throw $failure;
        }
    }

    /** @param array{title: string, url: string} $data */
    private function create(array $data, User $by, ?string $cover, ?string $pdf): RootsOfHope
    {
        $edition = new RootsOfHope([
            'title' => $data['title'],
            'url' => $data['url'],
            'image_path' => $cover ?? '0',
            'file_path' => $pdf ?? '0',
            'online' => 0,
        ]);
        $edition->save();

        $this->log->item($by, ContentKind::Roots, $edition->getKey(), 'Magazine Created');

        return $edition;
    }

    /** @param array{title: string, url: string} $data */
    private function update(RootsOfHope $edition, array $data, User $by, ?string $cover, ?string $pdf, bool $removeFile): RootsOfHope
    {
        $id = $edition->getKey();
        $old = ['image_path' => $edition->image_path, 'file_path' => $edition->file_path];

        $edition->title = $data['title'];
        $edition->url = $data['url'];

        if ($cover !== null) {
            $edition->image_path = $cover;
        }

        if ($pdf !== null || ($removeFile && $old['file_path'] !== '0')) {
            $edition->file_path = $pdf ?? '0';
        }

        foreach (['title' => 'Title', 'url' => 'Link', 'image_path' => 'Cover', 'file_path' => 'File'] as $column => $field) {
            if ($edition->isDirty($column)) {
                $this->log->item($by, ContentKind::Roots, $id, 'Magazine Edited: '.$field, $edition->getOriginal($column), $edition->{$column});
            }
        }

        $edition->save();

        foreach ($old as $column => $path) {
            if ($edition->wasChanged($column) && $path !== '0' && $path !== '') {
                PhotoFiles::delete($path);
            }
        }

        return $edition;
    }
}
