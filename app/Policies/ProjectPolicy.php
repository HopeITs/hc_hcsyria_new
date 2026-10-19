<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Queries\AdminAccessQuery;
use Illuminate\Database\Eloquent\Model;

/*
 * Micro-projects: the rules of ContentPolicy, and a project's city must be among
 * the cities the account is limited to (AdminAccessQuery::placeIds).
 */
class ProjectPolicy extends ContentPolicy
{
    protected function inScope(User $user, Model $item): bool
    {
        return $item instanceof Project && AdminAccessQuery::mayUsePlace($user, (int) $item->MPE_City);
    }
}
