<?php

namespace App\Queries;

use App\Models\Initiative;
use App\Models\InitiativeDonor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/*
 * The activities the site-wide search finds, across every programme and
 * category. Derived from PublicController::search() at legacy commit 6d45ef9:
 * an activity matches when its title, text, summary, organiser or date, the
 * name of one of its funders, or the name of its town contains the term.
 *
 * Differences from legacy, all deliberate:
 *  - the conditions are grouped, so "online" and the filters bind every
 *    alternative (legacy chained orWhere onto the whole query, which let an
 *    offline activity through when its funder or town matched);
 *  - only activities that have a page of their own are listed (online, and
 *    not a Family to Family story), so no result leads to a 404;
 *  - the term is matched literally, not as a pattern (legacy spliced it into
 *    its highlighting regular expression and into LIKE);
 *  - the centre's name is not searched: centres live in the Study Zone
 *    database, which this query does not reach.
 * The city, donor and video filters are the ones the other lists have.
 */
final class ActivitySearch
{
    private ?string $term = null;

    private ?int $cityId = null;

    private ?int $donorId = null;

    private bool $onlyWithVideo = false;

    public static function make(): self
    {
        return new self;
    }

    public function search(?string $term): self
    {
        $term = trim((string) $term);
        $this->term = $term === '' ? null : $term;

        return $this;
    }

    public function inCity(?int $cityId): self
    {
        $this->cityId = $cityId;

        return $this;
    }

    public function forDonor(?int $donorId): self
    {
        $this->donorId = $donorId;

        return $this;
    }

    public function onlyWithVideo(bool $only = true): self
    {
        $this->onlyWithVideo = $only;

        return $this;
    }

    /** @return Builder<Initiative> */
    public function builder(): Builder
    {
        $query = InitiativePageQuery::visible(Initiative::query())
            ->whereHas('content')
            ->whereHas('whatCategory');

        if ($this->term !== null) {
            $like = '%'.addcslashes(mb_strtolower($this->term), '\%_').'%';

            $query->where(fn (Builder $any) => $any
                ->whereHas('content', fn (Builder $content) => $content->where(fn (Builder $text) => $text
                    ->where('title', 'like', $like)
                    ->orWhere('content', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('organization', 'like', $like)
                    ->orWhere('date_string', 'like', $like)))
                ->orWhereHas('initiativeDonors.donor', fn (Builder $donor) => $donor->where('name', 'like', $like))
                ->orWhereHas('place', fn (Builder $place) => $place->where('name', 'like', $like)));
        }

        if ($this->cityId !== null) {
            $query->where('place_id', $this->cityId);
        }

        if ($this->donorId !== null) {
            $query->whereHas('initiativeDonors', fn (Builder $donors) => $donors->where('donor_id', $this->donorId));
        }

        if ($this->onlyWithVideo) {
            $query->where('video', '!=', '0')->where('video', '!=', '');
        }

        return $query->orderByDesc('id');
    }

    /**
     * The first $limit matches, with what a tile or a row shows.
     *
     * @return Collection<int, Initiative>
     */
    public function page(int $limit): Collection
    {
        return $this->builder()
            ->with([
                'content',
                'whatCategory',
                'initiativeImages' => fn ($images) => $images->orderBy('image_order')->orderBy('id'),
            ])
            ->limit($limit)
            ->get();
    }

    /**
     * The donors that fund any listed activity, for the filter.
     *
     * @return list<int>
     */
    public static function donorIds(): array
    {
        return array_values(InitiativeDonor::query()
            ->whereIn('initiative_id', self::make()->builder()->select('initiatives.id'))
            ->distinct()
            ->pluck('donor_id')
            ->map(fn ($id) => (int) $id)
            ->all());
    }
}
