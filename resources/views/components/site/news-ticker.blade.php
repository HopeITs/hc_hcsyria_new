@props(['stories'])

{{--
    The headlines scrolling under the cover photo, each linking to its story.
    The list is drawn twice, end to end, so that moving it half its length
    and starting again is seamless (resources/css/pages/news.css); the second
    copy is for the eye only.
--}}
<div class="news-ticker">
    <div class="news-ticker__track">
        <div class="news-ticker__items">
            @foreach ([false, true] as $copy)
                @foreach ($stories as $news)
                    @php($story = new App\Support\NewsPresenter($news))
                    <a href="{{ $story->url() }}" target="_blank" rel="noopener noreferrer" class="news-ticker__item" @if ($copy) aria-hidden="true" tabindex="-1" @endif>
                        {{ $news->content ? $story->title() : 'New Update' }}
                        <img src="/images/our-news/logo/001HC_Logomini.svg" class="news-ticker__logo" alt="logo">
                    </a>
                @endforeach
            @endforeach
        </div>
    </div>
</div>
