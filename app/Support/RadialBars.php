<?php

namespace App\Support;

/*
 * The geometry of the radial bars (resources/views/components/ui/radial-bars.blade.php):
 * where each ring sits and how far its round ends reach, measured from the
 * amCharts radar chart the legacy statistics used. The rings fill 0.4 of the
 * box's height from the centre, 80 pixels across in all, shared among the
 * bars; each bar is 0.8 of its share thick.
 */
final class RadialBars
{
    /** How wide the band of rings is, in the chart's own units. */
    public const BAND = 80;

    /**
     * @param  list<array{key: string, label: string, color: string}>  $bars  innermost first
     * @return list<array{key: string, label: string, color: string, radius: float, cap: float}>
     */
    public static function rings(array $bars, int $height): array
    {
        $outer = 0.4 * $height;
        $count = max(count($bars), 1);
        $thickness = self::thickness($count);

        return array_values(collect($bars)->values()->map(function (array $bar, int $i) use ($outer, $count, $thickness): array {
            $radius = $outer - self::BAND + self::BAND / $count * ($i + 0.5);

            return $bar + [
                'radius' => round($radius, 2),
                // Round ends sit inside a bar's length: half its thickness, in hundredths of its ring.
                'cap' => round($thickness / 2 / (2 * M_PI * $radius) * 100, 4),
            ];
        })->all());
    }

    /**
     * The rings for each size a chart is drawn at: '' for its main one, then
     * any named others (ui.radial-bars' :heights), keyed as the script wants them.
     *
     * @param  list<array{key: string, label: string, color: string}>  $bars
     * @param  array<string, int>  $heights  extra sizes, name => height
     * @return array<string, list<array{key: string, label: string, color: string, radius: float, cap: float}>>
     */
    public static function variants(array $bars, int $height, array $heights = []): array
    {
        return collect(['' => $height] + $heights)->map(fn (int $size) => self::rings($bars, $size))->all();
    }

    public static function thickness(int $barCount): float
    {
        return round(self::BAND / max($barCount, 1) * 0.8, 2);
    }
}
