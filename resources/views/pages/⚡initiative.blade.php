<?php

use App\Queries\InitiativePageQuery;
use Livewire\Attributes\Layout;
use Livewire\Component;

/*
 * One activity, /projects/{category}/{id}-{title}. Ported from
 * new/public/initiative-test.blade.php and PublicController::initiative()
 * at legacy commit 6d45ef9.
 *
 * Not ported: the English/French switch (English only here); the second,
 * commented-out copy of the whole page and the other blocks of commented-out
 * markup; the Facebook SDK, Google Analytics and Tag Manager the head loaded;
 * Leaflet, typeahead, prettyPhoto and the other scripts it loaded and never
 * used. Twentytwenty and Owl Carousel are replaced by components/ui/before-after
 * and components/site/related-carousel.
 *
 * Kept as legacy had it: the big carousel appears only when an activity has
 * more than one photo (one photo is not shown at all), and the three tiles
 * under "Related posts" are drawn at random on every visit. Not kept: the
 * "0" legacy printed in the page keywords when an activity had no partner.
 * The before-and-after labels hide per slider (legacy hid every slider's).
 */
new #[Layout('layouts::app', ['preloader' => true])] class extends Component
{
    public string $type = '';

    public int $id = 0;

    public string $slug = '';

    public function mount(string $type, int $id, string $slug): void
    {
        $this->type = $type;
        $this->id = $id;
        $this->slug = $slug;

        abort_if(InitiativePageQuery::find($type, $id, $slug) === null, 404);
    }

    public function render()
    {
        $activity = InitiativePageQuery::find($this->type, $this->id, $this->slug);
        abort_if($activity === null, 404);

        $content = $activity->content;
        $photos = $activity->initiativeImages;
        $pairs = $activity->initiativeImageRehabilitations;
        $organization = $content->organization !== '0' ? $content->organization : null;
        $share = $photos->first()?->path ?? $pairs->first()?->path_1;

        return $this->view([
            'activity' => $activity,
            'content' => $content,
            'category' => $activity->whatCategory->name,
            'photos' => $photos,
            'pairs' => $pairs,
            'organization' => $organization,
            'donors' => $activity->initiativeDonors->pluck('donor')->filter(),
            'related' => InitiativePageQuery::related($activity),
        ])->title($content->title)->layoutData([
            'keywords' => collect([$content->title, $activity->whatCategory->name, $organization])->filter()->implode(', '),
            'meta' => array_values(array_filter([
                ['property' => 'fb:app_id', 'content' => '2133577546862306'],
                ['property' => 'og:url', 'content' => url("/projects/{$this->type}/{$this->id}-{$this->slug}")],
                ['property' => 'og:type', 'content' => 'article'],
                ['property' => 'og:title', 'content' => $content->title],
                ['property' => 'og:description', 'content' => $content->description],
                $share ? ['property' => 'og:image', 'content' => url($share)] : null,
                ['name' => 'description', 'content' => $content->description],
            ])),
        ]);
    }
};
?>

@php
    /* The title line and the logo of a programme, as its legacy hero drew them; the others have only the category's name. */
    $hero = match ($category) {
        'Family to Family' => ['title' => 'Aid Program', 'logo' => 'Family-to-Family', 'logoClass' => 'initiative-logo'],
        'Children University' => ['title' => 'Educational Program', 'logo' => 'Children-Uni', 'logoClass' => 'initiative-logo--square'],
        'Study Zone' => ['title' => 'Youth Program', 'logo' => 'study', 'logoClass' => 'initiative-logo--study'],
        'School to School' => ['title' => 'Educational Support', 'logo' => 'school', 'logoClass' => 'initiative-logo'],
        'DISC' => ['title' => null, 'logo' => 'disc', 'logoClass' => 'initiative-logo--disc'],
        default => ['title' => null, 'logo' => null, 'logoClass' => null],
    };
    /* DISC's name is the two-line text in lang/en/what_we_do.php, whose <Br> legacy let through unescaped. */
    $subtitle = $category === 'DISC' ? __('what_we_do.disc') : $category;
@endphp

