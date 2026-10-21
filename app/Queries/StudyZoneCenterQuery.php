<?php

namespace App\Queries;

use App\Models\StudyzoneCenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/*
 * Study-zone centres for the public /study-zone page.
 *
 * Replaces six near-identical raw queries in PublicController::studyzone()
 * that differed only by place_id (1 Homs, 2 Damascus, 3 Aleppo, 4 Latakia,
 * 6 Tartus), plus a seventh that joined places for the display name.
 *
 * Two differences from the legacy SQL:
 *
 *  - It selected `name_fr`, which is French and no longer used.
 *  - It aliased SUBSTRING_INDEX(name, '-', -1) over the top of `name`, so the
 *    full name was unavailable to the view. That derived value is exposed as
 *    `short_name` here and `name` keeps the real value.
 */
class StudyZoneCenterQuery
{
    private ?int $placeId = null;

    private bool $activeOnly = false;

    private bool $withPlace = false;

    public static function make(): self
    {
        return new self;
    }

    public function inPlace(int|string|null $placeId): self
    {
        $this->placeId = ($placeId === null || $placeId === '') ? null : (int) $placeId;

        return $this;
    }

    public function activeOnly(bool $only = true): self
    {
        $this->activeOnly = $only;

        return $this;
    }

    /** Adds the place name, as the legacy "all centres" query did via a join. */
    public function withPlaceName(bool $with = true): self
    {
        $this->withPlace = $with;

        return $this;
    }

    /** @return Builder<StudyzoneCenter> */
    public function builder(): Builder
    {
        $query = StudyzoneCenter::query()
            ->select('study_zone_centers.*')
            // The centre names are stored as "City - Centre"; the public page
            // shows only the part after the last dash.
            ->selectRaw("SUBSTRING_INDEX(study_zone_centers.name, '-', -1) as short_name");

        if ($this->withPlace) {
            $query->leftJoin('places', 'places.id', '=', 'study_zone_centers.place_id')
                ->addSelect('places.name as place_name');
        }

        if ($this->placeId !== null) {
            $query->where('study_zone_centers.place_id', $this->placeId);
        }

        if ($this->activeOnly) {
            $query->where('study_zone_centers.active', '1');
        }

        // rank is not guaranteed unique; id keeps tied rows in a fixed order.
        return $query->orderBy('study_zone_centers.rank')->orderBy('study_zone_centers.id');
    }

    /** @return Collection<int, StudyzoneCenter> */
    public function get(): Collection
    {
        return $this->builder()->get();
    }

    /**
     * Centres grouped by place id, which is what the page actually needs —
     * the legacy code ran one query per city to build the same thing.
     *
     * @return \Illuminate\Support\Collection<array-key, Collection<int, StudyzoneCenter>>
     */
    public function groupedByPlace(): \Illuminate\Support\Collection
    {
        return $this->inPlace(null)->get()->groupBy('place_id');
    }
}
