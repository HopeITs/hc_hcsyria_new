/*
 * Livewire bundles Alpine and starts both itself (@livewireScripts in the
 * layout). Components are registered here; page-specific libraries are added
 * with dynamic import() so they load only on the pages that use them.
 */
import beforeAfter from './before-after';
import carousel from './carousel';
import categoryShowcase from './category-showcase';
import centreSpread from './centre-spread';
import familyMap from './family-map';
import hideNavBetween from './hide-nav-between';
import lightbox from './lightbox';
import preloader from './preloader';
import projectList from './project-list';
import projectMap from './project-map';
import radialBars from './radial-bars';
import relatedCarousel from './related-carousel';
import reveal from './reveal';
import searchBox from './search-box';
import siteNav from './site-nav';
import viewSwitch from './view-switch';
import zoneMap from './zone-map';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('searchBox', searchBox);
    window.Alpine.data('viewSwitch', viewSwitch);
    window.Alpine.data('siteNav', siteNav);
    window.Alpine.data('zoneMap', zoneMap);
    window.Alpine.data('beforeAfter', beforeAfter);
    window.Alpine.data('carousel', carousel);
    window.Alpine.data('categoryShowcase', categoryShowcase);
    window.Alpine.data('centreSpread', centreSpread);
    window.Alpine.data('familyMap', familyMap);
    window.Alpine.data('lightbox', lightbox);
    window.Alpine.data('preloader', preloader);
    window.Alpine.data('radialBars', radialBars);
    window.Alpine.data('relatedCarousel', relatedCarousel);
    window.Alpine.data('projectMap', projectMap);
    projectList(window.Alpine);
    reveal(window.Alpine);
    hideNavBetween(window.Alpine);
});
