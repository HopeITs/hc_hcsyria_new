<?php

use App\Actions\RecordPageView;
use App\Queries\CategoryQuery;
use App\Queries\CoverQuery;
use App\Queries\InitiativeListQuery;
use App\Support\Device;
use Livewire\Attributes\Layout;
use Livewire\Component;

/*
 * A category's activities, /projects/{type}: social activities, rehabilitation,
 * relief and earthquake response. Ported from new/public/initiatives.blade.php
 * and PublicController::initiatives_by_type() at legacy commit 6d45ef9.
 *
 * Only the four categories the navbar links to have a page. Legacy answered
 * every other name too: the programmes with their own page never got here, and
 * the rest (development, Homework Room, ...) drew a hero over an empty list
 * (Homework Room crashed with a 500); those are 404 here.
 *
 * Not ported: the English/French switch (English only here), the second copy
 * of the list the legacy view wrote out for phones, its jQuery click handler
 * for .org-post (no element has that class), the fancybox script for School to
 * School (that programme has its own page), and the description markup for
 * the programme categories that never reach this page.
 */
new #[Layout('layouts::app', ['keywords' => 'aleppo, syria, hope center, initiatives', 'preloader' => true])] class extends Component
{
    /** The categories with a page here: the address segment => [what_categories id, the page_views name legacy counted it under]. */
    private const CATEGORIES = [
        'social-activities' => [1, 'social_activities_all'],
        'rehabilitation' => [3, 'Rehabilitation'],
        'relief' => [4, 'relief_all'],
        'earthquake-response' => [18, 'Earthquake_response_all'],
    ];

    public string $type = '';

    public bool $phone = false;

    public function mount(string $type, RecordPageView $recordPageView): void
    {
        abort_unless(array_key_exists($type, self::CATEGORIES), 404);

        $this->type = $type;
        $this->phone = Device::isPhone(request());

        $recordPageView(self::CATEGORIES[$type][1]);
    }

    public function render()
    {
        $category = CategoryQuery::what()->bySlug($this->type);
        abort_unless($category !== null && $category->id === self::CATEGORIES[$this->type][0], 404);

        return $this->view([
            'category' => $category,
            'covers' => CoverQuery::images($category->id),
            'activities' => InitiativeListQuery::latest($category->id),
        ])->title($category->name);
    }
};
?>

