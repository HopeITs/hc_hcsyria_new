<?php

namespace App\Actions\Admin;

use App\Enums\ContentKind;
use App\Exceptions\AdminActionRefused;
use App\Models\Initiative;
use App\Models\User;
use App\Support\Admin\LegacyValue;
use App\Support\Admin\PhotoFiles;
use App\Support\Admin\PhotoFolders;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/*
 * The testimonies of an initiative: a person's picture, name and words. Replaces
 * TestimoniesController (store, update, destroy). Only initiatives of the
 * programmes legacy allowed them for have any (DISC, Homework Room, Study Zone).
 *
 * Kept from legacy: the two tables (testimonies and testimonies_english), the
 * picture's path in testimonies.image_path (a column of 80 characters), and the
 * log wording. Fixed: the legacy update looked the initiative's category and title
 * up by the testimony's own id; a removed or replaced picture is really deleted;
 * the text change counted line endings the box re-wrote; and the name's log row
 * had a typo. The caller has already checked the policy.
 */
class ManageTestimonies
{
    private const PATH_LENGTH = 80;

    private const PICTURE_BOX = [400, 400];

    public function __construct(private readonly WriteActivityLog $log) {}

    /**
     * Create (no $testimonyId) or update one. $picture is required for a new one.
     *
     * @param  array{name: string, text: string}  $data
     * @return int the testimony's id
     *
     * @throws AdminActionRefused
     */
    public function save(Initiative $initiative, ?int $testimonyId, array $data, ?UploadedFile $picture, User $by): int
    {
        if ($testimonyId === null && $picture === null) {
            throw new InvalidArgumentException('A new testimony needs a picture.');
        }

        $id = (int) $initiative->getKey();
        $path = $picture === null ? null : $this->storePicture($initiative, $picture);

        return DB::transaction(function () use ($testimonyId, $data, $path, $id, $by): int {
            if ($testimonyId === null) {
                $testimonyId = (int) DB::table('testimonies')->insertGetId([
                    'image_path' => $path,
                    'french_translation' => '0',
                    'initiative_id' => $id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('testimonies_english')->insert(['testimony_id' => $testimonyId, 'name' => $data['name'], 'text' => $data['text']]);
                $this->log->item($by, ContentKind::Initiative, $id, 'Testimony Created');

                return $testimonyId;
            }

            $current = DB::table('testimonies')->where('initiative_id', $id)->where('id', $testimonyId)->first();
            $english = DB::table('testimonies_english')->where('testimony_id', $testimonyId)->first();

            if ($current === null || $english === null) {
                throw new AdminActionRefused('testimony_gone');
            }

            if ($path !== null) {
                DB::table('testimonies')->where('id', $testimonyId)->update(['image_path' => $path, 'updated_at' => now()]);
                $this->forget($current->image_path);
                $this->log->item($by, ContentKind::Initiative, $id, 'Testimony image changed');
            }

            foreach (['name' => 'Testimony name edited', 'text' => 'Testimony text edited'] as $column => $operation) {
                if (LegacyValue::textChanged($english->{$column}, $data[$column])) {
                    DB::table('testimonies_english')->where('testimony_id', $testimonyId)->update([$column => $data[$column]]);
                    $this->log->item($by, ContentKind::Initiative, $id, $operation, $english->{$column}, $data[$column]);
                }
            }

            return $testimonyId;
        });
    }

    public function delete(Initiative $initiative, int $testimonyId, User $by): void
    {
        $testimony = DB::table('testimonies')->where('initiative_id', $initiative->getKey())->where('id', $testimonyId)->first();

        if ($testimony === null) {
            return;
        }

        // The English and French rows follow by the database's cascade.
        DB::table('testimonies')->where('id', $testimonyId)->delete();
        $this->forget($testimony->image_path);

        $this->log->item($by, ContentKind::Initiative, $initiative->getKey(), 'Testimony deleted');
    }

    /** @throws AdminActionRefused */
    private function storePicture(Initiative $initiative, UploadedFile $picture): string
    {
        $directory = PhotoFolders::initiative((string) DB::table('what_categories')->where('id', $initiative->what_category_id)->value('name'), (int) $initiative->getKey());
        $path = PhotoFiles::storeOne((string) $picture->getRealPath(), $directory, self::PICTURE_BOX, cover: true);

        if (strlen($path) > self::PATH_LENGTH) {
            PhotoFiles::delete($path);

            throw new AdminActionRefused('path_too_long');
        }

        return $path;
    }

    private function forget(?string $path): void
    {
        if (is_string($path) && $path !== '' && $path !== '0') {
            PhotoFiles::delete($path);
        }
    }
}
