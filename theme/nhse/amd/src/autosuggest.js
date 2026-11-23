define(['jquery'], function($) {
    return {
        init: function() {
            const searchInput = $('#search-field');
            // Ensure you have a <ul> element with this ID in your HTML,
            // typically right below the search input field.
            const suggestionsList = $('#search-field_listbox');

            // Create a hidden live region for screen readers to announce results
            const liveRegion = $('<div class="sr-only" aria-live="polite"></div>').appendTo('body');

            // Hide the list initially
            suggestionsList.empty().hide();

            searchInput.on('input', function() {
                const query = $(this).val().trim(); // Trim whitespace from the query

                // Only perform search if query length is 2 or more characters
                if (query.length < 2) {
                    suggestionsList.empty().hide(); // Clear and hide if query too short
                    liveRegion.text("");
                    return;
                }

                $.ajax({
                    url: M.cfg.wwwroot + '/theme/nhse/ajax/search_suggestions.php',
                    type: 'GET',
                    data: { query: query },
                    success: function(response) {

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
                                        type: 'Concepts', // Matches 'Concepts' from .NET GetUrl searchType
                                        payload: item._click.payload, // Pass the whole payload for tracking URL
                                    });
                                });
                            }

                            // Process Resource Documents
                            if (decodedResult.resources_documents && decodedResult.resources_documents.documents) {
                                decodedResult.resources_documents.documents.forEach(item => {
                                    allSuggestions.push({
                                        displayTitle: item.title,
                                        targetReferenceId: item.resource_reference_id, // Use ResourceReferenceId as per .NET GetUrl
                                        type: 'Resource', // Matches 'Resource' from .NET GetUrl searchType
                                        payload: item._click.payload
                                    });
                                });
                            }

                            // Process Catalogue Documents
                            if (decodedResult.catalogues_documents && decodedResult.catalogues_documents.documents) {
                                decodedResult.catalogues_documents.documents.forEach(item => {
                                    allSuggestions.push({
                                        displayTitle: item.name, // Use 'name' for catalogues
                                        targetReference: item.url, // Use item.Url as per .NET GetUrl
                                        type: 'Catalogues', // Matches 'Catalogues' from .NET GetUrl searchType
                                        payload: item._click.payload
                                    });
                                });
                            }
                        }
                        // Now, render the suggestion
                        if (allSuggestions.length > 0) {
                            suggestionsList.empty(); // Clear existing list items

                            allSuggestions.forEach(function(item) {
                                let actualTargetUrl = '#'; // This will be the '/Resource/ID' or '/Catalogue/name' part
                                let typeClass = '';
                                let subText = '';
                                let svgIconPath = '';
                                let svgWidth = '16'; // Default
                                let svgHeight = '12'; // Default

                                // Determine actualTargetUrl SVG path, and dimensions based on type, mimicking the .NET GetUrl logic
                                if (item.type === 'Resource') {
                                    if (item.targetReferenceId && item.targetReferenceId > 0) {
                                        actualTargetUrl = `/Resource/${item.targetReferenceId}`;
                                    } else {
                                        actualTargetUrl = `/Search/results?term=${encodeURIComponent(item.displayTitle)}`;
                                    }
                                    typeClass = 'autosugg-resource';
                                    subText = 'Resource';
                                    // eslint-disable-next-line max-len
                                    svgIconPath = 'M7.89365 11.4887C7.90726 11.4837 7.92086 11.4787 7.93508 11.4738C7.93508 11.4239 7.93508 11.3735 7.93508 11.3236C7.93508 8.61493 7.93199 5.90624 7.94003 3.19754C7.94064 2.90288 7.87263 2.64621 7.71621 2.40574C7.64263 2.29299 7.57091 2.17836 7.48744 2.0737C6.01592 0.238416 3.7023 -0.378952 1.5148 0.23094C1.47028 0.2434 1.41958 0.322518 1.41526 0.374225C1.40165 0.533706 1.40907 0.695679 1.40907 0.856407C1.4066 2.50044 1.40536 4.14447 1.40042 5.78787C1.39671 6.93975 1.38743 8.09163 1.37878 9.24351C1.37816 9.35814 1.38125 9.43103 1.53706 9.39676C2.4076 9.20613 3.28186 9.18433 4.15983 9.34942C5.54973 9.61107 6.73561 10.249 7.70631 11.2869C7.76876 11.3535 7.83121 11.4208 7.89365 11.4881V11.4887ZM8.06863 11.475C8.08718 11.4862 8.10573 11.4974 8.12427 11.5093C8.14159 11.4769 8.1521 11.4389 8.17621 11.4127C9.89195 9.56061 11.9978 8.93327 14.4462 9.39365C14.5823 9.41919 14.6157 9.39116 14.6144 9.25473C14.6064 8.38381 14.6045 7.51351 14.6014 6.64259C14.5996 6.11493 14.5983 5.58728 14.5971 5.05899C14.5946 4.19742 14.5915 3.33585 14.5891 2.47427C14.5872 1.789 14.5829 1.10435 14.5866 0.419079C14.5872 0.291369 14.5495 0.23094 14.4209 0.209759C14.223 0.177988 14.0289 0.123166 13.8311 0.0895252C12.5048 -0.135992 11.2454 0.0596224 10.0725 0.732435C9.29964 1.17537 8.64673 1.751 8.20094 2.54031C8.10511 2.70976 8.0575 2.88793 8.0575 3.0879C8.06121 5.83896 8.05997 8.58939 8.06059 11.3404C8.06059 11.3853 8.06554 11.4295 8.06801 11.4744L8.06863 11.475ZM0.012984 10.9673C2.58011 10.6072 5.08849 10.819 7.55669 11.5535C5.62084 9.96865 3.44076 9.39926 0.981219 9.97239C0.976273 9.90262 0.970709 9.85714 0.970709 9.81166C0.973182 8.20377 0.975037 6.59587 0.978746 4.98797C0.981219 3.65979 0.983074 2.33099 0.99173 1.00281C0.992349 0.857653 0.932375 0.797224 0.80439 0.800962C0.610248 0.806569 0.416106 0.824635 0.221965 0.839587C0.0513177 0.852669 0 0.940508 0 1.11619C0.00680114 3.0717 0.00494629 5.02722 0.00494629 6.98336C0.00494629 8.23928 0.00370971 9.49519 0.004328 10.7511C0.004328 10.8171 0.00989257 10.8826 0.0136023 10.9673H0.012984ZM15.9771 10.966C15.9815 10.8938 15.9876 10.8383 15.9883 10.7835C15.9932 9.39303 15.9994 8.00317 16 6.61269C16 5.82961 15.9901 5.04653 15.9889 4.26345C15.987 3.19443 15.9864 2.12541 15.9913 1.05638C15.9913 0.93241 15.9456 0.868243 15.8318 0.855161C15.6235 0.832111 15.4139 0.81093 15.2049 0.800339C15.0602 0.792863 15.0033 0.86762 15.0039 1.02212C15.0083 2.48549 15.0033 3.94823 15.0046 5.4116C15.0052 6.01214 15.0182 6.61331 15.0206 7.21386C15.0243 8.06983 15.0243 8.92642 15.025 9.78238C15.025 9.83783 15.0182 9.89328 15.0132 9.97488C13.8416 9.69953 12.6829 9.66838 11.5267 9.9537C10.3699 10.239 9.34601 10.7879 8.43713 11.5572C10.9047 10.8196 13.4112 10.6091 15.9771 10.9667V10.966Z';
                                    svgWidth = '16';
                                    svgHeight = '12';
                                } else if (item.type === 'Catalogues') {
                                    if (item.targetReference) {
                                        actualTargetUrl = `/Catalogue/${item.targetReference}`;
                                    } else {
                                        actualTargetUrl = `/Search/results?term=${encodeURIComponent(item.displayTitle)}`;
                                    }
                                    typeClass = 'autosugg-catalogue';
                                    subText = 'Catalogue';
                                    // eslint-disable-next-line max-len
                                    svgIconPath = 'M13.4986 1.62391C13.498 1.35936 13.4452 1.06975 13.3272 0.835751C13.0286 0.243103 12.5027 0.0164303 11.8428 0.0182632C8.84631 0.0255949 5.84982 0.0219291 2.85272 0.0213181C2.75073 0.0213181 2.64873 0.023151 2.54735 0.0127644C1.80514 -0.0623858 1.18766 0.19728 0.673397 0.712944C0.171423 1.217 -0.000612773 1.84325 1.639e-06 2.53977C0.00491694 6.56672 0.00184488 10.5937 0.00245929 14.62C0.00245929 14.7208 0.00368811 14.8229 0.0116755 14.9231C0.0645149 15.5566 0.516722 15.9941 1.15571 15.9953C4.34574 16.0008 7.53638 16.002 10.7264 15.9959C11.433 15.9947 11.9012 15.5322 11.9018 14.8332C11.9073 11.1099 11.9104 7.38665 11.8901 3.66335C11.8889 3.40796 11.7377 3.12081 11.5774 2.90635C11.3464 2.59659 10.9808 2.51899 10.5937 2.5196C7.8823 2.52449 5.17151 2.52083 2.46011 2.52388C2.10252 2.52388 1.77197 2.46156 1.49425 2.22084C1.11209 1.88908 1.11762 1.35691 1.509 1.03249C1.79654 0.794204 2.1357 0.709278 2.50312 0.709278C3.85851 0.709278 5.21452 0.709278 6.56991 0.709278C8.11946 0.709278 9.66839 0.705002 11.2179 0.711111C12.1445 0.714777 12.8345 1.30315 12.8357 2.09559C12.84 5.84576 12.8375 9.59655 12.8375 13.3376C13.2854 13.1604 13.5048 12.8133 13.5054 12.1853C13.5072 8.6642 13.5097 5.14314 13.4993 1.62269L13.4986 1.62391ZM4.60133 5.26595C4.60809 4.92013 4.766 4.7625 5.10945 4.76189C6.48328 4.75822 7.8571 4.75822 9.23032 4.76189C9.60326 4.76311 9.76854 4.93235 9.771 5.29955C9.77468 5.77428 9.77653 6.24901 9.77038 6.72374C9.76547 7.08727 9.59221 7.2614 9.22479 7.26506C8.5434 7.27117 7.8614 7.2669 7.17941 7.2669C6.50785 7.2669 5.8363 7.26873 5.16475 7.26567C4.76538 7.26384 4.60625 7.11415 4.59949 6.72068C4.5915 6.23618 4.59212 5.75106 4.60072 5.26656L4.60133 5.26595Z';
                                    svgWidth = '14';
                                    svgHeight = '16';
                                } else if (item.type === 'Concepts') {
                                    actualTargetUrl = `/Search/results?term=${encodeURIComponent(item.originalTermForUrl)}`;
                                    typeClass = 'autosugg-concepts';
                                    subText = ''; // .NET code does not show subtext for concepts
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
                                const dynamicSvgIcon = `                                                                  
                                    <svg class="nhsuk-icon autosuggestion-icon" width="${svgWidth}" height="${svgHeight}" viewBox="0 0 ${svgWidth} ${svgHeight}" xmlns="http://www.w3.org/2000/svg">
                                        <path d="${svgIconPath}" />
                                    </svg>
                                `;
                                /* eslint-enable max-len */
                                /* eslint-disable max-len */
                                const listItem = `
                                    <li class="autosuggestion-option ${typeClass}">
                                        <a tabindex="0" style="text-decoration:none !important" href="${trackingHref}">
                                            ${dynamicSvgIcon}                                        
                                            <p class="nhsuk-u-font-size-16 autosuggestion-link">${item.displayTitle}</p>
                                            ${subText ? `<p class="nhsuk-u-font-size-14 autosuggestion-subtext">${subText}</p>` : ''}
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

            // Added blur event to hide suggestions when input loses focus
            /*
            searchInput.on('blur', function() {
                // Use a small timeout to allow click events on suggestions to register
                setTimeout(function() {
                    suggestionsList.empty().hide();
                }, 200);
            });
            */

            // 2. ARROW NAVIGATION: From Input to List
            searchInput.on('keydown', function(e) {
                const items = suggestionsList.find('a');
                if (items.length > 0 && e.key === 'ArrowDown') {
                    e.preventDefault();
                    items.first().focus();
                }
            });

            // 3. ARROW NAVIGATION: Within the List
            suggestionsList.on('keydown', 'a', function(e) {
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
            $(document).on('focusin click', function(e) {
                // If the click or focus is NOT on the input AND NOT on a suggestion
                if (!searchInput.is(e.target) && !suggestionsList.has(e.target).length) {
                    suggestionsList.empty().hide();
                }
            });

            // Added focus event to potentially re-show suggestions if query is still valid
            searchInput.on('focus', function() {
                const query = $(this).val().trim();
                if (query.length >= 2 && suggestionsList.children().length > 0) {
                    suggestionsList.show();
                }
            });
        }
    };
});