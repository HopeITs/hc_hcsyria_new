@props(['presenter'])

{{-- The donor's mark, sized by how wide or tall it needs to be. --}}
<div class="project-logo project-logo--{{ $presenter->logoClass() }}">
    <img src="{{ $presenter->logo() }}" alt="{{ $presenter->donor() }}">
</div>
