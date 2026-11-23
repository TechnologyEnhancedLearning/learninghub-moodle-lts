// This import path uses a relative path from:
// /theme/nhse/javascript/nhsuk-init-module.js
// to:
// /theme/nhse/node_modules/nhsuk-frontend/dist/nhsuk/nhsuk-frontend.js
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
});