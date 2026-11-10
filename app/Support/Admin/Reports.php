<?php

namespace App\Support\Admin;

use App\Queries\AdminStatisticsQuery;

/*
 * The reports staff can read on /admin/reports and download as CSV. Each report is
 * a title and a function that turns the statistics into headings and rows, so the
 * page and the download are always the same table. Money columns carry no
 * currency symbol (the heading says which currency).
 */
final class Reports
{
    /** @return array<string, string> slug => title */
    public static function titles(): array
    {
        return [
            'funding-by-city' => 'Funding by city',
            'funding-by-donor' => 'Funding by donor',
            'funding-by-category' => 'Funding by category',
            'beneficiaries-by-category' => 'Beneficiaries by category',
            'beneficiaries-by-type' => 'Beneficiaries by project type',
            'beneficiaries-by-age' => 'Beneficiaries by age',
            'funded-per-month' => 'Projects funded per month',
        ];
    }

    public static function exists(string $slug): bool
    {
        return array_key_exists($slug, self::titles());
    }

    /** @return array{headings: list<string>, rows: list<list<string|int|float>>} */
    public static function build(string $slug, AdminStatisticsQuery $stats): array
    {
        $money = '('.config('hcsyria.currency').')';

        $funding = fn ($rows, string $first): array => [
            'headings' => [$first, 'Projects', 'Needed '.$money, 'Donated '.$money, 'Missing '.$money, 'Funded %'],
            'rows' => $rows->map(fn ($r): array => [$r->name, $r->projects, $r->needed, $r->donated, max(0, $r->needed - $r->donated), $r->needed > 0 ? round(min(100, $r->donated / $r->needed * 100), 1) : 0])->all(),
        ];
        $people = fn ($rows, string $first): array => [
            'headings' => [$first, 'Projects', 'People'],
            'rows' => $rows->map(fn ($r): array => [$r->name, $r->projects, $r->people])->all(),
        ];

        return match ($slug) {
            'funding-by-city' => $funding($stats->fundingByCity(), 'City'),
            'funding-by-donor' => $funding($stats->fundingByDonor(), 'Donor'),
            'funding-by-category' => $funding($stats->fundingByCategory(), 'Category'),
            'beneficiaries-by-category' => $people($stats->byCategory(), 'Category'),
            'beneficiaries-by-type' => $people($stats->byProjectType(), 'Project type'),
            'beneficiaries-by-age' => [
                'headings' => ['Age', 'Projects'],
                'rows' => array_map(fn (array $b): array => [$b['band'], $b['projects']], $stats->ageBands()),
            ],
            'funded-per-month' => [
                'headings' => ['Month', 'Projects', 'Donated '.$money],
                'rows' => $stats->fundedPerMonth(24)->map(fn ($r): array => [$r->month, $r->projects, $r->donated])->all(),
            ],
            default => throw new \InvalidArgumentException("Unknown report {$slug}"),
        };
    }
}
