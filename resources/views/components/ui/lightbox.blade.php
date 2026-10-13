{{--
    The lightbox every page carries, closed until a link with data-lightbox
    is clicked (resources/js/lightbox.js, resources/css/ui/lightbox.css).
--}}
<div class="lightbox" x-data="lightbox" x-show="shown" x-cloak @keydown.window="keydown($event)"
     role="dialog" aria-modal="true" aria-label="Gallery">
    <div class="lightbox__backdrop" @click="close()"></div>

    <div class="lightbox__count" x-text="`${index + 1} / ${slides.length}`"></div>

    <div class="lightbox__toolbar">
        <button type="button" class="lightbox__button" x-show="slide.type === 'image'" @click="zoomed = ! zoomed" aria-label="Zoom">
            <svg viewBox="0 0 24 24"><path d="M18.7 17.3l-3-3a5.9 5.9 0 0 0-.6-7.6 5.9 5.9 0 0 0-8.4 0 5.9 5.9 0 0 0 0 8.4 5.9 5.9 0 0 0 7.7.7l3 3a1 1 0 0 0 1.3 0c.4-.5.4-1 0-1.5zM8.1 13.8a4 4 0 0 1 0-5.7 4 4 0 0 1 5.7 0 4 4 0 0 1 0 5.7 4 4 0 0 1-5.7 0z"/></svg>
        </button>
        <button type="button" class="lightbox__button" :class="{ 'is-on': playing }" @click="toggleSlideshow()" aria-label="Slideshow" x-show="slides.length > 1">
            <svg viewBox="0 0 24 24"><path d="M6.5 5.4v13.2l11-6.6z"/></svg>
        </button>
        <button type="button" class="lightbox__button" :class="{ 'is-on': thumbs }" @click="thumbs = ! thumbs" aria-label="Thumbnails" x-show="slides.length > 1">
            <svg viewBox="0 0 24 24"><path d="M14.59 14.59h3.76v3.76h-3.76v-3.76zm-4.47 0h3.76v3.76h-3.76v-3.76zm-4.47 0h3.76v3.76H5.65v-3.76zm8.94-4.47h3.76v3.76h-3.76v-3.76zm-4.47 0h3.76v3.76h-3.76v-3.76zm-4.47 0h3.76v3.76H5.65v-3.76zm8.94-4.47h3.76v3.76h-3.76V5.65zm-4.47 0h3.76v3.76h-3.76V5.65zm-4.47 0h3.76v3.76H5.65V5.65z"/></svg>
        </button>
        <button type="button" class="lightbox__button" @click="close()" aria-label="Close">
            <svg viewBox="0 0 24 24"><path d="M12 10.6L6.6 5.2 5.2 6.6l5.4 5.4-5.4 5.4 1.4 1.4 5.4-5.4 5.4 5.4 1.4-1.4-5.4-5.4 5.4-5.4-1.4-1.4-5.4 5.4z"/></svg>
        </button>
    </div>

    <button type="button" class="lightbox__arrow lightbox__arrow--prev" x-show="slides.length > 1" @click="prev()" aria-label="Previous">
        <svg viewBox="0 0 24 24"><path d="M11.28 15.7l-1.34 1.37L5 12l4.94-5.07 1.34 1.38-2.68 2.72H19v1.94H8.6z"/></svg>
    </button>
    <button type="button" class="lightbox__arrow lightbox__arrow--next" x-show="slides.length > 1" @click="next()" aria-label="Next">
        <svg viewBox="0 0 24 24"><path d="M15.4 12.97l-2.68 2.72 1.34 1.38L19 12l-4.94-5.07-1.34 1.38 2.68 2.72H5v1.94z"/></svg>
    </button>

    <div class="lightbox__stage" @click.self="close()">
        <template x-if="slide.type === 'image'">
            <img class="lightbox__image" :class="{ 'is-zoomed': zoomed }" :src="slide.src" :alt="slide.caption" @click="zoomed = ! zoomed">
        </template>
        <template x-if="slide.type === 'video'">
            <iframe class="lightbox__video" :src="slide.src" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>
        </template>
    </div>

    <div class="lightbox__caption" x-show="slide.caption" x-text="slide.caption"></div>

    <div class="lightbox__thumbs" x-show="thumbs">
        <template x-for="(item, i) in slides" :key="i">
            <button type="button" class="lightbox__thumb" :class="{ 'is-current': i === index }" @click="go(i)">
                <img :src="item.thumb" alt="">
            </button>
        </template>
    </div>
</div>
