/*
 * The staff dashboard's script, loaded only by layouts/admin. Livewire starts
 * Alpine; the pieces every admin page shares are registered here, one file each
 * under resources/js/admin/.
 */
import alerts, { confirmStore } from './admin/alerts';
import palette from './admin/palette';
import richText from './admin/rich-text';
import shell from './admin/shell';
import siteStudio from './admin/site-studio';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('adminShell', shell);
    window.Alpine.data('adminAlerts', alerts);
    window.Alpine.data('adminPalette', palette);
    window.Alpine.data('richText', richText);
    window.Alpine.data('siteStudio', siteStudio);
    window.Alpine.store('confirm', confirmStore());
});
