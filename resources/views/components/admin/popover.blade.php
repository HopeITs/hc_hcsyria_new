@props(['label', 'width' => '14rem'])

{{-- A button that opens a small floating panel; closes on a click outside or Escape. The trigger is the `trigger` slot. --}}
<div class="wrap" x-data="{ open: false }" x-on:keydown.escape.window="open = false" x-on:click.outside="open = false">
    <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open" aria-haspopup="true" aria-label="{{ $label }}" {{ $trigger->attributes }}>{{ $trigger }}</button>
    <div class="pop" x-bind:class="{ open }" style="min-width: {{ $width }}" x-on:click="$event.target.closest('.mi') && (open = false)">
        {{ $slot }}
    </div>
</div>
