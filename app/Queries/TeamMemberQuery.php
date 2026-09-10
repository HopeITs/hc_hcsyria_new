<?php

namespace App\Queries;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/*
 * Staff profiles for the public team pages.
 *
 * Replaces ten near-identical DB::table('profile') calls spread across
 * PublicController::about(), our_team(), show_team(), studyzone_team(),
 * disc_team(), children_team(), leap_team(), show_homs_hope_team(),
 * show_damascus_hope_team() and show_latakia_hope_team().
 *
 * `inactive` is not a boolean despite the name: it holds '0' for current staff
 * and a departure date such as '31/03/2023' for people who have left.
 */
class TeamMemberQuery
{
    /** The value of `inactive` for someone still on the team. */
    private const ACTIVE = '0';

    /** @var string[] */
    private array $groups = [];
    private bool $activeOnly = true;
    private ?string $email = null;

    public static function make(): self
    {
        return new self();
    }

    /** One or more `belong_to` groups: hope, hope_h, hope_d, hope_l, homs, study, disc, children, leap. */
    public function inGroup(string|array $groups): self
    {
        $this->groups = array_values((array) $groups);

        return $this;
    }

    /** Include people who have left. Off by default. */
    public function includingFormer(bool $include = true): self
    {
        $this->activeOnly = ! $include;

        return $this;
    }

    /** The public profile pages identify a person by email, not id. */
    public function withEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function builder(): Builder
    {
        $query = Profile::query();

        if ($this->activeOnly) {
            $query->where('inactive', self::ACTIVE);
        }

        if ($this->groups !== []) {
            $query->whereIn('belong_to', $this->groups);
        }

        if ($this->email !== null) {
            $query->where('email', $this->email);
        }

        // `rank` is not unique — the "hope" group alone has ranks 4,4 and
        // 5,5,5 — so ordering by it alone leaves those rows in whatever order
        // MySQL returns, and the team page can reshuffle between requests.
        return $query->orderBy('rank')->orderBy('id');
    }

    public function get(): Collection
    {
        return $this->builder()->get();
    }

    public function first(): ?Profile
    {
        return $this->builder()->first();
    }
}
