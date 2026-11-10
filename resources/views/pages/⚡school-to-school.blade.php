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
 * School to School, /projects/school-to-school. Ported from
 * new/public/school-to-school.blade.php and PublicController::school_to_school()
 * at legacy commit 6d45ef9.
 *
 * Search: the box and ?search= work as on Study Zone; there are no towns here
 * (every activity has place 0), so no tabs and no pages.
 *
 * Not ported: the English/French switch (English only here), the second copy
 * of the activity list the legacy view wrote out for phones, Chart.js,
 * Leaflet, typeahead and the other scripts the page loaded and never used,
 * and the unfinished cursor-follower script. The statistics scene is an
 * image with the characters' outlines on top (components/site/school-statistics).
 * The page's keywords were Family to Family's; they name this programme now.
 */
new #[Layout('layouts::app', [
    'title' => 'school2school.title',
    'keywords' => 'school to school, educational support, aleppo syria, hope, center',
    'preloader' => true,
])] class extends Component
{
    /** The programme's id in what_categories: its covers, brief, funders and activities. */
    private const CATEGORY = 9;

    /** The letters in the Students Mailbox. */
    private const LETTERS = [
        '/images/initiatives/School to School/letter1.jpg',
        '/images/initiatives/School to School/letter2.jpg',
        '/images/initiatives/School to School/letter3.jpg',
    ];

    public bool $phone = false;

    public function mount(RecordPageView $recordPageView): void
    {
        $recordPageView('school_to_school');

        $this->phone = Device::isPhone(request());
    }

    public function with(): array
    {
        $search = trim((string) request('search'));

        return [
            'covers' => CoverQuery::images(self::CATEGORY),
            'brief' => ProjectPageQuery::brief(self::CATEGORY),
            'letters' => self::LETTERS,
            'activities' => InitiativeListQuery::ranked(self::CATEGORY, search: $search),
            'search' => $search,
            'suggestions' => InitiativeTitleListQuery::make()->forCategory(self::CATEGORY)->titles()->map(fn ($title) => html_entity_decode($title, ENT_QUOTES))->all(),
            'ongoing' => ProjectPageQuery::funders(self::CATEGORY, true),
            'previous' => ProjectPageQuery::funders(self::CATEGORY, false),
        ];
    }
};
?>

<div id="SchoolToSchool" class="page-start" style="--logo-width: 16vw; --logo-width-phone: 20vw">
    <x-site.page-title title="Educational Support" subtitle="School To School">
        <img class="reveal page-title__logo" style="--reveal-ms: 1000ms" x-reveal src="/images/logos/school.svg" alt="School to School">
    </x-site.page-title>

    <x-site.cover-carousel :images="$covers" :phone="$phone"/>

    {{-- Phones get a short bar under the cover, and a bar under the brief. --}}
    <x-ui.red-rule short :ms="600" class="sm:hidden" style="margin-top: 6vw"/>

    <x-site.project-brief :project="$brief"/>

    <x-ui.red-rule :ms="600" class="sm:hidden" style="margin-top: 3vw"/>

    <x-site.mailbox :photos="$letters"/>

    <x-site.school-statistics/>

    <x-ui.red-rule :ms="600" class="max-sm:hidden"/>

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
        <div class="h-[3vw] bg-white"></div>
        <div class="relative bg-white">
            @forelse ($activities as $activity)
                <x-site.activity-card :activity="$activity" type="school-to-school" :flip="! $phone && $loop->even"/>
            @empty
                <div class="activity-card">
                    <div class="activity-card__inner">
                        <div class="-mx-3 flex">
                            <div class="activity-tabs__empty flex w-full flex-col justify-end px-3 pr-1">
                                <div class="text-center">Sorry, we couldn't find any results</div>
                                <div>{{ __('projects.sorryMessage2') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>
    </x-ui.reveal>

    <x-ui.red-rule :ms="800"/>
    <x-site.funders :ongoing="$ongoing" :previous="$previous" page="/projects/school-to-school"/>
    <x-ui.red-rule class="red-rule--end"/>
</div>
