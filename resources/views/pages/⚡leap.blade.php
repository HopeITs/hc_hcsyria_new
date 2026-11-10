<?php

use App\Actions\RecordPageView;
use App\Queries\CoverQuery;
use App\Queries\InitiativeListQuery;
use App\Queries\ProjectPageQuery;
use App\Support\Device;
use Livewire\Attributes\Layout;
use Livewire\Component;

/*
 * LEAP, /projects/leap. Ported from new/public/leap.blade.php and
 * PublicController::leap() at legacy commit 6d45ef9.
 *
 * Not ported: the English/French switch (English only here), the second copy
 * of the activity list the legacy view wrote out for phones, the scripts the
 * page loaded for the four aspects and the tabs (jQuery, Bootstrap,
 * typeahead.js, AOS) and the Leaflet, Chart.js and fancybox links it never
 * used, and the commented-out video above Steps. The aspects' text was
 * written twice in the legacy script, for hovering and for clicking, with the
 * same words; it is written once. The activities are the shared list of
 * Study Zone and DISC (filter bar, tiles or rows, Load More) in place of
 * legacy's two tabs, search box and pager; like legacy, the page shows them
 * only once the programme has an online activity. Search takes bound values;
 * legacy spliced them into its SQL.
 */
new #[Layout('layouts::app', [
    'title' => 'LEAP',
    'keywords' => 'aleppo syria, hope, center, leap, youth',
    'footer' => 'leap',
    'preloader' => true,
])] class extends Component
{
    /** The programme's id in what_categories: its covers, brief, funders and activities. */
    private const CATEGORY = 19;

    public bool $phone = false;

    public function mount(RecordPageView $recordPageView): void
    {
        $recordPageView('leap');

        $this->phone = Device::isPhone(request());
    }

    public function with(): array
    {
        return [
            'covers' => CoverQuery::images(self::CATEGORY),
            'brief' => ProjectPageQuery::brief(self::CATEGORY),
            'ongoing' => ProjectPageQuery::funders(self::CATEGORY, true),
            'previous' => ProjectPageQuery::funders(self::CATEGORY, false),
            'hasActivities' => InitiativeListQuery::hasAny(self::CATEGORY),
            'aspects' => $this->aspects(),
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

    /**
     * The four aspects. An item is a bold label and its text, written with the
     * spaces legacy's script gave it; `tight` marks the two items of Personal
     * Growth that the page's own markup (what shows before a button is used)
     * ran together without one: "role rotation:Leaders", "Life coachingis".
     * An item with `bullets` lists them under its label.
     *
     * @return list<array{title: string, image: string, hover: string, items: list<array{label: string, text?: string, tight?: bool, bullets?: list<string>}>}>
     */
    private function aspects(): array
    {
        $images = '/images/home/LEAP/Categories/';

        return [
            [
                'title' => 'Personal Growth',
                'image' => $images.'personal.png',
                'hover' => $images.'personal_hover.png',
                'items' => [
                    ['label' => 'Focus:', 'text' => 'Building the leader’s identity, confidence, and relational intelligence'],
                    ['label' => 'Self-awareness phase:', 'text' => 'Leaders explore their strengths, weaknesses, and values through personality assessments and reflective tasks'],
                    ['label' => 'Group work:', 'text' => 'Participants self-organize into teams, take on roles, and practice responsibility and accountability'],
                    ['label' => 'Communication skills:', 'text' => 'Training on effective speaking, listening, and presentation techniques'],
                    ['label' => 'Case studies and role rotation:', 'text' => 'Leaders engage in real-world simulations (debate, business cases, social initiatives) to refine collaboration and leadership under pressure', 'tight' => true],
                    ['label' => 'Life coaching', 'text' => 'is a professional partnership in which a coach helps identify goals, overcome obstacles, and create strategies for personal and professional growth', 'tight' => true],
                ],
            ],
            [
                'title' => 'Spiritual Development',
                'image' => $images.'spiritual.png',
                'hover' => $images.'spiritual_hover.png',
                'items' => [
                    ['label' => 'Focus:', 'text' => 'Rooting leadership in Christian faith and Christ-like character.Explore how Jesus led with humility, service, courage, and vision.'],
                    ['label' => 'Daily spiritual practices:', 'text' => 'Individual prayer, group reflections, and biblical study incorporated into the training.'],
                    ['label' => 'Faith and leadership debates:', 'text' => 'Discussing ethical dilemmas and spiritual leadership challenges.'],
                    ['label' => 'Accompaniment:', 'text' => 'A relationship of guidance that helps leaders deepen their connection with God through peer support, and are encouraged to discern their God-given calling to live it through faithful service.'],
                ],
            ],
            [
                'title' => 'Visionary Leadership',
                'image' => $images.'visionary.png',
                'hover' => $images.'visionary_hover.png',
                'items' => [
                    ['label' => 'Focus:', 'text' => 'Strategic thinking, future planning, and community impact.'],
                    ['label' => 'Vision-casting workshops:', 'text' => 'Leaders learn how to shape a compelling vision rooted in values and real community needs.'],
                    ['label' => 'Strategic planning modules:', 'text' => 'Training in how to turn ideas into actionable plans with measurable outcomes.'],
                    ['label' => 'Project design:', 'text' => 'Each group designs and executes a community impact initiative, practicing real leadership under mentorship.'],
                    ['label' => 'Organizational skills:', 'text' => 'Learning core concepts in team building, conflict resolution, and institutional development.'],
                    ['label' => 'Evaluating impact:', 'text' => 'Understanding how to assess the success and long-term value of an initiative.'],
                ],
            ],
            [
                'title' => 'Technical Skills',
                'image' => $images.'technical.png',
                'hover' => $images.'technical_hover.png',
                'items' => [
                    ['label' => 'Focus:', 'text' => 'Equipping leaders with digital and practical tools for modern leadership.'],
                    ['label' => 'Training in essential technologies:', 'bullets' => ['Programming basics', 'Artificial Intelligence (AI) concepts', 'Networking and database essentials']],
                    ['label' => 'Visual identity design:', 'text' => 'Learning branding principles and creating a professional identity for projects.Presentation tools: Using digital tools to effectively communicate project goals and outcomes.'],
                ],
            ],
        ];
    }
};
?>

<div id="LEAP" class="page-start accent-disc" style="--logo-width: 10vw; --logo-width-phone: 10vw; --title-line-phone: 1.8vw; --title-strong-phone: 3vw; --title-lh-phone: 2.5vw">
    <x-site.page-title title="LEADERSHIP, EMPOWERMENT & ACHIEVEMENT" subtitle="LEAP PROGRAM">
        <img class="reveal page-title__logo" style="--logo-width: 15vw; --logo-width-phone: 15vw; --reveal-ms: 1000ms" x-reveal src="/images/logos/LEAP/LEAP.svg" alt="LEAP">
    </x-site.page-title>

    <x-site.cover-carousel :images="$covers" :phone="$phone"/>

    {{-- Phones get a short bar under the cover, and a bar under the brief. --}}
    <x-ui.red-rule short :ms="800" class="sm:hidden" style="margin-top: 6vw"/>

    <x-site.project-brief :project="$brief"/>

    <x-ui.red-rule :ms="800" class="sm:hidden" style="margin-top: 1vw"/>

    <x-ui.reveal :ms="600" id="vision-section" class="section-gap pb-[5vw] sm:pb-0">
        <div class="px-3">
            <div class="flex flex-col items-center justify-center">
                <x-ui.section-title class="pb-0">Vision</x-ui.section-title>
                <div class="section-title-desc text-center">
                    <x-ui.reveal :ms="300">A leader rooted in self-awareness, guided by empathy, and driven</x-ui.reveal>
                    <x-ui.reveal :ms="600">to lead transformative change.</x-ui.reveal>
                </div>
            </div>
        </div>
    </x-ui.reveal>

    <x-ui.red-rule :ms="800"/>

    <x-ui.reveal :ms="600" id="categories-section" class="section-gap pb-12">
        <div class="leap-aspects" x-data="categoryShowcase(@js(['categories' => collect($aspects)->map(fn ($aspect) => ['title' => $aspect['title']]), 'intro' => ['wide' => ['title' => 'Personal Growth']]]))">
            <div class="px-3">
                <x-ui.reveal :ms="600" class="flex flex-col items-center justify-center">
                    <x-ui.section-title class="pb-0">Four Aspects of LEAP Program</x-ui.section-title>
                    <div class="-mx-3 flex w-full justify-center text-center">
                        <div class="w-11/12 px-3 sm:w-2/3">
                            <div class="section-title-desc">
                                <x-ui.reveal :ms="300">In a time of growing uncertainty and limited leadership among youth in Syria, Hope Center</x-ui.reveal>
                                <x-ui.reveal :ms="600">recognized the importance of a Christian leader—one who embodies faith, resilience, and relevance.</x-ui.reveal>
                                <x-ui.reveal :ms="900">Leadership today cannot rely on spiritual passion alone; it must also be rooted in personal maturity,</x-ui.reveal>
                                <x-ui.reveal :ms="900">supported by technical capability, and guided by an impactful vision.</x-ui.reveal>
                            </div>
                        </div>
                    </div>
                </x-ui.reveal>
            </div>

            <div class="leap-aspects__buttons">
                @foreach ($aspects as $i => $aspect)
                    <button type="button" class="leap-aspects__button" aria-label="{{ $aspect['title'] }}" @mouseenter="select({{ $i }})" @click="select({{ $i }})">
                        <img src="{{ $i === 0 ? $aspect['hover'] : $aspect['image'] }}" :src="selected === {{ $i }} ? '{{ $aspect['hover'] }}' : '{{ $aspect['image'] }}'" alt="">
                    </button>
                @endforeach
            </div>

            <div class="px-3">
                <div class="page-margin mt-0 leap-aspects__body">
                    <x-ui.reveal :ms="600" class="flex flex-col items-start justify-start">
                        <div class="section-title leap-aspects__title" x-text="panel('wide').title">Personal Growth</div>
                        <div class="-mx-3 flex w-full justify-start text-start">
                            <div class="w-10/12 px-3 sm:w-11/12 lg:w-10/12">
                                <div class="section-title-desc leap-aspects__text">
                                    {{-- Before a button is used: the first aspect as the page was written --}}
                                    <div x-show="! used">
                                        @foreach ($aspects[0]['items'] as $item)
                                            <x-site.leap-aspect-item :$item intro/>
                                        @endforeach
                                    </div>
                                    @foreach ($aspects as $i => $aspect)
                                        <template x-if="used && selected === {{ $i }}">
                                            <div class="reveal" x-reveal style="--reveal-ms: 600ms">
                                                @foreach ($aspect['items'] as $item)
                                                    <x-site.leap-aspect-item :$item/>
                                                @endforeach
                                            </div>
                                        </template>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </x-ui.reveal>
                </div>
            </div>
        </div>
    </x-ui.reveal>

    <x-ui.red-rule :ms="800"/>

    <x-ui.reveal :ms="600" class="section-gap">
        <div class="flex flex-col items-center justify-center">
            <x-ui.section-title class="mt-4 pb-0">Steps</x-ui.section-title>
        </div>
        <img class="block w-full px-3" src="{{ \App\Support\Site\SiteContent::image('leap.steps_image') }}" alt="Steps">
    </x-ui.reveal>

    @if ($hasActivities)
        <x-ui.red-rule/>

        <livewire:activity-list :category="self::CATEGORY" :sub-category="null" type="leap" :towns="$this->towns()"
                                :title="__('studyzone.activities')" :phone="$phone"/>
    @endif

    <x-ui.red-rule :ms="800"/>
    <x-site.funders :ongoing="$ongoing" :previous="$previous" page="/projects/leap"/>
    <x-ui.red-rule class="red-rule--end"/>
</div>
