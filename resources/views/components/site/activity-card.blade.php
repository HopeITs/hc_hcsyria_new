@props(['activity', 'type', 'flip' => false, 'summary' => false, 'mirrored' => false])

{{--
    One activity of a programme in a list (legacy initiative card): its photos
    sliding on one side, and on the other its title, date, who ran it, the
    first thirty words of its text and a "Read More" link to its page
    (resources/css/ui/activity-card.css). :flip puts the photos on the right,
    which the wide layout does for every other card; narrow screens stack the
    photos on top either way. $type is the programme's address segment
    (disc in /projects/disc/1-title).

    The text is HTML an editor wrote; it is stripped to plain text for the
    excerpt. A video, when there is one, opens from the small icon by the title.

    :summary is the other list, a category's (/projects/social-activities): the
    date stands alone (no "Date: "), the excerpt is the short description, fifty
    words of it, shown whole when it is no longer than fifty characters, and a
    date the editors left empty still takes its line, as in legacy. Flipped, its photos lose their left padding rather than their right, and its text's inset moves to the right only from 576px up (its phone list was never flipped), except in a :mirrored list (Relief's), whose phone list keeps it on the right in every card.
--}}
@php
    $content = $activity->content;
    $photos = $activity->initiativeImages;
    $words = $summary ? 50 : 30;
    $text = $summary ? $content?->description : $content?->content;
    $excerpt = strlen($text ?? '') > ($summary ? 50 : 30)
        ? implode(' ', array_slice(explode(' ', strip_tags($text)), 0, $words)).'...'
        : null;
    $date = $content?->date_string !== '0' ? (string) $content?->date_string : null;
    $organization = $content?->organization !== '0' ? $content?->organization : null;
    $showDate = $summary ? $date !== null : filled($date);
    $showOrganization = filled($organization);
    $href = '/projects/'.$type.'/'.$activity->id.'-'.Str::slug(strtolower((string) $content?->title));
@endphp

<div class="activity-card">
    <div class="activity-card__inner">
        <div class="-mx-3 flex flex-wrap">
            <div @class(['activity-card__photos flex w-full justify-end px-3 sm:w-1/2', 'sm:pr-0' => ! ($summary && $flip), 'sm:pl-0' => $summary && $flip, 'sm:order-2' => $flip])>
                <x-ui.carousel :count="$photos->count()" :interval="5000" class="activity-card__carousel w-full">
                    <x-ui.carousel.track class="activity-card__track">
                        @foreach ($photos as $i => $photo)
                            <x-ui.carousel.item :i="$i" slide>
                                <a class="block h-full" href="{{ substr($photo->path, 0, -4).'b.jpg' }}" data-lightbox="activity-{{ $activity->id }}"
                                   data-caption="{{ ucfirst((string) $content?->title) }}">
                                    <img class="block h-full w-full object-cover" src="{{ $photo->path }}" alt="">
                                </a>
                            </x-ui.carousel.item>
                        @endforeach

                        <x-ui.carousel.controls/>
                    </x-ui.carousel.track>
                </x-ui.carousel>
            </div>

            <div class="relative w-full px-3 sm:w-1/2">
                <div @class(['activity-card__text', 'activity-card__text--flipped' => $flip && ! $summary, 'sm:pr-[0.6vw] sm:pl-0' => $flip && $summary, 'max-sm:pr-[0.6vw] max-sm:pl-0' => $mirrored])>
                    <div class="-mx-3 mt-[1vw] flex flex-wrap items-center">
                        <div class="w-5/6 px-3"><div class="activity-card__title">{{ $content?->title }}</div></div>
                        <div class="w-1/6 px-3 text-right">
                            @if ($activity->video !== '0')
                                <a href="{{ $activity->video }}" data-lightbox="video-{{ $activity->id }}">
                                    <img class="activity-card__video" src="/images/ico/video-ini-icon.svg" alt="Video">
                                </a>
                            @endif
                        </div>
                        <div class="w-full px-3"><div class="activity-card__rule"></div></div>
                    </div>

                    <div class="activity-card__content">
                        @if ($showDate)
                            <div>@unless ($summary)<strong>Date: </strong>{{ $date }}@else<strong>{{ $date }}</strong>@endunless</div>
                        @endif
                        @if ($showOrganization)
                            <strong> {{ __('initiatives.by') }}{{ $organization }}</strong>
                        @endif
                        @if ($showDate || $showOrganization)
                            <div><br></div>
                        @endif
                        @if ($excerpt)
                            <div>{!! $excerpt !!}</div>
                        @elseif ($summary)
                            <div>{{ $content?->description }}</div>
                        @endif
                    </div>

                    <div class="-mx-3 flex">
                        <div class="flex grow justify-end px-3">
                            <a class="activity-card__more" href="{{ $href }}">{{ __('main.readMore') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
