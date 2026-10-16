<?php

use App\Actions\RecordPageView;
use App\Queries\CoverQuery;
use App\Support\Device;
use Livewire\Attributes\Layout;
use Livewire\Component;

/*
 * The first gathering of the Syrian Christian youth leaders, /christianyouth.
 * Ported from new/public/youth.blade.php and PublicController::christian_youth()
 * at legacy commit 6d45ef9.
 *
 * Not ported: the English/French switch (English only here), the scripts and
 * stylesheets the page loaded for nothing (jQuery, Bootstrap, fancybox,
 * typeahead.js, Leaflet, Chart.js), the pages of commented-out markup at the
 * end, and the copy of the layout written out for phones: the description,
 * the brief, the two lists and the figures appear once, stacked on phones and
 * side by side above that. The words are the legacy language file's
 * (lang/en/youth.php); the description and the figures are cut into lines
 * (one reveal each) only where the legacy page cut them.
 *
 * The photos beside the two lists are files the legacy page named directly
 * (images/youth/new/1.jpg to 8.jpg); they are not rows in the database.
 * /christianyouth/{id} (a member's card) is a separate page, still to port.
 */
new #[Layout('layouts::app', [
    'title' => 'Christian Youth',
    'keywords' => null,
    'preloader' => true,
])] class extends Component
{
    /** The page's id in `backgrounds`: its cover photos. */
    private const COVER = 15;

    public bool $phone = false;

    public function mount(RecordPageView $recordPageView): void
    {
        $recordPageView('christian_youth');

        $this->phone = Device::isPhone(request());
    }

    public function with(): array
    {
        return [
            'covers' => CoverQuery::images(self::COVER),
        ];
    }
};
?>

