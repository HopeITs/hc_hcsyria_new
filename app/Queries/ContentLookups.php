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
}
