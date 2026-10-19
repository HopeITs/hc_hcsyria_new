<?php

namespace App\Actions\Admin;

use App\Enums\ProjectTransition;
use App\Models\Image;
use App\Models\Place;
use App\Models\Project;
use App\Models\User;
use App\Support\Admin\ProjectPhotoFiles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

/*
 * Adds, removes and orders the photos of a micro-project. Replaces
 * projectsController::store_image(), delete_images() and order_images().
 *
 * Kept from legacy: a project that is online goes back to the pending list when
 * its photos change (the "Made Offline" log row), and the log says "N Images
 * stored" and "Image Deleted". Changed: one upload becomes both file sizes
 * (ProjectPhotoFiles), and the photos' order is one run of 1, 2, 3... (legacy
 * left every new photo at 0, so the cover was whichever the database returned
 * first). The caller has already checked ProjectPolicy::managePhotos.
 */
class ManageProjectPhotos
{
    public function __construct(
        private readonly WriteActivityLog $log,
        private readonly ChangeProjectState $state,
    ) {}

    /**
     * @param  list<UploadedFile>  $files
     * @return array{added: int, offline: bool}
     */
    public function add(Project $project, array $files, User $by): array
    {
        $city = (string) Place::query()->whereKey($project->MPE_City)->value('name');
        $stored = [];

        try {
            $order = (int) Image::query()->where('project_id', $project->getKey())->max('image_order');

            DB::transaction(function () use ($project, $files, $city, &$stored, &$order): void {
                foreach ($files as $file) {
                    $path = ProjectPhotoFiles::store($file->getRealPath(), $city, $project->MicroProject_ID);
                    $stored[] = $path;

                    Image::query()->create([
                        'MPI_Path' => $path,
                        'MicroProject_ID' => $project->MicroProject_ID,
                        'project_id' => $project->getKey(),
                        'image_order' => ++$order,
                    ]);
                }
            });
        } catch (Throwable $failure) {
            array_map(ProjectPhotoFiles::delete(...), $stored);

            throw $failure;
        }

        $this->log->project($by, $project->getKey(), count($files).' Images stored');

        return ['added' => count($files), 'offline' => $this->takeOffline($project, $by)];
    }

    /** Returns whether the project went back to the pending list. */
    public function remove(Project $project, int $imageId, User $by): bool
    {
        $image = Image::query()->where('project_id', $project->getKey())->find($imageId);

        if ($image === null) {
            return false;
        }

        $path = $image->MPI_Path;
        DB::transaction(function () use ($image, $project): void {
            $image->delete();
            $this->renumber($project);
        });
        ProjectPhotoFiles::delete($path);

        $this->log->project($by, $project->getKey(), 'Image Deleted');

        return $this->takeOffline($project, $by);
    }

    /** Move a photo one place earlier (-1) or later (+1). */
    public function move(Project $project, int $imageId, int $offset): void
    {
        $ids = $this->orderedIds($project);
        $from = array_search($imageId, $ids, true);
        $to = $from === false ? false : $from + $offset;

        if ($from === false || $to < 0 || $to >= count($ids)) {
            return;
        }

        [$ids[$from], $ids[$to]] = [$ids[$to], $ids[$from]];
        $this->number($ids);
    }

    public function makeCover(Project $project, int $imageId): void
    {
        $ids = $this->orderedIds($project);

        if (in_array($imageId, $ids, true)) {
            $this->number([$imageId, ...array_values(array_diff($ids, [$imageId]))]);
        }
    }

    private function takeOffline(Project $project, User $by): bool
    {
        $project->refresh();

        if (! ProjectTransition::Unpublish->appliesTo($project)) {
            return false;
        }

        $this->state->handle($project, ProjectTransition::Unpublish, $by);

        return true;
    }

    /** @return array<int, int> */
    private function orderedIds(Project $project): array
    {
        return Image::query()->where('project_id', $project->getKey())->orderBy('image_order')->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();
    }

    private function renumber(Project $project): void
    {
        $this->number($this->orderedIds($project));
    }

    /** @param array<int, int> $ids in the order they should show */
    private function number(array $ids): void
    {
        foreach ($ids as $position => $id) {
            Image::query()->whereKey($id)->update(['image_order' => $position + 1]);
        }
    }
}
