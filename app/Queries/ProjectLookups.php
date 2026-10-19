<?php

namespace App\Queries;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/*
 * The choices a micro-project form and its filters offer: the cities and donors
 * flagged is_micro, the project types, and the categories with their
 * sub-categories (the legacy "type" and "sub type" the form let you pick).
 */
final class ProjectLookups
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
}
