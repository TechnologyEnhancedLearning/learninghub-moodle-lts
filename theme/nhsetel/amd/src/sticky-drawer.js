/**
 * Sticky drawer height layout adjustments for the NHSETEL theme.
 *
 * @module     theme_nhsetel/sticky-drawer
 * @copyright  2026 Your Name / Organisation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    return {
        /**
         * Initialises the header height observer to adjust the sticky sidebar.
         *
         * @method init
         */
        init: function() {
            /**
             * Calculates the exact height of the NHS header and updates CSS.
             */
            function updateHeaderHeight() {
                const header = document.querySelector('#page-wrapper > header') || document.querySelector('.nhs-header');
                if (header) {
                    const headerHeight = header.offsetHeight;
                    /* eslint-disable no-console */
                    console.log('StickyDrawer calculated header height:', headerHeight);
                    /* eslint-enable no-console */
                    document.documentElement.style.setProperty('--real-header-height', `${headerHeight}px`);
                }
            }
            updateHeaderHeight();
            window.addEventListener('resize', updateHeaderHeight);
        }
    };
});