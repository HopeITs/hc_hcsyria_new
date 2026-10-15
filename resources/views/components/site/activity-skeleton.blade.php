@props(['view' => 'tiles', 'count' => 6])

{{--
    The grey shapes of activities while a list reloads or grows, drawn with
    the classes of the real tile and row so each placeholder is the size of
    what replaces it (resources/css/ui/card-skeleton.css). Text is a bar the
    height of its line.
--}}
@if ($view === 'tiles')
    <div {{ $attributes->class('card-grid activity-tiles') }}>
        @for ($i = 0; $i < $count; $i++)
            <div class="activity-tile">
                <div class="activity-tile__photos card-skeleton__shape card-skeleton__photo"></div>
                <div class="activity-tile__body">
                    <div class="activity-tile__title">
                        <div class="card-skeleton__ink" style="--w: 90%">&nbsp;</div>
                        <div class="card-skeleton__ink" style="--w: 50%">&nbsp;</div>
                    </div>
                    <div class="activity-tile__meta card-skeleton__ink" style="--w: 45%">&nbsp;</div>
                    <div class="activity-tile__excerpt">
                        <div class="card-skeleton__ink">&nbsp;</div>
                        <div class="card-skeleton__ink">&nbsp;</div>
                        <div class="card-skeleton__ink">&nbsp;</div>
                        <div class="card-skeleton__ink" style="--w: 60%">&nbsp;</div>
                    </div>
                    <span class="activity-tile__more card-skeleton__button">Read More</span>
                </div>
            </div>
        @endfor
    </div>
@else
    <div {{ $attributes }}>
        @for ($i = 0; $i < $count; $i++)
            <div class="activity-card">
                <div class="activity-card__inner">
                    <div class="-mx-3 flex flex-wrap">
                        <div @class(['flex w-full justify-end px-3 sm:w-1/2 sm:pr-0', 'sm:order-2' => $i % 2])>
                            <div class="activity-card__carousel w-full card-skeleton__shape card-skeleton__photo"></div>
                        </div>
                        <div class="relative w-full px-3 sm:w-1/2">
                            <div @class(['activity-card__text', 'activity-card__text--flipped' => $i % 2])>
                                <div class="-mx-3 mt-[1vw] flex flex-wrap items-center">
                                    <div class="w-full px-3"><div class="activity-card__title card-skeleton__ink" style="--w: 60%">&nbsp;</div></div>
                                    <div class="w-full px-3"><div class="activity-card__rule"></div></div>
                                </div>
                                <div class="activity-card__content">
                                    @foreach ([100, 100, 100, 100, 60] as $width)
                                        <div class="card-skeleton__ink" style="--w: {{ $width }}%">&nbsp;</div>
                                    @endforeach
                                </div>
                                <span class="activity-card__more card-skeleton__button">Read More</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endfor
    </div>
@endif
