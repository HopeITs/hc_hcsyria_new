<?php

namespace App\Queries;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/*
 * The choices the staff forms and their filters offer. For micro-projects: the
 * cities and donors flagged is_micro, the project types, and the categories with
 * their sub-categories (the legacy "type" and "sub type" the form let you pick).
 * For initiatives: the programme categories the dashboard manages itself, who an
 * initiative serves, every donor, and the family-to-family kinds.
 */
final class ContentLookups
{
    /**
     * Micro-project cities in the legacy order (Aleppo, Damascus, Homs, Latakia, Al Wadi, Idleb, Tartus).
     *
     * @return Collection<int, object{id: int, name: string}>
     */
    public static function cities(?array $onlyIds = null): Collection
    {
        return DB::table('places')
            ->where('is_micro', 1)
            ->when($onlyIds !== null, fn ($query) => $query->whereIn('id', $onlyIds))
            ->orderByRaw("FIELD(name, 'Aleppo', 'Damascus', 'Homs', 'Latakia', 'Al Wadi', 'Idleb', 'Tartus')")
            ->get(['id', 'name']);
    }

    /** @return Collection<int, object{id: int, name: string}> */
    public static function donors(): Collection
    {
        return DB::table('donors')->where('is_micro', 1)->orderBy('rank')->orderBy('name')->get(['id', 'name']);
    }

    /** "Micro Project", "Rehabilitation", "Agricultural Project". @return Collection<int, object{id: int, name: string}> */
    public static function types(): Collection
    {
        return DB::table('projecttype')->orderBy('id')->get(['id', 'name']);
    }

    /** @return Collection<int, object{id: int, name: string}> */
    public static function categories(): Collection
    {
        return DB::table('category')->orderBy('ID')->get(['ID as id', 'Name as name']);
    }

    /** @return Collection<int, object{id: int, name: string, category_id: int}> */
    public static function subCategories(): Collection
    {
        return DB::table('subcategory')->orderBy('Name')->get(['ID as id', 'Name as name', 'Category_ID as category_id']);
    }

    /** The next unused MicroProject_ID, offered on a new project. */
    public static function nextNumber(): int
    {
        return (int) DB::table('projects')->max('MicroProject_ID') + 1;
    }

    /** The initiative categories managed in the Initiatives section; the programmes (DISC, Study Zone, ...) have their own. */
    public const INITIATIVE_CATEGORIES = [1, 3, 4, 8, 18];

    /** Homework Room is not part of the dashboard: nothing of that category is listed, opened or changed here. */
    public const EXCLUDED_CATEGORIES = [6];

    /** The categories whose initiatives have testimonies, as in legacy: DISC and Study Zone (Homework Room is not part of the dashboard). */
    public const TESTIMONY_CATEGORIES = [5, 7];

    /** The categories of success stories the Successes section manages: social activities, DISC, Study Zone, Family to Family, micro projects, Children University. */
    public const SUCCESS_CATEGORIES = [1, 5, 7, 8, 10, 17];

    public const DISC = 5;

    public const STUDY_ZONE = 7;

    public const MICRO_PROJECTS = 10;

    /** What kind of newsletter item it is: legacy's type column. */
    public const NEWS_TYPES = [0 => 'Article', 1 => 'Product', 2 => 'Short paragraph', 3 => 'Video'];

    /** The kind that must have a video link. */
    public const NEWS_VIDEO = 3;

    /** The Family to Family category, whose initiatives are either partners or stories. */
    public const FAMILY_TO_FAMILY = 8;

    /** @return Collection<int, object{id: int, name: string}> */
    public static function initiativeCategories(): Collection
    {
        return DB::table('what_categories')->whereIn('id', self::INITIATIVE_CATEGORIES)->orderBy('id')->get(['id', 'name']);
    }

    /** "men", "women", "youth"... @return Collection<int, object{id: int, name: string}> */
    public static function whoCategories(): Collection
    {
        return DB::table('who_categories')->orderBy('id')->get(['id', 'name']);
    }

    /** @return Collection<int, object{id: int, name: string}> */
    public static function allDonors(): Collection
    {
        return DB::table('donors')->orderBy('name')->get(['id', 'name']);
    }

    /** Family to Family: "Partners" or "Stories". @return Collection<int, object{id: int, name: string}> */
    public static function familyKinds(): Collection
    {
        return DB::table('sub_what_categories')->where('what_category_id', self::FAMILY_TO_FAMILY)->orderBy('id')->get(['id', 'name']);
    }

    /** @return Collection<int, object{id: int, name: string}> */
    public static function successCategories(): Collection
    {
        return DB::table('what_categories')->whereIn('id', self::SUCCESS_CATEGORIES)->orderBy('id')->get(['id', 'name']);
    }

    /** What a DISC story is about: "Job Opportunity", "Students Of The Month". @return Collection<int, object{id: int, name: string}> */
    public static function discKinds(): Collection
    {
        return DB::table('sub_what_categories')->where('what_category_id', self::DISC)->orderBy('id')->get(['id', 'name']);
    }

    /** @return Collection<int, object{id: int, name: string}> */
    public static function studyCenters(): Collection
    {
        return DB::table('studyzonecenters')->orderBy('rank')->orderBy('name')->get(['id', 'name']);
    }

    /** Every place, "All" included, as the legacy forms listed them. @return Collection<int, object{id: int, name: string}> */
    public static function places(): Collection
    {
        return DB::table('places')->orderBy('id')->get(['id', 'name']);
    }

    /** One project's number and name, to show which is linked. */
    public static function projectLabel(int $id): ?object
    {
        return DB::table('projects')->join('projects_english', 'projects_english.project_id', '=', 'projects.id')
            ->where('projects.id', $id)->first(['projects.id', 'projects.MicroProject_ID as number', 'projects_english.MPE_Name as name']);
    }

    /**
     * Funded, online micro projects matching what was typed, for linking a story to one (at most $limit).
     *
     * @return Collection<int, object{id: int, number: int, name: string, city: ?string}>
     */
    public static function linkableProjects(string $term, int $limit = 8): Collection
    {
        $like = '%'.addcslashes(trim($term), '%_\\').'%';

        return DB::table('projects')
            ->join('projects_english', 'projects_english.project_id', '=', 'projects.id')
            ->leftJoin('places', 'places.id', '=', 'projects.MPE_City')
            ->where('projects.MPE_Published', '1')
            ->where(fn ($q) => $q->where('projects_english.MPE_Name', 'like', $like)->orWhere('projects.MicroProject_ID', 'like', $like))
            ->orderByDesc('projects.MicroProject_ID')
            ->limit($limit)
            ->get(['projects.id', 'projects.MicroProject_ID as number', 'projects_english.MPE_Name as name', 'places.name as city']);
    }

    /** The magazine editions a newsletter item may belong to. @return Collection<int, object{id: int, name: string}> */
    public static function editions(): Collection
    {
        return DB::table('editions')->orderBy('id')->get(['id', 'name']);
    }

    /** Every tag, by name. @return Collection<int, object{id: int, name: string}> */
    public static function tags(): Collection
    {
        return DB::table('tags')->orderBy('name')->get(['id', 'name']);
    }
}
