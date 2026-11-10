<?php

use App\Actions\RecordPageView;
use App\Queries\CoverQuery;
use App\Queries\DiscStatisticsQuery;
use App\Queries\ProjectPageQuery;
use App\Queries\SuccessQuery;
use App\Support\Device;
use Livewire\Attributes\Layout;
use Livewire\Component;

/*
 * DISC, /projects/disc. Ported from new/public/disc.blade.php and
 * PublicController::disc() at legacy commit 6d45ef9.
 *
 * Not ported: the activity search (the box was commented out of the page,
 * though the controller still answered ?search=), the English/French switch
 * (the site is English only here), the three copies of the activity list the
 * legacy view wrote out for phones and again for everything else (one card
 * now), amCharts (the learner rings are SVG), the Facebook/Chart.js/GSAP
 * scripts the page loaded and never used, and the narrow-screen "Social
 * Zone" text copied from Study Zone, which is kept as the legacy page
 * showed it until a category is used.
 */
new #[Layout('layouts::app', [
    'title' => 'what_we_do.disc',
    'keywords' => 'aleppo syria, hope, center, disc, development impact support center',
    'footer' => 'disc',
    'preloader' => true,
])] class extends Component
{
    /** The programme's id in what_categories: its covers, brief, funders and stories. */
    private const CATEGORY = 5;

    /** The sub-category of its activities, and of its job-opportunity testimonies. */
    private const ACTIVITIES = 8;

    private const TESTIMONIES = 16;

    public bool $phone = false;

    public function mount(RecordPageView $recordPageView): void
    {
        $recordPageView('disc');

        $this->phone = Device::isPhone(request());
    }

    public function with(): array
    {
        return [
            'covers' => CoverQuery::images(self::CATEGORY),
            'brief' => ProjectPageQuery::brief(self::CATEGORY),
            'stories' => SuccessQuery::latest(self::CATEGORY, self::TESTIMONIES, 4),
            'ongoing' => ProjectPageQuery::funders(self::CATEGORY, true),
            'previous' => ProjectPageQuery::funders(self::CATEGORY, false),
        ];
    }

    /**
     * The tabs of the activities: the three towns, each with the places it lists (legacy's page
     * parameter, ini_A, ini_H, ini_D, still opens its town).
     *
     * @return array<string, array<string, mixed>>
     */
    public function towns(): array
    {
        $sorry = ["Sorry, we couldn't find any results", __('projects.sorryMessage2')];

        return [
            'aleppo' => ['label' => __('projects.aleppo'), 'places' => [3], 'page' => 'ini_A', 'byDate' => true, 'empty' => ['No Activities Yet', null]],
            'homs' => ['label' => __('projects.homs'), 'places' => [1], 'page' => 'ini_H', 'byDate' => false, 'empty' => $sorry],
            'damas' => ['label' => __('projects.damascus'), 'places' => [2], 'page' => 'ini_D', 'byDate' => false, 'empty' => $sorry],
        ];
    }

    /** @return list<array{label: string, title: string, image: string, lines: list<array{ms: int, text: string}>}> */
    public function categories(): array
    {
        return [
            [
                'label' => 'English|Skills',
                'title' => 'English Skills',
                'image' => \App\Support\Site\SiteContent::image('disc.english_image'),
                'lines' => [
                    ['ms' => 300, 'text' => 'Fluency in English is essential to access more opportunities and have a successful career.'],
                    ['ms' => 600, 'text' => 'Our goal is to create a positive and exciting learning environment where students'],
                    ['ms' => 900, 'text' => 'can improve their language skills and achieve their language learning goals.'],
                    ['ms' => 1000, 'text' => 'Through the innovative Wide-Angle e-book from Oxford students have fully'],
                    ['ms' => 1100, 'text' => 'automated learning experience with access to extracurricular activities'],
                    ['ms' => 1200, 'text' => 'to support their language learning journey'],
                ],
            ],
            [
                'label' => 'Computer|Skills',
                'title' => 'Computer Skills',
                'image' => \App\Support\Site\SiteContent::image('disc.computer_image'),
                'lines' => [
                    ['ms' => 600, 'text' => 'These sessions aid in the development of digital skills, fostering confidence,'],
                    ['ms' => 800, 'text' => 'and keeping participants up-to-date with the latest software.'],
                    ['ms' => 900, 'text' => 'The program encompasses the International Computer Driving License (ICDL)'],
                    ['ms' => 1000, 'text' => 'and an advanced course aimed at individuals seeking to further enhance their skills.'],
                ],
            ],
            [
                'label' => 'Accompaniment',
                'title' => 'Accompaniment',
                'image' => \App\Support\Site\SiteContent::image('disc.accompaniment_image'),
                'lines' => [
                    ['ms' => 600, 'text' => 'The program aims to foster individuals in personal growth'],
                    ['ms' => 800, 'text' => 'the youth by promoting self-awareness and development.'],
                    ['ms' => 900, 'text' => 'It helps them express emotions, organize thoughts, and explore'],
                    ['ms' => 1000, 'text' => 'career and life aspirations in a healthy and secure environment.'],
                    ['ms' => 1100, 'text' => 'The adopted program is The Positive Psychology Toolkit,'],
                    ['ms' => 1200, 'text' => 'which encompasses a wide range of over 500 exercises covering topics'],
                    ['ms' => 1300, 'text' => 'such as life balance, resilience building, goal achievement, and more.'],
                ],
            ],
            [
                'label' => 'Employability|Skills',
                'title' => 'Employability Skills',
                'image' => \App\Support\Site\SiteContent::image('disc.employability_image'),
                'lines' => [
                    ['ms' => 600, 'text' => 'A seriese of training sessions focusing on crucial interpersonal skills'],
                    ['ms' => 800, 'text' => 'to assist individuals in securing employment and thriving in professional settings.'],
                    ['ms' => 900, 'text' => 'This comprehensive program on employability skills adresses key areas'],
                    ['ms' => 1100, 'text' => 'such as CV preparation, excelling in job interviews, understanding AI, and more.'],
                    ['ms' => 1100, 'text' => 'Cultivating these non-technical skills broaden career'],
                    ['ms' => 1100, 'text' => 'prospects and equip participants for enhanced job performance through'],
                    ['ms' => 1200, 'text' => 'practical experience in a simulated work setting.'],
                ],
            ],
        ];
    }

    /** What the panel says before a button is used: the legacy markup, which differs from the buttons' own text. */
    public function intro(): array
    {
        return [
            'wide' => [
                'title' => 'English Skills',
                'lines' => [
                    ['ms' => 600, 'text' => 'Fluency in English is essential to access more opportunities and have a successful career.'],
                    ['ms' => 900, 'text' => 'Our goal is to create a positive and exciting learning environment where students can improve their language skills and achieve their language learning goals.'],
                    ['ms' => 1100, 'text' => 'Through the innovative Wide-Angle e-book from Oxford students have fully automated learning experience with access to extracurricular activities to support their language learning journey'],
                ],
            ],
            'narrow' => [
                'title' => 'Social Zone',
                'lines' => [
                    ['ms' => 300, 'text' => 'Designated for small group study and permits study and project-related discussions'],
                    ['ms' => 500, 'text' => 'without causing disturbance to others. Cellphones must be set to silent or vibrate mode,'],
                    ['ms' => 700, 'text' => 'and the administration selects the music.'],
                ],
            ],
        ];
    }

    /** The centres on the map: governorate => centre number; and each centre's start and logo. */
    public function centres(): array
    {
        $figures = DiscStatisticsQuery::figures();

        return [
            ['title' => 'Number of DISC students in HOMS', 'year' => '2018', 'logo' => '/images/logos/HDISC1.svg', 'figures' => $figures['hdisc'] ?? null],
            ['title' => 'Number of DISC students in DAMASCUS', 'year' => '2019', 'logo' => '/images/logos/DDISC1.svg', 'figures' => $figures['ddisc'] ?? null],
            ['title' => 'Number of DISC students in ALEPPO', 'year' => '2022', 'logo' => '/images/logos/ADISC1.svg', 'figures' => $figures['adisc'] ?? null],
        ];
    }
};
?>

