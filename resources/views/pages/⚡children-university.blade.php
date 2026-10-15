<?php

use App\Actions\RecordPageView;
use App\Queries\CoverQuery;
use App\Queries\ProjectPageQuery;
use App\Support\Device;
use Livewire\Attributes\Layout;
use Livewire\Component;

/*
 * Children University, /projects/childrenuni. Ported from
 * new/public/children-uni.blade.php and PublicController::childrenUni() at
 * legacy commit 6d45ef9.
 *
 * Not ported: the English/French switch (English only here), the second
 * copy of the activity list the legacy view wrote out for phones, the
 * scripts the page loaded for the subject buttons and the tabs (jQuery,
 * Bootstrap, typeahead.js) and the Leaflet, Chart.js and fancybox links it
 * never used. The subjects' text was written twice in the legacy script, for
 * hovering and for clicking, with the same words; it is written once.
 * The activities are the shared list of Study Zone and DISC (filter bar,
 * tiles or rows, Load More) in place of legacy's two tabs, search box and
 * pager. Search takes bound values; legacy spliced them into its SQL.
 */
new #[Layout('layouts::app', [
    'title' => 'what_we_do.children_uni',
    'keywords' => 'aleppo syria, hope, center, children, university',
    'footer' => 'children',
    'preloader' => true,
])] class extends Component
{
    /** The programme's id in what_categories: its covers, brief, funders and activities. */
    private const CATEGORY = 17;

    public bool $phone = false;

    public function mount(RecordPageView $recordPageView): void
    {
        $recordPageView('children_uni');

        $this->phone = Device::isPhone(request());
    }

    public function with(): array
    {
        return [
            'covers' => CoverQuery::images(self::CATEGORY),
            'brief' => ProjectPageQuery::brief(self::CATEGORY),
            'ongoing' => ProjectPageQuery::funders(self::CATEGORY, true),
            'previous' => ProjectPageQuery::funders(self::CATEGORY, false),
        ];
    }

    /** The two towns of the activity list; the programme has no sub-categories, and Homs is place 1 alone. */
    public function towns(): array
    {
        $sorry = ["Sorry, we couldn't find any results", __('projects.sorryMessage2')];

        return [
            'aleppo' => ['label' => __('projects.aleppo'), 'places' => [3], 'page' => 'ini_A', 'byDate' => false, 'empty' => $sorry],
            'homs' => ['label' => __('projects.homs'), 'places' => [1], 'page' => 'ini_H', 'byDate' => false, 'empty' => $sorry],
        ];
    }

    /** @return list<array{name: string, image: string, lines: list<array{ms: int, text: string}>}> */
    public function subjects(): array
    {
        $characters = '/images/initiatives/Children Uni/characters/';

        return [
            [
                'name' => 'Informatics',
                'image' => $characters.'Robotic.png',
                'lines' => [
                    ['ms' => 300, 'text' => 'The foundation begins with STEM courses, building a base for enhancing scientific and mathematical problem-solving'],
                    ['ms' => 500, 'text' => 'abilities. Next, children study block programming using websites like SCRATCH. At the advanced level, they'],
                    ['ms' => 700, 'text' => 'learn PYTHON. LEGO aids in practically implementing these ideas, making it engaging for children.'],
                ],
            ],
            [
                'name' => 'Medicine',
                'image' => $characters.'Doctor.png',
                'lines' => [
                    ['ms' => 300, 'text' => 'In this field, children would find themselves in a medical environment similar to hospitals to truly interact with, and learn'],
                    ['ms' => 500, 'text' => 'the real purpose of this profession, in addition to integrating modern teaching techniques in the process of teaching,'],
                    ['ms' => 700, 'text' => 'such as using HUMAN ANATOMY ATLAS to experience aspects of the medical field through innovative experiments.'],
                ],
            ],
            [
                'name' => 'Engineering',
                'image' => $characters.'Arch.png',
                'lines' => [
                    ['ms' => 300, 'text' => 'Children explore the basics of architectural design, and how they can turn their sketches and blueprints'],
                    ['ms' => 500, 'text' => 'into digital plans, 3d printed and hand-crafted maquettes. They also get the opportunity'],
                    ['ms' => 700, 'text' => 'to visit engineering sites to see how real buildings are built.'],
                ],
            ],
            [
                'name' => 'Science',
                'image' => $characters.'Science.png',
                'lines' => [
                    ['ms' => 300, 'text' => 'Children dive into the fascinating worlds of physics and chemistry, enjoying the thrill of unraveling the'],
                    ['ms' => 500, 'text' => 'mysteries of the universe through safe and exciting experimentation, they solve'],
                    ['ms' => 700, 'text' => 'complex problems and study scientific phenomena.'],
                ],
            ],
            [
                'name' => 'Media',
                'image' => $characters.'Media.png',
                'lines' => [
                    ['ms' => 300, 'text' => 'This curriculum is designed to enhance the child’s speaking and interactive skills, as children experience'],
                    ['ms' => 500, 'text' => 'interactive forms that will train them in public speaking, conduct journalistic interviews,'],
                    ['ms' => 700, 'text' => 'and teach them how to professionally use the camera for directing and production.'],
                ],
            ],
        ];
    }
};
?>

<div id="Children" class="page-start accent-disc" style="--logo-width: 4.5vw; --logo-width-phone: 5vw" x-data x-hide-nav-between="'#categories-section', '#initiative-section'">
    <x-site.page-title title="Educational Program" subtitle="Children University">
        <img class="reveal page-title__logo" style="--reveal-ms: 1000ms" x-reveal src="/images/logos/Children-Uni.svg" alt="Children University">
    </x-site.page-title>

    <x-site.cover-carousel :images="$covers" :phone="$phone"/>

    {{-- Phones get a short bar under the cover, and a bar under the brief. --}}
    <x-ui.red-rule short :ms="800" class="sm:hidden" style="margin-top: 6vw"/>

    <x-site.project-brief :project="$brief"/>

    <x-ui.red-rule :ms="800" class="sm:hidden" style="margin-top: 1vw"/>

    <x-ui.reveal :ms="600">
        <x-site.character-showcase :subjects="$this->subjects()" title="/images/home/children_title.png"/>
    </x-ui.reveal>

    <x-ui.red-rule/>

    <livewire:activity-list :category="self::CATEGORY" :sub-category="null" type="children-university" :towns="$this->towns()"
                            :title="__('studyzone.activities')" :phone="$phone"/>

    <x-ui.red-rule :ms="800"/>
    <x-site.funders :ongoing="$ongoing" :previous="$previous"/>
    <x-ui.red-rule class="red-rule--end"/>
</div>
