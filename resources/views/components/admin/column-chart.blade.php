@props(['rows', 'height' => 150, 'empty' => 'Nothing to show yet.'])

{{--
    A column chart for a series over time (the dashboard's funded projects per
    month). Each row is an object or an array with a "label" (under the column), a
    "value" (how tall it is) and optionally a "title" (the tooltip). The tallest
    column sets the scale.
--}}
@php
    $get = fn ($row, string $key) => is_array($row) ? ($row[$key] ?? null) : ($row->{$key} ?? null);
    $values = collect($rows)->map(fn ($row) => (float) $get($row, 'value'));
    $max = max(1, (float) ($values->max() ?? 1));
@endphp

<div class="chart" style="--h: {{ (int) $height }}px">
    <div class="chart-plot">
        @forelse ($rows as $row)
            @php
                $value = (float) $get($row, 'value');
                $label = (string) $get($row, 'label');
                $title = (string) ($get($row, 'title') ?? $label);
            @endphp
            <div class="chart-col" title="{{ $title }}: {{ number_format($value) }}">
                <i style="height: {{ $value > 0 ? max($value / $max * 100, 3) : 0 }}%">
                    @if ($value > 0)<span class="chart-v tnum">{{ number_format($value) }}</span>@endif
                </i>
            </div>
        @empty
            <p class="muted chart-empty">{{ $empty }}</p>
        @endforelse
    </div>

    @if ($rows !== [])
        <div class="chart-axis">
            @foreach ($rows as $row)
                <span>{{ $get($row, 'label') }}</span>
            @endforeach
        </div>
    @endif
</div>
