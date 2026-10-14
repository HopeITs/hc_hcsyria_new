@props(['presenter', 'skills' => false])

{{-- The facts of a project: who it is for, when it was funded, where, how old and how big the family. --}}
@php($label = $presenter->peopleLabel())
@if ($presenter->people())
    <div class="project-fact"><strong>{{ $label }}</strong> <span>{{ implode(', ', $presenter->people()) }}</span></div>
@endif
@if ($presenter->fundDate())
    <div class="project-fact"><strong>{{ __('projects.fundDate') }}</strong> <span>{{ $presenter->fundDate() }}</span></div>
@endif
@if ($presenter->city() !== '')
    <div class="project-fact"><strong>City:</strong> <span>{{ $presenter->city() }}</span></div>
@endif
@if ($presenter->age() !== null)
    <div class="project-fact"><strong>{{ __('projects.age') }}</strong> <span>{{ $presenter->age() }} {{ __('projects.years') }}</span></div>
@endif
@if ($presenter->family() !== null)
    <div class="project-fact"><strong>{{ __('projects.family') }}</strong> <span>{{ $presenter->family() }}</span></div>
@endif
@if ($skills && $presenter->skills() !== '')
    <div class="project-fact"><strong>{{ __('projects.skills') }}</strong> <span>{{ $presenter->skills() }}</span></div>
@endif
