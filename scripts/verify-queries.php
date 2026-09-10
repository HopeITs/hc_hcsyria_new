<?php

/*
 * Runs each extracted query class side by side with the legacy query it
 * replaces, against the real database, and reports any divergence.
 *
 *   php scripts/verify-queries.php
 *
 * Read-only. Exits non-zero if a class diverges in a way that was not an
 * intended fix. Intended fixes are asserted explicitly instead.
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Project;
use App\Queries\ActivityLogQuery;
use App\Queries\CategoryQuery;
use App\Queries\ProjectSearch;
use App\Queries\StudyZoneCenterQuery;
use App\Queries\TeamMemberQuery;
use Illuminate\Support\Facades\DB;

$failures = [];

function check(string $label, bool $ok, string $detail = ''): void
{
    global $failures;
    printf("  %-58s %s%s\n", $label, $ok ? 'ok' : 'FAIL', $detail === '' ? '' : "  $detail");
    if (! $ok) $failures[] = $label . ($detail ? " ($detail)" : '');
}

/* ===================================================================== */
echo "ProjectSearch  vs  Livewire\\MicroProjects::loadProjects()\n";
/* ===================================================================== */

function legacyLiveProjectIds(string $search, string $donor, string $city, bool $hasVideo): array
{
    $query = Project::query()->whereIn('MPE_Done', [1, 2])->whereIn('MPE_Published', [1, 2]);

    if ($search !== '') {
        $query->whereHas('content', function ($q) use ($search) {
            $q->where('MPE_Name', 'like', '%' . $search . '%')
                ->orWhere('MPE_BeneficiaryName', 'like', '%' . $search . '%');
        });
    }
    if ($donor !== '') $query->where('MPE_Donor', $donor);
    if ($city !== '')  $query->where('MPE_City', $city);
    if ($hasVideo)     $query->where('MPE_Video', '!=', '0')->where('MPE_Video', '!=', '');

    return $query->orderBy('MPE_PublishDate', 'desc')->pluck('id')->map(fn($i) => (int) $i)->all();
}

$sampleTerms = collect(DB::select(
    "SELECT MPE_Name FROM projects_english WHERE MPE_Name <> '' ORDER BY RAND() LIMIT 3"
))->map(fn($r) => mb_strtolower(preg_split('/\s+/', trim((string) $r->MPE_Name))[0] ?? ''))
  ->filter(fn($w) => mb_strlen($w) >= 3)->values()->all();

$divergent = 0;
$combos = 0;
foreach (array_merge(['', 'a', 'school'], $sampleTerms) as $term) {
    foreach (['', '1', '5'] as $donor) {
        foreach (['', '1', '3'] as $city) {
            foreach ([false, true] as $video) {
                $combos++;
                $legacy = legacyLiveProjectIds((string) $term, $donor, $city, $video);
                $new = ProjectSearch::make()->search($term ?: null)->forDonor($donor ?: null)
                    ->inCity($city ?: null)->onlyWithVideo($video)->ids();
                sort($legacy);
                sort($new);
                if ($legacy !== $new) $divergent++;
            }
        }
    }
}
check("$combos filter combinations match", $divergent === 0, "divergent=$divergent");

$first20 = ProjectSearch::make()->builder()->limit(20)->pluck('id')->all();
$first50 = ProjectSearch::make()->builder()->limit(50)->pluck('id')->all();
check('stable under a growing limit (load-more)', $first20 === array_slice($first50, 0, 20));
check("wildcard '%' is escaped, not a match-all",
    count(ProjectSearch::make()->search('%')->ids()) === 0);

/* ===================================================================== */
echo "\nTeamMemberQuery  vs  PublicController profile queries\n";
/* ===================================================================== */

$groups = ['hope', 'hope_h', 'hope_d', 'hope_l', 'disc', 'study', 'children', 'leap'];
$mismatch = [];
$rankOutOfOrder = [];
foreach ($groups as $group) {
    $legacy = DB::table('profile')->where('inactive', '0')->where('belong_to', $group)
        ->orderBy('rank')->pluck('id')->map(fn($i) => (int) $i)->all();
    $new = TeamMemberQuery::make()->inGroup($group)->get()->pluck('id')->map(fn($i) => (int) $i)->all();

    // Compared as sets: `rank` has ties, so the legacy order is not defined.
    $l = $legacy; $n = $new; sort($l); sort($n);
    if ($l !== $n) $mismatch[] = $group;

    $ranks = TeamMemberQuery::make()->inGroup($group)->get()->pluck('rank')->values();
    if ($ranks->toArray() !== $ranks->sort()->values()->toArray()) $rankOutOfOrder[] = $group;
}
check('all ' . count($groups) . ' single-group team queries return the same rows', $mismatch === [],
    $mismatch ? implode(',', $mismatch) : '');
check('team results are ordered by rank', $rankOutOfOrder === [],
    $rankOutOfOrder ? implode(',', $rankOutOfOrder) : '');
