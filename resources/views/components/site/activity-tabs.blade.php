@props(['lists', 'type', 'selected', 'phone' => false])

{{--
    A programme's activities, one tab per town, each a list of cards a page
    at a time (resources/css/ui/activity-tabs.css, resources/js/activity-tabs.js).
    $lists is town key => ['label', 'activities' (a paginator), 'empty']; the
    wide layout puts the photos on alternate sides, counting across the towns.
    $selected is the tab open first; $type the programme's address segment.
--}}
@php
    $count = 0;
@endphp

<div x-data="activityTabs({ tab: @js($selected) })">
    <ul class="activity-tabs" role="tablist">
        @foreach ($lists as $key => $list)
            <li class="activity-tabs__item" role="presentation">
                <button type="button" class="activity-tabs__tab" :class="{ 'is-active': tab === @js($key) }" role="tab"
                        :aria-selected="tab === @js($key)" @click="select(@js($key))">{{ $list['label'] }}</button>
            </li>
        @endforeach
    </ul>
    <div class="h-[3vw] bg-white"></div>

    <div class="relative bg-white">
        @foreach ($lists as $key => $list)
            <div class="activity-tabs__pane" role="tabpanel" x-show="tab === @js($key)" @if ($key !== $selected) x-cloak @endif>
                @forelse ($list['activities'] as $activity)
                    <x-site.activity-card :activity="$activity" :type="$type" :flip="! $phone && $count++ % 2 === 1"/>
                @empty
                    <div class="activity-card">
                        <div class="activity-card__inner">
                            <div class="-mx-3 flex">
                                <div class="activity-tabs__empty flex w-full flex-col justify-end px-3 pr-1">
                                    <div class="text-center">{{ $list['empty'][0] }}</div>
                                    @if ($list['empty'][1])
                                        <div>{{ $list['empty'][1] }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforelse

                <x-ui.red-rule short :ms="600"/>
                <x-ui.reveal :ms="800" class="text-center">
                    {{ $list['activities']->withQueryString()->links('ui.pagination') }}
                </x-ui.reveal>
            </div>
        @endforeach
    </div>
</div>
