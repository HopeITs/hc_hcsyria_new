<?php

use App\Actions\RecordPageView;
use App\Support\Device;
use Livewire\Attributes\Layout;
use Livewire\Component;

/*
 * The home page, /home. Ported from new/public/home.blade.php and
 * PublicController::homepage() at legacy commit 6d45ef9.
 *
 * Not ported: the funded-project counts, the work-loss ratio, the edition
 * dates and the carousel images the controller computed — the view never
 * used them (the cover became a fixed Roots of Hope image) — and the GSAP
 * horizontal scroller, which pointed at an element that no longer exists and
 * threw on every load.
 */
new #[Layout('layouts::app', [
    'title' => 'Home',
    'keywords' => 'aleppo syria, hope, center, children, university',
    'preloader' => true,
    'overlayUntil' => '#firstContainer',
])] class extends Component
{
    public bool $phone = false;

    public function mount(RecordPageView $recordPageView): void
    {
        $recordPageView('home');

        $this->phone = Device::isPhone(request());
    }

    /** @return list<array{year: string, name: string, text: string, href: string, image: string}> */
    public function programs(): array
    {
        return [
            [
                'year' => '2024',
                'name' => 'LEAP',
                'text' => 'The LEAP is designed to empower a new generation of young Syrian Christian leaders who live out their faith with integrity, insight, and empathy.Through a dynamic and values-driven approuch, that will help..',
                'href' => '/projects/leap',
                'image' => '/images/home/LEAP.jpg',
            ],
            [
                'year' => '2023',
                'name' => 'Rehabilitation',
                'text' => 'Distorted financial capacities were followed by an earthquake that shook the infrastructure of the country, rehabilitating family residencies became the first step to re-normalizing life…',
                'href' => '/projects/rehabilitation',
                'image' => '/images/home/Reh.jpg',
            ],
            [
                'year' => '2022',
                'name' => 'Children University',
                'text' => 'Children were the youngest victims of war, which took a toll on their healthy upbringing, our child-friendly space is a lighthouse that promotes social-emotional health besides educational development…',
                'href' => '/projects/childrenuni',
                'image' => '/images/home/CU.jpg',
            ],
            [
                'year' => '2019',
                'name' => 'School to School',
                'text' => 'The road to shaping the future generation of the country was paved with financial hardships jeopardizing the educational process, Polish schools helped schools in need with equipment and teachers training…',
                'href' => '/projects/school-to-school',
                'image' => '/images/home/S2S.jpg',
            ],
            [
                'year' => '2018',
                'name' => 'Family To Family',
                'text' => 'Families suffered to make ends meet amid a very sharp increase in the prices of daily commodities, which is why Polish families started supporting vulnerable families through monthly cash aid…',
                'href' => '/projects/family-to-family',
                'image' => '/images/home/F2F.jpg',
            ],
            [
                'year' => '2018',
                'name' => 'Micro Project',
                'text' => 'Family providers lost their livelihoods, becoming unable to generate an income, our program aimed to facilitate vocational recovery and encourage beneficiaries’ financial independence…',
                'href' => '/projects/micro-projects',
                'image' => '/images/home/MP.jpg',
            ],
            [
                'year' => '2016',
                'name' => 'DISC',
                'text' => 'Our training program focuses on equipping university graduates with job skills they lack sought by all employers and helping them land job opportunities to establish sustainable careers…',
                'href' => '/projects/disc',
                'image' => '/images/home/DISC.jpg',
            ],
            [
                'year' => '2013',
                'name' => 'Study Zone',
                'text' => 'The scarcity of electricity disrupted university student\'s ability to complete their academic careers, what they truly needed, was a study-friendly environment equipped with basic amenities...',
                'href' => '/projects/study-zone',
                'image' => '/images/home/SZ.jpg',
            ],
        ];
    }
};
?>

<div>
    {{-- Cover: Roots of Hope. A different photo, and a differently placed magazine, for phones. --}}
    <section class="home-cover {{ $phone ? 'home-cover--phone' : 'home-cover--desktop' }}" x-data @contextmenu.prevent>
        <div class="home-cover__frame">
            @if ($phone)
                <img class="home-cover__photo" src="/images/home/roots-cover-mobile.jpg"
                     alt="Roots of Hope — stories of hope, faith &amp; heritage" width="1080" height="1920">
                <a href="/rootsofhope" class="home-cover__magazine" aria-label="Read Roots of Hope magazine">
                    <img src="/images/home/roots-magazine-mobile.png" alt="Roots of Hope magazine cover" width="349" height="478">
                </a>
            @else
                <img class="home-cover__photo" src="/images/home/roots-cover-desktop.jpg"
                     alt="Roots of Hope — stories of hope, faith &amp; heritage" width="3000" height="1553">
                <a href="/rootsofhope" class="home-cover__magazine" aria-label="Read Roots of Hope magazine">
                    <img src="/images/home/roots-magazine-desktop.png" alt="Roots of Hope magazine cover" width="441" height="624">
                </a>
            @endif
        </div>
    </section>

    <section id="page-content">
        <div id="firstContainer" class="home-intro">
            <p class="home-intro__welcome">
                Welcome to <span>HOPE CENTER</span>, a dedicated non-governmental organization,
                working actively to serve the most vulnerable communities in Syria.
            </p>

            <div class="home-matters" x-data @contextmenu.prevent>
                <div class="home-matters__head">
                    <x-ui.reveal :ms="600" class="home-matters__title">why it matters?</x-ui.reveal>
                </div>

                <div>
                    <video class="home-matters__video" playsinline muted autoplay loop>
                        <source src="/images/home/matters.mp4" type="video/mp4">
                    </video>
                </div>
            </div>

            <div class="px-3">
                <div class="page-margin">
                    {{-- A flex row, so the heading's margins do not collapse into the block above. --}}
                    <div class="flex justify-center pt-12 md:pb-12">
                        <x-ui.rule-heading title caps class="w-[calc(58.3333%-10px)]">our programs</x-ui.rule-heading>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-x-6 gap-y-6 pb-6 md:grid-cols-3 lg:grid-cols-4">
                        @foreach ($this->programs() as $program)
                            <x-site.program-card :year="$program['year']" :name="$program['name']" :text="$program['text']"
                                                 :href="$program['href']" :image="$program['image']"/>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
