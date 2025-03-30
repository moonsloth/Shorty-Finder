/**
 * Shortcode Reader JavaScript
 *
 * Handles the AJAX search functionality
 */
(function($) {
    'use strict';

    // On document ready
    $(document).ready(function() {
        const $searchForm = $('.shortcode-reader-search-form');
        const $searchInput = $('#shortcode-search');
        const $searchButton = $('#shortcode-search-button');
        const $searchResults = $('#shortcode-search-results');
        const $selectAllCheckbox = $('.select-all-post-types');
        const $postTypeCheckboxes = $('.post-type-checkbox');

        // Handle select all post types checkbox
        $selectAllCheckbox.on('change', function() {
            const isChecked = $(this).prop('checked');
            $postTypeCheckboxes.prop('checked', isChecked);
        });

        // Update select all checkbox based on individual checkboxes
        $postTypeCheckboxes.on('change', function() {
            const allChecked = $postTypeCheckboxes.length === $postTypeCheckboxes.filter(':checked').length;
            $selectAllCheckbox.prop('checked', allChecked);
        });

        // Handle the search button click
        $searchButton.on('click', function() {
            performSearch();
        });

        // Also trigger search on Enter key in the search input
        $searchInput.on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                performSearch();
            }
        });

        /**
         * Perform the AJAX search
         */
        function performSearch() {
            const shortcodeQuery = $searchInput.val().trim();
            
            // Validate input
            if (!shortcodeQuery) {
                $searchResults.html('<div class="notice notice-error"><p>' + 'Please enter a shortcode to search for.' + '</p></div>');
                return;
            }

            // Get selected post types
            const selectedPostTypes = [];
            $postTypeCheckboxes.filter(':checked').each(function() {
                selectedPostTypes.push($(this).val());
            });

            // Show loading indicator
            $searchResults.html('<div class="shortcode-reader-loading">' + shortcodeReader.loading + '</div>');
            
            // Make the AJAX request
            $.ajax({
                url: shortcodeReader.ajax_url,
                type: 'POST',
                data: {
                    action: 'shortcode_search',
                    shortcode: shortcodeQuery,
                    post_types: selectedPostTypes,
                    nonce: shortcodeReader.nonce
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.found) {
                            displayResults(response.data.results);
                        } else {
                            $searchResults.html('<div class="notice notice-warning"><p>' + response.data.message + '</p></div>');
                        }
                    } else {
                        $searchResults.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                    }
                },
                error: function() {
                    $searchResults.html('<div class="notice notice-error"><p>Error connecting to the server. Please try again.</p></div>');
                }
            });
        }

        /**
         * Display the search results
         *
         * @param {Array} results The search results
         */
        function displayResults(results) {
            let output = '<div class="shortcode-reader-results-count">';
            output += '<p>' + results.length + ' ' + (results.length === 1 ? 'result' : 'results') + ' found:</p>';
            output += '</div>';
            
            output += '<table class="wp-list-table widefat fixed striped">';
            output += '<thead><tr>';
            output += '<th>Title</th>';
            output += '<th>Post Type</th>';
            output += '<th>Actions</th>';
            output += '</tr></thead>';
            
            output += '<tbody>';
            
            results.forEach(function(result) {
                output += '<tr>';
                output += '<td><strong>' + result.title + '</strong></td>';
                output += '<td>' + result.post_type + '</td>';
                output += '<td class="shortcode-reader-actions">';
                output += '<a href="' + result.url + '" target="_blank" class="button button-small">View</a> ';
                output += '<a href="' + result.edit_url + '" target="_blank" class="button button-small">Edit</a>';
                output += '</td>';
                output += '</tr>';
            });
            
            output += '</tbody></table>';
            
            $searchResults.html(output);
        }
    });

})(jQuery); 