<div id="initiatives" class="page-start" style="--title-line: 1.6vw; --title-strong: 2.4vw; --title-line-phone: 1.65vw; --title-strong-phone: 2vw">
    <x-site.page-title title="SOME SEE A HOPELESS END" subtitle="OTHERS SEE AN ENDLESS HOPE"/>

    <x-site.cover-carousel :images="$covers" :phone="$phone"/>

    <x-ui.red-rule short class="max-sm:hidden" style="margin-top: 2vw"/>
    <x-ui.red-rule short :ms="600" class="sm:hidden" style="margin-top: 4.5vw"/>

    <div class="mt-[6vw] max-sm:mt-[14vw]">
        <div class="px-3">
            <x-ui.reveal :ms="600" class="-mx-3 flex">
                <div class="flex w-full flex-col items-center justify-center px-3">
                    <x-ui.section-title style="padding-bottom: 0">{{ $category->name }}</x-ui.section-title>
                    <div class="-mx-3 flex w-full justify-center text-center">
                        <div class="w-[91.6667%] px-3 sm:w-5/6">
                            <div class="section-title-desc">
                                @switch($category->id)
                                    @case(1)
                                        <div class="max-sm:hidden">
                                            <x-ui.reveal :ms="300">The Christian organizations in Aleppo have lost the ability to do most of their common activities due to the war and the financial crisis. By supporting these organization,the center aims to give them a chance to continue</x-ui.reveal>
                                            <x-ui.reveal :ms="600">supporting their activities through a micro-project.The micro-project should also</x-ui.reveal>
                                            <x-ui.reveal :ms="900">serve a purpose related to the Christian message of the organization.</x-ui.reveal>
                                        </div>
                                        <x-ui.reveal :ms="300" class="sm:hidden">The Christian organizations in Aleppo have lost the ability to do most of their common activities due to the war and the financial crisis. By supporting these organization,the center aims to give them a chance to continue supporting their activities through a micro-project.The micro-project should also serve a purpose related to the Christian message of the organization.</x-ui.reveal>
                                        @break
                                    @case(3)
                                        <x-ui.reveal :ms="300" class="max-sm:hidden">
                                            <p class="m-0 text-justify [text-align-last:justify]">The Rehabilitation work in the post-war period is crucial for the early recovery of communities. It is also imperative to restore damaged infrastructures and support services that allow people to resume their lives. Christian Hope Center has contributed in this field through the rehabilitation of destroyed educational and communal facilities such as schools and playgrounds, and the restoration of houses, and workplaces. Our efforts also extended to</p>
                                            <p class="m-0 text-center">supporting rural communities by providing photovoltaic infrastructure for electricity production and water extraction in several villages.</p>
                                        </x-ui.reveal>
                                        <x-ui.reveal :ms="600" class="sm:hidden">
                                            <div>The Rehabilitation work in the post-war period is crucial for the early recovery of communities. It is also imperative to restore damaged infrastructures and support services that allow people to resume their lives. Christian Hope Center has contributed in this field through the rehabilitation of destroyed educational and communal facilities such as schools and playgrounds, and the restoration of houses, and workplaces. Our efforts also extended to supporting rural communities by providing photovoltaic infrastructure for electricity production and water extraction in several villages.</div>
                                        </x-ui.reveal>
                                        @break
                                    @case(4)
                                        <div class="max-sm:hidden">
                                            <x-ui.reveal :ms="300">Our relief programs are designed to ease the hardships resulting from the Syrian war and its intricate</x-ui.reveal>
                                            <x-ui.reveal :ms="600">economic effects, which are putting strain on the majority of families living below the poverty line.</x-ui.reveal>
                                            <x-ui.reveal :ms="900">Our goal is to help these families meet their basic needs and maintain their dignity.</x-ui.reveal>
                                        </div>
                                        <x-ui.reveal :ms="300" class="sm:hidden">
                                            <div>Our relief programs are designed to ease the hardships resulting from the Syrian war and its intricate economic effects, which are putting strain on the majority of families living below the poverty line. Our goal is to help these families meet their basic needs and maintain their dignity.</div>
                                        </x-ui.reveal>
                                        @break
                                    @case(18)
                                        <div class="max-sm:hidden">
                                            <x-ui.reveal :ms="300">Following the 7.8 magnitude earthquake that struck Turkey and Syria on February 6th, the cities of</x-ui.reveal>
                                            <x-ui.reveal :ms="600">Aleppo and Latakia suffered widespread damage and destruction, with thousands of buildings</x-ui.reveal>
                                            <x-ui.reveal :ms="900">destroyed and tens of thousands of people displaced.</x-ui.reveal>
                                        </div>
                                        <x-ui.reveal :ms="300" class="sm:hidden">
                                            <div>Following the 7.8 magnitude earthquake that struck Turkey</div>
                                            <div>and Syria on February 6th, the cities of Aleppo and Latakia</div>
                                            <div>suffered widespread damage and destruction,</div>
                                            <div>with thousands of buildings destroyed and</div>
                                            <div>tens of thousands of people displaced.</div>
                                        </x-ui.reveal>
                                        @break
                                @endswitch
                            </div>
                        </div>
                    </div>
                </div>
            </x-ui.reveal>
        </div>
    </div>

    <x-ui.red-rule short :ms="600"/>

    {{-- The last card has no space under it (legacy .single-card:last-child). --}}
    <x-ui.reveal :ms="600" class="mt-[5vw] max-sm:mt-[10vw] [&>.activity-card:last-child_.activity-card\_\_inner]:mb-0">
        @if ($category->id === 4)
            {{-- Relief opens with its sister programme, Family to Family: four photos beside a short text. --}}
            <div class="activity-card">
                <div class="activity-card__inner">
                    <div class="-mx-3 flex flex-wrap">
                        <div class="activity-card__photos flex w-full justify-end px-3 sm:w-1/2 sm:pr-0">
                            <x-ui.carousel :count="4" :interval="5000" class="activity-card__carousel w-full">
                                <x-ui.carousel.track class="activity-card__track">
                                    @foreach (range(1, 4) as $i)
                                        <x-ui.carousel.item :i="$i - 1" slide>
                                            {{-- No set height: the photo keeps its own shape and the track clips it, as in legacy. --}}
                                            <img class="block w-full" src="/images/initiatives/Family to Family/f{{ $i }}.jpg" alt="">
                                        </x-ui.carousel.item>
                                    @endforeach

                                    <x-ui.carousel.controls/>
                                </x-ui.carousel.track>
                            </x-ui.carousel>
                        </div>

                        <div class="relative w-full px-3 sm:w-1/2">
                            <div class="activity-card__text">
                                <div class="-mx-3 mt-[1vw] flex flex-wrap items-center">
                                    <div class="w-full px-3"><div class="activity-card__title">{{ __('development.f2fTitle') }}</div></div>
                                    <div class="w-full px-3"><div class="activity-card__rule"></div></div>
                                </div>

                                <div class="activity-card__content">{{ __('development.f2fText') }}</div>

                                <div class="-mx-3 flex">
                                    <div class="flex grow justify-end px-3">
                                        <a class="activity-card__more" href="/projects/family-to-family">{{ __('development.previewProgram') }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @foreach ($activities as $activity)
            <x-site.activity-card :$activity :type="$type" :flip="! $phone && ($category->id === 4 ? $loop->odd : $loop->even)" :mirrored="$category->id === 4" summary/>
        @endforeach
    </x-ui.reveal>

    <x-ui.red-rule class="red-rule--end"/>
</div>
