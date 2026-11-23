// This import path uses a relative path from:
// /theme/nhsetel/javascript/nhsuk-init-module.js
// to:
// /theme/nhsetel/node_modules/nhsuk-frontend/dist/nhsuk/nhsuk-frontend.js
// (Using .js as per the file you provided, assuming it's the un-minified version).

import { initAll } from '../node_modules/nhsuk-frontend/dist/nhsuk/nhsuk-frontend.js';

// Initialize all components after the DOM is fully loaded.
// This is critical for ensuring the header component exists before it's initialized.
document.addEventListener('DOMContentLoaded', function() {
    try {
        initAll();
        console.log("NHS Frontend V10 components initialized via import.");
    } catch (e) {
        console.error("Failed to run NHS initAll():", e);
    }

    // --------------------------------------------------------------------------
    // Disable Moodle Course Index ScrollSpy
    // --------------------------------------------------------------------------
    const drawerContainer = document.querySelector('#nhs-courseindex-sidebar');
    
    if (drawerContainer) {
        console.log("Moodle Course Index ScrollSpy detected. Disabling auto-scroll behavior...");
        // 1. Intercept scrollIntoView calls on ALL child elements inside the course index
        const originalScrollIntoView = Element.prototype.scrollIntoView;

        Element.prototype.scrollIntoView = function(...args) {
            // If Moodle tries to auto-scroll any element INSIDE the LHS drawer, block it!
            if (drawerContainer.contains(this)) {
                console.log("BLOCKED Moodle ScrollSpy scrollIntoView on:", this);
                return; 
            }
            // Otherwise, allow standard scrollIntoView behavior for the rest of the page
            return originalScrollIntoView.apply(this, args);
        };

        // 2. Prevent Moodle JS from focusing active links and triggering native browser auto-scroll
        drawerContainer.addEventListener('focusin', (e) => {
            // Prevents programmatic focus during scrolling from shifting the inner drawer scrollbar
            if (document.activeElement && drawerContainer.contains(document.activeElement)) {
                // If focus was triggered programmatically (not via keyboard/tab key), stop scroll jumping
                if (!e.detail) { 
                    e.stopPropagation();
                }
            }
        }, true);

        // 3. (Optional) Prevent ScrollSpy from auto-expanding accordions on scroll
        drawerContainer.addEventListener('show.bs.collapse', (e) => {
            // Only block synthetic JS calls; allow real user clicks (!e.isTrusted)
            if (!e.isTrusted) {
                e.preventDefault();
                e.stopPropagation();
            }
        }, true);

        console.log("Moodle Course Index ScrollSpy successfully disabled via Prototype Interception.");
    }



});