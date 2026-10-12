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

require __DIR__.'/../vendor/autoload.php';

use App\Models\Project;
use App\Models\WhatCategory;
use App\Queries\ActivityLogQuery;
use App\Queries\AdminProjectListQuery;
use App\Queries\CategoryQuery;
use App\Queries\InitiativeTitleListQuery;
use App\Queries\MicroProjectStatsQuery;
use App\Queries\PartnerQuery;
use App\Queries\ProjectPageQuery;
use App\Queries\ProjectSearch;
use App\Queries\StudyZoneCenterQuery;
use App\Queries\TeamMemberQuery;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$failures = [];

/*
 * An uncaught exception here was being rendered by the framework's error
 * handler and still exiting 0, which would let a broken query look like a
 * passing run. Fail loudly instead.
 */
set_exception_handler(function (Throwable $e) {
    fwrite(STDERR, "\nUNCAUGHT: ".get_class($e).': '.$e->getMessage()."\n");
    exit(1);
});

function check(string $label, bool $ok, string $detail = ''): void
{
    global $failures;
    printf("  %-58s %s%s\n", $label, $ok ? 'ok' : 'FAIL', $detail === '' ? '' : "  $detail");
    if (! $ok) {
        $failures[] = $label.($detail ? " ($detail)" : '');
    }
}

/* ===================================================================== */
echo "ProjectSearch  vs  Livewire\\MicroProjects::loadProjects()\n";
/* ===================================================================== */

/* Copied from legacy App\Livewire\MicroProjects::loadProjects() @ 6d45ef9. */
function legacyLiveProjectIds(string $search, string $donor, string $city, bool $hasVideo): array
{
    $query = Project::query()->whereIn('MPE_Done', [1, 2])->whereIn('MPE_Published', [1, 2]);

    $search = trim($search);
    if ($search !== '') {
        if (ctype_digit($search)) {
            $query->where('MicroProject_ID', $search);
        } else {
            $query->where(function ($q) use ($search) {
                $q->where('MPE_BeneficiaryName', 'like', '%'.$search.'%')
                    ->orWhere('MPE_PartnerName', 'like', '%'.$search.'%')
                    ->orWhere('MPE_PartnerName2', 'like', '%'.$search.'%')
                    ->orWhereHas('content', function ($languageQuery) use ($search) {
                        $languageQuery->where('MPE_Name', 'like', '%'.$search.'%')
                            ->orWhere('MPE_ExpSkill', 'like', '%'.$search.'%')
                            ->orWhere('MPE_Suffering', 'like', '%'.$search.'%');
                    });
            });
        }
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

    return $query->orderBy('MPE_PublishDate', 'desc')->pluck('id')->map(fn ($i) => (int) $i)->all();
}

$sampleTerms = collect(DB::connection('legacy')->select(
    "SELECT MPE_Name FROM projects_english WHERE MPE_Name <> '' ORDER BY RAND() LIMIT 3"
))->map(fn ($r) => mb_strtolower(preg_split('/\s+/', trim((string) $r->MPE_Name))[0] ?? ''))
    ->filter(fn ($w) => mb_strlen($w) >= 3)->values()->all();

/* A real project number, and a real partner name: the two search paths added since Phase 0. */
$projectNumber = (string) DB::table('projects')->whereIn('MPE_Published', [1, 2])->whereIn('MPE_Done', [1, 2])->value('MicroProject_ID');
$partnerWord = mb_strtolower(preg_split('/\s+/', trim((string) DB::table('projects')
    ->whereNotIn('MPE_PartnerName', ['', '0'])->whereIn('MPE_Published', [1, 2])->value('MPE_PartnerName')))[0] ?? '');
$sampleTerms = array_values(array_filter(array_merge($sampleTerms, [$projectNumber, " $projectNumber ", $partnerWord]),
    fn ($t) => trim($t) !== ''));

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
                if ($legacy !== $new) {
                    $divergent++;
                }
            }
        }
    }
}
check("$combos filter combinations match", $divergent === 0, "divergent=$divergent");

