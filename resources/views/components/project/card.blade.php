@props(['project', 'compact' => false])

@php
    /*
     * Ported from the project tile in resources/views/livewire/micro-projects.blade.php.
     * Fields and formatting are as they are there: the title truncated at 34
     * characters, the beneficiary followed by any partner whose value is not
     * the string '0', then "place , donor".
     */
    $title = (string) ($project->content?->MPE_Name ?? '');

    $names = array_filter(
        [$project->MPE_BeneficiaryName, $project->MPE_PartnerName, $project->MPE_PartnerName2],
        fn($name) => filled($name) && $name !== '0'
    );

    $subtitle = implode(', ', array_map(fn($name) => ucfirst($name), $names));

    $images = $project->images->sortBy('image_order')->values();
    $first  = $images->first();
@endphp

<div {{ $attributes->merge(['class' => 'project-card bg-[#f4f4f4]']) }}>
    <div class="p-3">
        <div class="aspect-[4/3] w-full overflow-hidden bg-ink-100">
            @if ($first)
                <img src="{{ $first->MPI_Path }}" alt="{{ $title }}" loading="lazy" decoding="async"
                     class="size-full object-cover">
            @endif
        </div>

        <div class="mt-3 font-bold text-ink-900 {{ $compact ? 'text-sm' : 'text-base' }}">
            {{ Str::limit($title, 34, '...') }}
        </div>

        @if ($subtitle !== '')
            <div class="mt-1 text-[#616262] {{ $compact ? 'text-xs' : 'text-sm' }}">
                <span class="font-bold">{{ $subtitle }}</span>
            </div>
        @endif

        <div class="mt-2 text-xs text-[#1b1b1b70]">
            {{ $project->mPECity?->name }} , {{ $project->mPEDonor?->name }}
        </div>
    </div>
</div>
