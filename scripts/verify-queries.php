<?php

/*
 * Proves App\Queries\ProjectSearch returns exactly what the legacy
 * PublicController::get_projects_IDs() returned, across a matrix of inputs,
 * using the real database.
 *
 *   php scripts/verify-queries.php
 *
 * Read-only. Exits non-zero on any divergence.
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Queries\InitiativeSearch;
use App\Queries\ProjectSearch;
use Illuminate\Support\Facades\DB;

/**
 * The legacy query, reproduced verbatim from PublicController so the
 * comparison is against what actually shipped — string concatenation,
 * addslashes and all.
 *
 * @return array<int, int>
 */
function legacyProjectIds(string $searchStr, string $donor, string $city, bool $video): array
{
    $langTable = 'projects_english';

    $donorString = $donor === '' ? '' : ' and MPE_Donor like ' . $donor;
    $cityString  = $city === ''  ? '' : ' and MPE_City like ' . $city;
    $videoString = $video === false ? '' : " and MPE_Video <> '0'";

    $microproject_id_string = '';
    if ($searchStr != '' && is_numeric($searchStr)) {
        $microproject_id_string = ' or projects.MicroProject_ID = ' . $searchStr;
    }

    $rows = DB::select('select projects.id from projects inner join ' . $langTable . '
                                on ' . $langTable . '.project_id = projects.id
                                where (LOWER(MPE_Name) like \'%' . $searchStr . '%\'
                                ' . $microproject_id_string . '
                                or LOWER(MPE_BeneficiaryName) like \'%' . $searchStr . '%\'
                                or LOWER(MPE_PartnerName) like \'%' . $searchStr . '%\'
                                or LOWER(MPE_PartnerName2) like \'%' . $searchStr . '%\')
                                and MPE_Published in (1,2)
                                and MPE_Done in (1,2) ' . $donorString . $cityString . $videoString . '
                                order by MPE_PublishDate desc ');

    return array_map(fn($r) => (int) $r->id, $rows);
}

/* Build a realistic matrix: terms drawn from actual data, plus edge cases. */
$sampleTerms = collect(DB::select(
    "SELECT MPE_Name FROM projects_english WHERE MPE_Name <> '' ORDER BY RAND() LIMIT 4"
))->map(function ($r) {
    $words = preg_split('/\s+/', trim((string) $r->MPE_Name));
    return mb_strtolower($words[0] ?? '');
})->filter(fn($w) => mb_strlen($w) >= 3)->values()->all();

$terms = array_merge(['', 'a', 'school', 'water'], $sampleTerms);

$donorIds = collect(DB::select('SELECT DISTINCT MPE_Donor d FROM projects WHERE MPE_Donor IS NOT NULL LIMIT 3'))
    ->map(fn($r) => (string) $r->d)->all();
$donors = array_merge([''], $donorIds);

$cities = ['', '1', '2', '3', '4', '7'];

$checked = $failed = 0;
$failures = [];

foreach ($terms as $term) {
    foreach ($donors as $donor) {
        foreach ($cities as $city) {
            foreach ([false, true] as $video) {
                $checked++;

                $legacy = legacyProjectIds((string) $term, (string) $donor, (string) $city, $video);

                $new = ProjectSearch::make()
                    ->search($term === '' ? null : $term)
                    ->forDonor($donor === '' ? null : $donor)
                    ->inCity($city === '' ? null : $city)
                    ->onlyWithVideo($video)
                    ->ids();

                /*
                 * Compare as sets, not sequences. The legacy query orders only
                 * by MPE_PublishDate, and 220 dates are shared by more than one
                 * project, so the order of tied rows is left to MySQL and is not
                 * guaranteed — two semantically identical queries were observed
                 * returning tied rows in different orders. Ordering is asserted
                 * separately below: dates must descend, and the sequence must be
                 * reproducible.
                 */
                sort($legacy);
                $newSorted = $new;
                sort($newSorted);

                if ($legacy !== $newSorted) {
                    $failed++;
                    if (count($failures) < 5) {
                        $failures[] = sprintf(
                            "term=%-12s donor=%-4s city=%-3s video=%s | legacy=%d new=%d | only-legacy=%s only-new=%s",
                            var_export($term, true), $donor ?: '-', $city ?: '-', $video ? 'y' : 'n',
                            count($legacy), count($newSorted),
                            json_encode(array_slice(array_values(array_diff($legacy, $newSorted)), 0, 5)),
                            json_encode(array_slice(array_values(array_diff($newSorted, $legacy)), 0, 5))
                        );
                    }
                }
            }
        }
    }
}

printf("%d input combinations checked, %d divergent result sets\n", $checked, $failed);

/* Ordering: dates must descend, and the sequence must be reproducible. */
$rows = ProjectSearch::make()->builder()->limit(400)->get(['projects.id', 'projects.MPE_PublishDate']);
$descending = true;
$previous = null;
foreach ($rows as $row) {
    $current = $row->MPE_PublishDate;
    if ($previous !== null && $current !== null && $current > $previous) {
        $descending = false;
        break;
    }
    $previous = $current ?? $previous;
}
printf("publish dates descending: %s\n", $descending ? 'yes' : 'NO');

$runA = ProjectSearch::make()->ids();
$runB = ProjectSearch::make()->ids();
printf("new query is deterministic across runs: %s\n", $runA === $runB ? 'yes' : 'NO');

$legacyA = legacyProjectIds('', '', '', false);
$legacyB = legacyProjectIds('', '', '', false);
printf("legacy query is deterministic across runs: %s%s\n",
    $legacyA === $legacyB ? 'yes' : 'NO',
    $legacyA === $legacyB ? '' : ' (ties ordered arbitrarily - pagination can repeat or skip rows)');

if (! $descending || $runA !== $runB) {
    $failed++;
}

/* InitiativeSearch against its own legacy query. */
$initiativeTerms = array_merge(['', 'a', 'school', 'women', 'caritas'], $sampleTerms);
$initChecked = $initFailed = 0;

foreach ($initiativeTerms as $term) {
    $initChecked++;

    $rows = DB::select('select initiatives.id  from initiatives inner join initiatives_english
                                on initiatives_english.initiative_id = initiatives.id
                                where (LOWER(title) like \'%' . $term . '%\'
                                or LOWER(organization) like \'% ' . $term . ' %\'
                                or LOWER(description) like \'%' . $term . '%\'
                                or LOWER(content) like \'%' . $term . '%\')');
    $legacy = array_map(fn($r) => (int) $r->id, $rows);
    sort($legacy);

    $new = InitiativeSearch::make()->search($term === '' ? null : $term)->ids();
    sort($new);

    if ($legacy !== $new) {
        $initFailed++;
        $failures[] = sprintf(
            'initiative term=%-12s legacy=%d new=%d only-legacy=%s only-new=%s',
            var_export($term, true), count($legacy), count($new),
            json_encode(array_slice(array_values(array_diff($legacy, $new)), 0, 5)),
            json_encode(array_slice(array_values(array_diff($new, $legacy)), 0, 5))
        );
    }
}

printf("\n%d initiative terms checked, %d divergent\n", $initChecked, $initFailed);
$failed += $initFailed;

/* Separately: the injection case the legacy query could not express safely. */
$hostile = "' OR '1'='1";
$safe = ProjectSearch::make()->search($hostile)->ids();
printf("\nhostile term %s -> %d results (parameterised, no error)\n", var_export($hostile, true), count($safe));

$wildcard = ProjectSearch::make()->search('%')->ids();
$all = ProjectSearch::make()->ids();
printf("wildcard term '%%' -> %d results vs %d unfiltered (escaped, so not 'match everything')\n",
    count($wildcard), count($all));

if ($failed > 0) {
    echo "\nFAILURES:\n";
    foreach ($failures as $f) echo "  $f\n";
    exit(1);
}

echo "\nEQUIVALENT to the legacy query across all combinations\n";
exit(0);
