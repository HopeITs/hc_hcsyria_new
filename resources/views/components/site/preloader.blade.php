{{--
    The loading screen: a white overlay with the animated logo ring for two
    seconds after the page loads, then a half-second fade (resources/js/
    preloader.js, resources/css/site/preloader.css). Without JavaScript it
    would never leave, so it is hidden.
--}}
<div class="preloader" x-data="preloader" :class="{ 'is-leaving': leaving }">
    <div class="preloader__gif"></div>
</div>

<noscript><style>.preloader { display: none; }</style></noscript>
