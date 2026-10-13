<?php

use App\Actions\RecordPageView;
use App\Queries\CoverQuery;
use App\Queries\PartnerQuery;
use App\Support\Device;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/*
 * Who We Are, /who-we-are (and /who-we-are/partners, which the legacy router
 * pointed at the same page). Ported from new/public/who-we-are.blade.php and
 * PublicController::about_us() at legacy commit 6d45ef9.
 *
 * Differences from the legacy page, all invisible: the mission paragraph was
 * written out twice (once for phones, once for the rest) and is now one;
 * the page no longer sets user-scalable=no, which stopped visitors zooming.
 */
new #[Layout('layouts::app', [
    'title' => 'Who We Are',
    'keywords' => 'aleppo syria, hope, center, micro project, projects, caritas poland',
    'preloader' => true,
])] class extends Component
{
    /** The order the partner logos are laid out in, so the rows balance (6 / 6 / 7). */
    private const PARTNER_ORDER = [1, 3, 4, 12, 11, 13, 8, 6, 7, 14, 20, 16, 17, 18, 5, 2, 15, 21];

    public bool $phone = false;

    public function mount(RecordPageView $recordPageView): void
    {
        $recordPageView('who_we_are');

        $this->phone = Device::isPhone(request());
    }

    public function covers(): Collection
    {
        return CoverQuery::images(20);
    }

    public function values(): Collection
    {
        return PartnerQuery::hopeValues();
    }

    public function donors(): Collection
    {
        return PartnerQuery::publicDonorsInOrder(self::PARTNER_ORDER);
    }

    /** @return list<array{title: string, text: string}> */
    public function visions(): array
    {
        return [
            ['title' => 'family support', 'text' => 'Sustainable and income generating opportunities for family providers to become an active agent of economic and social recovery.'],
            ['title' => 'youth support', 'text' => 'Skilled youth with better job opportunities motivated to stay in their country, fostering economic growth and community stability.'],
            ['title' => 'children support', 'text' => 'Children with quality education and creative opportunities in all aspects of growth and development.'],
            ['title' => 'elderlies support', 'text' => 'Resilient, Self- sufficient families that are less reliant on relief aids, especially most vulnerable elderly families.'],
        ];
    }

    /** @return list<string> */
    public function visionPhotos(): array
    {
        return ['elderlies', 'youth', 'children', 'family'];
    }
};
?>

