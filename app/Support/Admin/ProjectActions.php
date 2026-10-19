<?php

namespace App\Support\Admin;

use App\Enums\AdminCapability;
use App\Enums\ProjectTransition;
use App\Models\User;
use App\Queries\AdminAccessQuery;

/*
 * What this signed-in account may do to one project row, worked out once and
 * handed to the shared actions menu (components/admin/project-actions), so the
 * list, the dashboard queue and the project page offer the same things by the
 * same rules as ProjectPolicy.
 *
 * $project is a list row or the detail: city_id, published, hidden,
 * english_check, photos, donor_id, funded, latitude.
 */
final class ProjectActions
{
    /**
     * @return array{
     *     transitions: list<ProjectTransition>,
     *     blocked: array<string, string>,
     *     edit: bool,
     *     delete: bool,
     *     photos: bool,
     * }
     */
    public static function for(User $user, object $project): array
    {
        $place = AdminAccessQuery::mayUsePlace($user, (int) $project->city_id);
        $may = fn (AdminCapability $capability): bool => $place && AdminAccessQuery::can($user, $capability);

        $allowed = [
            ProjectTransition::Publish->value => $may(AdminCapability::PublishProjects),
            ProjectTransition::Unpublish->value => $may(AdminCapability::UnpublishProjects),
            ProjectTransition::Hide->value => $may(AdminCapability::HideProjects),
            ProjectTransition::Unhide->value => $may(AdminCapability::HideProjects),
            ProjectTransition::CheckEnglish->value => $may(AdminCapability::CheckEnglish),
        ];

        $transitions = array_values(array_filter(
            ProjectTransition::cases(),
            fn (ProjectTransition $transition): bool => $allowed[$transition->value] && $transition->appliesTo($project),
        ));

        $blockers = ProjectReadiness::blockers($project);
        $blocked = in_array(ProjectTransition::Publish, $transitions, true) && $blockers !== []
            ? [ProjectTransition::Publish->value => ProjectReadiness::describe($blockers)]
            : [];

        return [
            'transitions' => $transitions,
            'blocked' => $blocked,
            'edit' => $may(AdminCapability::EditProjects),
            'delete' => $may(AdminCapability::DeleteProjects),
            'photos' => $may(AdminCapability::ManagePhotos),
        ];
    }
}
