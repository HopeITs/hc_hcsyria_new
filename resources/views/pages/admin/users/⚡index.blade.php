<?php

use App\Actions\Admin\ManageUsers;
use App\Enums\AdminCapability;
use App\Exceptions\AdminActionRefused;
use App\Livewire\Concerns\SendsAlerts;
use App\Models\User;
use App\Queries\AdminUserQuery;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/*
 * /admin/users: the staff accounts, for the Admin only. Replaces legacy's users page
 * (which listed every account three times). Tabs for active and inactive accounts, a role
 * filter and a search; an account can be activated, deactivated or deleted from its row.
 * The work is ManageUsers, which also refuses what would lock everyone out.
 */
new #[Layout('layouts::admin', ['title' => 'Users'])] class extends Component
{
    use SendsAlerts;
    use WithPagination;

    private const PER_PAGE = 20;

    #[Url(history: true, except: '')]
    public string $search = '';

    #[Url(history: true, except: '')]
    public string $role = '';

    #[Url(history: true, except: 'all')]
    public string $tab = 'all';

    public function mount(): void
    {
        $this->authorize('admin.'.AdminCapability::ManageUsers->value);
        $this->search = mb_substr(trim($this->search), 0, 100);
        $this->tab = in_array($this->tab, AdminUserQuery::TABS, true) ? $this->tab : 'all';
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'role', 'tab'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'role');
        $this->resetPage();
    }

    public function setActive(int $id, bool $active): void
    {
        $this->authorize('admin.'.AdminCapability::ManageUsers->value);
        $user = User::query()->find($id);

        if ($user === null) {
            $this->alert('gone', ['noun' => 'account'], 'error');

            return;
        }

        try {
            app(ManageUsers::class)->setActive($user, $active, Auth::user());
        } catch (AdminActionRefused $refused) {
            $this->alert($refused->alertKey(), $refused->params, 'error');

            return;
        }

        $this->alert($active ? 'users.activated' : 'users.deactivated', ['name' => $user->name]);
    }

    public function remove(int $id): void
    {
        $this->authorize('admin.'.AdminCapability::ManageUsers->value);
        $user = User::query()->find($id);

        if ($user === null) {
            $this->alert('gone', ['noun' => 'account'], 'error');

            return;
        }

        try {
            app(ManageUsers::class)->delete($user, Auth::user());
        } catch (AdminActionRefused $refused) {
            $this->alert($refused->alertKey(), $refused->params, 'error');

            return;
        }

        $this->alert('users.deleted', ['name' => $user->name]);
    }

    public function with(): array
    {
        $query = fn (): AdminUserQuery => AdminUserQuery::make()->search($this->search)->withRole($this->role);

        return [
            'rows' => $query()->tab($this->tab)->paginate(self::PER_PAGE),
            'counts' => $query()->counts(),
            'roles' => AdminUserQuery::roles(),
            'me' => Auth::id(),
            'filtered' => $this->search !== '' || $this->role !== '',
        ];
    }
};
?>

<div class="stack">
    <x-admin.page-header title="Users" :count="number_format($counts['all'])" subtitle="Who can sign in to the dashboard, with which roles and for which cities.">
        <x-slot:actions>
            <a href="{{ route('admin.users.create') }}" wire:navigate class="btn btn-p"><x-admin.icon name="plus" />New user</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.panel>
        <x-admin.tabs :tabs="AdminUserQuery::TABS" :counts="$counts" :current="$tab" />

        <x-admin.filter-bar placeholder="Name or email" :$filtered>
            <x-admin.select-field label="Role" name="role" :options="$roles" all="All roles" />
        </x-admin.filter-bar>

        @if ($rows->isEmpty())
            <x-admin.empty-state icon="search" title="No users match">
                {{ $filtered ? 'Try removing a filter.' : 'Nothing in this tab.' }}
                @if ($filtered)<div style="margin-top: 14px"><button type="button" class="btn btn-o" wire:click="clearFilters">Clear filters</button></div>@endif
            </x-admin.empty-state>
        @else
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead><tr><th>User</th><th>Roles</th><th class="hide-m">Cities</th><th>Status</th><th style="text-align: end">Actions</th></tr></thead>
                    <tbody>
                        @foreach ($rows as $user)
                            <tr wire:key="user-{{ $user->id }}">
                                <td>
                                    <div class="nm">{{ $user->name }}@if ($user->id === $me) <span class="muted">(you)</span>@endif</div>
                                    <div class="muted" style="font-size: 12px">{{ $user->email }}</div>
                                </td>
                                <td>{{ $user->roles ?: '—' }}</td>
                                <td class="hide-s hide-m">{{ $user->places ?: 'All cities' }}</td>
                                <td><x-admin.status-pill :tone="$user->active ? 'good' : 'grey'" :label="$user->active ? 'Active' : 'Inactive'" /></td>
                                <td class="acell">
                                    <div class="acts">
                                        <a href="{{ route('admin.users.edit', $user->id) }}" wire:navigate class="btn btn-o s"><x-admin.icon name="edit" />Edit</a>
                                        @if ($user->id !== $me)
                                            <x-admin.popover label="More actions">
                                                <x-slot:trigger class="btn btn-g s" style="width: 2rem; padding: 0"><x-admin.icon name="dots" /></x-slot:trigger>
                                                <button type="button" class="mi" wire:click="setActive({{ $user->id }}, {{ $user->active ? 'false' : 'true' }})">
                                                    <x-admin.icon :name="$user->active ? 'lock' : 'check'" />{{ $user->active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                                <div class="sep"></div>
                                                <button type="button" class="mi danger"
                                                    x-on:click="$store.confirm.ask({ title: @js('Delete '.$user->name.'?'), body: 'The account is removed for good. If it has made changes, deactivate it instead.', action: @js(__('admin.confirm.delete_action')), cancel: @js(__('admin.confirm.cancel')), run: () => $wire.remove({{ $user->id }}) })">
                                                    <x-admin.icon name="trash" />{{ __('admin.confirm.delete_action') }}
                                                </button>
                                            </x-admin.popover>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-admin.pager :paginator="$rows" noun="users" />
    </x-admin.panel>
</div>
