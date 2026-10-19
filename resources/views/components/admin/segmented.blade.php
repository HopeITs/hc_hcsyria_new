@props(['name', 'options', 'disabled' => false, 'live' => false])

{{-- A row of choices that act as radio buttons, bound to the Livewire property $name. --}}
<div class="seg" role="radiogroup">
    @foreach ($options as $value => $label)
        <label>
            <input type="radio" value="{{ $value }}" @if ($live) wire:model.live="{{ $name }}" @else wire:model="{{ $name }}" @endif @disabled($disabled)>
            <span>{{ $label }}</span>
        </label>
    @endforeach
</div>