$first20 = ProjectSearch::make()->builder()->limit(20)->pluck('id')->all();
$first50 = ProjectSearch::make()->builder()->limit(50)->pluck('id')->all();
check('stable under a growing limit (load-more)', $first20 === array_slice($first50, 0, 20));
/*
 * Since the search covers long text (MPE_Suffering, MPE_ExpSkill), some rows
 * really contain a '%' ("50% disability"). Escaped, '%' must find exactly
 * those rows; unescaped it would find every published project.
 */
$percentIds = ProjectSearch::make()->search('%')->ids();
$literalPercentIds = Project::query()->whereIn('MPE_Done', [1, 2])->whereIn('MPE_Published', [1, 2])
    ->where(fn ($q) => $q->whereRaw("LOCATE('%', MPE_BeneficiaryName) > 0")
        ->orWhereRaw("LOCATE('%', MPE_PartnerName) > 0")
        ->orWhereRaw("LOCATE('%', MPE_PartnerName2) > 0")
        ->orWhereHas('content', fn ($c) => $c->whereRaw("LOCATE('%', MPE_Name) > 0")
            ->orWhereRaw("LOCATE('%', MPE_ExpSkill) > 0")
            ->orWhereRaw("LOCATE('%', MPE_Suffering) > 0")))
    ->pluck('id')->map(fn ($i) => (int) $i)->sort()->values()->all();
sort($percentIds);
check("wildcard '%' is escaped: matches only a literal '%'",
    $percentIds === $literalPercentIds && count($percentIds) < count(ProjectSearch::make()->ids()),
    count($percentIds).' rows contain a literal %');

/* ===================================================================== */
echo "\nTeamMemberQuery  vs  PublicController profile queries\n";
/* ===================================================================== */

