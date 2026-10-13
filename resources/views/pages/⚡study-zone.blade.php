<?php

use App\Actions\RecordPageView;
use App\Queries\CoverQuery;
use App\Queries\InitiativeListQuery;
use App\Queries\InitiativeTitleListQuery;
use App\Queries\ProjectPageQuery;
use App\Queries\StudyZoneCenterQuery;
use App\Queries\StudyZoneStatisticsQuery;
use App\Queries\SuccessQuery;
use App\Support\Device;
use Livewire\Attributes\Layout;
use Livewire\Component;

/*
 * Study Zone, /projects/study-zone. Ported from new/public/study-zone.blade.php
 * and PublicController::studyzone() at legacy commit 6d45ef9.
 *
 * Differences from legacy, besides what the upgrade plan lists for every page:
 *  - The statistics come from the Study Zone attendance database, which is
 *    optional (config/database.php); without it the section is left out
 *    where legacy fataled.
 *  - The gender rings are labelled by gender; legacy labelled the larger share
 *    "Male" whichever it was.
 *  - Search takes bound values; legacy spliced them into its SQL.
 *  - A centre's working hours: legacy wrote the hours of nine centres into its
 *    script and kept the previous centre's text for any other it chose; here
 *    those without their own fall back to their record's.
 */
new #[Layout('layouts::app', [
    'title' => 'Study Zone',
    'keywords' => 'aleppo syria, hope, center, study zone, youth, students',
    'footer' => 'default',
    'preloader' => true,
])] class extends Component
{
    /** The programme's id in what_categories: its covers, brief, funders and stories. */
    private const CATEGORY = 7;

    /** The sub-category of its activities. */
    private const ACTIVITIES = 12;

    /** The towns of the activity tabs, in order: tab key => [label, place ids, page parameter]. */
    private const TOWNS = [
        'aleppo' => ['Aleppo', [3], 'ini_A'],
        'homs' => ['Homs', [1, 5], 'ini_H'],
        'damas' => ['Damascus', [2], 'ini_D'],
        'latakia' => ['Latakia', [4], 'ini_L'],
        'tartus' => ['Tartus', [6], 'ini_T'],
    ];

    /** Centres whose hours the legacy script wrote out, by id. */
    private const HOURS = [
        17 => 'Working Hours: <br><strong> Saturday to Thursday </strong> <br> from 9:30 Am to 9:00 Pm',
        14 => 'Working Hours: <br><strong> Monday to Saturday </strong> <br> from 9:30 Am to 9:00 Pm',
        19 => 'Working Hours: <br><strong> Monday to Saturday </strong> <br> from 9:30 Am to 9:00 Pm',
        3 => 'Working Hours: <br><strong>Saturday to Thursday</strong> <br>from 9:30 Am to 9:00 Pm',
        15 => 'Working Hours: <br><strong>Saturday to Thursday</strong> <br>from 9:30 Am to 9:00 Pm',
        6 => 'Working Hours: <br><strong>Sunday to Friday</strong><br> from 9:30 Am to 9:00 Pm',
        4 => 'Working Hours: <br><strong>Saturday to Thursday</strong> <br>from 9:00 Am to 9:00 Pm',
        8 => 'Working Hours: <br><strong>Saturday to Thursday</strong> <br>from 9:00 Am to 9:00 Pm',
        12 => 'Working Hours: <br><strong>Saturday to Thursday</strong> <br>from 9:00 Am to 9:00 Pm',
    ];

    public bool $phone = false;

    public function mount(RecordPageView $recordPageView): void
    {
        $recordPageView('study_zone');

        $this->phone = Device::isPhone(request());
    }

    public function with(): array
    {
        $search = trim((string) request('search'));

        return [
            'covers' => CoverQuery::images(self::CATEGORY),
            'brief' => ProjectPageQuery::brief(self::CATEGORY),
            'stories' => SuccessQuery::latest(self::CATEGORY, null, 4),
            'ongoing' => ProjectPageQuery::funders(self::CATEGORY, true),
            'previous' => ProjectPageQuery::funders(self::CATEGORY, false),
            'figures' => StudyZoneStatisticsQuery::figures(),
            'lists' => $this->activityLists($search),
            'tab' => $this->openTab(),
            'search' => $search,
            'suggestions' => InitiativeTitleListQuery::make()->forCategory(self::CATEGORY)->titles()->map(fn ($title) => html_entity_decode($title, ENT_QUOTES))->all(),
            'spread' => $this->spread(),
        ];
    }

    /** @return array<string, array{label: string, activities: mixed, empty: array{0: string, 1: ?string}}> */
    private function activityLists(string $search): array
    {
        $sorry = ["Sorry, we couldn't find any results", __('projects.sorryMessage2')];

        return collect(self::TOWNS)->map(fn (array $town, string $key) => [
            'label' => $town[0],
            'activities' => InitiativeListQuery::forPlace(self::CATEGORY, self::ACTIVITIES, $town[1], $town[2], byDate: $key === 'aleppo', search: $search),
            'empty' => $key === 'aleppo' ? ['No Activities Yet', null] : $sorry,
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

    /**
     * "Our Spread": the centres grouped by town, and the one shown first (the
     * second in alphabetical order of town then name, as legacy picked it).
     *
     * @return array{groups: list<array{name: string, centres: list<array<string, mixed>>}>, initial: int}
     */
    private function spread(): array
    {
        $centres = StudyZoneCenterQuery::make()->withPlaceName()->get()
            ->map(function ($centre) {
                $place = $centre->place_name === 'Idleb' ? 'Idlib' : (string) $centre->place_name;
                $hours = trim((string) $centre->work_hours);

                return [
                    'id' => (int) $centre->id,
                    'place' => $place,
                    'placeId' => (int) $centre->place_id,
                    'rank' => (int) $centre->rank,
                    'name' => trim((string) $centre->short_name),
                    'fullName' => (string) $centre->name,
                    'startDate' => (string) $centre->start_date,
                    'active' => (int) $centre->active === 1,
                    'address' => (string) $centre->address,
                    'location' => (string) $centre->location,
                    'telephone' => (string) $centre->telephone,
                    'email' => (string) $centre->email,
                    'facebook' => (string) $centre->facebook,
                    'instagram' => (string) $centre->instagram,
                    // Editors' HTML: line breaks and emphasis only.
                    'hoursRecord' => strip_tags($hours, '<br><strong><b><em>'),
                    'hours' => self::HOURS[$centre->id] ?? '<span>Working Hours: </span> '.strip_tags($hours, '<br><strong><b><em>'),
                    'hoursText' => strip_tags($hours),
                    'latitude' => $centre->latitude,
                    'longitude' => $centre->longtitude,
                ];
            });

        $alphabetical = $centres->sortBy([['place', 'asc'], ['fullName', 'asc']])->values();

        $groups = collect([3 => 'Aleppo', 2 => 'Damascus', 1 => 'Homs', 4 => 'Latakia', 6 => 'Tartus'])
            ->map(fn (string $name, int $placeId) => [
                'name' => $name,
                'centres' => $centres->where('placeId', $placeId)->sortBy('rank')->values()->all(),
            ])->values()->all();

        return ['groups' => $groups, 'initial' => ($alphabetical[1] ?? $alphabetical->first())['id'] ?? 0];
    }

    /** @return list<array<string, mixed>> */
    public function categories(): array
    {
        return [
            [
                'label' => 'Social',
                'title' => 'Social Zone',
                'color' => '#F99D1C',
                'image' => '/images/home/zone-categories.jpg',
                'lines' => [
                    ['ms' => 300, 'text' => 'Designated for small group study and permits study and'],
                    ['ms' => 600, 'text' => 'project-related discussions without causing disturbance to others.'],
                    ['ms' => 900, 'text' => 'Cellphones must be set to silent or vibrate mode, and the'],
                    ['ms' => 1000, 'text' => 'administration selects the music.'],
                ],
                'narrow' => [
                    ['ms' => 300, 'text' => 'Designated for small group study and permits study and project-related discussions'],
                    ['ms' => 500, 'text' => 'without causing disturbance to others. Cellphones must be set to silent or vibrate mode,'],
                    ['ms' => 700, 'text' => 'and the administration selects the music.'],
                ],
            ],
            [
                'label' => 'Quiet',
                'title' => 'Quiet Zone',
                'color' => '#414042',
                'image' => '/images/home/zone-categories.jpg',
                'lines' => [
                    ['ms' => 300, 'text' => 'Intended for individuals who wish to study in a quiet setting,'],
                    ['ms' => 500, 'text' => 'with the option of brief and subdued conversations.'],
                    ['ms' => 700, 'text' => 'Cellphones are required to be on silent or vibrate mode,'],
                    ['ms' => 900, 'text' => 'and headphones must be set to the lowest volume.'],
                ],
                'narrow' => [
                    ['ms' => 300, 'text' => 'Intended for individuals who wish to study in a quiet setting,'],
                    ['ms' => 400, 'text' => 'with the option of brief and subdued conversations.'],
                    ['ms' => 500, 'text' => 'Cellphones are required to be on silent or vibrate mode,'],
                    ['ms' => 700, 'text' => 'and headphones must be set to the lowest volume.'],
                ],
            ],
            [
                'label' => 'Silent',
                'title' => 'Silent Zone',
                'color' => '#BC1818',
                'image' => '/images/home/zone-categories.jpg',
                'lines' => [
                    ['ms' => 300, 'text' => 'Designated for those who prefer to study alone without any talking.'],
                    ['ms' => 500, 'text' => 'All cellphones must be put on silent or on vibrate mode,'],
                    ['ms' => 700, 'text' => 'and the use of headphones and making phone calls is prohibited.'],
                ],
            ],
        ];
    }

    /** What the panel says before a button is used: the legacy markup, whose text differs from the buttons' own. */
    public function intro(): array
    {
        return [
            'wide' => [
                'title' => 'Social Zone',
                'color' => '#F99D1C',
                'lines' => [
                    ['ms' => 300, 'text' => 'Designated for small group study and permits study and project-related discussions without causing disturbance to others. Cellphones must be set to silent or vibrate mode, and the administration selects the music. to others. Cellphones must be set to silent or vibrate mode, and the administration selects the music.'],
                ],
            ],
            'narrow' => [
                'title' => 'Social Zone',
                'color' => '#F99D1C',
                'lines' => [
                    ['ms' => 300, 'text' => 'Designated for small group study and permits study and project-related discussions'],
                    ['ms' => 500, 'text' => 'without causing disturbance to others. Cellphones must be set to silent or vibrate mode,'],
                    ['ms' => 700, 'text' => 'and the administration selects the music.'],
                ],
            ],
        ];
    }
};
?>

<div id="Study" class="page-start accent-disc"
     style="--logo-width: 5vw; --logo-width-phone: 5vw; --title-strong-phone: 3vw; --title-lh-phone: 2.5vw; --testimonials-top: 9vw; --testimonials-top-phone: 13vw">
    <x-site.page-title title="Youth Program" subtitle="Study Zone">
        <img class="reveal page-title__logo" style="--reveal-ms: 1000ms" x-reveal src="/images/logos/study.svg" alt="Study Zone">
    </x-site.page-title>

    <x-site.cover-carousel :images="$covers" :phone="$phone"/>

    {{-- Phones get a short bar under the cover, and a bar under the brief. --}}
    <x-ui.red-rule short :ms="800" class="sm:hidden" style="margin-top: 6vw"/>

    <x-site.project-brief :project="$brief"/>

    <x-ui.red-rule :ms="800" class="sm:hidden" style="margin-top: 1vw"/>

    <x-ui.reveal :ms="800">
        <x-site.category-showcase :categories="$this->categories()" :intro="$this->intro()" heading="Find Your Zone" zones/>
    </x-ui.reveal>

    <x-ui.red-rule/>

    @if ($figures)
        <x-site.zone-stats :$figures/>

        <x-ui.red-rule :ms="800"/>
    @endif

    <x-site.testimonials :stories="$stories" title="Testimonies" spaced/>

    <x-ui.red-rule :ms="800"/>

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
        <x-site.activity-tabs :lists="$lists" type="study-zone" :selected="$tab" :phone="$phone"/>
    </x-ui.reveal>

    <x-ui.red-rule :ms="800"/>

    <x-site.centre-spread :groups="$spread['groups']" :initial="$spread['initial']"
                          :tiles="config('hcsyria.map_tiles.url').'?api_key='.config('hcsyria.map_tiles.key')"/>

    <x-ui.red-rule :ms="800"/>
    <x-site.funders :ongoing="$ongoing" :previous="$previous"/>
    <x-ui.red-rule class="red-rule--end"/>
</div>
