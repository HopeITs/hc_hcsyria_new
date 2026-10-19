@props(['model', 'disabled' => false])

{{-- The story box (resources/js/admin/rich-text.js), bound to the Livewire property $model. --}}
<div class="rte" @if ($disabled) data-disabled @endif x-data="richText($wire.$entangle('{{ $model }}'))" wire:ignore>
    <div class="rte-bar" role="toolbar" aria-label="Formatting">
        <button type="button" x-on:click="format('bold')" aria-label="Bold"><b>B</b></button>
        <button type="button" x-on:click="format('italic')" aria-label="Italic"><i>I</i></button>
        <button type="button" x-on:click="format('underline')" aria-label="Underline"><u>U</u></button>
        <button type="button" x-on:click="format('insertUnorderedList')" aria-label="Bulleted list"><x-admin.icon name="list" /></button>
        <button type="button" x-on:click="format('insertOrderedList')" aria-label="Numbered list">1.</button>
    </div>
    <div class="rte-body" x-ref="body" contenteditable="{{ $disabled ? 'false' : 'true' }}" role="textbox" aria-multiline="true" aria-label="Story" x-on:input="input()" x-on:paste="paste($event)"></div>
</div>
