/*
 * Livewire bundles Alpine and starts both itself (@livewireScripts in the
 * layout). Components are registered here; page-specific libraries are added
 * with dynamic import() so they load only on the pages that use them.
 */
import activityTabs from './activity-tabs';
import carousel from './carousel';
import centreStatistics from './centre-statistics';
import categoryShowcase from './category-showcase';
import hideNavBetween from './hide-nav-between';
import lightbox from './lightbox';
import preloader from './preloader';
import reveal from './reveal';
import siteNav from './site-nav';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('siteNav', siteNav);
    window.Alpine.data('activityTabs', activityTabs);
    window.Alpine.data('carousel', carousel);
    window.Alpine.data('centreStatistics', centreStatistics);
    window.Alpine.data('categoryShowcase', categoryShowcase);
    window.Alpine.data('lightbox', lightbox);
    window.Alpine.data('preloader', preloader);
    reveal(window.Alpine);
    hideNavBetween(window.Alpine);
});
