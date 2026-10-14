<?php

namespace App\Queries;

use App\Models\Donor;
use App\Models\Place;
use App\Models\Project;
use App\Support\PlaceName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/*
 * What a signed-in member sees on /projects/micro-projects: the filter
 * choices, the suggestions for the search box, a page of projects, the pins
 * for the map and the project behind a deep link. Derived from
 * App\Livewire\MicroProjects (legacy commit 6d45ef9); which projects match
 * a search is ProjectSearch's business.
 */
final class MemberProjectQuery
{
    /** The cities offered in the filter: the ones the programme works in. */
    private const CITY_IDS = [1, 2, 3, 4, 6, 7, 8];

    /** Marks a missing partner: the column holds '0', not NULL. */
    private const NO_PARTNER = '0';

    /** Where the search box's suggestions are kept, and for how long: they read every listed project. */
    public const SUGGESTIONS_KEY = 'micro-projects.suggestions';

    private const SUGGESTIONS_MINUTES = 10;

    /**
     * The cities of the filter, each with the slug that stands for it in an address.
     *
     * @return Collection<int, Place>
     */
    public static function cities(): Collection
    {
        $cities = Place::query()->whereIn('id', self::CITY_IDS)->orderBy('rank')->get(['id', 'name'])
            ->each(fn (Place $place) => $place->name = PlaceName::of($place->name));

        return self::withSlugs($cities);
    }

    /**
     * The donors of the filter, each with its slug.
     *
     * @return Collection<int, Donor>
     */
    public static function donors(): Collection
    {
        return self::withSlugs(Donor::query()->orderBy('name')->get(['id', 'name']));
    }

    /** The id of the city a slug stands for; null for any other text. */
    public static function cityId(string $slug): ?int
    {
        return self::cities()->firstWhere('slug', $slug)?->id;
    }

    /** The id of the donor a slug stands for; null for any other text. */
    public static function donorId(string $slug): ?int
    {
        return self::donors()->firstWhere('slug', $slug)?->id;
    }

    /**
     * Names as addresses spell them: lower case, hyphens, no accents. Two
     * names that come to the same slug keep it apart with their id.
     *
     * @template TModel of Place|Donor
     *
     * @param  Collection<int, TModel>  $models
     * @return Collection<int, TModel>
     */
    private static function withSlugs(Collection $models): Collection
    {
        $taken = [];

        return $models->each(function (Place|Donor $model) use (&$taken) {
            $slug = Str::slug((string) $model->name) ?: (string) $model->id;
            $slug = isset($taken[$slug]) ? $slug.'-'.$model->id : $slug;
            $taken[$slug] = true;
            $model->setAttribute('slug', $slug);
        });
    }

    /** Every project the public listing can show, whatever the filters. */
    public static function total(): int
    {
        return ProjectSearch::make()->builder()->count();
    }

    /**
     * What the search box suggests, over every listed project: its name and
     * number, and the names of its beneficiary and partners.
     *
     * @return array{projects: array<int, string>, beneficiaries: array<int, string>}
     */
    public static function suggestions(): array
    {
        return Cache::remember(self::SUGGESTIONS_KEY, now()->addMinutes(self::SUGGESTIONS_MINUTES), fn () => self::readSuggestions());
    }

    /** @return array{projects: array<int, string>, beneficiaries: array<int, string>} */
    private static function readSuggestions(): array
    {
        $projects = ProjectSearch::make()->builder()->with('content:project_id,MPE_Name')
            ->get(['id', 'MicroProject_ID', 'MPE_BeneficiaryName', 'MPE_PartnerName', 'MPE_PartnerName2']);

        $names = fn (Project $project): array => [
            $project->content?->MPE_Name,
            (string) $project->MicroProject_ID,
        ];
        $people = fn (Project $project): array => [
            $project->MPE_BeneficiaryName,
            $project->MPE_PartnerName !== self::NO_PARTNER ? $project->MPE_PartnerName : null,
            $project->MPE_PartnerName2 !== self::NO_PARTNER ? $project->MPE_PartnerName2 : null,
        ];

        return [
            'projects' => $projects->flatMap($names)->filter()->unique()->values()->all(),
            'beneficiaries' => $projects->flatMap($people)->filter()->unique()->values()->all(),
        ];
    }

    /**
     * The first $limit projects of a search, newest first, with what the
     * cards and the details window show.
     *
     * @return Collection<int, Project>
     */
    public static function page(ProjectSearch $search, int $limit): Collection
    {
        return self::cards($search->builder())->limit($limit)->get();
    }

    /** One listed project, for its details window; null when it is not on the public list. */
    public static function find(int $id): ?Project
    {
        return self::cards(ProjectSearch::make()->builder())->whereKey($id)->first();
    }

    /**
     * A pin for each matching project that has a position. PHP's idea of
     * "has one": the columns hold text, and '0' and '' count as none.
     *
     * @return array<int, array{lat: string, lng: string, name: ?string, beneficiary: string}>
     */
    public static function markers(ProjectSearch $search): array
    {
        return $search->builder()->with('content:project_id,MPE_Name')
            ->get(['id', 'latitude', 'longtitude', 'MPE_BeneficiaryName'])
            ->filter(fn (Project $project) => $project->latitude && $project->longtitude)
            ->map(fn (Project $project) => [
                'lat' => $project->latitude,
                'lng' => $project->longtitude,
                'name' => $project->content?->MPE_Name,
                'beneficiary' => $project->MPE_BeneficiaryName,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    private static function cards(Builder $query): Builder
    {
        return $query->with([
            'content',
            'images' => fn ($images) => $images->orderBy('image_order'),
            'mPEDonor',
            'mPECity',
        ]);
    }
}
