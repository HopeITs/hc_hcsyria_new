<?php

use App\Models\Project;
use App\Queries\ProjectSearch;

function publishedProject(): Project
{
    return Project::query()->whereIn('MPE_Done', [1, 2])->whereIn('MPE_Published', [1, 2])->firstOrFail();
}

/*
 * MicroProject_ID is not unique — the same number recurs across projects — so
 * a number search returns every project carrying it, as the legacy one does.
 */
function idsWithNumber(int|string $number): array
{
    return Project::query()->whereIn('MPE_Done', [1, 2])->whereIn('MPE_Published', [1, 2])
        ->where('MicroProject_ID', $number)->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();
}

it('matches a numeric term exactly against the project number', function () {
    $number = publishedProject()->MicroProject_ID;
    $ids = ProjectSearch::make()->search((string) $number)->ids();
    sort($ids);

    expect($ids)->toBe(idsWithNumber($number));
});

it('trims the term before deciding it is a project number', function () {
    $number = publishedProject()->MicroProject_ID;
    $ids = ProjectSearch::make()->search("  {$number}  ")->ids();
    sort($ids);

    expect($ids)->toBe(idsWithNumber($number));
});

it('finds a project by its partner name', function () {
    $project = Project::query()->whereIn('MPE_Done', [1, 2])->whereIn('MPE_Published', [1, 2])
        ->whereNotIn('MPE_PartnerName', ['', '0'])->firstOrFail();

    expect(ProjectSearch::make()->search($project->MPE_PartnerName)->ids())->toContain((int) $project->id);
});

it('treats LIKE wildcards literally', function () {
    $all = count(ProjectSearch::make()->ids());

    expect(count(ProjectSearch::make()->search('%')->ids()))->toBeLessThan($all)
        ->and(count(ProjectSearch::make()->search('_')->ids()))->toBeLessThan($all);
});
