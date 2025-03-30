# Shortcode Reader

A WordPress plugin that allows you to search for shortcodes across your website content and find all pages where they are used.

## Description

Shortcode Reader helps you identify which pages, posts, or custom post types contain specific shortcodes. This is especially useful for site administrators and content managers who need to track where certain shortcodes are being used.

### Features

- Search for complete shortcodes including parameters (e.g., `[gallery id=123]`)
- Filter search results by post type
- Displays post title, type, and links to view or edit the content
- Uses AJAX for smooth, responsive searches without page reloads
- Secure implementation with proper validation and sanitization

## Installation

1. Upload the `shortcode-reader` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Tools > Shortcode Reader to use the plugin

## Usage

1. Navigate to Tools > Shortcode Reader in your WordPress admin dashboard
2. Enter the complete shortcode you want to search for (e.g., `[gallery id=123]`)
3. Optionally select which post types to include in the search
4. Click the "Search" button
5. View the results showing all pages where the shortcode is used

## Security Features

- Access restricted to administrators only (users with the `manage_options` capability)
- Input sanitization for all form fields
- Prepared SQL statements to prevent SQL injection
- Nonce verification to prevent CSRF attacks
- XSS protection through proper escaping of all output
- Error logging for troubleshooting without exposing sensitive information

## Troubleshooting

### Plugin Causes Site to Load Endlessly

If you experience the site loading endlessly after activating the plugin:

1. Deactivate the plugin via FTP/SFTP 
2. Access your WordPress admin area and make sure the plugin is deactivated
3. Re-upload the plugin files
4. Reactivate the plugin

Note: This plugin is designed to work only in the WordPress admin area and should not affect your frontend pages.

### No Results Found

If you don't see any results when searching for a shortcode:

1. Verify that you're entering the exact shortcode format including all parameters
2. Make sure the post types containing the shortcode are selected in the filter
3. Check if your shortcodes are using different formatting or spacing than what you're searching for

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher

## Author

Robby Abbas

## Version

0.1.0

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### 0.1.0
- Initial release