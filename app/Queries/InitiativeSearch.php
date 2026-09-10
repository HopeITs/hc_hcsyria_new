<?php

namespace App\Queries;

use App\Models\Initiative;
use Illuminate\Database\Eloquent\Builder;

/*
 * Initiative search, replacing PublicController::get_initiatives_IDs().
 *
 * As with ProjectSearch, the legacy version concatenated the user's search
 * term into raw SQL; every value here is bound and LIKE metacharacters are
 * escaped.
 *
 * One legacy quirk is preserved deliberately: `organization` is matched with
 * spaces around the term ('% term %') while the other three columns are matched
 * as a bare substring ('%term%'). That is a crude word-boundary match, and
 * changing it would silently change which initiatives are found.
 */
class InitiativeSearch
{
    /** Columns matched as a plain substring. */
    private const SUBSTRING_COLUMNS = [
        'initiatives_english.title',
        'initiatives_english.description',
        'initiatives_english.content',
    ];

    private ?string $term = null;
    private ?bool $online = null;

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

    /** The legacy id query applied no online filter; listings applied it separately. */
    public function onlyOnline(bool $only = true): self
    {
        $this->online = $only ? true : null;

        return $this;
    }

    public function builder(): Builder
    {
        $query = Initiative::query()
            ->join(
                'initiatives_english',
                'initiatives_english.initiative_id',
                '=',
                'initiatives.id'
            );

        if ($this->term !== null) {
            $escaped = $this->escapeLike($this->term);

            $query->where(function (Builder $q) use ($escaped) {
                foreach (self::SUBSTRING_COLUMNS as $column) {
                    $q->orWhere($column, 'like', '%' . $escaped . '%');
                }

                // Spaces are intentional - see the class docblock.
                $q->orWhere('initiatives_english.organization', 'like', '% ' . $escaped . ' %');
            });
        }

        if ($this->online === true) {
            $query->where('initiatives.online', '1');
        }

        return $query
            ->select('initiatives.*')
            // The legacy query specified no order at all, which leaves the
            // sequence entirely up to MySQL. Ordering by id keeps results
            // stable between requests.
            ->orderByDesc('initiatives.id');
    }

    /** @return array<int, int> */
    public function ids(): array
    {
        return $this->builder()
            ->pluck('initiatives.id')
            ->map(fn($id) => (int) $id)
            ->all();
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
