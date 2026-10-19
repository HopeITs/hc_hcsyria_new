<?php

namespace App\Queries;

use App\Models\Place;
use App\Models\Role;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/*
 * The staff accounts as the Users page shows them: the list (tabs for active and
 * inactive accounts, a role filter and a search box) and one account with its roles
 * and cities. Replaces UserController::index(), which loaded every account three
 * times with all its relations. Nothing here reads the password.
 */
final class AdminUserQuery
{
    public const TABS = ['all', 'active', 'inactive'];

    private string $search = '';

    private ?int $role = null;

    private string $tab = 'all';

    public static function make(): self
    {
        return new self;
    }

    public function search(?string $term): self
    {
        $this->search = trim((string) $term);

        return $this;
    }

    public function withRole(int|string|null $id): self
    {
        $this->role = $id === null || $id === '' ? null : (int) $id;

        return $this;
    }

    public function tab(?string $tab): self
    {
        $this->tab = in_array($tab, self::TABS, true) ? (string) $tab : 'all';

        return $this;
    }

    /** One count per tab, with the filters other than the tab applied. @return array<string, int> */
    public function counts(): array
    {
        $row = $this->base(withTab: false)
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(users.active = 1), 0) as active, COALESCE(SUM(users.active = 0), 0) as inactive')
            ->first();

        return ['all' => (int) $row->total, 'active' => (int) $row->active, 'inactive' => (int) $row->inactive];
    }

    /** @return LengthAwarePaginator<int, object> */
    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return $this->base()
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.active',
                'users.created_at',
                DB::raw('(SELECT GROUP_CONCAT(roles.name ORDER BY roles.name SEPARATOR ", ") FROM users_roles JOIN roles ON roles.id = users_roles.role_id WHERE users_roles.user_id = users.id) as roles'),
                DB::raw('(SELECT GROUP_CONCAT(places.name ORDER BY places.name SEPARATOR ", ") FROM users_places JOIN places ON places.id = users_places.place_id WHERE users_places.user_id = users.id) as places'),
            ])
            ->orderByDesc('users.active')
            ->orderBy('users.name')
            ->orderBy('users.id')
            ->paginate($perPage);
    }

    /** One account with the ids of its roles and cities, or null. */
    public static function find(int $id): ?object
    {
        $user = DB::table('users')->where('id', $id)->first(['id', 'name', 'email', 'active']);

        if ($user === null) {
            return null;
        }

        $user->role_ids = DB::table('users_roles')->where('user_id', $id)->pluck('role_id')->map(fn ($value): int => (int) $value)->all();
        $user->place_ids = DB::table('users_places')->where('user_id', $id)->pluck('place_id')->map(fn ($value): int => (int) $value)->all();

        return $user;
    }

    /** @return Collection<int, Role> */
    public static function roles(): Collection
    {
        return Role::query()->orderBy('id')->get(['id', 'name']);
    }

    /** @return Collection<int, Place> */
    public static function places(): Collection
    {
        return Place::query()->orderBy('id')->get(['id', 'name']);
    }

    private function base(bool $withTab = true): Builder
    {
        $query = DB::table('users');

        if ($this->role !== null) {
            $query->whereExists(fn ($q) => $q->selectRaw('1')->from('users_roles')->whereColumn('users_roles.user_id', 'users.id')->where('users_roles.role_id', $this->role));
        }

        if ($this->search !== '') {
            $like = '%'.addcslashes($this->search, '%_\\').'%';

            $query->where(fn (Builder $q) => $q->where('users.name', 'like', $like)->orWhere('users.email', 'like', $like));
        }

        if ($withTab) {
            match ($this->tab) {
                'active' => $query->where('users.active', 1),
                'inactive' => $query->where('users.active', 0),
                default => null,
            };
        }

        return $query;
    }
}
