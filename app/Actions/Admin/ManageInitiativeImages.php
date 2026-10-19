<?php

namespace App\Actions\Admin;

use App\Enums\ContentKind;
use App\Exceptions\AdminActionRefused;
use App\Models\Initiative;
use App\Models\User;
use App\Support\Admin\PhotoFiles;
use App\Support\Admin\PhotoFolders;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

/*
 * The initiative pictures that are not gallery photos: its logo, and the
 * before-and-after pairs of a rehabilitation. Replaces the logo field of
 * InitiativesController::store() and update() and
 * initiativeImagesRehabilitationController.
 *
 * Kept from legacy: where each path is kept (initiatives.logo, and path_1 and
 * path_2 of initiatives_images_rehabilitation) and the log wording ("Logo
 * changed", "Before & After Images stored"). Changed: a replaced or removed file
 * is really deleted (legacy asked File::delete() for a path that does not exist
 * on disk); the pair's two pictures are cut to one size, so the before-and-after
 * slider lines up; the removal log counted every pair, not the ones removed; and
 * the logo keeps its see-through parts. The caller has already checked the policy.
 */
class ManageInitiativeImages
{
    /** What the columns hold: logo is varchar(100), a pair's paths varchar(150). */
    private const LOGO_LENGTH = 100;

    private const PAIR_LENGTH = 150;

    private const LOGO_BOX = [800, 800];

    private const PAIR_BOX = [1200, 800];

    public function __construct(private readonly WriteActivityLog $log) {}

    /** @throws AdminActionRefused */
    public function replaceLogo(Initiative $initiative, UploadedFile $file, User $by): void
    {
        $path = PhotoFiles::storeOne((string) $file->getRealPath(), $this->directory($initiative), self::LOGO_BOX, cover: false, keepAlpha: true);
        $this->requireFits($path, self::LOGO_LENGTH, [$path]);

        $old = $initiative->logo;
        $initiative->forceFill(['logo' => $path])->save();
        $this->forget($old);

        $this->log->item($by, ContentKind::Initiative, $initiative->getKey(), 'Logo changed');
    }

    public function removeLogo(Initiative $initiative, User $by): void
    {
        $old = $initiative->logo;
        $initiative->forceFill(['logo' => '0'])->save();
        $this->forget($old);

        $this->log->item($by, ContentKind::Initiative, $initiative->getKey(), 'Logo removed');
    }

    /** @throws AdminActionRefused */
    public function addPair(Initiative $initiative, UploadedFile $before, UploadedFile $after, User $by): void
    {
        $directory = $this->directory($initiative);
        $stored = [];

        try {
            $stored[] = $first = PhotoFiles::storeOne((string) $before->getRealPath(), $directory, self::PAIR_BOX, cover: true, suffix: 'before');
            $stored[] = $second = PhotoFiles::storeOne((string) $after->getRealPath(), $directory, self::PAIR_BOX, cover: true, suffix: 'after');
            $this->requireFits($first, self::PAIR_LENGTH, $stored);
            $this->requireFits($second, self::PAIR_LENGTH, $stored);

            DB::table('initiatives_images_rehabilitation')->insert([
                'initiative_id' => $initiative->getKey(),
                'path_1' => $first,
                'path_2' => $second,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Throwable $failure) {
            array_map(PhotoFiles::delete(...), $stored);

            throw $failure;
        }

        $this->log->item($by, ContentKind::Initiative, $initiative->getKey(), 'Before & After Images stored');
    }

    public function removePair(Initiative $initiative, int $pairId, User $by): void
    {
        $pair = DB::table('initiatives_images_rehabilitation')->where('initiative_id', $initiative->getKey())->where('id', $pairId)->first();

        if ($pair === null) {
            return;
        }

        DB::table('initiatives_images_rehabilitation')->where('id', $pairId)->delete();
        $this->forget($pair->path_1);
        $this->forget($pair->path_2);

        $this->log->item($by, ContentKind::Initiative, $initiative->getKey(), '1 Before & After Images deleted');
    }

    private function directory(Initiative $initiative): string
    {
        return PhotoFolders::initiative((string) DB::table('what_categories')->where('id', $initiative->what_category_id)->value('name'), (int) $initiative->getKey());
    }

    /** Delete a stored file, if the column held one ("0" means none). */
    private function forget(?string $path): void
    {
        if (is_string($path) && $path !== '' && $path !== '0') {
            PhotoFiles::delete($path);
        }
    }

    /**
     * The column cannot hold a longer path: take back what was written and say so.
     *
     * @param  list<string>  $written
     *
     * @throws AdminActionRefused
     */
    private function requireFits(string $path, int $length, array $written): void
    {
        if (strlen($path) > $length) {
            array_map(PhotoFiles::delete(...), $written);

            throw new AdminActionRefused('path_too_long');
        }
    }
}
