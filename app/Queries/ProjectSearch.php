<?php

namespace App\Queries;

use App\Models\Project;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/*
 * Micro-project search, replacing PublicController::get_projects_IDs().
 *
 * The legacy version built SQL by string concatenation, interpolating the
 * user's search term directly into a LIKE clause with only addslashes() in
 * front of it. Every value here is bound instead.
 *
 * Two deliberate behaviour changes from the legacy query:
 *
 *  - LIKE wildcards in the search term are escaped, so a user typing "50%"
 *    searches for the literal text rather than matching everything.
 *  - The search term is matched case-insensitively via the column collation
 *    rather than LOWER(), which lets MySQL use an index on these columns if
 *    one is ever added. utf8mb4_unicode_ci is already case-insensitive, so
 *    results are unchanged.
 */
class ProjectSearch
{
    /** Publication states treated as visible on the public site. */
    private const PUBLISHED = ['1', '2'];

    /** Completion states treated as finished. */
    private const COMPLETED = ['1', '2'];

    /** Columns on `projects` that the free-text term matches against. */
    private const SEARCHABLE = [
        'projects.MPE_BeneficiaryName',
        'projects.MPE_PartnerName',
        'projects.MPE_PartnerName2',
    ];

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

    /**
     * The query, ready to paginate or extend. Joins the content table because
     * MPE_Name — the primary thing people search for — lives there.
     */
    public function builder(): Builder
    {
        $query = Project::query()
            ->join('projects_english', 'projects_english.project_id', '=', 'projects.id')
            ->whereIn('projects.MPE_Published', self::PUBLISHED)
            ->whereIn('projects.MPE_Done', self::COMPLETED);

        if ($this->term !== null) {
            $query->where(function (Builder $q) {
                $like = '%' . $this->escapeLike($this->term) . '%';

                $q->where('projects_english.MPE_Name', 'like', $like);

                foreach (self::SEARCHABLE as $column) {
                    $q->orWhere($column, 'like', $like);
                }

                // The legacy query also matched the visible project number
                // exactly, but only when the term was entirely numeric.
                if (ctype_digit($this->term)) {
                    $q->orWhere('projects.MicroProject_ID', '=', (int) $this->term);
                }
            });
        }

        if ($this->donorId !== null) {
            $query->where('projects.MPE_Donor', $this->donorId);
        }

        if ($this->cityId !== null) {
            $query->where('projects.MPE_City', $this->cityId);
        }

        if ($this->onlyWithVideo) {
            $query->where('projects.MPE_Video', '<>', '0');
        }

        return $query
            ->select('projects.*')
            ->orderByDesc('projects.MPE_PublishDate')
            // 220 publish dates are shared by more than one project (largest
            // group: 13). The legacy query had no tiebreaker, so MySQL was free
            // to order ties differently between runs — which makes paginated
            // listings repeat or skip a project. Ordering by id after the date
            // makes the sequence stable without changing the date ordering.
            ->orderByDesc('projects.id');
    }

    /**
     * Project ids only, in display order. The legacy controller worked in ids
     * and then re-fetched, so this keeps that call site working unchanged.
     *
     * @return array<int, int>
     */
    public function ids(): array
    {
        return $this->builder()
            ->pluck('projects.id')
            ->map(fn($id) => (int) $id)
            ->all();
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
