@props(['label', 'name', 'options', 'all'])

{{-- A filter of a list: a select bound to the page's property $name, over rows with an id and a name. --}}
<div class="field">
    <label for="f-{{ $name }}">{{ $label }}</label>
    <select id="f-{{ $name }}" class="sel" wire:model.live="{{ $name }}">
        <option value="">{{ $all }}</option>
        @foreach ($options as $option)<option value="{{ $option->id }}">{{ $option->name }}</option>@endforeach
    </select>
</div>
