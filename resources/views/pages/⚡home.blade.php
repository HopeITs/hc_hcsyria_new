<?php

use App\Actions\RecordPageView;
use App\Support\Device;
use App\Support\Site\SiteContent;
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
                'text' => SiteContent::text('home.leap_text'),
                'href' => '/projects/leap',
                'image' => SiteContent::image('home.leap_image'),
            ],
            [
                'year' => '2023',
                'name' => 'Rehabilitation',
                'text' => SiteContent::text('home.rehabilitation_text'),
                'href' => '/projects/rehabilitation',
                'image' => SiteContent::image('home.rehabilitation_image'),
            ],
            [
                'year' => '2022',
                'name' => 'Children University',
                'text' => SiteContent::text('home.children_university_text'),
                'href' => '/projects/childrenuni',
                'image' => SiteContent::image('home.children_university_image'),
            ],
            [
                'year' => '2019',
                'name' => 'School to School',
                'text' => SiteContent::text('home.school_to_school_text'),
                'href' => '/projects/school-to-school',
                'image' => SiteContent::image('home.school_to_school_image'),
            ],
            [
                'year' => '2018',
                'name' => 'Family To Family',
                'text' => SiteContent::text('home.family_to_family_text'),
                'href' => '/projects/family-to-family',
                'image' => SiteContent::image('home.family_to_family_image'),
            ],
            [
                'year' => '2018',
                'name' => 'Micro Project',
                'text' => SiteContent::text('home.micro_project_text'),
                'href' => '/projects/micro-projects',
                'image' => SiteContent::image('home.micro_project_image'),
            ],
            [
                'year' => '2016',
                'name' => 'DISC',
                'text' => SiteContent::text('home.disc_text'),
                'href' => '/projects/disc',
                'image' => SiteContent::image('home.disc_image'),
            ],
            [
                'year' => '2013',
                'name' => 'Study Zone',
                'text' => SiteContent::text('home.study_zone_text'),
                'href' => '/projects/study-zone',
                'image' => SiteContent::image('home.study_zone_image'),
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
                <img class="home-cover__photo" src="{{ \App\Support\Site\SiteContent::image('home.cover_mobile') }}"
                     alt="Roots of Hope — stories of hope, faith &amp; heritage" width="1080" height="1920">
                <a href="/rootsofhope" class="home-cover__magazine" aria-label="Read Roots of Hope magazine">
                    <img src="{{ \App\Support\Site\SiteContent::image('home.magazine_mobile') }}" alt="Roots of Hope magazine cover" width="349" height="478">
                </a>
            @else
                <img class="home-cover__photo" src="{{ \App\Support\Site\SiteContent::image('home.cover_desktop') }}"
                     alt="Roots of Hope — stories of hope, faith &amp; heritage" width="3000" height="1553">
                <a href="/rootsofhope" class="home-cover__magazine" aria-label="Read Roots of Hope magazine">
                    <img src="{{ \App\Support\Site\SiteContent::image('home.magazine_desktop') }}" alt="Roots of Hope magazine cover" width="441" height="624">
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
                        <source src="{{ \App\Support\Site\SiteContent::video('home.matters_video') }}" type="video/mp4">
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