<div id="Who" class="page-start">
    <x-site.page-title title="SOME SEE A HOPELESS END" subtitle="OTHERS SEE AN ENDLESS HOPE"/>

    <x-site.cover-carousel :images="$this->covers()" :phone="$phone"/>

    {{-- Phones get an extra short bar under the cover. --}}
    <x-ui.red-rule short :ms="800" class="sm:hidden" style="margin-top: 3vw"/>
    <x-ui.red-rule :ms="600"/>

    <div id="who-we-are" class="section-gap">
        <div class="px-3">
            <x-ui.reveal :ms="800" class="flex flex-col items-center justify-center">
                <x-ui.section-title>Who We Are</x-ui.section-title>
            </x-ui.reveal>
        </div>
    </div>

    <x-ui.red-rule short :ms="600"/>

    <x-ui.reveal :ms="600" class="about-card">
        <div class="page-margin">
            <div class="px-3">
                <x-ui.reveal :ms="800" class="-mx-3 flex flex-wrap items-center justify-center">
                    <div class="flex w-full items-center justify-center px-3 sm:w-1/2">
                        <div class="about-card__logo">
                            <img class="h-3/4 w-3/4" src="/images/logos/hc.svg" alt="Christian Hope Center">
                        </div>
                    </div>
                    <x-ui.reveal :ms="2000" class="about-card__text w-full px-3 sm:w-1/2">
                        <div>
                            <b>Christian Hope Center</b> is a Syrian non- profit NGO, established in 2018 and headquartered in Aleppo.
                        </div>
                        <div>
                            The organization operates across multiple regions of Syria, delivering humanitarian and development interventions in response to the country’s ongoing needs.
                        </div>
                        Christian Hope Center works in the fields of emergency response, relief assistance, early recovery, community development, and Water, Sanitation, and Hygiene (WASH). Through these programs, the organization aims to strengthen community resilience, promote livelihoods and financial self-reliance.
                    </x-ui.reveal>
                </x-ui.reveal>
            </div>
        </div>
    </x-ui.reveal>

    <x-ui.red-rule :ms="600"/>

    {{-- Values: a photo, and the five values (with a verse each) taking turns beside it. --}}
    <x-ui.reveal :ms="600" id="values-section" class="section-gap section-gap--padded">
        <div class="flex flex-col items-center justify-center pt-4">
            <x-ui.section-title class="capitalize">our values</x-ui.section-title>
        </div>
        <x-ui.red-rule short/>

        <div class="px-3">
            <x-ui.reveal :ms="1000" class="showcase">
                <div class="showcase__half">
                    <img class="showcase__photo" src="/images/mission/Responsibility.jpg" alt="">
                    <img class="torn-edge" src="/images/home/image-mask.png" alt="">
                </div>
                <div class="showcase__half">
                    <x-ui.carousel :count="$this->values()->count()" :interval="8000" :pause="false" keys>
                        <x-ui.carousel.track class="h-[40vw] sm:h-[28vw]">
                            @foreach ($this->values() as $i => $value)
                                <x-ui.carousel.item :i="$i">
                                    <div class="showcase__panel">
                                        <div class="showcase__text">
                                            <div>
                                                <div class="fade-in-up pb-4 uppercase" style="animation-delay: .1s">{{ strip_tags($value->name) }}</div>
                                                <div class="showcase__quote fade-in-up" style="animation-delay: .8s"><span>“</span>{{ strip_tags($value->quote) }}
                                                    <span>“</span></div>
                                                <div class="showcase__source fade-in-up" style="animation-delay: 1.2s">{{ strip_tags($value->quote_source) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </x-ui.carousel.item>
                            @endforeach

                            <x-ui.carousel.controls/>
                        </x-ui.carousel.track>

                        <x-ui.carousel.dots :count="$this->values()->count()"/>
                    </x-ui.carousel>
                </div>
            </x-ui.reveal>
        </div>
    </x-ui.reveal>

    <x-ui.reveal :ms="800"><x-ui.red-rule/></x-ui.reveal>

    <x-ui.reveal :ms="600" id="mission-section" class="section-gap">
        <div class="px-3">
            <div class="flex flex-col items-center justify-center">
                <x-ui.section-title class="pb-0">Our Mission</x-ui.section-title>
                <div class="section-title-desc text-center">
                    <x-ui.reveal :ms="300">Our mission is to strengthen families and communities in Syria by providing practical support, educational opportunities,</x-ui.reveal>
                    <x-ui.reveal :ms="600">social services, and community-based programs that promote dignity, resilience,</x-ui.reveal>
                    <x-ui.reveal :ms="900">and a better quality of life.</x-ui.reveal>
                </div>
            </div>
        </div>
    </x-ui.reveal>

    <x-ui.reveal :ms="800"><x-ui.red-rule/></x-ui.reveal>

    {{-- Vision: four areas of support. The text and the photos are one carousel in two halves. --}}
    <x-ui.reveal :ms="600" id="vision-section" class="section-gap section-gap--padded">
        <div class="flex flex-col items-center justify-center pt-4">
            <x-ui.section-title class="capitalize">our vision and strategy</x-ui.section-title>
        </div>
        <x-ui.red-rule short/>

        <div class="px-3">
            <x-ui.carousel :count="count($this->visions())" :interval="8000" :pause="false" keys :pane="false">
                <x-ui.reveal :ms="1000" class="showcase">
                    <div class="showcase__half">
                        <x-ui.carousel :scope="false">
                            <x-ui.carousel.track class="h-[40vw] sm:h-[28vw]">
                                @foreach ($this->visions() as $i => $vision)
                                    <x-ui.carousel.item :i="$i">
                                        <div class="showcase__panel showcase__panel--vision">
                                            <div class="showcase__text">
                                                <div>
                                                    <div class="showcase__heading">{{ $vision['title'] }}</div>
                                                    <div class="showcase__lead">we aspire to support our community to promote:</div>
                                                    <div class="fade-in-up" style="animation-delay: .8s">
                                                        <span class="showcase__body">{{ $vision['text'] }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </x-ui.carousel.item>
                                @endforeach

                                <x-ui.carousel.controls/>
                            </x-ui.carousel.track>

                            <x-ui.carousel.dots :count="count($this->visions())"/>
                        </x-ui.carousel>
                    </div>

                    <div class="showcase__half">
                        <x-ui.carousel :scope="false">
                            <x-ui.carousel.track class="h-[40vw] sm:h-[28vw]">
                                @foreach ($this->visionPhotos() as $i => $photo)
                                    <x-ui.carousel.item :i="$i" slide class="relative">
                                        <img class="showcase__photo" src="/images/who-we-serve/{{ $photo }}.jpg" alt="">
                                        <img class="torn-edge" src="/images/home/image-mask.png" alt="">
                                    </x-ui.carousel.item>
                                @endforeach

                                <x-ui.carousel.controls/>
                            </x-ui.carousel.track>

                            <x-ui.carousel.dots :count="count($this->visionPhotos())"/>
                        </x-ui.carousel>
                    </div>
                </x-ui.reveal>
            </x-ui.carousel>
        </div>
    </x-ui.reveal>

    <x-ui.reveal :ms="800"><x-ui.red-rule/></x-ui.reveal>

    <div id="donor-section" class="section-gap">
        <div class="px-3">
            <x-ui.reveal :ms="600" class="flex flex-col items-center justify-center">
                <x-ui.section-title>Our Partners</x-ui.section-title>
            </x-ui.reveal>
        </div>

        <x-ui.red-rule short :ms="600"/>

        <x-ui.reveal :ms="600" class="partners-block">
            <div class="px-3">
                <x-ui.reveal :ms="800">
                    <x-site.partners :donors="$this->donors()"/>
                </x-ui.reveal>
            </div>
        </x-ui.reveal>
    </div>
</div>
