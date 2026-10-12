@props(['ongoing', 'previous'])

{{--
    A programme's funders (legacy components/project-partners-section):
    "Our Partners", then the logos of those funding it now and, if any, of
    those who did before, each group under a thin labelled divider
    (resources/css/ui/funders.css). Each logo has the width legacy gave
    that donor's artwork.
--}}
@php
    $size = fn ($donor) => match ((int) $donor->id) {
        12, 13 => 'funder-logo--missio',
        4, 5, 15 => 'funder-logo--kerk',
        3, 6 => 'funder-logo--louvre',
        8 => 'funder-logo--hungary',
        default => '',
    };
@endphp

<div {{ $attributes->class('funders') }}>
    <div class="px-3">
        <x-ui.reveal :ms="600" class="flex flex-col items-center justify-center">
            <x-ui.section-title class="mt-4 pb-0">Our Partners</x-ui.section-title>

            @foreach (['Ongoing Project Funders' => $ongoing, 'Previous Project Funders' => $previous] as $label => $donors)
                @if ($donors->isNotEmpty())
                    <div class="funders__divider">
                        <div class="funders__line"></div>
                        <div class="funders__label">{{ $label }}</div>
                        <div class="funders__line"></div>
                    </div>
                    <div class="funders__logos">
                        @foreach ($donors as $donor)
                            <div @class(['funder-logo', $size($donor)])>
                                <img class="h-full w-full object-contain" src="{{ $donor->logo }}" alt="{{ $donor->name }}">
                            </div>
                        @endforeach
                    </div>
                @endif
            @endforeach
        </x-ui.reveal>
    </div>
</div>
