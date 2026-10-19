<?php

namespace App\Support\Admin;

use App\Enums\AdminCapability;
use App\Enums\ContentKind;
use App\Enums\ContentTransition;
use App\Models\User;
use App\Queries\AdminAccessQuery;

/*
 * What this signed-in account may do to one project row, worked out once and
 * handed to the shared actions menu (components/admin/content-actions), so the
 * list, the dashboard queue and the project page offer the same things by the
 * same rules as ProjectPolicy.
 *
 * $project is a list row or the detail: city_id, published, hidden,
 * english_check, photos, donor_id, funded, latitude.
 */
final class ContentActions
{
    /**
     * @return array{
     *     transitions: list<ContentTransition>,
     *     blocked: array<string, string>,
     *     edit: bool,
     *     delete: bool,
     *     photos: bool,
     * }
     */
    public static function for(User $user, ContentKind $kind, object $project): array
    {
        // Only micro-projects belong to a city.
        $place = $kind !== ContentKind::Project || AdminAccessQuery::mayUsePlace($user, (int) $project->city_id);
        $may = fn (AdminCapability $capability): bool => $place && AdminAccessQuery::can($user, $capability);

        $allowed = [
            ContentTransition::Publish->value => $may(AdminCapability::Publish),
            ContentTransition::Unpublish->value => $may(AdminCapability::Unpublish),
            ContentTransition::Hide->value => $may(AdminCapability::Hide),
            ContentTransition::Unhide->value => $may(AdminCapability::Hide),
            ContentTransition::CheckEnglish->value => $may(AdminCapability::CheckEnglish),
        ];

        $transitions = array_values(array_filter(
            ContentTransition::cases(),
            fn (ContentTransition $transition): bool => $allowed[$transition->value] && $transition->appliesTo($project),
        ));

        $blockers = $kind->rowBlockers($project);
        $blocked = in_array(ContentTransition::Publish, $transitions, true) && $blockers !== []
            ? [ContentTransition::Publish->value => ProjectReadiness::describe($blockers)]
            : [];

        return [
            'transitions' => $transitions,
            'blocked' => $blocked,
            'edit' => $may(AdminCapability::Edit),
            'delete' => $may(AdminCapability::Delete),
            'photos' => $may(AdminCapability::ManagePhotos),
        ];
    }
}
