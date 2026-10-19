@props(['status' => null, 'tone' => null, 'label' => null])

@php
    $tone ??= $status?->tone();
    $label ??= $status ? __('admin.status.'.$status->value) : '';
@endphp
<span {{ $attributes->class(['pill', 'p-'.$tone]) }}><i></i>{{ $label }}</span>
