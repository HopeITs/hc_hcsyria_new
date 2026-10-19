<?php

use App\Actions\Admin\ManageUsers;
use App\Enums\AdminCapability;
use App\Exceptions\AdminActionRefused;
use App\Livewire\Concerns\SendsAlerts;
use App\Livewire\Forms\UserForm;
use App\Models\User;
use App\Queries\AdminUserQuery;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/*
 * /admin/users/create and /admin/users/{id}/edit: the one form for a staff account, for
 * the Admin only. The rules are UserForm, the saving is ManageUsers. Replaces legacy's
 * user create and edit forms. The page permissions and Study Zone centres of an account
 * are not here: the dashboard decides by role (see docs/admin-plan.md).
 */
new #[Layout('layouts::admin', ['title' => 'User'])] class extends Component
{
    use SendsAlerts;

    public UserForm $form;

    #[Locked]
    public ?int $userId = null;

    public function mount(?User $user = null): void
    {
        $this->authorize('admin.'.AdminCapability::ManageUsers->value);

        if ($user === null) {
            return;
        }

        $this->userId = $user->getKey();
        $this->form->fillFrom(AdminUserQuery::find($this->userId));
    }

    public function save(): void
    {
        $this->authorize('admin.'.AdminCapability::ManageUsers->value);
        $user = $this->userId === null ? null : User::query()->findOrFail($this->userId);

        try {
            $data = $this->form->payload();
        } catch (ValidationException $invalid) {
            $this->alert('invalid', type: 'error');

            throw $invalid;
        }

        try {
            $saved = app(ManageUsers::class)->save($data, Auth::user(), $user);
        } catch (AdminActionRefused $refused) {
            $this->alert($refused->alertKey(), $refused->params, 'error');

            return;
        }

        $this->flashAlert($user === null ? 'users.created' : 'users.saved', ['name' => $saved->name]);
        $this->redirectRoute('admin.users.index', navigate: true);
    }

    public function with(): array
    {
        $editing = $this->userId !== null;

        return [
            'editing' => $editing,
            'crumbs' => ['Dashboard' => route('admin.dashboard'), 'Users' => route('admin.users.index')] + ($editing ? ['Edit' => null] : ['New' => null]),
            'roles' => AdminUserQuery::roles()->pluck('name', 'id')->all(),
            'places' => AdminUserQuery::places()->pluck('name', 'id')->all(),
        ];
    }
};
?>

<div class="stack">
    <x-admin.page-header :title="$editing ? 'Edit '.$form->name : 'New user'" :subtitle="$editing ? $form->email : 'The account can sign in at once, with the roles you choose.'" :crumbs="$crumbs" />

    <form wire:submit="save" class="panel" style="padding: 24px 24px 0; overflow: visible" novalidate autocomplete="off">
        <x-admin.form-section title="Account" hint="Who they are and how they sign in.">
            <x-admin.field label="Name" name="form.name" required>
                <input id="f-form.name" class="inp" wire:model="form.name" maxlength="100" autocomplete="off">
            </x-admin.field>
            <x-admin.field label="Email" name="form.email" required>
                <input id="f-form.email" type="email" class="inp" wire:model="form.email" maxlength="191" autocomplete="off">
            </x-admin.field>
            <x-admin.field label="Password" name="form.password" :required="! $editing" full :help="$editing ? 'Leave empty to keep the current password. A new one needs 12 or more characters, with letters and numbers.' : 'At least 12 characters, with letters and numbers.'">
                <input id="f-form.password" type="password" class="inp" wire:model="form.password" maxlength="200" autocomplete="new-password">
            </x-admin.field>
        </x-admin.form-section>

        <x-admin.form-section title="Roles" hint="What they may do. Choose at least one; without a role the account cannot open the dashboard.">
            <x-admin.field label="Roles" name="form.roles" required full>
                <x-admin.multi-check name="form.roles" :options="$roles" />
            </x-admin.field>
        </x-admin.form-section>

        <x-admin.form-section title="Cities" hint="Micro projects of other cities are closed to them. None, or “All”, means every city.">
            <x-admin.field label="Cities" name="form.places" full>
                <x-admin.multi-check name="form.places" :options="$places" />
            </x-admin.field>
        </x-admin.form-section>

        <div class="sticky-bar">
            <span class="dirty" wire:dirty.class="on"><x-admin.icon name="warn" class="i16" />Unsaved changes</span>
            <a href="{{ route('admin.users.index') }}" wire:navigate class="btn btn-o">Cancel</a>
            <button type="submit" class="btn btn-p" wire:loading.attr="disabled" wire:target="save">
                <span class="spin" wire:loading wire:target="save"></span><x-admin.icon name="check" wire:loading.remove wire:target="save" />{{ $editing ? 'Save changes' : 'Create user' }}
            </button>
        </div>
    </form>
</div>
