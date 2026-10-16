@props(['narrow' => false])

{{--
    "Get Latest Updates On Our News" and the newsletter sign-up box under it
    (resources/css/pages/news.css), at the foot of the news list and of each
    story. The story's page has the box in a narrower column.
--}}
<x-ui.reveal :ms="800" class="news-signup-title">
    <x-ui.section-title>Get Latest Updates On Our News</x-ui.section-title>
</x-ui.reveal>

<x-ui.reveal :ms="800" class="news-signup">
    <div @class(['news-signup__column', 'news-signup__column--narrow' => $narrow])>
        <livewire:newsletter-subscribe/>
    </div>
</x-ui.reveal>
