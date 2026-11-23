/* eslint-disable no-console, max-len */
define([], function() {
    return {
        init: function() {
            const header = document.querySelector('.nhsuk-header') || document.querySelector('header');

            if (header) {
                const headerObserver = new ResizeObserver(entries => {
                    for (let entry of entries) {
                        const height = entry.borderBoxSize?.[0]?.blockSize || entry.contentRect.height;
                        const calculatedHeight = `${Math.round(height)}px`;

                        // Set the CSS variable
                        document.documentElement.style.setProperty('--header-height', calculatedHeight);

                        // Log to DevTools console
                        console.log('[Dynamic Header Height Sync] Updated --header-height to:', calculatedHeight); // eslint-disable-line no-console
                    }
                });

                headerObserver.observe(header);
            } else {
                console.warn('[Header Height Sync] Could not find header element on this page.'); // eslint-disable-line no-console
            }
        }
    };
});