/*
 * Livewire bundles Alpine and starts both itself (@livewireScripts in the
 * layout). Components are registered here; page-specific libraries are added
 * with dynamic import() so they load only on the pages that use them.
 */
import activityTabs from './activity-tabs';
import carousel from './carousel';
import categoryShowcase from './category-showcase';
import centreSpread from './centre-spread';
import familyMap from './family-map';
import hideNavBetween from './hide-nav-between';
import lightbox from './lightbox';
import preloader from './preloader';
import radialBars from './radial-bars';
import reveal from './reveal';
import searchBox from './search-box';
import siteNav from './site-nav';
import zoneMap from './zone-map';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('searchBox', searchBox);
    window.Alpine.data('siteNav', siteNav);
    window.Alpine.data('zoneMap', zoneMap);
    window.Alpine.data('activityTabs', activityTabs);
    window.Alpine.data('carousel', carousel);
    window.Alpine.data('categoryShowcase', categoryShowcase);
    window.Alpine.data('centreSpread', centreSpread);
    window.Alpine.data('familyMap', familyMap);
    window.Alpine.data('lightbox', lightbox);
    window.Alpine.data('preloader', preloader);
    window.Alpine.data('radialBars', radialBars);
    reveal(window.Alpine);
    hideNavBetween(window.Alpine);
});
