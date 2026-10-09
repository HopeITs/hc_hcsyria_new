/*
 * Livewire bundles Alpine and starts both itself (@livewireScripts in the
 * layout). Components are registered here; page-specific libraries are added
 * with dynamic import() so they load only on the pages that use them.
 */
import siteNav from './site-nav';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('siteNav', siteNav);
});
