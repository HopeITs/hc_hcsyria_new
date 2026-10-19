<?php

namespace App\Support\Admin;

/*
 * What a project still needs before it goes online. One list, used by the
 * Publish guard, the checklist on the project page and the "not ready" note
 * on a row, so they never disagree.
 *
 * Blocking: the English text checked (legacy's own rule), at least one photo
 * and a donor (the public page shows both; legacy let a project go online
 * without them). Advisory only: the fund date and the map location, because
 * over a thousand online projects have no pin.
 *
 * $project is a list row or the detail: english_check, photos, donor_id,
 * funded, latitude.
 */
final class ProjectReadiness
{
    public const BLOCKING = ['english', 'photos', 'donor'];

    /** @return array<string, bool> check => met, in the order the checklist shows them */
    public static function checks(object $project): array
    {
        return [
            'english' => (int) $project->english_check === 1,
            'photos' => (int) $project->photos > 0,
            'donor' => (int) ($project->donor_id ?? 0) > 0,
            'funded' => ! empty($project->funded),
            'location' => self::located($project->latitude ?? null),
        ];
    }

    /** @return list<string> the blocking checks that are not met */
    public static function blockers(object $project): array
    {
        return array_values(array_filter(
            self::BLOCKING,
            fn (string $key): bool => ! self::checks($project)[$key],
        ));
    }

    public static function describe(array $keys): string
    {
        return implode(', ', array_map(fn (string $key): string => __('admin.needs.'.$key), $keys));
    }

    /** Legacy stores "no location" as the text 0. */
    private static function located(mixed $latitude): bool
    {
        return $latitude !== null && $latitude !== '' && (float) $latitude !== 0.0;
    }
}
