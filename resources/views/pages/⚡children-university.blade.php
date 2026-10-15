<?php

use App\Actions\RecordPageView;
use App\Queries\CoverQuery;
use App\Queries\InitiativeListQuery;
use App\Queries\InitiativeTitleListQuery;
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
 * Search takes bound values; legacy spliced them into its SQL.
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

    /** The towns of the activity tabs, in order: tab key => [label, place id, page parameter]. */
    private const TOWNS = [
        'aleppo' => ['aleppo', 3, 'ini_A'],
        'homs' => ['homs', 1, 'ini_H'],
    ];

    public bool $phone = false;

    public function mount(RecordPageView $recordPageView): void
    {
        $recordPageView('children_uni');

        $this->phone = Device::isPhone(request());
    }

    public function with(): array
    {
        $search = trim((string) request('search'));

        return [
            'covers' => CoverQuery::images(self::CATEGORY),
            'brief' => ProjectPageQuery::brief(self::CATEGORY),
            'ongoing' => ProjectPageQuery::funders(self::CATEGORY, true),
            'previous' => ProjectPageQuery::funders(self::CATEGORY, false),
            'lists' => $this->activityLists($search),
            'tab' => $this->openTab(),
            'search' => $search,
            'suggestions' => InitiativeTitleListQuery::make()->forCategory(self::CATEGORY)->titles()->map(fn ($title) => html_entity_decode($title, ENT_QUOTES))->all(),
        ];
    }

    /** @return array<string, array{label: string, activities: mixed, empty: array{0: string, 1: ?string}}> */
    private function activityLists(string $search): array
    {
        $sorry = ["Sorry, we couldn't find any results", __('projects.sorryMessage2')];

        return collect(self::TOWNS)->map(fn (array $town) => [
            'label' => __('projects.'.$town[0]),
            'activities' => InitiativeListQuery::forPlace(self::CATEGORY, null, $town[1], $town[2], search: $search),
            'empty' => $sorry,
        ])->all();
    }

    /** The tab to open: the one in ?tab=, else the town whose page number is in the address, else Aleppo. */
    private function openTab(): string
    {
        $tab = request('tab');

        if (is_string($tab) && array_key_exists($tab, self::TOWNS)) {
            return $tab;
        }

        foreach (self::TOWNS as $key => $town) {
            if (request()->has($town[2])) {
                return $key;
            }
        }

        return 'aleppo';
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

<div id="Children" class="page-start accent-disc" style="--logo-width: 4.5vw; --logo-width-phone: 5vw; --search-width-phone: 50vw; --search-icon-phone: 3vw" x-data x-hide-nav-between="'#categories-section', '#initiative-section'">
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

    <div id="initiative-section" class="section-gap">
        <div class="flow-root px-3">
            <x-ui.reveal :ms="600" class="mb-2 flex flex-col items-center justify-center">
                <x-ui.section-title class="mt-4">{{ __('studyzone.activities') }}</x-ui.section-title>
                <x-ui.search-box :$suggestions :value="$search"/>
            </x-ui.reveal>
        </div>
    </div>
    <x-ui.red-rule short :ms="800" style="z-index: -1"/>

    <x-ui.reveal :ms="800">
        <x-site.activity-tabs :lists="$lists" type="children-university" :selected="$tab" :phone="$phone"/>
    </x-ui.reveal>

    <x-ui.red-rule :ms="800"/>
    <x-site.funders :ongoing="$ongoing" :previous="$previous"/>
    <x-ui.red-rule class="red-rule--end"/>
</div>
