@props(['presenter', 'skills' => false, 'highlight' => null])

{{-- The facts of a project: who it is for, when it was funded, where, how old and how big the family. --}}
@php($label = $presenter->peopleLabel())
@if ($presenter->people())
    <div class="project-fact"><strong>{{ $label }}</strong> <span>{{ \App\Support\Highlight::text(implode(', ', $presenter->people()), $highlight) }}</span></div>
@endif
@if ($presenter->fundDate())
    <div class="project-fact"><strong>{{ __('projects.fundDate') }}</strong> <span>{{ $presenter->fundDate() }}</span></div>
@endif
@if ($presenter->city() !== '')
    <div class="project-fact"><strong>City:</strong> <span>{{ \App\Support\Highlight::text($presenter->city(), $highlight) }}</span></div>
@endif
@if ($presenter->age() !== null)
    <div class="project-fact"><strong>{{ __('projects.age') }}</strong> <span>{{ $presenter->age() }} {{ __('projects.years') }}</span></div>
@endif
@if ($presenter->family() !== null)
    <div class="project-fact"><strong>{{ __('projects.family') }}</strong> <span>{{ $presenter->family() }}</span></div>
@endif
@if ($skills && $presenter->skills() !== '')
    <div class="project-fact"><strong>{{ __('projects.skills') }}</strong> <span>{{ \App\Support\Highlight::text($presenter->skills(), $highlight) }}</span></div>
@endif
