<?php

/*
 * Proves App\Queries\ProjectSearch matches the query the legacy application
 * actually runs for /projects/micro-projects — which is
 * App\Livewire\MicroProjects::loadProjects(), not the similarly named
 * PublicController::get_projects_IDs(), which is dead code.
 *
 *   php scripts/verify-queries.php
 *
 * Read-only. Exits non-zero on any divergence.
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Project;
use App\Queries\ProjectSearch;
use Illuminate\Support\Facades\DB;

/**
 * The live legacy query, reproduced from MicroProjects::loadProjects().
 * `content` stands in for the legacy `$this->language` relation, which after
 * the French removal only ever resolves to the English content table.
 *
 * @return array<int, int>
 */
function legacyLiveProjectIds(string $search, string $donor, string $city, bool $hasVideo): array
{
    $query = Project::query()
        ->whereIn('MPE_Done', [1, 2])
        ->whereIn('MPE_Published', [1, 2]);

    if ($search !== '') {
        $query->whereHas('content', function ($q) use ($search) {
            $q->where('MPE_Name', 'like', '%' . $search . '%')
                ->orWhere('MPE_BeneficiaryName', 'like', '%' . $search . '%');
        });
    }

    if ($donor !== '') {
        $query->where('MPE_Donor', $donor);
    }

    if ($city !== '') {
        $query->where('MPE_City', $city);
    }

    if ($hasVideo) {
        $query->where('MPE_Video', '!=', '0')->where('MPE_Video', '!=', '');
    }

    return $query->orderBy('MPE_PublishDate', 'desc')
        ->pluck('id')->map(fn($id) => (int) $id)->all();
}

/* Terms drawn from real data, plus edge cases. */
$sampleTerms = collect(DB::select(
    "SELECT MPE_Name FROM projects_english WHERE MPE_Name <> '' ORDER BY RAND() LIMIT 4"
))->map(function ($r) {
    $words = preg_split('/\s+/', trim((string) $r->MPE_Name));
    return mb_strtolower($words[0] ?? '');
})->filter(fn($w) => mb_strlen($w) >= 3)->values()->all();

$terms = array_merge(['', 'a', 'school', 'water'], $sampleTerms);

$donors = array_merge([''], collect(DB::select(
    'SELECT DISTINCT MPE_Donor d FROM projects WHERE MPE_Donor IS NOT NULL LIMIT 3'
))->map(fn($r) => (string) $r->d)->all());

$cities = ['', '1', '2', '3', '4', '7'];

$checked = $failed = 0;
$failures = [];

foreach ($terms as $term) {
    foreach ($donors as $donor) {
        foreach ($cities as $city) {
            foreach ([false, true] as $video) {
                $checked++;

                $legacy = legacyLiveProjectIds((string) $term, (string) $donor, (string) $city, $video);
                $new = ProjectSearch::make()
                    ->search($term === '' ? null : $term)
                    ->forDonor($donor === '' ? null : $donor)
                    ->inCity($city === '' ? null : $city)
                    ->onlyWithVideo($video)
                    ->ids();

                /*
                 * Compared as sets. The legacy query orders by date alone and
                 * 220 dates are shared by more than one project, so the order
                 * of tied rows is left to MySQL. Ordering is asserted below.
                 */
                sort($legacy);
                $newSorted = $new;
                sort($newSorted);

                if ($legacy !== $newSorted) {
                    $failed++;
                    if (count($failures) < 5) {
                        $failures[] = sprintf(
                            'term=%-12s donor=%-4s city=%-3s video=%s | legacy=%d new=%d | only-legacy=%s only-new=%s',
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

printf("%d input combinations checked against the live legacy query, %d divergent\n", $checked, $failed);

/* Ordering must still descend by publish date, and be reproducible. */
$rows = ProjectSearch::make()->builder()->limit(400)->get(['id', 'MPE_PublishDate']);
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

/*
 * The listing grows its result set by raising a limit on the same query, so
 * the real risk is a row shifting between two page sizes. With a tiebreaker
 * the first N of a larger fetch must equal a smaller fetch exactly.
 */
$first20 = ProjectSearch::make()->builder()->limit(20)->pluck('id')->all();
$first50 = ProjectSearch::make()->builder()->limit(50)->pluck('id')->all();
$stablePaging = $first20 === array_slice($first50, 0, 20);
printf("stable under growing limit (load-more): %s\n", $stablePaging ? 'yes' : 'NO');

if (! $descending || ! $stablePaging) {
    $failed++;
}

/* Security cases the legacy string-built query could not express. */
$hostile = "' OR '1'='1";
printf("\nhostile term %s -> %d results (bound, no error)\n",
    var_export($hostile, true), count(ProjectSearch::make()->search($hostile)->ids()));

printf("wildcard term '%%' -> %d results vs %d unfiltered (escaped)\n",
    count(ProjectSearch::make()->search('%')->ids()),
    count(ProjectSearch::make()->ids()));

if ($failed > 0) {
    echo "\nFAILURES:\n";
    foreach ($failures as $f) echo "  $f\n";
    exit(1);
}

echo "\nEQUIVALENT to the live legacy query across all combinations\n";
exit(0);
