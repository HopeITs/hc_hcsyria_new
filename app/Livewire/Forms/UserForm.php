<?php

namespace App\Livewire\Forms;

use App\Queries\AdminUserQuery;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Form;

/*
 * The create and edit form of a staff account: a name, a unique email, a password
 * (needed for a new account; left empty on an edit it stays as it is), the roles and
 * the cities. The password rules are the Tracking app's baseline: at least 12
 * characters with letters and numbers. Roles and cities are ones that exist.
 */
class UserForm extends Form
{
    public ?int $userId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    /** @var list<string> */
    public array $roles = [];

    /** @var list<string> */
    public array $places = [];

    /** @param object{id: int, name: string, email: string, role_ids: list<int>, place_ids: list<int>} $user from AdminUserQuery::find() */
    public function fillFrom(object $user): void
    {
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->roles = array_map('strval', $user->role_ids);
        $this->places = array_map('strval', $user->place_ids);
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:filter', 'max:191', Rule::unique('users', 'email')->ignore($this->userId)],
            'password' => [$this->userId === null ? 'required' : 'nullable', 'string', Password::min(12)->letters()->numbers(), 'max:200'],
            'roles' => ['array'],
            'roles.*' => [Rule::in(AdminUserQuery::roles()->pluck('id')->map(fn ($id): string => (string) $id)->all())],
            'places' => ['array'],
            'places.*' => [Rule::in(AdminUserQuery::places()->pluck('id')->map(fn ($id): string => (string) $id)->all())],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return ['places' => 'cities', 'places.*' => 'city', 'roles.*' => 'role'];
    }

    /**
     * What ManageUsers takes. A new account needs at least one role (without one it cannot open the dashboard).
     *
     * @return array{name: string, email: string, password: ?string, role_ids: list<int>, place_ids: list<int>}
     */
    public function payload(): array
    {
        $data = $this->validate();

        if ($data['roles'] === []) {
            throw ValidationException::withMessages(['roles' => 'Choose at least one role.']);
        }

        return [
            'name' => trim($data['name']),
            'email' => mb_strtolower(trim($data['email'])),
            'password' => filled($data['password'] ?? null) ? $data['password'] : null,
            'role_ids' => array_values(array_unique(array_map('intval', $data['roles']))),
            'place_ids' => array_values(array_unique(array_map('intval', $data['places']))),
        ];
    }
}
