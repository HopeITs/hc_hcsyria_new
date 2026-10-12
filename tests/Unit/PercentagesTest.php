<?php

use App\Support\Percentages;

it('makes the shares add up to exactly 100, giving the spare points to the biggest remainders', function () {
    $shares = Percentages::largestRemainder(['a' => 1, 'b' => 1, 'c' => 1]);

    expect(array_sum($shares))->toBe(100)
        ->and(array_keys($shares))->toBe(['a', 'b', 'c']);
});

it('keeps exact shares as they are', function () {
    expect(Percentages::largestRemainder([50, 25, 25]))->toBe([50, 25, 25]);
});

it('gives zero to everyone when there is nothing to count', function () {
    expect(Percentages::largestRemainder(['a' => 0, 'b' => 0]))->toBe(['a' => 0, 'b' => 0]);
});
