<?php

namespace App\Queries;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/*
 * The three figures on the Study Zone page, from the Study Zone attendance
 * system's own database (config/database.php, 'studyzone'): entries per city,
 * the five most common fields of education, and the split by gender. All
 * count the last three calendar months (this one and the two before).
 *
 * Legacy ran these inside the page request and fataled when the server was
 * unreachable or a window was empty. Here an unreachable or unset database
 * answers null (logged), which hides the section, and empty windows answer
 * zeros.
 */
final class StudyZoneStatisticsQuery
{
    /** Cities the attendance system holds that the map does not show. */
    private const EXCLUDED_CITY_IDS = [0, 5, 7];

    private const WINDOW_FROM = 'DATE_FORMAT(DATE_SUB(CURRENT_DATE, INTERVAL 2 MONTH), "%Y-%m-01")';

    private const WINDOW_TO = 'DATE_ADD(LAST_DAY(CURRENT_DATE), INTERVAL 1 DAY)';

    /**
     * @return array{entries: array<string, int>, education: list<array{name: string, count: int}>, gender: array{male: float, female: float}}|null
     */
    public static function figures(): ?array
    {
        if (config('database.connections.studyzone.host') === null) {
            return null;
        }

        try {
            return [
                'entries' => self::entriesPerCity(),
                'education' => self::topEducation(),
                'gender' => self::gender(),
            ];
        } catch (Throwable $e) {
            Log::warning('Study Zone statistics unavailable: '.$e->getMessage());

            return null;
        }
    }

    /** @return array<string, int> entries per city, keyed by lower-case code (aleppo, homs ...) */
    private static function entriesPerCity(): array
    {
        $rows = DB::connection('studyzone')->select(
            'SELECT hcsz_cities.code AS name, COALESCE(COUNT(hcsz_student_entries.id), 0) AS student_count
             FROM hcsz_cities
             LEFT JOIN hcsz_centers ON hcsz_cities.id = hcsz_centers.city_id
             LEFT JOIN hcsz_student_entries ON hcsz_centers.id = hcsz_student_entries.center_id
                 AND hcsz_student_entries.entry_time >= '.self::WINDOW_FROM.'
                 AND hcsz_student_entries.entry_time < '.self::WINDOW_TO.'
             WHERE hcsz_cities.id NOT IN ('.implode(',', self::EXCLUDED_CITY_IDS).')
             GROUP BY hcsz_cities.code'
        );

        return collect($rows)->mapWithKeys(fn ($row) => [strtolower($row->name) => (int) $row->student_count])->all();
    }

    /** @return list<array{name: string, count: int}> */
    private static function topEducation(): array
    {
        $rows = DB::connection('studyzone')->select(
            'SELECT JSON_UNQUOTE(JSON_EXTRACT(hcsz_educations.name, \'$.en\')) AS name, COUNT(hcsz_students.id) AS students_count
             FROM hcsz_students
             LEFT JOIN hcsz_student_entries ON hcsz_students.id = hcsz_student_entries.student_id
             LEFT JOIN hcsz_centers ON hcsz_student_entries.center_id = hcsz_centers.id
             LEFT JOIN hcsz_cities ON hcsz_centers.city_id = hcsz_cities.id
             LEFT JOIN hcsz_student_educations ON hcsz_student_educations.student_id = hcsz_students.id
             LEFT JOIN hcsz_educations ON hcsz_educations.id = hcsz_student_educations.education_id
             WHERE hcsz_student_entries.entry_time >= '.self::WINDOW_FROM.'
                 AND hcsz_student_entries.entry_time < '.self::WINDOW_TO.'
             GROUP BY hcsz_educations.name
             HAVING hcsz_educations.name IS NOT NULL
             ORDER BY students_count DESC
             LIMIT 5'
        );

        return array_values(collect($rows)->map(fn ($row) => ['name' => (string) $row->name, 'count' => (int) $row->students_count])->all());
    }

    /** @return array{male: float, female: float} percentages, with two decimals */
    private static function gender(): array
    {
        $rows = DB::connection('studyzone')->select(
            'SELECT hcsz_students.gender AS gender, COUNT(hcsz_students.id) AS student_count
             FROM hcsz_students
             LEFT JOIN hcsz_student_entries ON hcsz_students.id = hcsz_student_entries.student_id
             WHERE hcsz_student_entries.entry_time >= '.self::WINDOW_FROM.'
                 AND hcsz_student_entries.entry_time < '.self::WINDOW_TO.'
                 AND hcsz_students.gender IS NOT NULL AND hcsz_students.gender <> 0
             GROUP BY hcsz_students.gender'
        );

        $counts = collect($rows)->mapWithKeys(fn ($row) => [(int) $row->gender => (int) $row->student_count]);
        $total = $counts->sum();

        $share = fn (int $gender): float => $total > 0 ? round(($counts[$gender] ?? 0) / $total * 100, 2) : 0.0;

        return ['male' => $share(1), 'female' => $share(2)];
    }
}
