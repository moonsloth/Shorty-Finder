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
        const $adminNonce = $('#shortcode_reader_admin_nonce');

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
                showError('Please enter a shortcode to search for.');
                return;
            }

            // Get selected post types
            const selectedPostTypes = [];
            $postTypeCheckboxes.filter(':checked').each(function() {
                selectedPostTypes.push($(this).val());
            });

            // Check if at least one post type is selected
            if (selectedPostTypes.length === 0) {
                showError('Please select at least one post type to search in.');
                return;
            }

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
                    nonce: shortcodeReader.nonce,
                    admin_nonce: $adminNonce.val() // Include the admin page nonce for additional security
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.found) {
                            displayResults(response.data.results);
                        } else {
                            showWarning(response.data.message);
                        }
                    } else {
                        showError(response.data.message || 'An error occurred while processing your request.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    showError('Error connecting to the server. Please try again.');
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
            output += '<p>' + escapeHtml(results.length) + ' ' + (results.length === 1 ? 'result' : 'results') + ' found:</p>';
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
                output += '<td><strong>' + escapeHtml(result.title) + '</strong></td>';
                output += '<td>' + escapeHtml(result.post_type) + '</td>';
                output += '<td class="shortcode-reader-actions">';
                output += '<a href="' + escapeHtml(result.url) + '" target="_blank" class="button button-small">View</a> ';
                output += '<a href="' + escapeHtml(result.edit_url) + '" target="_blank" class="button button-small">Edit</a>';
                output += '</td>';
                output += '</tr>';
            });
            
            output += '</tbody></table>';
            
            $searchResults.html(output);
        }

        /**
         * Show error message
         * 
         * @param {string} message The error message
         */
        function showError(message) {
            $searchResults.html('<div class="notice notice-error"><p>' + escapeHtml(message) + '</p></div>');
        }

        /**
         * Show warning message
         * 
         * @param {string} message The warning message
         */
        function showWarning(message) {
            $searchResults.html('<div class="notice notice-warning"><p>' + escapeHtml(message) + '</p></div>');
        }

        /**
         * Escape HTML to prevent XSS attacks
         * 
         * @param {string} unsafe The unsafe string
         * @return {string} The escaped string
         */
        function escapeHtml(unsafe) {
            if (typeof unsafe !== 'string') {
                return unsafe;
            }
            
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
    });

})(jQuery); 