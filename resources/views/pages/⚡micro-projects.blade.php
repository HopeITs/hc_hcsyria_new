<?php

use App\Actions\RecordPageView;
use App\Queries\CoverQuery;
use App\Queries\MicroProjectStatsQuery;
use App\Queries\ProjectPageQuery;
use App\Support\Device;
use Livewire\Attributes\Layout;
use Livewire\Component;

/*
 * Micro-projects, /projects/micro-projects, as a visitor sees it. Ported from
 * new/public/micro-project-new-test.blade.php and
 * PublicController::micro_project_new_test() at legacy commit 6d45ef9.
 *
 * Visitors get the brief, the statistics and, where members see the project
 * list and map, a sign-in gate. The list, the map and the sign-in itself come
 * with the members' phase (upgrade plan, Phase 2 step 6).
 *
 * Not ported: the Facebook SDK the page loaded and never used, the edition
 * and donor lists the controller passed and the view ignored, and the three
 * copies of Chart.js (the age gauges are SVG now).
 */
new #[Layout('layouts::app', [
    'title' => 'main.micro',
    'keywords' => 'aleppo syria, hope, center, micro project, projects, caritas poland',
    'footer' => 'micro',
    'preloader' => true,
])] class extends Component
{
    /** The programme's id in what_categories: its covers, brief and funders. */
    private const CATEGORY = 10;

    public bool $phone = false;

    public function mount(RecordPageView $recordPageView): void
    {
        $recordPageView('micro_projects');

        $this->phone = Device::isPhone(request());
    }

    public function with(): array
    {
        $stats = MicroProjectStatsQuery::make();

        return [
            'covers' => CoverQuery::images(self::CATEGORY),
            'brief' => ProjectPageQuery::brief(self::CATEGORY),
            'cities' => $stats->countsByCity()->all(),
            'rate' => $stats->sustainableRate(),
            'ages' => $stats->countsByAgeRange(),
            'categories' => $stats->topCategories(),
            'ongoing' => ProjectPageQuery::funders(self::CATEGORY, true),
            'previous' => ProjectPageQuery::funders(self::CATEGORY, false),
        ];
    }
};
?>

<div id="Micro" class="page-start" style="--brief-gap: 2vw">
    <x-site.page-title compact title="BE SUCCESSFUL & INSPIRE OTHERS TO BE" subtitle="MICRO PROJECTS">
        <img class="reveal page-title__logo" style="--reveal-ms: 1000ms" x-reveal src="/images/logos/micro.svg" alt="Micro Projects">
    </x-site.page-title>

    <x-site.cover-carousel :images="$covers" :phone="$phone"/>

    {{-- Phones get a short bar under the cover, and another under the brief. --}}
    <x-ui.red-rule short :ms="800" class="sm:hidden" style="margin-top: 5vw"/>

    <x-site.project-brief :project="$brief"/>

    <x-ui.red-rule short :ms="800" class="mt-4 sm:hidden"/>

    <x-site.micro-statistics :cities="$cities" :rate="$rate" :ages="$ages" :categories="$categories"/>

    <div class="locked-section">
        <div class="locked-section__rule"></div>
        <x-site.locked-gate/>
        <div class="locked-section__rule"></div>
        <x-site.funders :ongoing="$ongoing" :previous="$previous" class="funders--after-gate"/>
    </div>

    <x-ui.red-rule class="red-rule--end"/>
</div>
