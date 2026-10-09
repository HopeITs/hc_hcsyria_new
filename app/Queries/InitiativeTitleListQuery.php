<?php

namespace App\Queries;

use App\Models\Initiative;
use Illuminate\Support\Collection;

/*
 * Distinct initiative titles for the search typeahead.
 *
 * Replaces four identical methods in PublicController that differed only by
 * what_category_id: search_list_cu_activities (17), search_list_SZ_activities
 * (7), search_list_leap_activities (19) and search_list_DISC_activities (5).
 *
 * Each also took a $langTable parameter to pick the English or French content
 * table. With French gone there is one content table, so that is fixed.
 */
class InitiativeTitleListQuery
{
    /** what_category_id per programme, as used by the public search. */
    public const STUDY_ZONE = 7;

    public const DISC = 5;

    public const CHILDREN_UNI = 17;

    public const LEAP = 19;

    private ?int $whatCategoryId = null;

    private bool $onlineOnly = false;

    public static function make(): self
    {
        return new self;
    }

    public function forCategory(int $whatCategoryId): self
    {
        $this->whatCategoryId = $whatCategoryId;

        return $this;
    }

    /**
     * Restrict to published initiatives. The four search_list_* methods did
     * not do this, though the general one did — so it is off by default to
     * preserve their behaviour, and available when a caller wants it.
     */
    public function onlyOnline(bool $only = true): self
    {
        $this->onlineOnly = $only;

        return $this;
    }

    /** @return Collection<int, string> */
    public function titles(): Collection
    {
        $query = Initiative::query()
            ->join('initiatives_english', 'initiatives_english.initiative_id', '=', 'initiatives.id')
            ->select('initiatives_english.title')
            ->distinct();

        if ($this->whatCategoryId !== null) {
            $query->where('initiatives.what_category_id', $this->whatCategoryId);
        }

        if ($this->onlineOnly) {
            $query->where('initiatives.online', '1');
        }

        return $query->orderBy('initiatives_english.title')->pluck('title');
    }
}
