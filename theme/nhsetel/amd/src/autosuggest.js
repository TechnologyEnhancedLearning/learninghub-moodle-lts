define(['jquery'], function ($) {
    return {
        init: function () {
            const searchInput = $('#search-field');
            // Ensure you have a <ul> element with this ID in your HTML,
            // typically right below the search input field.
            const suggestionsList = $('#search-field_listbox');

            // Create a hidden live region for screen readers to announce results
            const liveRegion = $('<div class="sr-only" aria-live="polite"></div>').appendTo('body');

            // Hide the list initially
            suggestionsList.empty().hide();

            searchInput.on('input', function () {
                const query = $(this).val().trim(); // Trim whitespace from the query

                // Only perform search if query length is 2 or more characters
                if (query.length < 2) {
                    suggestionsList.empty().hide(); // Clear and hide if query too short
                    liveRegion.text("");
                    return;
                }

                $.ajax({
                    url: M.cfg.wwwroot + '/theme/nhsetel/ajax/search_suggestions.php',
                    type: 'GET',
                    data: { query: query },
                    success: function (response) {

                        let allSuggestions = [];

                        // Check if the expected nested structure exists and is an object
                        if (response.api_decoded_result && typeof response.api_decoded_result === 'object') {
                            const decodedResult = response.api_decoded_result;

                            // Process Concept Documents - order them as per the .NET code (Concepts first)
                            if (decodedResult.concepts_documents && decodedResult.concepts_documents.documents) {
                                decodedResult.concepts_documents.documents.forEach(item => {
                                    allSuggestions.push({
                                        displayTitle: item.concept, // As per .NET code, uses item.Concept
                                        originalTermForUrl: item.concept, // Used for the actualHref for concepts
                                        type: "concepts", // Matches 'Concepts' from .NET GetUrl searchType
                                        payload: item._click.payload, // Pass the whole payload for tracking URLcd
                                    });
                                });
                            }

                            // Process Resource Documents
                            // eslint-disable-next-line max-len
                            if (decodedResult.resources_collection_documents && decodedResult.resources_collection_documents.documents) {
                                decodedResult.resources_collection_documents.documents.forEach(item => {

                                    // Construct the correct course URL if the resource_type is 'course'
                                    let itemUrl;
                                    if (item.resource_type && item.resource_type.toLowerCase() === "course") {
                                        // Ensure the base url has a trailing slash before appending the path
                                        const baseUrl = item.url.endsWith('/') ? item.url : `${item.url}/`;
                                        itemUrl = `${baseUrl}course/view.php?id=${item.resource_reference_id}`;
                                    }

                                    allSuggestions.push({
                                        displayTitle: item.title,
                                        targetReferenceId: item.resource_reference_id, // Use ResourceReferenceId as per .NET GetUrl
                                        type: item.resource_type, // Matches 'Resource' from .NET GetUrl searchType
                                        url: itemUrl,
                                        payload: item._click.payload
                                    });
                                });
                            }                           
                        }
                        // Now, render the suggestion
                        if (allSuggestions.length > 0) {
                            suggestionsList.empty(); // Clear existing list items

                            allSuggestions.forEach(function (item) {
                                let actualTargetUrl = '#'; // This will be the '/Resource/ID' or '/Catalogue/name' part
                                let typeClass = '';
                                let subText = '';
                                let svgIconPath = '';
                                let svgWidth = '16'; // Default
                                let svgHeight = '12'; // Default

                                // Determine actualTargetUrl SVG path, and dimensions based on type, mimicking the .NET GetUrl logic
                                if (item.type.toLowerCase() === 'resource') {
                                    if (item.targetReferenceId && item.targetReferenceId > 0) {
                                        actualTargetUrl = `/Resource/${item.targetReferenceId}`;
                                    } else {
                                        actualTargetUrl = `/Search/results?term=${encodeURIComponent(item.displayTitle)}`;
                                    }
                                    typeClass = 'autosugg-resource';
                                    subText = 'Learning resource';
                                } else if (item.type.toLowerCase() === 'catalogue') {
                                    if (item.targetReference) {
                                        actualTargetUrl = `/Catalogue/${item.targetReference}`;
                                    } else {
                                        actualTargetUrl = `/Search/results?term=${encodeURIComponent(item.displayTitle)}`;
                                    }
                                    typeClass = 'autosugg-catalogue';
                                    subText = 'Catalogue';
                                } else if (item.type.toLowerCase() === 'course') {
                                    if (item.url) {
                                        actualTargetUrl = item.url;
                                    } else {
                                        actualTargetUrl = `/Search/results?term=${encodeURIComponent(item.displayTitle)}`;
                                    }
                                    typeClass = 'autosugg-course';
                                    subText = 'Course';
                                } else if (item.type.toLowerCase() === 'concepts') {
                                    actualTargetUrl = `/Search/results?term=${encodeURIComponent(item.originalTermForUrl)}`;
                                    typeClass = 'autosugg-concepts';
                                    subText = ''; // There is no subtext for Concepts
                                    // eslint-disable-next-line max-len
                                    svgIconPath = 'M11.8558 10.5296L15.7218 14.3861C15.8998 14.5627 16 14.8031 16 15.0539C16 15.3047 15.8998 15.5451 15.7218 15.7218C15.5451 15.8998 15.3047 16 15.0539 16C14.8031 16 14.5627 15.8998 14.3861 15.7218L10.5296 11.8558C7.76424 13.9254 3.86973 13.5064 1.60799 10.8959C-0.653748 8.28537 -0.513826 4.37088 1.92852 1.92852C4.37088 -0.513826 8.28537 -0.653748 10.8959 1.60799C13.5064 3.86973 13.9254 7.76424 11.8558 10.5296ZM6.58846 1.88528C3.99101 1.88528 1.88537 3.99093 1.88537 6.58837C1.88537 9.18581 3.99101 11.2915 6.58846 11.2915C9.1859 11.2915 11.2915 9.18581 11.2915 6.58837C11.2915 3.99093 9.1859 1.88528 6.58846 1.88528Z';
                                    svgWidth = '16';
                                    svgHeight = '16';
                                }

                                // Construct the full tracking URL based on the .NET GetUrl method
                                // Ensure M.cfg.wwwroot is correctly prepended for relative paths
                                // eslint-disable-next-line max-len
                                // The actualTargetUrl is encoded and used as the value for the 'url' query parameter.
                                const baseUrl = M.cfg.dotnet_base_url || '';
                                const payload = item.payload || {};
                                /* eslint-disable max-len */
                                const params = new URLSearchParams({
                                    term: item.displayTitle,
                                    url: actualTargetUrl,
                                    clickTargetUrl: payload.ClickTargetUrl || '',
                                    itemIndex: payload.HitNumber || '',
                                    totalNumberOfHits: (payload.SearchSignal && payload.SearchSignal.Stats && payload.SearchSignal.Stats.TotalHits) || '',
                                    containerId: payload.ContainerId || '',
                                    name: payload.DocumentFields ? payload.DocumentFields.Name : '',
                                    query: payload.SearchSignal ? payload.SearchSignal.Query : '',
                                    userQuery: payload.SearchSignal && payload.SearchSignal.UserQuery ? encodeURIComponent(payload.SearchSignal.UserQuery) : '',
                                    searchId: payload.SearchSignal ? payload.SearchSignal.SearchId : '',
                                    timeOfSearch: payload.SearchSignal ? payload.SearchSignal.TimeOfSearch : '',
                                    title: payload.DocumentFields ? payload.DocumentFields.Title : ''
                                });
                                /* eslint-enable max-len */

                                // eslint-disable-next-line max-len
                                const trackingHref = `${baseUrl}search/record-autosuggestion-click?${params.toString()}`;

                                /* eslint-disable max-len */
                                const dynamicSvgIcon = svgIconPath ? `
                                <svg class="nhsuk-icon autosuggestion-icon" width="${svgWidth}" height="${svgHeight}" viewBox="0 0 ${svgWidth} ${svgHeight}" xmlns="http://www.w3.org/2000/svg">
                                    <path d="${svgIconPath}" />
                                </svg>
                                ` : '';
                                /* eslint-enable max-len */
                                /* eslint-disable max-len */
                                const listItem = `
                                    <li class="autosuggestion-option ${typeClass}">
                                        <a tabindex="0" style="text-decoration:none !important" href="${trackingHref}">
                                            ${dynamicSvgIcon}                                        
                                            <p class="nhsuk-u-font-size-16 autosuggestion-link">${item.displayTitle}</p>
                                            ${subText ? `<p class="nhsuk-u-font-size-14 autosuggestion-subtext">Type: <span style="font-weight:bold;">${subText}</span></p>` : ''}
                                        </a>
                                    </li>
                                `;
                                /* eslint-enable max-len */
                                suggestionsList.append(listItem);
                            });

                            suggestionsList.show(); // Show the list after adding items
                            liveRegion.text(allSuggestions.length + " suggestions found. Use up and down arrows to navigate.");

                        } else {
                            suggestionsList.empty().hide(); // Hide if no suggestions
                            liveRegion.text("No suggestions found.");
                        }
                    }
                });
            });

            // 2. ARROW NAVIGATION: From Input to List
            searchInput.on('keydown', function (e) {
                const items = suggestionsList.find('a');
                if (items.length > 0 && e.key === 'ArrowDown') {
                    e.preventDefault();
                    items.first().focus();
                }
            });

            // 3. ARROW NAVIGATION: Within the List
            suggestionsList.on('keydown', 'a', function (e) {
                const items = suggestionsList.find('a');
                const index = items.index(this);

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    // Explicit if/else to satisfy ESLint
                    if (index + 1 < items.length) {
                        items.eq(index + 1).focus();
                    } else {
                        items.first().focus();
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    // Explicit if/else to satisfy ESLint
                    if (index > 0) {
                        items.eq(index - 1).focus();
                    } else {
                        searchInput.focus();
                    }
                } else if (e.key === 'Escape') {
                    suggestionsList.empty().hide();
                    searchInput.focus();
                }
            });

            // Updated Focus/Blur logic for Accessibility
            $(document).on('focusin click', function (e) {
                // If the click or focus is NOT on the input AND NOT on a suggestion
                if (!searchInput.is(e.target) && !suggestionsList.has(e.target).length) {
                    suggestionsList.empty().hide();
                }
            });

            // Added focus event to potentially re-show suggestions if query is still valid
            searchInput.on('focus', function () {
                const query = $(this).val().trim();
                if (query.length >= 2 && suggestionsList.children().length > 0) {
                    suggestionsList.show();
                }
            });
        }
    };
});