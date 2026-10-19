<?php

namespace App\Support\Admin;

use Illuminate\Support\Str;

/*
 * Where each kind of content keeps its pictures under MEDIA_ROOT/images. A
 * project keeps its photos in its city's folder, as legacy did; an initiative in a
 * folder of its category. Legacy named the initiative folder after its title,
 * which could hold any character and moved if the title changed; here it is the
 * initiative's number.
 */
final class PhotoFolders
{
    /** City => folder, as legacy's store_image() chose them. */
    private const PROJECT_FOLDERS = [
        'Aleppo' => 'projects',
        'Homs' => 'projects_homs',
        'Damascus' => 'projects_damascus',
        'Latakia' => 'projects_latakia',
        'Al Wadi' => 'projects_wadi',
        'Tartus' => 'projects_tartus',
        'Idleb' => 'projects_idleb',
    ];

    public static function project(string $city, int $number): string
    {
        return '/images/'.(self::PROJECT_FOLDERS[$city] ?? 'projects').'/'.$number;
    }

    /** A success story's picture goes in the folder of the people it is about (men, women, youth...), as in legacy. */
    public static function success(string $who): string
    {
        $name = trim((string) preg_replace('/[^\p{L}\p{N} _-]+/u', '', $who));

        return '/images/successes/'.($name === '' ? 'other' : $name);
    }

    /** A newsletter item keeps all its pictures in a folder of its number, as legacy did. */
    public static function news(int $id): string
    {
        return '/images/news/'.$id;
    }

    public static function initiative(string $category, int $id): string
    {
        // The category's own name, as the existing folders are named, without anything that could leave the folder.
        $name = trim((string) preg_replace('/[^\p{L}\p{N} _-]+/u', '', $category)) ?: Str::slug($category);

        return '/images/initiatives/'.($name === '' ? 'other' : $name).'/'.$id;
    }
}
