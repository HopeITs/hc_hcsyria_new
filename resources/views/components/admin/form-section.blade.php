@props(['title', 'hint' => null, 'id' => null])

<section @if ($id) id="{{ $id }}" @endif {{ $attributes->class('fs') }}>
    <div class="fs-rail">
        <h3><span class="dotk"></span>{{ $title }}</h3>
        @if ($hint)<p>{{ $hint }}</p>@endif
    </div>
    <div class="fs-f">{{ $slot }}</div>
</section>
