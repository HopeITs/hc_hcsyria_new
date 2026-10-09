@props(['donors'])

{{--
    The partner logos. Each organisation's artwork has its own proportions, so
    each logo has the width the legacy site gave it, chosen by the donor's id
    (resources/css/ui/partner-logos.css). From 768px a break after the sixth
    and twelfth logo makes the rows 6 / 6 / 7.
--}}
<div class="partner-grid">
    @foreach ($donors as $donor)
        @php
            $logo = match ((int) $donor->id) {
                3, 6, 17 => 'partner-logo--louvre',
                4, 5 => 'partner-logo--kerk',
                2 => 'partner-logo--salt',
                15 => 'partner-logo--jm',
                8 => 'partner-logo--hungary',
                12, 13, 16, 20 => 'partner-logo--missio',
                18 => 'partner-logo--paris',
                23 => 'partner-logo--rs',
                default => '',
            };
        @endphp

        <div class="partner-grid__cell">
            <div @class(['partner-logo', $logo, 'ms-6' => $donor->id == 5])>
                <img class="h-full w-full object-contain" src="{{ $donor->logo }}" alt="{{ $donor->name }}">
            </div>
        </div>

        @if ($loop->iteration === 6 || $loop->iteration === 12)
            <div class="partner-grid__break"></div>
        @endif
    @endforeach
</div>
