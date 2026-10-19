<?php

namespace App\Support\Admin;

use App\Enums\AdminCapability;
use App\Models\User;
use App\Queries\AdminAccessQuery;

/*
 * What a person's dashboard puts first: the list of projects waiting for them.
 * Whoever publishes sees what waits to be published; whoever checks English,
 * what waits for it; whoever looks after photos, what has none; everyone else
 * the latest projects. The `tab` is one of AdminProjectIndexQuery::TABS.
 */
final class WorkQueue
{
    /** @return array{tab: string, title: string, action: string} */
    public static function for(User $user): array
    {
        return match (true) {
            AdminAccessQuery::can($user, AdminCapability::PublishProjects) => ['tab' => 'pending', 'title' => 'Waiting to be published', 'action' => 'pending'],
            AdminAccessQuery::can($user, AdminCapability::CheckEnglish) => ['tab' => 'english', 'title' => 'Waiting for the English check', 'action' => 'english'],
            AdminAccessQuery::can($user, AdminCapability::ManagePhotos) => ['tab' => 'nophoto', 'title' => 'Projects without photos', 'action' => 'nophoto'],
            default => ['tab' => 'all', 'title' => 'Latest projects', 'action' => 'all'],
        };
    }
}
