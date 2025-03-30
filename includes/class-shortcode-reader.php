<?php
/**
 * Main Shortcode Reader class
 *
 * @package Shortcode_Reader
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcode Reader Main Class
 */
class Shortcode_Reader {

    /**
     * Initialize the plugin
     *
     * @return void
     */
    public function init() {
        // Only initialize in admin
        if ( ! is_admin() ) {
            return;
        }
        
        // Add admin menu
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        
        // Register scripts and styles
        add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ) );
        
        // Register AJAX handler
        add_action( 'wp_ajax_shortcode_search', array( $this, 'ajax_shortcode_search' ) );
    }

    /**
     * Add admin menu under Tools
     *
     * @return void
     */
    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            __( 'Shortcode Reader', 'shortcode-reader' ),
            __( 'Shortcode Reader', 'shortcode-reader' ),
            'manage_options',
            'shortcode-reader',
            array( $this, 'render_admin_page' )
        );
    }

    /**
     * Register scripts and styles
     *
     * @param string $hook The current admin page.
     * @return void
     */
    public function register_scripts( $hook ) {
        if ( 'tools_page_shortcode-reader' !== $hook ) {
            return;
        }

        // Register and enqueue CSS
        wp_register_style(
            'shortcode-reader-css',
            SHORTCODE_READER_PLUGIN_URL . 'assets/css/shortcode-reader.css',
            array(),
            SHORTCODE_READER_VERSION
        );
        wp_enqueue_style( 'shortcode-reader-css' );

        // Register and enqueue JS
        wp_register_script(
            'shortcode-reader-js',
            SHORTCODE_READER_PLUGIN_URL . 'assets/js/shortcode-reader.js',
            array( 'jquery' ),
            SHORTCODE_READER_VERSION,
            true
        );
        
        // Add the ajax url to the script
        wp_localize_script(
            'shortcode-reader-js',
            'shortcodeReader',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'shortcode_reader_nonce' ),
                'loading'  => __( 'Searching...', 'shortcode-reader' ),
            )
        );
        
        wp_enqueue_script( 'shortcode-reader-js' );
    }

    /**
     * Render the admin page HTML
     *
     * @return void
     */
    public function render_admin_page() {
        // Get all available post types for filtering
        $post_types = get_post_types( array(
            'public' => true,
        ), 'objects' );
        
        // Remove post types that don't typically contain shortcodes
        if ( isset( $post_types['attachment'] ) ) {
            unset( $post_types['attachment'] );
        }
        
        ?>
        <div class="wrap shortcode-reader-wrap">
            <h1><?php esc_html_e( 'Shortcode Reader', 'shortcode-reader' ); ?></h1>
            
            <p><?php esc_html_e( 'Search for WordPress shortcodes in your content. Enter the complete shortcode including parameters (e.g. [gallery id=123]).', 'shortcode-reader' ); ?></p>
            
            <div class="shortcode-reader-search-container">
                <div class="shortcode-reader-search-form">
                    <label for="shortcode-search" class="screen-reader-text"><?php esc_html_e( 'Enter shortcode to search for:', 'shortcode-reader' ); ?></label>
                    <input type="text" id="shortcode-search" name="shortcode-search" placeholder="<?php esc_attr_e( 'e.g. [gallery id=123]', 'shortcode-reader' ); ?>" class="regular-text">
                    
                    <div class="shortcode-reader-filters">
                        <h3><?php esc_html_e( 'Filter by Post Type', 'shortcode-reader' ); ?></h3>
                        <div class="shortcode-reader-post-types">
                            <label><input type="checkbox" class="select-all-post-types" checked> <?php esc_html_e( 'All Post Types', 'shortcode-reader' ); ?></label>
                            <?php foreach ( $post_types as $post_type ) : ?>
                                <label>
                                    <input type="checkbox" name="post_types[]" value="<?php echo esc_attr( $post_type->name ); ?>" class="post-type-checkbox" checked>
                                    <?php echo esc_html( $post_type->label ); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <button type="button" id="shortcode-search-button" class="button button-primary"><?php esc_html_e( 'Search', 'shortcode-reader' ); ?></button>
                </div>
                
                <div id="shortcode-search-results" class="shortcode-reader-results">
                    <!-- Results will be loaded here via AJAX -->
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Handle AJAX search request
     *
     * @return void
     */
    public function ajax_shortcode_search() {
        try {
            // Get database connection
            global $wpdb;
            
            // Verify nonce
            if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'shortcode_reader_nonce' ) ) {
                wp_send_json_error( array( 'message' => __( 'Security check failed.', 'shortcode-reader' ) ) );
            }
            
            // Sanitize input
            $shortcode = isset( $_POST['shortcode'] ) ? sanitize_text_field( wp_unslash( $_POST['shortcode'] ) ) : '';
            
            // Get selected post types
            $selected_post_types = array();
            if ( isset( $_POST['post_types'] ) && is_array( $_POST['post_types'] ) ) {
                foreach ( $_POST['post_types'] as $post_type ) {
                    $selected_post_types[] = sanitize_key( $post_type );
                }
            }
            
            if ( empty( $shortcode ) ) {
                wp_send_json_error( array( 'message' => __( 'Please enter a shortcode to search for.', 'shortcode-reader' ) ) );
            }
            
            // Escape the shortcode for use in the query
            $escaped_shortcode = '%' . $wpdb->esc_like( $shortcode ) . '%';
            
            // Default to all public post types if none selected
            if ( empty( $selected_post_types ) ) {
                $selected_post_types = array_keys( get_post_types( array( 'public' => true ) ) );
                // Remove attachment post type
                $attachment_key = array_search( 'attachment', $selected_post_types, true );
                if ( false !== $attachment_key ) {
                    unset( $selected_post_types[ $attachment_key ] );
                }
            }
            
            // Prepare post types for the query
            $post_types_string = "'" . implode( "','", array_map( 'esc_sql', $selected_post_types ) ) . "'";
            
            // Search for the shortcode in post content
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT ID, post_title, post_type 
                     FROM {$wpdb->posts} 
                     WHERE post_status = 'publish' 
                     AND post_type IN ({$post_types_string}) 
                     AND post_content LIKE %s",
                    $escaped_shortcode
                )
            );
            
            // Check if any results were found
            if ( empty( $results ) ) {
                wp_send_json_success( array(
                    'found' => false,
                    'message' => __( 'No content found containing this shortcode.', 'shortcode-reader' )
                ) );
            }
            
            // Format the results
            $formatted_results = array();
            
            foreach ( $results as $post ) {
                $post_type_obj = get_post_type_object( $post->post_type );
                
                $formatted_results[] = array(
                    'id'        => $post->ID,
                    'title'     => $post->post_title,
                    'url'       => get_permalink( $post->ID ),
                    'post_type' => $post_type_obj ? $post_type_obj->labels->singular_name : $post->post_type,
                    'edit_url'  => get_edit_post_link( $post->ID ),
                );
            }
            
            wp_send_json_success( array(
                'found'   => true,
                'results' => $formatted_results,
            ) );
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => 'Error: ' . $e->getMessage() ) );
        }
    }
} 