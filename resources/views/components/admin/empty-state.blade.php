@props(['icon' => 'folder', 'title'])

<div {{ $attributes->class('empty') }}>
    <div class="ic"><x-admin.icon :name="$icon" /></div>
    <b>{{ $title }}</b>
    {{ $slot }}
</div>
