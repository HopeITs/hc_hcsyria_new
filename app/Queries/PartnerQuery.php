<?php

namespace App\Queries;

use App\Models\Donor;
use App\Models\FamilyPartner;
use App\Models\HopeValue;
use App\Models\Place;
use Illuminate\Database\Eloquent\Collection;

/*
 * Donors, partners, values and place lookups for the public content pages.
 *
 * Replaces the scattered DB::table calls in PublicController::about_us() and
 * family_to_family(), and the raw slug lookup in discByPlace().
 */
class PartnerQuery
{
    /**
     * Donors excluded from the public partner strip. Carried over from
     * about_us(), which hid ids 9, 10 and 19 with no explanation given.
     */
    private const HIDDEN_DONOR_IDS = [9, 10, 19];

    /**
     * Donors shown on the public pages, in display order.
     *
     * @return Collection<int, Donor>
     */
    public static function publicDonors(): Collection
    {
        return Donor::query()
            ->whereNotIn('id', self::HIDDEN_DONOR_IDS)
            ->orderBy('rank')
            // rank is not unique; id keeps tied rows in a fixed order.
            ->orderBy('id')
            ->get();
    }

    /**
     * The public donors with the ones named in $order first, in that order;
     * the rest follow in their usual order. /who-we-are sets the order by
     * hand so its rows of logos balance (6 / 6 / 7).
     *
     * @param  list<int>  $order  donor ids
     * @return Collection<int, Donor>
     */
    public static function publicDonorsInOrder(array $order): Collection
    {
        // sortBy is stable, so donors not listed keep their rank order.
        return self::publicDonors()
            ->sortBy(fn (Donor $donor) => ($position = array_search((int) $donor->id, $order, true)) === false
                ? PHP_INT_MAX
                : $position)
            ->values();
    }

    /**
     * Micro-project donors, as used by the project filters.
     *
     * @return Collection<int, Donor>
     */
    public static function microDonors(): Collection
    {
        return Donor::query()
            ->where('is_micro', '1')
            ->whereNotIn('id', [9, 10])
            ->orderBy('rank')
            ->orderBy('id')
            ->get();
    }

    /**
     * The organisation's stated values, shown on /about-us.
     *
     * @return Collection<int, HopeValue>
     */
    public static function hopeValues(): Collection
    {
        return HopeValue::query()->orderBy('id')->get();
    }

    /**
     * Partner organisations plotted on the family-to-family map.
     *
     * @return Collection<int, FamilyPartner>
     */
    public static function familyPartners(): Collection
    {
        return FamilyPartner::query()->orderBy('id')->get();
    }

    /**
     * A place name by id.
     *
     * The admin controllers wrote this as
     * DB::table('places')->where('id', $id)->get()->pluck('name')[0], which
     * throws an undefined-offset error rather than returning null when the id
     * does not exist.
     */
    public static function placeName(int|string|null $id): ?string
    {
        if ($id === null || $id === '') {
            return null;
        }

        return Place::query()->whereKey($id)->value('name');
    }

    /**
     * Resolves a place slug from the URL — "al-wadi" finds "Al Wadi".
     *
     * discByPlace() built this with whereRaw and the URL segment spliced
     * straight in, which was injectable. The value is bound here.
     */
    public static function placeBySlug(string $slug): ?Place
    {
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $slug);

        return Place::query()
            ->whereRaw('replace(lower(name), " ", "-") LIKE ?', [$escaped])
            ->first();
    }
}
