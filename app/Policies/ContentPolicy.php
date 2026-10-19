<?php

namespace App\Policies;

use App\Enums\AdminCapability;
use App\Models\User;
use App\Queries\AdminAccessQuery;
use Illuminate\Database\Eloquent\Model;

/*
 * Who may act on a piece of content in the admin. Every ability needs the role
 * (AdminCapability) and, for a given item, that it is within the account's
 * scope (inScope: a project's city; an initiative has none). One class for every
 * kind of content, so the rules cannot drift between them.
 */
abstract class ContentPolicy
{
    public function viewAny(User $user): bool
    {
        return AdminAccessQuery::can($user, AdminCapability::View);
    }

    public function view(User $user, Model $item): bool
    {
        return $this->allowed($user, AdminCapability::View, $item);
    }

    /** Creating has no item yet: the role is enough, the form limits the scope. */
    public function create(User $user): bool
    {
        return AdminAccessQuery::can($user, AdminCapability::Create);
    }

    public function update(User $user, Model $item): bool
    {
        return $this->allowed($user, AdminCapability::Edit, $item);
    }

    public function delete(User $user, Model $item): bool
    {
        return $this->allowed($user, AdminCapability::Delete, $item);
    }

    public function publish(User $user, Model $item): bool
    {
        return $this->allowed($user, AdminCapability::Publish, $item);
    }

    public function unpublish(User $user, Model $item): bool
    {
        return $this->allowed($user, AdminCapability::Unpublish, $item);
    }

    public function hide(User $user, Model $item): bool
    {
        return $this->allowed($user, AdminCapability::Hide, $item);
    }

    public function checkEnglish(User $user, Model $item): bool
    {
        return $this->allowed($user, AdminCapability::CheckEnglish, $item);
    }

    public function managePhotos(User $user, Model $item): bool
    {
        return $this->allowed($user, AdminCapability::ManagePhotos, $item);
    }

    /** Is this item within what the account may reach? Everything, unless a kind says otherwise. */
    protected function inScope(User $user, Model $item): bool
    {
        return true;
    }

    private function allowed(User $user, AdminCapability $capability, Model $item): bool
    {
        return AdminAccessQuery::can($user, $capability) && $this->inScope($user, $item);
    }
}