<div id="Youth" class="page-start">
    <x-site.page-title/>

    <div class="youth-title">The First Gathering Of The Syrian Christian Youth Leaders</div>

    <x-site.cover-carousel :images="$covers" :phone="$phone"/>

    {{-- The emblem hangs over the cover: out of the flow, 19vw from the top of the page, centred. --}}
    <div class="flex justify-center">
        <x-ui.reveal :ms="400" class="reveal--zoom youth-emblem">
            <img class="youth-emblem__image" src="/images/logos/CYG.svg" alt="The First Gathering of the Syrian Christian Youth Leaders">
        </x-ui.reveal>
    </div>

    {{-- Phones read the description as one paragraph; wider screens as four lines, one reveal each. --}}
    <div class="px-3 max-sm:mb-2">
        <div class="-mx-3 flex justify-center sm:justify-start">
            <div class="w-11/12 px-3 sm:w-full sm:pr-0">
                <div class="youth-description">
                    <x-ui.reveal :ms="300" class="max-sm:contents">For years young Syrian Christians have been suffering in silence from immense daily hardships, and most lost Hope. </x-ui.reveal>
                    <x-ui.reveal :ms="600" class="max-sm:contents">The prolonged frustration and distance from the Christian Hope is pushing them towards immigration. Therefore, </x-ui.reveal>
                    <x-ui.reveal :ms="900" class="max-sm:contents">Hope Center designed this meeting to create the opportunity for this young generation to express their </x-ui.reveal>
                    <x-ui.reveal :ms="1200" class="max-sm:contents">needs, discuss their problems, and propose real solutions that will help solve current problems.</x-ui.reveal>
                </div>
            </div>
        </div>
    </div>

    <x-ui.red-rule :ms="300" class="sm:hidden"/>

    <div id="about-section">
        <div class="youth-seam"></div>
        <x-ui.red-rule class="youth-opening-rule max-sm:hidden"/>

        <div class="mt-[13vw] mb-2 sm:mt-[7vw] sm:mb-0">
            <div class="px-3">
                <div class="-mx-3 flex flex-wrap justify-center sm:justify-start">
                    <div class="w-11/12 px-3 sm:w-1/2">
                        <div class="youth-copy text-end">
                            <div class="-mx-3 flex">
                                <div class="flex-1 px-3">
                                    <x-ui.reveal :ms="800" class="youth-brief">
                                        <div>The Syrian Christian Young Leaders Conference is held under the guidance of their Eminence Eastern Patriarchy, in the conference palace in Damascus, from 26 to 30 October under the motto</div>
                                        <x-ui.reveal :ms="1000" class="youth-quote">“Together Today, for a<br class="max-sm:hidden"> Better Future”</x-ui.reveal>
                                    </x-ui.reveal>
                                </div>
                            </div>
                        </div>
                    </div>

                    <x-site.video-column src="https://www.youtube-nocookie.com/embed/gF8AzxiWRos" title="Syrian Christian Youth Gathering | Intro" class="max-sm:order-first"/>
                </div>
            </div>
        </div>
    </div>

    <x-ui.red-rule :ms="800" class="sm:hidden"/>

    <div class="section-gap max-sm:mb-2">
        <x-ui.red-rule class="youth-second-rule max-sm:hidden"/>

        <x-ui.reveal :ms="1000">
            <div class="px-3">
                <div class="-mx-3 flex flex-wrap">
                    <div class="w-full sm:w-1/2 max-sm:mt-4">
                        <x-ui.carousel :count="4" :interval="5000" class="youth-slides">
                            <x-ui.carousel.track class="youth-slides__track">
                                @foreach (range(1, 4) as $i)
                                    <x-ui.carousel.item :i="$i - 1" slide class="relative">
                                        <img class="block h-full w-full object-cover" src="/images/youth/new/{{ $i }}.jpg" alt="">
                                        <img class="torn-edge" src="/images/home/image-mask.png" alt="">
                                    </x-ui.carousel.item>
                                @endforeach

                                <x-ui.carousel.controls/>
                            </x-ui.carousel.track>

                            <x-ui.carousel.dots :count="4"/>
                        </x-ui.carousel>
                    </div>

                    <div class="w-full px-3 sm:w-1/2 max-sm:mt-4">
                        <div class="youth-copy">
                            <div class="-mx-3 flex">
                                <div class="flex flex-1 flex-col justify-end px-3">
                                    <x-ui.reveal :ms="800" class="youth-heading">Work Plan and Agenda:</x-ui.reveal>
                                    <x-ui.reveal :ms="1000" as="ul" class="youth-list">
                                        <li>Preceding the conference, three preparatory meetings that aim to give the participating Syrian youth who are divided into eight groups, according to their geographic location, the chance to prepare what will be discussed during the conference, and allow them to share their past experience and future aspirations.</li>
                                        <li>The conference that will take place from 26 to 30 October will gather the Syrian young leaders, the ecclesiastical working closely with youth and representatives of Christian organizations present and active in Syria. </li>
                                    </x-ui.reveal>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui.reveal>
    </div>

    <x-ui.red-rule :ms="1000" class="sm:hidden"/>

    <div id="plan" class="section-gap max-sm:mb-2">
        <x-ui.red-rule class="youth-plan-rule max-sm:hidden"/>

        <x-ui.reveal :ms="1000">
            <div class="px-3">
                <div class="-mx-3 flex flex-wrap">
                    <div class="w-full sm:order-last sm:w-1/2 max-sm:mt-4">
                        <x-ui.carousel :count="4" :interval="5000" class="youth-slides">
                            <x-ui.carousel.track class="youth-slides__track">
                                @foreach (range(5, 8) as $n => $i)
                                    <x-ui.carousel.item :i="$n" slide class="relative">
                                        <img class="block h-full w-full object-cover" src="/images/youth/new/{{ $i }}.jpg" alt="">
                                        <img class="torn-edge" src="/images/home/image-mask.png" alt="">
                                    </x-ui.carousel.item>
                                @endforeach

                                <x-ui.carousel.controls/>
                            </x-ui.carousel.track>

                            <x-ui.carousel.dots :count="4"/>
                        </x-ui.carousel>
                    </div>

                    <div class="w-full px-3 sm:w-1/2">
                        <div class="youth-copy">
                            <div class="-mx-3 flex">
                                <div class="flex flex-1 flex-col justify-end px-3 max-sm:mt-4">
                                    <x-ui.reveal :ms="800" class="youth-heading">The Main Objectives:</x-ui.reveal>
                                    <x-ui.reveal :ms="1000" as="ul" class="youth-list">
                                        <li>The exchange of experiences between all participants, as leaders get the opportunity to share other leaders experience from different Syrian youth communities.</li>
                                        <li>Display the work conducted in 8 different cities in Syria, and explain their community’s social and Christian youth activities.</li>
                                        <li>Create a personal space for each leader, that allows them to find their true Christian belief and hope in their lives.</li>
                                        <li>Put forward recommendations which the participants aspire to achieve in their cities in the future.</li>
                                    </x-ui.reveal>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui.reveal>
    </div>

    <x-ui.red-rule :ms="1000" class="sm:hidden"/>

    <div id="statistic-section" class="section-gap">
        <div class="px-3">
            <x-ui.reveal :ms="600" class="-mx-3 flex">
                <div class="flex w-full flex-col items-center justify-center px-3">
                    <x-ui.section-title class="youth-participants-title max-sm:mt-4">Participants</x-ui.section-title>
                    <div class="-mx-3 flex justify-center text-center">
                        <div class="px-3">
                            <div class="section-title-desc">
                                <x-ui.reveal :ms="300" class="max-sm:contents">More <span class="youth-figure"> than 135 influencing young individuals</span> will form <span class="youth-figure"> 8 groups </span>representing different locations in Syria, and these locations are: </x-ui.reveal>
                                <x-ui.reveal :ms="600" class="max-sm:contents">Damascus/Damascus villages, Aleppo, Homs, Hama, Lattakia,<br class="sm:hidden"> Tartous, Daraa/Sweidaa, Hasaka/Qamishli</x-ui.reveal>
                            </div>
                        </div>
                    </div>
                </div>
            </x-ui.reveal>
        </div>
    </div>

    <x-ui.red-rule class="red-rule--end"/>
</div>
