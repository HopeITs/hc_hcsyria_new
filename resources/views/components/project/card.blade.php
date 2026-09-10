@props(['project'])

@php
    /*
     * Ported from the project tile in resources/views/livewire/micro-projects.blade.php.
     * Title truncated at 34 characters, then the beneficiary followed by any
     * partner whose stored value is not the string '0', then "place , donor".
     */
    $title = (string) ($project->content?->MPE_Name ?? '');

    $names = array_filter(
        [$project->MPE_BeneficiaryName, $project->MPE_PartnerName, $project->MPE_PartnerName2],
        fn($name) => filled($name) && $name !== '0'
    );

    $first = $project->images->sortBy('image_order')->first();
@endphp

<div {{ $attributes->merge(['class' => 'project-card']) }}>
    <div class="p-3">
        <div class="aspect-[4/3] w-full overflow-hidden">
            @if ($first)
                <img src="{{ $first->MPI_Path }}" alt="{{ $title }}" loading="lazy" decoding="async"
                     class="size-full object-cover">
            @endif
        </div>

        <div class="card-title mt-2">{{ Str::limit($title, 34, '...') }}</div>

        @if ($names !== [])
            <div class="card-subtitle">
                <span>{{ implode(', ', array_map(fn($name) => ucfirst($name), $names)) }}</span>
            </div>
        @endif

        <div class="card-content mt-2">
            {{ $project->mPECity?->name }} , {{ $project->mPEDonor?->name }}
        </div>
    </div>
</div>
