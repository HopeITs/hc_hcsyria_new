@props(['rows', 'label' => 'name', 'value' => 'value', 'total' => null, 'empty' => 'Nothing to show yet.'])

{{--
    A ranked list for a narrow column: the name, a bar behind the figure and the
    figure itself, with the share of the whole when a total is given.
    $rows: objects or arrays; $label and $value name the two fields.
--}}
@php
    $get = fn ($row, string $key) => is_array($row) ? ($row[$key] ?? null) : ($row->{$key} ?? null);
    $max = max(1, (float) (collect($rows)->map(fn ($row) => (float) $get($row, $value))->max() ?? 1));
@endphp

<div class="rank">
    @forelse ($rows as $row)
        @php
            $n = (float) $get($row, $value);
            $share = $total ? $n / $total * 100 : null;
        @endphp
        <div class="rank-row">
            <span class="rank-l" title="{{ $get($row, $label) }}">{{ $get($row, $label) }}</span>
            <span class="rank-t"><i style="width: {{ round($n / $max * 100) }}%"></i></span>
            <span class="rank-v tnum">{{ number_format($n) }}@if ($share !== null)<small>{{ number_format($share, 1) }}%</small>@endif</span>
        </div>
    @empty
        <p class="muted" style="padding: 16px; margin: 0">{{ $empty }}</p>
    @endforelse
</div>
