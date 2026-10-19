<?php

namespace App\Policies;

use App\Enums\AdminCapability;
use App\Models\Project;
use App\Models\User;
use App\Queries\AdminAccessQuery;

/*
 * Who may act on a micro-project in the admin. Every ability needs the role
 * (AdminCapability) and, for a given project, the project's city among the
 * cities the account is limited to (AdminAccessQuery::placeIds).
 */
class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return AdminAccessQuery::can($user, AdminCapability::ViewProjects);
    }

    public function view(User $user, Project $project): bool
    {
        return $this->allowed($user, AdminCapability::ViewProjects, $project);
    }

    /** Creating has no project yet: the role is enough, the form limits the city. */
    public function create(User $user): bool
    {
        return AdminAccessQuery::can($user, AdminCapability::CreateProjects);
    }

    public function update(User $user, Project $project): bool
    {
        return $this->allowed($user, AdminCapability::EditProjects, $project);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->allowed($user, AdminCapability::DeleteProjects, $project);
    }

    public function publish(User $user, Project $project): bool
    {
        return $this->allowed($user, AdminCapability::PublishProjects, $project);
    }

    public function unpublish(User $user, Project $project): bool
    {
        return $this->allowed($user, AdminCapability::UnpublishProjects, $project);
    }

    public function hide(User $user, Project $project): bool
    {
        return $this->allowed($user, AdminCapability::HideProjects, $project);
    }

    public function checkEnglish(User $user, Project $project): bool
    {
        return $this->allowed($user, AdminCapability::CheckEnglish, $project);
    }

    public function managePhotos(User $user, Project $project): bool
    {
        return $this->allowed($user, AdminCapability::ManagePhotos, $project);
    }

    private function allowed(User $user, AdminCapability $capability, Project $project): bool
    {
        return AdminAccessQuery::can($user, $capability)
            && AdminAccessQuery::mayUsePlace($user, (int) $project->MPE_City);
    }
}
