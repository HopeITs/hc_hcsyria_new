<?php

use App\Actions\RecordPageView;
use App\Queries\RootsOfHopeQuery;
use Livewire\Attributes\Layout;
use Livewire\Component;

/*
 * The Roots of Hope magazine, /rootsofhope. Ported from
 * new/public/rootsofhope.blade.php and PublicController::roots() at legacy
 * commit 6d45ef9.
 *
 * Not ported: the English/French switch (English only here), the nine
 * stylesheets and fifteen scripts the page loaded (jQuery, Bootstrap,
 * fancybox, typeahead.js, Leaflet, the heyzine flipbook plugin, ...) for
 * nothing it drew, and the unused Edition and Agent lookups of the controller.
 * The words are the page's own; the latest edition's title, cover, preview
 * link and PDF come from the `roots_of_hope` table.
 *
 * Legacy crashed (a 500) when no edition was online; here the page is drawn
 * without the latest edition and without the list.
 */
new #[Layout('layouts::app', [
    'title' => 'Roots of Hope',
    'keywords' => 'aleppo syria, hope, center, roots,light',
    'preloader' => true,
])] class extends Component
{
    public function mount(RecordPageView $recordPageView): void
    {
        $recordPageView('roots_of_hope');
    }

    public function with(): array
    {
        $editions = RootsOfHopeQuery::editions();

        return [
            'editions' => $editions,
            'latest' => $editions->last(),
        ];
    }
};
?>

<div class="page-start">
    <x-site.page-title title="STORIES OF HOPE, FAITH & HERITAGE" subtitle="roots of hope"/>

    <div class="roots-cover">
        <img class="block w-full" src="{{ \App\Support\Site\SiteContent::image('roots.banner') }}" alt="">

        @if ($latest)
            <img class="roots-cover__edition" src="{{ $latest->image_path }}" alt="">

            <div class="roots-latest">
                <div class="uppercase">
                    <div class="roots-latest__actions">
                        <div class="roots-latest__preview">
                            <a @if ($latest->file_path !== '0') href="{{ route('roots.read', $latest->id) }}" @else target="_blank" href="{{ $latest->url }}" @endif>
                                <img class="roots-latest__preview-icon" src="/images/ico/preview-icon.png" alt="Preview">
                            </a>
                        </div>
                        <div class="roots-latest__download">
                            <a href="{{ $latest->file_path }}" download>
                                <img class="roots-latest__download-icon" src="/images/ico/download-icon.png" alt="Download">
                            </a>
                        </div>
                    </div>
                </div>
                <div>{{ $latest->title }}</div>
                <div>Published By <strong class="uppercase">Christian Hope Center</strong></div>
            </div>
        @endif

        <div class="roots-about">
            <div class="roots-about__name">roots of hope</div>
            <hr class="roots-about__rule">
            <div class="roots-about__text">Since 2013 (officially established in 2018), Christian
                Hope Center has been dedicated to supporting
                Christian families across Syria. Through a wide range
                of programs and initiatives, the Center seeks to serve
                all age groups, helping them navigate the hardships
                and long-term challenges brought on by war and
                protracted crises. A key strength of the Center lies in
                its meticulous follow-up and management of
                projects, which has fostered strong trust among
                donors, beneficiaries, and partners alike.
            </div>
        </div>
    </div>

    @if ($editions->isNotEmpty())
        <div class="px-3">
            <div class="-mx-3 flex flex-wrap justify-center">
                <div class="w-1/2 px-3 md:w-1/3">
                    <div class="roots-divider">
                        <div class="roots-divider__line"></div>
                        <div class="roots-divider__label">All Editions</div>
                        <div class="roots-divider__line"></div>
                    </div>
                </div>
            </div>

            <div class="-mx-3 flex flex-wrap justify-center">
                <div class="w-7/12 px-3 md:w-5/12">
                    <div class="-mx-3 flex flex-wrap justify-center">
                        @foreach ($editions as $edition)
                            <div class="flex w-1/3 shrink-0 flex-col items-center justify-center px-3">
                                <div class="-mx-3 flex flex-wrap">
                                    <img class="roots-edition__cover" src="{{ $edition->image_path }}" alt="">
                                </div>
                                <div class="roots-edition__meta">
                                    <span class="roots-edition__title">{{ $edition->title }}</span>
                                    <span>
                                        <a @if ($edition->file_path !== '0') href="{{ route('roots.read', $edition->id) }}" @else target="_blank" href="{{ $edition->url }}" @endif>
                                            <img class="roots-edition__icon" src="/images/ico/eye-icon.png" alt="Preview">
                                        </a>
                                    </span>
                                    <span>
                                        <a href="{{ $edition->file_path }}" download>
                                            <img class="roots-edition__icon" src="/images/ico/download1-icon.png" alt="Download">
                                        </a>
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