<div id="DISC" class="page-start accent-disc" style="--logo-width: 8vw; --logo-width-phone: 8vw" x-data x-hide-nav-between="'#categories-section', '#testimonies-section'">
    <x-site.page-title solo :subtitle="__('what_we_do.disc')">
        <img class="reveal page-title__logo" style="--reveal-ms: 1000ms" x-reveal
             src="/images/logos/disc.svg" alt="DISC">
    </x-site.page-title>

    <x-site.cover-carousel :images="$covers" :phone="$phone"/>

    {{-- Phones get a short bar under the cover, and another under the brief. --}}
    <x-ui.red-rule short :ms="800" class="sm:hidden" style="margin-top: 6vw"/>

    <x-site.project-brief :project="$brief"/>

    <x-ui.red-rule :ms="800" class="sm:hidden" style="margin-top: 1vw"/>
    <x-ui.red-rule short :ms="800"/>

    <x-ui.reveal :ms="800"><x-site.category-showcase :categories="$this->categories()" :intro="$this->intro()"/></x-ui.reveal>

    <x-site.testimonials :stories="$stories"/>

    <x-ui.reveal :ms="800"><x-ui.red-rule :ms="800"/></x-ui.reveal>

    <x-site.centre-statistics :centres="$this->centres()" :cities="['SY-HI' => 0, 'SY-RD' => 1, 'SY-DI' => 1, 'SY-HL' => 2]"/>

    <x-ui.red-rule :ms="800" class="mt-4"/>

    <livewire:activity-list :category="self::CATEGORY" :sub-category="self::ACTIVITIES" type="disc" :towns="$this->towns()"
                            :title="__('studyzone.activities')" :phone="$phone"/>

    <x-ui.red-rule :ms="800"/>
    <x-site.funders :ongoing="$ongoing" :previous="$previous" page="/projects/disc"/>
    <x-ui.red-rule class="red-rule--end"/>
</div>