$groups = ['hope', 'hope_h', 'hope_d', 'hope_l', 'disc', 'study', 'children', 'leap'];
$mismatch = [];
$rankOutOfOrder = [];
foreach ($groups as $group) {
    $legacy = DB::table('profile')->where('inactive', '0')->where('belong_to', $group)
        ->orderBy('rank')->pluck('id')->map(fn ($i) => (int) $i)->all();
    $new = TeamMemberQuery::make()->inGroup($group)->get()->pluck('id')->map(fn ($i) => (int) $i)->all();

    // Compared as sets: `rank` has ties, so the legacy order is not defined.
    $l = $legacy;
    $n = $new;
    sort($l);
    sort($n);
    if ($l !== $n) {
        $mismatch[] = $group;
    }

    $ranks = TeamMemberQuery::make()->inGroup($group)->get()->pluck('rank')->values();
    if ($ranks->toArray() !== $ranks->sort()->values()->toArray()) {
        $rankOutOfOrder[] = $group;
    }
}
check('all '.count($groups).' single-group team queries return the same rows', $mismatch === [],
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
$formerShown = $aboutLegacy->filter(fn ($p) => $p->inactive !== '0')->count();

printf("  %-58s legacy=%d  fixed=%d  former staff removed=%d\n",
    'about() orWhere bug (intended difference)',
    $aboutLegacy->count(), $aboutNew->count(), $formerShown);
check('  -> fixed query returns only active staff',
    $aboutNew->every(fn ($p) => $p->inactive === '0'));
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
$legacyName = DB::connection('legacy')->select(
    'select what_categories.name from what_categories where what_categories.id ='
    .' (SELECT what_category_id from initiatives where initiatives.id = '.$initiativeId.')'
);
check('category name for an initiative (was a raw subquery)',
    CategoryQuery::what()->nameForInitiative($initiativeId) === ($legacyName[0]->name ?? null));

/* ===================================================================== */
echo "\nStudyZoneCenterQuery  vs  six per-place raw queries\n";
/* ===================================================================== */

$mismatch = [];
foreach ([1, 2, 3, 4, 6] as $placeId) {
    $legacy = DB::connection('legacy')->select(
        'select id FROM `studyzonecenters` WHERE place_id = ? ORDER BY studyzonecenters.rank',
        [$placeId]
    );
    $legacyIds = array_map(fn ($r) => (int) $r->id, $legacy);
    $newIds = StudyZoneCenterQuery::make()->inPlace($placeId)->get()->pluck('id')->map(fn ($i) => (int) $i)->all();
    if ($legacyIds !== $newIds) {
        $mismatch[] = $placeId;
    }
}
check('all 5 per-place centre queries match, in order', $mismatch === [],
    $mismatch ? 'places '.implode(',', $mismatch) : '');

$sample = StudyZoneCenterQuery::make()->inPlace(1)->get()->first();
check('short_name derived, full name preserved',
    $sample === null || ($sample->short_name !== null && $sample->name !== null));

/* ===================================================================== */
echo "\nActivityLogQuery  vs  six per-category raw queries\n";
/* ===================================================================== */

$mismatch = [];
foreach (ActivityLogQuery::DISPLAYED as $key => $category) {
    $legacy = DB::connection('legacy')->select(
        "SELECT logs.id FROM logs LEFT JOIN users ON users.id = logs.user_id
         WHERE category = ? and year(logs.created_at) > '2021'",
        [$category]
    );
    $legacyIds = array_map(fn ($r) => (int) $r->id, $legacy);
    sort($legacyIds);

    $newIds = ActivityLogQuery::make()->forCategory($category)->get()
        ->pluck('id')->map(fn ($i) => (int) $i)->all();
    sort($newIds);

    if ($legacyIds !== $newIds) {
        $mismatch[] = "$key(".count($legacyIds).' vs '.count($newIds).')';
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
echo "\nSlug lookups  vs  the interpolated whereRaw versions\n";
/* ===================================================================== */

$slug = 'school-to-school';
$legacySlug = WhatCategory::whereRaw('replace(lower(name)," ","-") LIKE "'.$slug.'"')->first();
check('legitimate slug resolves identically',
    optional(CategoryQuery::what()->bySlug($slug))->id === optional($legacySlug)->id,
    optional($legacySlug)->name ?? '(none)');

/* The reason this was extracted: the legacy form is injectable. */
$hostile = 'nothing-matches" OR "1"="1';
$legacyHostile = WhatCategory::whereRaw('replace(lower(name)," ","-") LIKE "'.$hostile.'"')->first();
$newHostile = CategoryQuery::what()->bySlug($hostile);

printf("  %-58s legacy=%s  bound=%s\n", 'hostile slug (injection)',
    $legacyHostile->name ?? '(none)', $newHostile->name ?? '(none)');
check('  -> legacy form was exploitable', $legacyHostile !== null);
check('  -> bound form returns nothing', $newHostile === null);

check('place slug resolves', PartnerQuery::placeBySlug('homs') !== null);
check('place slug injection returns nothing',
    PartnerQuery::placeBySlug('nothing" OR "1"="1') === null);

/* ===================================================================== */
echo "\nMicroProjectStatsQuery  vs  micro_project_new_test() aggregates\n";
/* ===================================================================== */
echo "\nProjectPageQuery  vs  ProjectDonor / HopeProjects lines in the programme methods\n";
/* ===================================================================== */

foreach ([[true, 1], [false, 0]] as [$active, $flag]) {
    $legacyIds = DB::connection('legacy')->table('projects_donors')->where('what_category_id', 10)
        ->where('isActive', $flag)->orderBy('rank')->pluck('donor_id')->map(fn ($v) => (int) $v)->all();
    check('micro funders, '.($active ? 'ongoing' : 'previous').', in rank order',
        ProjectPageQuery::funders(10, $active)->pluck('id')->map(fn ($v) => (int) $v)->all() === $legacyIds);
}

$legacyBrief = DB::connection('legacy')->table('hope_projects')->where('what_category_id', 10)->first();
$legacyMedia = DB::connection('legacy')->table('hope_projects_media')->where('project_id', $legacyBrief->id)
    ->orderBy('mediaOrder')->pluck('mediaURL')->all();
$brief = ProjectPageQuery::brief(10);
check('micro brief and its media, in media order',
    $brief->name === $legacyBrief->name && $brief->hopeProjectMedia->pluck('mediaURL')->all() === $legacyMedia);

/* ===================================================================== */

$legacyCity = collect(DB::connection('legacy')->select(
    'SELECT MPE_City, COUNT(*) as total FROM projects
     WHERE MPE_City in (1,2,3,4,6,7,8) AND MPE_Done in (1,2) AND MPE_Published in (1,2)
     GROUP BY MPE_City'
))->mapWithKeys(fn ($r) => [(int) $r->MPE_City => (int) $r->total])->sortKeys()->all();
check('per-city project counts match',
    MicroProjectStatsQuery::make()->countsByCity()
        ->mapWithKeys(fn ($v, $k) => [(int) $k => (int) $v])->sortKeys()->all() === $legacyCity);

$legacyCategories = collect(DB::connection('legacy')->select(
    'SELECT subcategory.Name, COUNT(projects.id) AS project_count FROM projects
     LEFT JOIN subcategory on subcategory.ID = projects.SubCategory_ID
     WHERE projects.MPE_Published in (1,2) AND projects.MPE_Done in (1)
     AND projects.SubCategory_ID not in (1,2) GROUP BY subcategory.Name
     HAVING subcategory.Name is not null ORDER BY project_count DESC LIMIT 5'
))->mapWithKeys(fn ($r) => [(string) $r->Name => (int) $r->project_count])->all();
$newCategories = MicroProjectStatsQuery::make()->topCategories()
    ->mapWithKeys(fn ($r) => [(string) $r->Name => (int) $r->project_count])->all();
// Tied counts have no fixed order in legacy, so compare the counts in order and the names as a set.
check('top five categories match (counts in order, names as a set)',
    array_values($newCategories) === array_values($legacyCategories)
    && collect($newCategories)->sortKeys()->all() === collect($legacyCategories)->sortKeys()->all());

$legacyAge = collect(DB::connection('legacy')->select(
    'SELECT age.Name, COUNT(projects.id) AS project_count FROM projects
     LEFT JOIN age ON (CASE WHEN YEAR(CURDATE()) - projects.DOB = 0 THEN 1
        ELSE YEAR(CURDATE()) - projects.DOB END) BETWEEN age.Min AND age.Max
     WHERE projects.MPE_Published in (1,2) AND projects.MPE_Done in (1)
     GROUP BY age.Name HAVING age.Name is not null ORDER BY age.ID'
))->mapWithKeys(fn ($r) => [(string) $r->Name => (int) $r->project_count])->all();
check('per-age-band counts match, in order',
    MicroProjectStatsQuery::make()->countsByAgeRange()
        ->mapWithKeys(fn ($r) => [(string) $r->Name => (int) $r->project_count])->all() === $legacyAge);

/* Copied from PublicController::getPublishedProjectSustainabilityRate() @ 6d45ef9. */
$eligible = fn () => DB::connection('legacy')->table('projects')->where('MPE_Published', 1)
    ->whereDate('MPE_FundedDate', '<=', now()->subMonths(9));
$legacyTotal = $eligible()->count();
$legacyRate = $legacyTotal > 0 ? (int) round($eligible()->where('MPE_Done', 1)->count() * 100 / $legacyTotal) : 0;
check('sustainable rate matches',
    MicroProjectStatsQuery::make()->sustainableRate() === $legacyRate, (string) $legacyRate);

/* ===================================================================== */
echo "\nInitiativeTitleListQuery  vs  four search_list_* methods\n";
/* ===================================================================== */

$mismatch = [];
foreach ([
    'children_uni' => InitiativeTitleListQuery::CHILDREN_UNI,
    'study_zone' => InitiativeTitleListQuery::STUDY_ZONE,
    'leap' => InitiativeTitleListQuery::LEAP,
    'disc' => InitiativeTitleListQuery::DISC,
] as $label => $categoryId) {
    $legacy = collect(DB::connection('legacy')->select(
        'SELECT DISTINCT title FROM initiatives
         inner join initiatives_english on initiatives_english.initiative_id = initiatives.id
         where what_category_id = ?',
        [$categoryId]
    ))->pluck('title')->sort()->values()->all();

    $new = InitiativeTitleListQuery::make()->forCategory($categoryId)->titles()->sort()->values()->all();

    if ($legacy !== $new) {
        $mismatch[] = "$label(".count($legacy).' vs '.count($new).')';
    }
}
check('all 4 typeahead title lists match', $mismatch === [],
    $mismatch ? implode(' ', $mismatch) : '');

/* ===================================================================== */
echo "\nAdminProjectListQuery  vs  three per-city admin queries\n";
/* ===================================================================== */

$mismatch = [];
foreach ([3 => 'Aleppo', 1 => 'Homs', 2 => 'Damascus'] as $cityId => $label) {
    $legacy = collect(DB::connection('legacy')->select(
        'SELECT projects.id as NO, count(images.id) as images
         FROM projects
         LEFT JOIN projects_english on projects.id = projects_english.project_id
         LEFT JOIN donors on donors.id = projects.MPE_Donor
         LEFT JOIN projecttype on projects.projecttype_id = projecttype.id
         LEFT JOIN images on projects.id = images.project_id
         WHERE projects.MPE_City = ? AND `MPE_Published` = 1 GROUP BY projects.id',
        [$cityId]
    ))->mapWithKeys(fn ($r) => [(int) $r->NO => (int) $r->images])->sortKeys()->all();

    $new = AdminProjectListQuery::make()->inCity($cityId)->get()
        ->mapWithKeys(fn ($r) => [(int) $r->NO => (int) $r->images])->sortKeys()->all();

    if ($legacy !== $new) {
        $mismatch[] = "$label(".count($legacy).' vs '.count($new).')';
    }
}
check('all 3 per-city admin tables match, with image counts', $mismatch === [],
    $mismatch ? implode(' ', $mismatch) : '');

check('cancelled and pending tabs return rows',
    AdminProjectListQuery::cancelled() !== null && AdminProjectListQuery::pending() !== null);

/* ===================================================================== */
echo "\nPartnerQuery  vs  about_us() and family_to_family()\n";
/* ===================================================================== */

check('public donors match',
    PartnerQuery::publicDonors()->pluck('id')->sort()->values()->all()
    === DB::table('donors')->whereNotIn('id', [9, 10, 19])->pluck('id')
        ->map(fn ($i) => (int) $i)->sort()->values()->all());

check('micro donors match',
    PartnerQuery::microDonors()->pluck('id')->sort()->values()->all()
    === DB::table('donors')->where('is_micro', '1')->whereNotIn('id', [9, 10])->pluck('id')
        ->map(fn ($i) => (int) $i)->sort()->values()->all());

check('hope values match',
    PartnerQuery::hopeValues()->count() === DB::table('hope_values')->count());

check('family partners match',
    PartnerQuery::familyPartners()->count() === DB::table('familypartners')->count());

echo "\n";
if ($failures !== []) {
    printf("%d CHECK(S) FAILED:\n", count($failures));
    foreach ($failures as $f) {
        echo "  $f\n";
    }
    exit(1);
}
echo "ALL QUERY CLASSES VERIFIED\n";
exit(0);