<div id="initiative" @class(['page-start', 'accent-disc' => in_array($category, ['Study Zone', 'DISC', 'Children University'])])
     style="--title-strong-case: capitalize; @if ($category === 'DISC') --title-strong: 2vw; --title-strong-phone: 2vw; --title-lh-phone: 2vw; @endif">
    <x-site.page-title :title="$hero['title']" :subtitle="$subtitle" :html="$category === 'DISC'">
        @if ($hero['logo'])
            <img class="reveal page-title__logo {{ $hero['logoClass'] }}" style="--reveal-ms: 1000ms" x-reveal src="/images/logos/{{ $hero['logo'] }}.svg" alt="{{ $category }}">
        @endif
    </x-site.page-title>

    <div class="page-margin">
        <div class="px-3">
            <div class="-mx-3 flex justify-center">
                <div class="w-full px-3 sm:w-1/2">
                    <div class="page-margin">
                        <div class="initiative">
                            <div class="px-6">
                                <div class="flex">
                                    <div class="w-5/6">
                                        <div class="initiative__title">{{ $content->title }}</div>
                                    </div>
                                    <div class="flex w-1/6 items-end justify-end">
                                        @if ($activity->video !== '0')
                                            <a href="{{ $activity->video }}" data-lightbox="video-{{ $activity->id }}">
                                                <img class="activity-card__video" src="/images/ico/video-ini-icon.svg" alt="Video">
                                            </a>
                                        @endif
                                    </div>
                                </div>

                                @if ($photos->count() > 1)
                                    <x-ui.carousel :count="$photos->count()" :interval="5000" class="initiative__carousel">
                                        <x-ui.carousel.track class="initiative__track">
                                            @foreach ($photos as $i => $photo)
                                                <x-ui.carousel.item :i="$i" slide>
                                                    <a class="block h-full" href="{{ substr($photo->path, 0, -4).'b.jpg' }}" data-lightbox="initiative-{{ $activity->id }}" data-caption="{{ ucfirst($content->title) }}">
                                                        <img class="block h-full w-full object-cover" src="{{ $photo->path }}" alt="">
                                                    </a>
                                                </x-ui.carousel.item>
                                            @endforeach

                                            <x-ui.carousel.controls/>
                                        </x-ui.carousel.track>
                                        <x-ui.carousel.dots :count="$photos->count()" class="initiative__dots"/>
                                    </x-ui.carousel>
                                @endif

                                <div class="-mx-3 flex justify-center">
                                    <div class="w-full px-3">
                                        <div class="-mx-3 mb-4 flex">
                                            <div class="flex w-full justify-start px-3">
                                                @if ($organization)
                                                    <div class="initiative__organization">in partnership With:  {{ $organization }}</div>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="activity-card__rule"></div>
                                        <div class="initiative__content">
                                            @if ($organization)
                                                {{ $organization }}
                                            @endif
                                            @if ($content->date_string !== '0')
                                                <p> {{ $content->date_string }}</p>
                                            @endif
                                            <p>{!! strip_tags((string) $content->content) !!}</p>
                                        </div>
                                        <div class="activity-card__rule flex items-center justify-end">
                                            @if ($donors->isNotEmpty())
                                                <div class="initiative__funded">
                                                    <div>{{ __('initiatives.fundedBy') }}</div>
                                                    @foreach ($donors as $donor)
                                                        <div><img src="{{ $donor->logo }}" alt=""></div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                @if ($pairs->isNotEmpty())
                                    <div class="initiative__results">
                                        <div class="container-fluid-24">
                                            <x-ui.reveal :ms="600">
                                                <div class="initiative__heading">The Result of Reconstruction</div>
                                            </x-ui.reveal>
                                            @foreach ($pairs as $pair)
                                                <x-ui.reveal :ms="600" class="flex justify-center">
                                                    <div class="my-4 w-full">
                                                        <x-ui.before-after :before="$pair->path_1" :after="$pair->path_2"/>
                                                    </div>
                                                </x-ui.reveal>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <x-ui.reveal :ms="600" class="initiative__related">
                                    <div class="container-fluid-24">
                                        <div class="initiative__heading capitalize">{{ __('initiatives.related') }}</div>
                                        <x-site.related-carousel :items="$related"/>
                                    </div>
                                </x-ui.reveal>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-ui.red-rule :ms="600"/>
    <div class="h-[1vw] bg-white"></div>
</div>
