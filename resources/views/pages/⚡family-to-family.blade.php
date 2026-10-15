<?php

use App\Actions\RecordPageView;
use App\Models\FamilyPartner;
use App\Queries\InitiativeListQuery;
use App\Queries\ProjectPageQuery;
use App\Support\Device;
use Livewire\Attributes\Layout;
use Livewire\Component;

/*
 * Family to Family, /projects/family-to-family. Ported from
 * new/public/family-to-family.blade.php and PublicController::family_to_family()
 * at legacy commit 6d45ef9.
 *
 * Not ported: the English/French switch (English only here), a second copy of
 * the cover carousel written out for phones, the Chart.js and amCharts
 * scripts and the "count up" script the page loaded and never used, and the
 * pages of commented-out markup (a figures section with two charts) that sat
 * between the live sections.
 *
 * The cover photos, the survey figures (s1-s3 and the phone's s1-s4) and the
 * partners' photographs (p1, p2, ... in the order of the partners) are files
 * the legacy page named directly; they are not rows in the database.
 */
new #[Layout('layouts::app', [
    'title' => 'family2family.title',
    'keywords' => 'family to family, aid program, aleppo syria, hope, center',
    'preloader' => true,
])] class extends Component
{
    /** The programme's id in what_categories: its brief and funders. */
    private const CATEGORY = 8;

    /** The sub-category its partners (the churches helping the families) are filed under; legacy picked them by it alone. */
    private const PARTNERS = 1;

    public bool $phone = false;

    public function mount(RecordPageView $recordPageView): void
    {
        $recordPageView('family_to_family');

        $this->phone = Device::isPhone(request());
    }

    public function with(): array
    {
        return [
            'covers' => collect(range(1, 4))->map(fn (int $i) => (object) ['image_path' => "/images/home/F2F/$i.jpg"]),
            'brief' => ProjectPageQuery::brief(self::CATEGORY),
            'partners' => InitiativeListQuery::ranked(null, self::PARTNERS),
            'places' => FamilyPartner::query()->get(['name', 'location_latitude', 'location_longtitude']),
            'ongoing' => ProjectPageQuery::funders(self::CATEGORY, true),
            'previous' => ProjectPageQuery::funders(self::CATEGORY, false),
        ];
    }
};
?>

<div id="Family" class="page-start" style="--logo-width: 16vw; --logo-width-phone: 20vw">
    <x-site.page-title title="Aid Program" subtitle="Family To Family">
        <img class="reveal page-title__logo" style="--reveal-ms: 1000ms" x-reveal src="/images/logos/Family-to-Family.svg" alt="Family to Family">
    </x-site.page-title>

    <x-site.cover-carousel :images="$covers" :phone="$phone"/>

    {{-- Phones get a short bar under the cover. --}}
    <x-ui.red-rule short :ms="800" class="sm:hidden" style="margin-top: 6vw"/>

    <x-site.verse-video/>

    <x-ui.red-rule :ms="800" class="sm:hidden" style="margin-top: 3vw"/>

    <x-site.project-brief :project="$brief" mirror/>

    <x-ui.red-rule :ms="800" class="sm:hidden"/>

    <x-site.family-survey/>

    <x-ui.red-rule :ms="800"/>

    <x-site.partner-showcase :partners="$partners"/>

    <x-ui.red-rule :ms="800"/>

    <x-site.family-map :places="$places" :tiles="config('hcsyria.map_tiles.url').'?api_key='.config('hcsyria.map_tiles.key')"/>

    <x-ui.red-rule :ms="800"/>
    <x-site.funders :ongoing="$ongoing" :previous="$previous"/>
    <x-ui.red-rule class="red-rule--end"/>
</div>
