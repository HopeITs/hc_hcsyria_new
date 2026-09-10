<?php

namespace App\Queries;

use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;

/*
 * Micro-project search for the public listing at /projects/micro-projects.
 *
 * Derived from App\Livewire\MicroProjects::loadProjects() in the legacy
 * application, which is what that route actually runs. The similarly named
 * PublicController::get_projects_IDs() is dead code — its only caller,
 * micro_project_new(), has been commented out of the routes — so it is
 * deliberately not reproduced here.
 *
 * One deliberate change: a stable tiebreaker. See builder().
 */
class ProjectSearch
{
    /** Publication states treated as visible on the public site. */
    private const PUBLISHED = [1, 2];

    /** Completion states treated as finished. */
    private const COMPLETED = [1, 2];

    private ?string $term = null;
    private ?int $donorId = null;
    private ?int $cityId = null;
    private bool $onlyWithVideo = false;

    public static function make(): self
    {
        return new self();
    }

    public function search(?string $term): self
    {
        $term = trim((string) $term);
        $this->term = $term === '' ? null : $term;

        return $this;
    }

    public function forDonor(int|string|null $donorId): self
    {
        $this->donorId = $this->toId($donorId);

        return $this;
    }

    public function inCity(int|string|null $cityId): self
    {
        $this->cityId = $this->toId($cityId);

        return $this;
    }

    public function onlyWithVideo(bool $only = true): self
    {
        $this->onlyWithVideo = $only;

        return $this;
    }

    public function builder(): Builder
    {
        $query = Project::query()
            ->whereIn('MPE_Done', self::COMPLETED)
            ->whereIn('MPE_Published', self::PUBLISHED);

        if ($this->term !== null) {
            $like = '%' . $this->escapeLike($this->term) . '%';

            /*
             * The legacy version put both conditions inside one whereHas on the
             * content relation, even though MPE_BeneficiaryName lives on
             * `projects`. MySQL resolved that column against the outer query,
             * so it happened to work. Written out explicitly here; the result is
             * the same because every project has exactly one content row.
             */
            $query->where(function (Builder $q) use ($like) {
                $q->whereHas('content', fn(Builder $c) => $c->where('MPE_Name', 'like', $like))
                    ->orWhere('projects.MPE_BeneficiaryName', 'like', $like);
            });
        }

        if ($this->donorId !== null) {
            $query->where('MPE_Donor', $this->donorId);
        }

        if ($this->cityId !== null) {
            $query->where('MPE_City', $this->cityId);
        }

        if ($this->onlyWithVideo) {
            $query->where('MPE_Video', '!=', '0')
                ->where('MPE_Video', '!=', '');
        }

        return $query
            ->orderByDesc('MPE_PublishDate')
            /*
             * 220 publish dates are shared by more than one project, the largest
             * group being 13, and the legacy query ordered by date alone. That
             * listing loads more rows by raising a limit on the same query, so
             * unordered ties could shift between loads and show a project twice
             * or not at all. Ordering by id after the date makes it stable.
             */
            ->orderByDesc('id');
    }

    /** @return array<int, int> */
    public function ids(): array
    {
        return $this->builder()->pluck('id')->map(fn($id) => (int) $id)->all();
    }

    /**
     * Escapes LIKE metacharacters so user input is matched literally.
     * The backslash must be escaped first or it would double-escape the rest.
     */
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /** Treats '', 'all' and null alike: no filter. */
    private function toId(int|string|null $value): ?int
    {
        if ($value === null || $value === '' || $value === 'all') {
            return null;
        }

        return (int) $value;
    }
}
