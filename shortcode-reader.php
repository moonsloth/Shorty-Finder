<?php
/**
 * Plugin Name: Find Shorty
 * Plugin URI: 
 * Description: Search for WordPress shortcodes across all content and display pages where they are used.
 * Version: 0.1.0
 * Author: Robby Abbas
 * Author URI: rabeah.me
 * License: GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main Shortcode Reader Plugin Class
 * 
 * This class is instantiated only in the admin area to prevent
 * frontend issues.
 */
class Shortcode_Reader_Plugin {
    
    /**
     * Plugin instance
     *
     * @var Shortcode_Reader_Plugin
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     *
     * @return Shortcode_Reader_Plugin
     */
    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Only load in admin
        if ( ! is_admin() ) {
            return;
        }
        
        // Define plugin constants
        $this->define_constants();
        
        // Include required files
        $this->include_files();
        
        // Initialize plugin
        add_action( 'plugins_loaded', array( $this, 'init' ) );
        
        // Register activation and deactivation hooks
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
    }
    
    /**
     * Define plugin constants
     */
    private function define_constants() {
        define( 'SHORTCODE_READER_VERSION', '0.1.0' );
        define( 'SHORTCODE_READER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
        define( 'SHORTCODE_READER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
    }
    
    /**
     * Include required files
     */
    private function include_files() {
        require_once SHORTCODE_READER_PLUGIN_DIR . 'includes/class-shortcode-reader.php';
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Make sure we're in the admin area
        if ( ! is_admin() ) {
            return;
        }
        
        // Initialize the main class
        $shortcode_reader = new Shortcode_Reader();
        $shortcode_reader->init();
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Activation code
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Deactivation code
        flush_rewrite_rules();
    }
}

// Initialize the plugin
function shortcode_reader_load() {
    // Only load in admin to avoid frontend issues
    if ( is_admin() ) {
        Shortcode_Reader_Plugin::get_instance();
    }
}
add_action( 'plugins_loaded', 'shortcode_reader_load', 5 );