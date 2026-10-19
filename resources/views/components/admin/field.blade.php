@props(['label', 'name', 'required' => false, 'help' => null, 'full' => false])

{{-- One form field: its label, the control (the slot), a hint, and the error for $name. --}}
<div {{ $attributes->class(['field', 'full' => $full, 'bad' => $errors->has($name)]) }}>
    <label for="f-{{ $name }}">{{ $label }}@if ($required) <span class="req" aria-hidden="true">*</span>@endif</label>
    {{ $slot }}
    @error($name)<div class="err" role="alert" style="display:block">{{ $message }}</div>@enderror
    @if ($help)<div class="help">{{ $help }}</div>@endif
</div>
