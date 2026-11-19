<?php
// We need to include config.php to get $CFG->wwwroot
require_once(__DIR__ . '/../../../config.php');

// Set header to ensure it's served as JavaScript
header('Content-Type: application/javascript');
?>
document.addEventListener('DOMContentLoaded', function () {   

    // Pass the Moodle base URL from PHP to JavaScript
    const MOODLE_BASE_URL = '<?php echo $CFG->wwwroot; ?>';

    // Determine if viewed on mobile as this will determine the full screen implementation
    // If not mobile then will use the native full screen implementation
    // If mobile then will use a simulated full screen implementation
    const isMobile = () => {
        console.log("navigator.platform: " + navigator.platform);
        console.log("navigator.maxTouchPoints = " + navigator.maxTouchPoints);
        const ua = navigator.userAgent || navigator.vendor || window.opera;

        // iOS detection (iPhone, iPod)
        if (/iPhone|iPod/i.test(ua)) return true;

        // iPad detection
        // Modern iPads on iOS 13+ report MacIntel in navigator.platform
        if ((/iPad/i.test(ua)) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)) {
            return true;
        }

        // Android detection (phones & tablets)
        if (/Android/i.test(ua)) return true;

        return false;
    };    

    // Function to get a URL parameter from a given URL string
    function getUrlParameterFromHref(href, name) {
        name = name.replace(/[\[\]]/g, '\\$&');
        var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
            results = regex.exec(href);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, ' '));
    }

    // --- New: Get the target section link from the breadcrumbs ---
    let targetSectionLink = null;
    if (window.location.pathname.includes('/mod/scorm/player.php')) {
        const breadcrumbItems = document.querySelectorAll('.nhsuk-breadcrumb__list .nhsuk-breadcrumb__item a');
        for (let i = 0; i < breadcrumbItems.length; i++) {
            const link = breadcrumbItems[i];
            if (link.href.includes('/course/section.php?id=')) {
                targetSectionLink = link.href;
                console.log('Found target section link:', targetSectionLink);
                break;
            }
        }
        if (!targetSectionLink) {
            console.warn("Could not find the target section link in the breadcrumbs. Exit buttons will use default URL.");
        }
    }

    // --- Determine the Course Return URL ---
    let courseReturnUrl = MOODLE_BASE_URL + '/course/view.php'; // Default fallback (leads to error if no ID)
    let foundCourseId = null; // Variable to store the course ID once found

    // 1. Try to get the Moodle-generated "Exit activity" button's href and extract the course ID
    const moodleExitButtonOriginal = document.querySelector('div.d-flex.flex-row-reverse.mb-2 > a.btn.btn-secondary[title="Exit activity"]');
    if (moodleExitButtonOriginal && moodleExitButtonOriginal.href) {
        // Crucial: For SCORM modules, the return link often uses 'course' for the course ID
        foundCourseId = getUrlParameterFromHref(moodleExitButtonOriginal.href, 'course');
        if (foundCourseId) {
            console.log(`[${Date.now()}] Course ID found from Moodle Exit button HREF 'course' parameter: ${foundCourseId}`);
        } else {
            // Fallback: If 'course' not found, try 'id' (might be a direct link to course/view.php)
            foundCourseId = getUrlParameterFromHref(moodleExitButtonOriginal.href, 'id');
            if (foundCourseId) {
                console.log(`[${Date.now()}] Course ID found from Moodle Exit button HREF 'id' parameter: ${foundCourseId}`);
            }
        }
    }

    // 2. Fallback: If not found from button, try Moodle's global M.cfg object
    if (!foundCourseId && typeof M !== 'undefined' && M.cfg && M.cfg.courseid) {
        foundCourseId = M.cfg.courseid;
        console.log(`[${Date.now()}] Course ID found from M.cfg.courseid (fallback): ${foundCourseId}`);
    }

    // 3. Fallback: If still not found, try parsing 'course' parameter from current window URL
    // (This is less likely for player.php but included for robustness)
    if (!foundCourseId) {
        foundCourseId = getUrlParameterFromHref(window.location.href, 'course');
        if (foundCourseId) {
            console.log(`[${Date.now()}] Course ID found from current URL parameter (fallback): ${foundCourseId}`);
        }
    }

    // 4. Final Fallback: Try document.referrer (the page that linked to this SCORM)
    if (!foundCourseId && document.referrer) {
        foundCourseId = getUrlParameterFromHref(document.referrer, 'course');
        if (foundCourseId) {
            console.log(`[${Date.now()}] Course ID found from document.referrer 'course' parameter (fallback): ${foundCourseId}`);
        } else {
            foundCourseId = getUrlParameterFromHref(document.referrer, 'id'); // Try 'id' too
            if (foundCourseId) {
                console.log(`[${Date.now()}] Course ID found from document.referrer 'id' parameter (fallback): ${foundCourseId}`);
            }
        }
    }

    // Construct the final return URL based on the foundCourseId
    if (foundCourseId) {
        courseReturnUrl = MOODLE_BASE_URL + '/course/view.php?id=' + foundCourseId;
        console.log(`[${Date.now()}] Final course return URL determined: ${courseReturnUrl}`);
    } else {
        // This warning should hopefully be rare 
        console.warn(`[${Date.now()}] Could not determine specific course ID. Exit button will go to generic course view: ${courseReturnUrl}`);
    }

    // --- End Determine the Course Return URL ---


    const scormContentDiv = document.getElementById('scorm_content');
    let isFullScreen = false; // Moved here to be accessible by all fullscreen related logic
    let fullscreenMode = null; // "native" or "simulated"

    // --- Font Awesome 6.x 'right-from-bracket' SVG Icon Code ---
    const svgIconCode = `
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="width: 1em; height: 1em; vertical-align: middle; margin-right: 0.5em; fill: currentColor;">
            <path d="M377.9 105.9L500.7 228.7c7.2 7.2 11.3 17.1 11.3 27.3s-4.1 20.1-11.3 27.3L377.9 406.1c-6.4 6.4-15 9.9-24 9.9c-18.7 0-33.9-15.2-33.9-33.9l0-62.1-128 0c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32l128 0 0-62.1c0-18.7 15.2-33.9 33.9-33.9c9 0 17.6 3.6 24 9.9zM160 96L96 96c-17.7 0-32 14.3-32 32l0 256c0 17.7 14.3 32 32 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-64 0c-53 0-96-43-96-96L0 128C0 75 43 32 96 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32z"/>
        </svg>
    `;

    // Modify the Moodle-generated "Exit activity" button
    const moodleExitButton = document.querySelector('div.d-flex.flex-row-reverse.mb-2 > a.btn.btn-secondary[title="Exit activity"]');
    if (moodleExitButton) {
        if (!moodleExitButton.querySelector('svg')) { // Only add icon if not already present
            moodleExitButton.innerHTML = svgIconCode + moodleExitButton.textContent.trim();
            console.log('Icon added to Moodle-generated Exit Activity button.');
        }
        
        if (targetSectionLink) {
            moodleExitButton.href = targetSectionLink;
            console.log('Moodle-generated Exit Activity button href updated to:', targetSectionLink);
        } else {
            console.log('Moodle-generated Exit Activity button will use its default href.');
        }
    } else if (!moodleExitButton) {
        console.warn('Moodle-generated Exit Activity button not found for icon injection.');
    }

    if (scormContentDiv) {
        // Create custom Fullscreen button
        const fullScreenButton = document.createElement('a');
        fullScreenButton.className = 'btn btn-secondary';
        fullScreenButton.textContent = 'Full screen';
        fullScreenButton.style.marginRight = '10px';
        fullScreenButton.style.wordSpacing = 'normal';
        fullScreenButton.id = 'scorm-fullscreen-button';

        // Create the white box container for fullscreen buttons
        const fullscreenButtonContainer = document.createElement('div');
        fullscreenButtonContainer.id = 'fullscreen-button-container';
        Object.assign(fullscreenButtonContainer.style, {
            position: 'absolute',
            top: '0px', // Align to the very top of the fullscreen element
            left: '0px', // Align to the very left
            width: '100%', // Span the full width of the fullscreen element
            backgroundColor: 'white',
            padding: '10px 20px',
            borderRadius: '0px',            
            zIndex: '2147483647',
            display: 'none', // Initially hidden, only shown in fullscreen
            justifyContent: 'space-between', // Spaces out the buttons nicely
            alignItems: 'center',
            boxSizing: 'border-box' // Ensures padding is included within the element's total width/height
        });

        // Create custom "Exit full screen" button
        const exitFullscreenButton = document.createElement('a');
        exitFullscreenButton.className = 'btn btn-secondary';
        exitFullscreenButton.textContent = 'Exit full screen';
        exitFullscreenButton.style.wordSpacing = 'normal';
        exitFullscreenButton.id = 'scorm-exit-fullscreen-button';

        // Create custom "Exit activity" button (shown in fullscreen)
        const exitActivityButton = document.createElement('a');
        exitActivityButton.className = 'btn btn-secondary';
        exitActivityButton.setAttribute('href', targetSectionLink || courseReturnUrl); // Use targetSectionLink if available
        exitActivityButton.setAttribute('title', 'Exit activity');
        exitActivityButton.style.wordSpacing = 'normal';
        exitActivityButton.id = 'scorm-exit-activity-button';
        exitActivityButton.innerHTML = svgIconCode + 'Exit activity';


        // Append the exit buttons to the white box container
        fullscreenButtonContainer.appendChild(exitFullscreenButton);
        fullscreenButtonContainer.appendChild(exitActivityButton);

        // Locate the parent container of the Moodle-generated "Exit activity" button
        const moodleButtonsContainer = document.querySelector('div.d-flex.flex-row-reverse.mb-2');

        if (moodleButtonsContainer) {
            // Apply 'justify-content: space-between' to push items to opposite ends
            moodleButtonsContainer.style.setProperty('justify-content', 'space-between', 'important');
            // Ensure flex-direction is 'row' so 'space-between' works as expected (left to right)
            moodleButtonsContainer.style.setProperty('flex-direction', 'row', 'important');

            // Insert the new Full screen button as the FIRST child
            // This makes it the "start" item in the now 'row' direction,
            // so 'space-between' pushes it to the far left.
            const firstChild = moodleButtonsContainer.firstChild;
            moodleButtonsContainer.insertBefore(fullScreenButton, firstChild);
            console.log(`[${Date.now()}] "Full screen" button inserted to the far left.`);
        } else if (scormContentDiv.parentNode) {
            // Fallback: If Moodle's button container isn't found, insert it before the SCORM content div
            scormContentDiv.parentNode.insertBefore(fullScreenButton, scormContentDiv);
            console.warn(`[${Date.now()}] Moodle's button container not found. "Full screen" button inserted before SCORM content div.`);
        } else {
            console.error('scormContentDiv has no parentNode. Cannot insert Full Screen button.');
        }

        // Insert the white box container for fullscreen buttons as the FIRST child of #scormpage
        const scormPageDiv = document.getElementById('scormpage'); // The element to make fullscreen
        if (scormPageDiv) {
            scormPageDiv.insertBefore(fullscreenButtonContainer, scormPageDiv.firstChild);
            console.log(`[${Date.now()}] fullscreenButtonContainer inserted inside #scormpage as first child.`);
        } else {
            console.error('scormPageDiv (#scormpage) not found. Cannot insert Fullscreen button container.');
        }

        const enterSimulatedFullscreen = () => {            
            document.body.classList.add("simulated-fullscreen");
            isFullScreen = true;
        };

        const exitSimulatedFullscreen = () => {            
            document.body.classList.remove("simulated-fullscreen");
            resetFullscreenUI();        
        };

        const enterNativeFullscreen = () => {
            
            const elementToFullscreen = scormPageDiv;

            if (!elementToFullscreen) {
                console.error("SCORM page div (#scormpage) not found for fullscreen.");
                return;
            }

            elementToFullscreen.requestFullscreen()
                .then(() => {
                // --- layout adjustments ---
                fullScreenButton.style.display = "none";
                fullscreenButtonContainer.style.display = "flex";

                // const headerHeight = fullscreenButtonContainer.offsetHeight;
                const headerHeight = 30;
                console.log(`[${Date.now()}] Fullscreen header height: ${headerHeight}px`);

                scormContentDiv.style.setProperty("padding-top", `${headerHeight}px`, "important");
                scormContentDiv.style.setProperty("height", `calc(100vh - ${headerHeight}px)`, "important");
                scormContentDiv.style.setProperty("width", "100vw", "important");
                scormContentDiv.style.setProperty("box-sizing", "border-box", "important");

                const scormIframe = document.getElementById("scorm_object");
                if (scormIframe) {
                    scormIframe.style.setProperty("width", "100%", "important");
                    scormIframe.style.setProperty("height", "100%", "important");
                    scormIframe.style.setProperty("display", "block", "important");
                    scormIframe.style.setProperty("background-color", "#ffffff", "important");
                }

                isFullScreen = true;
                })
                .catch(err => {
                console.error("Failed to enter fullscreen:", err);
                });

        };

        const exitNativeFullscreen = () => {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            }           
        };
        
        
        fullScreenButton.addEventListener('click', function () {
            if (!isFullScreen) { // Currently not in fullscreen
                if (isMobile()) {
                    enterSimulatedFullscreen();
                    applySimulatedFullscreenUI();  
                    fullscreenMode = "simulated";
                } else {
                    enterNativeFullscreen();
                    applyNativeFullscreenUI(); 
                    fullscreenMode = "native";
                }               
            }
        });

        // Event listener for the "Exit full screen" button within the white box
        exitFullscreenButton.addEventListener('click', function () {
             if (fullscreenMode === "native") {
                exitNativeFullscreen();
            } else if (fullscreenMode === "simulated") {
                exitSimulatedFullscreen();
            }
        });


        const applyNativeFullscreenUI = () => {
            fullScreenButton.style.display = 'none'; 
            fullscreenButtonContainer.style.display = 'flex';

            // const headerHeight = fullscreenButtonContainer.offsetHeight;
            const headerHeight = 30;

            scormContentDiv.style.setProperty('padding-top', `${headerHeight}px`, 'important');
            scormContentDiv.style.setProperty('height', `calc(100vh - ${headerHeight}px)`, 'important');
            scormContentDiv.style.setProperty('width', '100vw', 'important');
            scormContentDiv.style.setProperty('box-sizing', 'border-box', 'important');

            const scormIframe = document.getElementById('scorm_object');
            if (scormIframe) {
                scormIframe.style.setProperty('width', '100%', 'important');
                scormIframe.style.setProperty('height', '100%', 'important');
            }

            isFullScreen = true;
        };

        const applySimulatedFullscreenUI = () => {
            document.body.classList.add('simulated-fullscreen'); // hides Moodle chrome
            applyNativeFullscreenUI(); // reuse shared iframe adjustments
        };

        const resetFullscreenUI = () => {
            fullScreenButton.style.display = 'block';
            fullscreenButtonContainer.style.display = 'none';

            scormContentDiv.style.removeProperty('padding-top');
            scormContentDiv.style.removeProperty('height');
            scormContentDiv.style.removeProperty('width');
            scormContentDiv.style.removeProperty('box-sizing');

            const scormIframe = document.getElementById('scorm_object');
            if (scormIframe) {
                scormIframe.style.removeProperty('width');
                scormIframe.style.removeProperty('height');
            }

            document.body.classList.remove('simulated-fullscreen'); // cleanup if simulated
            isFullScreen = false;
        };

        // fullscreenchange listener
        const handleFullscreenChange = () => {
            isFullScreen = !!document.fullscreenElement;
            if (!isFullScreen) {
                resetFullscreenUI();
            }
        };

        // Listen for native fullscreen changes (ESC key, browser actions)
        document.addEventListener("fullscreenchange", handleFullscreenChange);

       
        exitActivityButton.addEventListener('click', function (event) {
            event.preventDefault(); // Prevent default link behavior
            window.location.href = targetSectionLink || courseReturnUrl;
        });


        // Hiding Adapt's Internal Exit Button
        let adaptButtonHiderInterval = null;
        let iframeFinderInterval = null;
        const MAX_IFRAME_FIND_TIME = 20000; // Max 20 seconds to find the iframe
        const MAX_POLL_TIME = 15000; // Max 15 seconds for button hiding inside iframe

        // Function to find and hide the Adapt button
       const hideAdaptExitButton = () => {
            const scormIframe = document.getElementById('scorm_object');
            if (!scormIframe || !scormIframe.contentDocument && !scormIframe.contentWindow) {
                return false;
            }

            const iframeDoc = scormIframe.contentDocument || scormIframe.contentWindow.document;

            // Try both selectors
            const selectors = [
                '.nav-course__exit-btn',            // Newer Adapt exit button
                '.navigation-course-exit-button'    // Older Adapt exit button
            ];

            let found = false;

            selectors.forEach(selector => {
                const button = iframeDoc.querySelector(selector);
                if (button) {
                    if (getComputedStyle(button).display !== 'none') {
                        button.style.setProperty('display', 'none', 'important');
                        console.log(`[${Date.now()}] Hidden Adapt exit button via selector: ${selector}`);
                    }
                    found = true;
                }
            });

            return found;
        };

        // Function to set up the hiding logic *inside* the iframe
        const setupIframeHidingLogic = () => {
            console.log(`[${Date.now()}] setupIframeHidingLogic called.`);
            const scormIframe = document.getElementById('scorm_object');

            // Only proceed if iframe is available and we haven't already set up the observer/interval
            if (!scormIframe || scormIframe._adaptHidingLogicInitialized) {
                return;
            }
            scormIframe._adaptHidingLogicInitialized = true; // Mark as initialized

            // IMMEDIATE & PERSISTENT POLLING FOR BUTTON INSIDE IFRAME
            if (!adaptButtonHiderInterval) {
                let pollStartTime = Date.now();
                adaptButtonHiderInterval = setInterval(() => {
                    if (Date.now() - pollStartTime > MAX_POLL_TIME) {
                        clearInterval(adaptButtonHiderInterval);
                        adaptButtonHiderInterval = null;
                        console.log(`[${Date.now()}] Stopped polling for Adapt button inside iframe after max time limit.`);
                        return;
                    }
                    if (hideAdaptExitButton()) {
                        clearInterval(adaptButtonHiderInterval);
                        adaptButtonHiderInterval = null;
                        console.log(`[${Date.now()}] Polling stopped: button found and hidden.`);
                    }
                }, 50);
            }

            // MUTATION OBSERVER
            if (!scormIframe._adaptExitButtonObserver) {
                const iframeDoc = scormIframe.contentDocument || scormIframe.contentWindow.document;
                const targetNode = iframeDoc ? iframeDoc.documentElement || iframeDoc.body : null;

                if (targetNode) {
                    const observer = new MutationObserver((mutationsList) => {
                        for (const mutation of mutationsList) {
                            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                                if (hideAdaptExitButton()) {
                                    console.log(`[${Date.now()}] Adapt button re-hidden by MutationObserver after mutation.`);
                                }
                            }
                        }
                    });
                    observer.observe(targetNode, { childList: true, subtree: true });
                    scormIframe._adaptExitButtonObserver = observer;
                    console.log(`[${Date.now()}] MutationObserver for Adapt button set up successfully on ${targetNode === iframeDoc.documentElement ? 'documentElement' : 'body'}.`);
                } else {
                    console.warn(`[${Date.now()}] Iframe document or body/documentElement not available to attach MutationObserver (targetNode null).`);
                }
            }
        };

        // Main entry point: Polling for the iframe itself
        console.log(`[${Date.now()}] Starting polling for #scorm_object iframe...`);
        let iframeFindStartTime = Date.now();
        iframeFinderInterval = setInterval(() => {
            const scormIframe = document.getElementById('scorm_object');
            if (scormIframe) {
                clearInterval(iframeFinderInterval);
                iframeFinderInterval = null;
                console.log(`[${Date.now()}] #scorm_object iframe found! Time taken: ${Date.now() - iframeFindStartTime}ms.`);

                scormIframe.onload = () => {
                    console.log(`[${Date.now()}] Iframe ONLOAD event fired.`);
                    setupIframeHidingLogic();
                };

                if (scormIframe.contentWindow) {
                    scormIframe.contentWindow.onload = () => {
                        console.log(`[${Date.now()}] Iframe CONTENTWINDOW.ONLOAD event fired.`);
                        setupIframeHidingLogic();
                    };
                }

                if ((scormIframe.contentDocument && scormIframe.contentDocument.readyState === 'complete') ||
                    (scormIframe.contentWindow && scormIframe.contentWindow.document)) {
                    console.log(`[${Date.now()}] Iframe content ready on initial check. Attempting hiding setup.`);
                    setupIframeHidingLogic();
                } else {
                    setTimeout(() => {
                        console.log(`[${Date.now()}] Micro-delay (100ms) fallback for hiding logic setup after iframe found.`);
                        setupIframeHidingLogic();
                    }, 100);
                }

            } else if (Date.now() - iframeFindStartTime > MAX_IFRAME_FIND_TIME) {
                clearInterval(iframeFinderInterval);
                iframeFinderInterval = null;
                console.error(`[${Date.now()}] #scorm_object iframe NOT FOUND after max time limit (${MAX_IFRAME_FIND_TIME / 1000}s). Cannot set up Adapt button hiding.`);
            }
        }, 50);

        // Cleanup Observers and Intervals on Page Unload
        window.addEventListener('beforeunload', () => {
            if (adaptButtonHiderInterval) {
                clearInterval(adaptButtonHiderInterval);
                console.log('Cleared Adapt button polling interval on page unload.');
            }
            if (iframeFinderInterval) {
                clearInterval(iframeFinderInterval);
                console.log('Cleared iframe finder interval on page unload.');
            }
            const scormIframe = document.getElementById('scorm_object');
            if (scormIframe && scormIframe._adaptExitButtonObserver) {
                scormIframe._adaptExitButtonObserver.disconnect();
                console.log('Disconnected Adapt button MutationObserver on page unload.');
            }
        });

    } else {
        console.error(`[${Date.now()}] SCORM content div (#scorm_content) not found. Full Screen button could not be added.`);
    }
});