<?php

namespace App\Actions\Admin;

use App\Enums\ContentKind;
use App\Exceptions\AdminActionRefused;
use App\Models\Success;
use App\Models\SuccessContent;
use App\Models\User;
use App\Support\Admin\LegacyValue;
use App\Support\Admin\PhotoFiles;
use App\Support\Admin\PhotoFolders;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

/*
 * Creates or updates a success story, its English text and its picture. Replaces
 * the success_store() and success_update() of StudyController, DiscController and
 * SuccessController, which were three near copies.
 *
 * Kept from legacy: a new story is offline and unchecked; an empty video is the
 * text "0"; and the activity log gets one row per change, worded as legacy
 * worded them ("Title edited", "Place changed"), with the old and new value
 * where there were two.
 *
 * Changed: the picture is checked and re-encoded (PhotoFiles; a 500 x 500 square
 * like the existing ones) and a replaced one is really deleted (legacy asked
 * File::delete() for a path that does not exist on disk); the micro-project link
 * change was logged with a column that does not exist ("MIE_BeneficiaryName"), so
 * it shows the project's number and name instead.
 *
 * $data is what SuccessForm validated.
 */
class SaveSuccess
{
    private const PICTURE_BOX = [500, 500];

    /** Success column => [log wording, table that names it]: the ids the log shows by name. */
    private const NAMED = [
        'who_category_id' => ['Who we serve category changed', 'who_categories'],
        'what_category_id' => ['What we do category changed', 'what_categories'],
        'place_id' => ['Place changed', 'places'],
    ];

    public function __construct(private readonly WriteActivityLog $log) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws AdminActionRefused
     */
    public function handle(array $data, User $by, ?UploadedFile $picture, ?Success $success = null): Success
    {
        $path = $picture === null ? null : $this->storePicture((string) $picture->getRealPath(), (int) $data['who_category_id']);

        try {
            return DB::transaction(fn (): Success => $success === null
                ? $this->create($data, $by, (string) $path)
                : $this->update($success, $data, $by, $path));
        } catch (Throwable $failure) {
            $path === null || PhotoFiles::delete($path);

            throw $failure;
        }
    }

    /** @param array<string, mixed> $data */
    private function create(array $data, User $by, string $path): Success
    {
        $success = new Success;
        $success->forceFill([
            'image_path' => $path,
            'inner_image_path' => '0',
            'inner_big_image_path' => '0',
            'video' => LegacyValue::store($data['video']),
            'who_category_id' => $data['who_category_id'],
            'what_category_id' => $data['what_category_id'],
            'sub_what_category_id' => $data['sub_what_category_id'],
            'place_id' => $data['place_id'],
            'sz_center_id' => $data['sz_center_id'],
            'project_id' => $data['project_id'],
            'english_check' => 0,
            'french_translation' => '0',
            'online' => '0',
        ])->save();

        SuccessContent::query()->create(['success_id' => $success->getKey(), 'title' => $data['title'], 'text' => $data['text']]);

        $this->log->item($by, ContentKind::Success, $success->getKey(), 'Success Created');

        return $success;
    }

    /** @param array<string, mixed> $data */
    private function update(Success $success, array $data, User $by, ?string $path): Success
    {
        $id = $success->getKey();
        $old = $success->image_path;

        if ($path !== null) {
            $success->image_path = $path;
            $this->log->item($by, ContentKind::Success, $id, 'Image changed');
        }

        $success->video = LegacyValue::store($data['video']);
        if ($success->isDirty('video')) {
            $this->log->item($by, ContentKind::Success, $id, 'Video Edited', $success->getOriginal('video'), $success->video);
        }

        foreach (self::NAMED as $column => [$operation, $table]) {
            $success->{$column} = $data[$column];

            if ($success->isDirty($column)) {
                $this->log->item($by, ContentKind::Success, $id, $operation, $this->name($table, $success->getOriginal($column)), $this->name($table, $success->{$column}));
            }
        }

        $success->forceFill([
            'sub_what_category_id' => $data['sub_what_category_id'],
            'sz_center_id' => $data['sz_center_id'],
        ]);

        $success->project_id = $data['project_id'];
        if ($success->isDirty('project_id')) {
            $this->log->item($by, ContentKind::Success, $id, 'Link to micro project changed', $this->project($success->getOriginal('project_id')), $this->project($success->project_id));
        }

        $success->save();

        if ($path !== null && $old !== '' && $old !== '0') {
            PhotoFiles::delete($old);
        }

        $content = SuccessContent::query()->where('success_id', $id)->firstOrFail();
        foreach (['title' => 'Title edited', 'text' => 'Text edited'] as $column => $operation) {
            if (LegacyValue::textChanged($content->{$column}, $data[$column])) {
                $this->log->item($by, ContentKind::Success, $id, $operation, $content->{$column}, $data[$column]);
                $content->{$column} = $data[$column];
            }
        }
        $content->save();

        return $success;
    }

    /** @throws AdminActionRefused */
    private function storePicture(string $source, int $whoCategoryId): string
    {
        $who = (string) DB::table('who_categories')->where('id', $whoCategoryId)->value('name');
        $path = PhotoFiles::storeOne($source, PhotoFolders::success($who), self::PICTURE_BOX, cover: true);

        if (strlen($path) > 150) {
            PhotoFiles::delete($path);

            throw new AdminActionRefused('path_too_long');
        }

        return $path;
    }

    private function name(string $table, mixed $id): string
    {
        return (string) DB::table($table)->where('id', $id)->value('name');
    }

    /** "#3071 Tailoring workshop", or nothing if there is no link. */
    private function project(mixed $id): string
    {
        $project = DB::table('projects')->leftJoin('projects_english', 'projects_english.project_id', '=', 'projects.id')->where('projects.id', $id)->first(['projects.MicroProject_ID as number', 'projects_english.MPE_Name as name']);

        return $project === null ? '' : '#'.$project->number.' '.$project->name;
    }
}
