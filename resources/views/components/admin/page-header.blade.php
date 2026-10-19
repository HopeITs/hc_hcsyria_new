@props(['title', 'count' => null, 'subtitle' => null, 'crumbs' => []])

{{-- The top of every admin page: breadcrumbs, the title with its count, a line under it, and the page's buttons. --}}
<header class="ph-head">
    <div>
        @if ($crumbs !== [])
            <nav class="crumbs" aria-label="Breadcrumb">
                @foreach ($crumbs as $label => $url)
                    @if (! $loop->first)<span aria-hidden="true">/</span>@endif
                    @if ($url)<a href="{{ $url }}" wire:navigate>{{ $label }}</a>@else<b aria-current="page">{{ $label }}</b>@endif
                @endforeach
            </nav>
        @endif
        <h1>{{ $title }}@if ($count !== null)<span class="cnt">{{ $count }}</span>@endif</h1>
        @if ($subtitle)<p class="sub">{{ $subtitle }}</p>@endif
    </div>
    @isset($actions)<div class="row-s">{{ $actions }}</div>@endisset
</header>
