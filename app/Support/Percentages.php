<?php

namespace App\Support;

/*
 * Whole-number percentages of a set of counts that add up to exactly 100:
 * each share is rounded down, and the points left over go to the shares
 * that lost the most in rounding (the largest-remainder method). Legacy
 * did this inline in the micro-projects view for the age gauges.
 */
final class Percentages
{
    /**
     * @param  array<array-key, int>  $counts
     * @return array<array-key, int> the same keys, each a whole percentage
     */
    public static function largestRemainder(array $counts): array
    {
        $total = array_sum($counts);

        if ($total <= 0) {
            return array_map(fn () => 0, $counts);
        }

        $shares = [];
        $remainders = [];

        foreach ($counts as $key => $count) {
            $exact = $count / $total * 100;
            $shares[$key] = (int) floor($exact);
            $remainders[$key] = $exact - $shares[$key];
        }

        arsort($remainders);

        foreach (array_slice(array_keys($remainders), 0, 100 - array_sum($shares)) as $key) {
            $shares[$key]++;
        }

        return $shares;
    }
}
