@props(['name', 'options'])

{{-- Several choices ticked from a list, bound to the Livewire array property $name. $options: value => label. --}}
<div class="chips" style="gap: 8px" role="group">
    @foreach ($options as $value => $label)
        <label class="chip" style="height: 34px; padding: 0 12px; cursor: pointer; gap: 8px">
            <input type="checkbox" class="cb" value="{{ $value }}" wire:model="{{ $name }}">{{ $label }}
        </label>
    @endforeach
</div>
