@props(['rows', 'label' => 'name', 'value' => 'value', 'suffix' => '', 'empty' => 'Nothing to show yet.'])

{{--
    A ranked list with a bar behind each figure, for the dashboard and the reports.
    $rows: objects or arrays; $label and $value name the two fields; the longest bar is the biggest value.
--}}
@php
    $get = fn ($row, string $key) => is_array($row) ? ($row[$key] ?? null) : ($row->{$key} ?? null);
    $max = max(1, collect($rows)->map(fn ($row) => (float) $get($row, $value))->max() ?? 1);
@endphp
<div class="bars">
    @forelse ($rows as $row)
        @php $n = (float) $get($row, $value); @endphp
        <div class="bar-row">
            <span class="bar-l" title="{{ $get($row, $label) }}">{{ $get($row, $label) }}</span>
            <span class="bar-t"><i style="width: {{ round($n / $max * 100) }}%"></i></span>
            <span class="bar-v tnum">{{ number_format($n) }}{{ $suffix }}</span>
        </div>
    @empty
        <p class="muted" style="padding: 16px; margin: 0">{{ $empty }}</p>
    @endforelse
</div>