check('team ordering is deterministic across runs',
    TeamMemberQuery::make()->inGroup('hope')->get()->pluck('id')->all()
    === TeamMemberQuery::make()->inGroup('hope')->get()->pluck('id')->all());

/* about() combined its groups with orWhere, which escaped the active filter. */
$aboutLegacy = DB::table('profile')->where('inactive', '0')->where('belong_to', 'homs')
    ->orWhere('belong_to', 'study')->orWhere('belong_to', 'disc')->orderBy('rank')->get();
$aboutNew = TeamMemberQuery::make()->inGroup(['homs', 'study', 'disc'])->get();
$formerShown = $aboutLegacy->filter(fn($p) => $p->inactive !== '0')->count();

printf("  %-58s legacy=%d  fixed=%d  former staff removed=%d\n",
    'about() orWhere bug (intended difference)',
    $aboutLegacy->count(), $aboutNew->count(), $formerShown);
check('  -> fixed query returns only active staff',
    $aboutNew->every(fn($p) => $p->inactive === '0'));
check('  -> fixed query is a subset of the legacy result',
    $aboutNew->pluck('id')->diff($aboutLegacy->pluck('id'))->isEmpty());

/* ===================================================================== */
echo "\nCategoryQuery  vs  the admin form lookups\n";
/* ===================================================================== */

$legacySubset = DB::table('what_categories')->whereIn('id', [1, 3, 4, 8, 18])
    ->orderBy('id')->pluck('name', 'id')->all();
check('what categories, restricted subset',
    CategoryQuery::what()->only([1, 3, 4, 8, 18])->options()->all() == $legacySubset);

check('who categories, all',
    CategoryQuery::who()->options()->all() ==
    DB::table('who_categories')->orderBy('id')->pluck('name', 'id')->all());

$initiativeId = (int) DB::table('initiatives')->whereNotNull('what_category_id')->value('id');
$legacyName = DB::select(
    'select what_categories.name from what_categories where what_categories.id ='
    . ' (SELECT what_category_id from initiatives where initiatives.id = ' . $initiativeId . ')'
);
check('category name for an initiative (was a raw subquery)',
    CategoryQuery::what()->nameForInitiative($initiativeId) === ($legacyName[0]->name ?? null));

/* ===================================================================== */
echo "\nStudyZoneCenterQuery  vs  six per-place raw queries\n";
/* ===================================================================== */

$mismatch = [];
foreach ([1, 2, 3, 4, 6] as $placeId) {
    $legacy = DB::select(
        'select id FROM `studyzonecenters` WHERE place_id = ? ORDER BY studyzonecenters.rank',
        [$placeId]
    );
    $legacyIds = array_map(fn($r) => (int) $r->id, $legacy);
    $newIds = StudyZoneCenterQuery::make()->inPlace($placeId)->get()->pluck('id')->map(fn($i) => (int) $i)->all();
    if ($legacyIds !== $newIds) $mismatch[] = $placeId;
}
check('all 5 per-place centre queries match, in order', $mismatch === [],
    $mismatch ? 'places ' . implode(',', $mismatch) : '');

$sample = StudyZoneCenterQuery::make()->inPlace(1)->get()->first();
check('short_name derived, full name preserved',
    $sample === null || ($sample->short_name !== null && $sample->name !== null));

/* ===================================================================== */
echo "\nActivityLogQuery  vs  six per-category raw queries\n";
/* ===================================================================== */

$mismatch = [];
foreach (ActivityLogQuery::DISPLAYED as $key => $category) {
    $legacy = DB::select(
        "SELECT logs.id FROM logs LEFT JOIN users ON users.id = logs.user_id
         WHERE category = ? and year(logs.created_at) > '2021'",
        [$category]
    );
    $legacyIds = array_map(fn($r) => (int) $r->id, $legacy);
    sort($legacyIds);

    $newIds = ActivityLogQuery::make()->forCategory($category)->get()
        ->pluck('id')->map(fn($i) => (int) $i)->all();
    sort($newIds);

    if ($legacyIds !== $newIds) {
        $mismatch[] = "$key(" . count($legacyIds) . ' vs ' . count($newIds) . ')';
    }
}
check('all 6 category log queries return the same rows', $mismatch === [],
    $mismatch ? implode(' ', $mismatch) : '');

$screen = ActivityLogQuery::forAdminScreen();
check('one grouped query covers every displayed category',
    array_keys($screen) === array_keys(ActivityLogQuery::DISPLAYED));

$projects = $screen['projectsLog'];
$ordered = $projects->pluck('created_at')->filter()->values();
$isDescending = $ordered->toArray() === $ordered->sortDesc()->values()->toArray();
check('projects log is ordered (legacy had no ORDER BY here)', $isDescending);

/* ===================================================================== */
echo "\n";
if ($failures !== []) {
    printf("%d CHECK(S) FAILED:\n", count($failures));
    foreach ($failures as $f) echo "  $f\n";
    exit(1);
}
echo "ALL QUERY CLASSES VERIFIED\n";
exit(0);
