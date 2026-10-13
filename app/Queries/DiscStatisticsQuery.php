<?php

namespace App\Queries;

use Illuminate\Support\Facades\DB;

/*
 * The DISC learner counts shown beside the map: applied, completed and
 * employed learners at each of the three centres.
 *
 * They come from the get_statistics() stored procedure, which reads the
 * three centres' own databases (hcsyria_disc_a, _h, _d). The procedure
 * answers with every figure NULL when those databases are missing, so no
 * figures at all is a normal answer, and null is returned for it.
 */
final class DiscStatisticsQuery
{
    private const CENTRES = ['hdisc', 'ddisc', 'adisc'];

    /**
     * The three figures per centre, keyed by centre (hdisc, ddisc, adisc),
     * or null when there are none.
     *
     * @return array<string, array{applied: ?int, completed: ?int, employed: ?int}>|null
     */
    public static function figures(): ?array
    {
        $row = DB::select('CALL get_statistics()')[0] ?? null;

        if ($row === null) {
            return null;
        }

        $figure = fn (string $column): ?int => isset($row->{$column}) ? (int) $row->{$column} : null;

        $figures = [];

        foreach (self::CENTRES as $centre) {
            $figures[$centre] = [
                'applied' => $figure("{$centre}_applied_students"),
                'completed' => $figure("{$centre}_completed_students"),
                'employed' => $figure("{$centre}_employed_students"),
            ];
        }

        return collect($figures)->flatten()->filter(fn ($value) => $value !== null)->isEmpty() ? null : $figures;
    }
}
