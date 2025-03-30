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
        
        // Check minimum requirements
        if ( ! $this->check_requirements() ) {
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
     * Check if the plugin requirements are met
     *
     * @return bool True if requirements are met, false otherwise
     */
    private function check_requirements() {
        // Check WordPress version
        if ( version_compare( get_bloginfo( 'version' ), '5.0', '<' ) ) {
            add_action( 'admin_notices', array( $this, 'wordpress_version_notice' ) );
            return false;
        }
        
        // Check PHP version
        if ( version_compare( PHP_VERSION, '7.0', '<' ) ) {
            add_action( 'admin_notices', array( $this, 'php_version_notice' ) );
            return false;
        }
        
        return true;
    }
    
    /**
     * WordPress version notice
     */
    public function wordpress_version_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e( 'Shortcode Reader requires WordPress version 5.0 or higher.', 'shortcode-reader' ); ?></p>
        </div>
        <?php
    }
    
    /**
     * PHP version notice
     */
    public function php_version_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e( 'Shortcode Reader requires PHP version 7.0 or higher.', 'shortcode-reader' ); ?></p>
        </div>
        <?php
    }
    
    /**
     * Define plugin constants
     */
    private function define_constants() {
        define( 'SHORTCODE_READER_VERSION', '0.1.0' );
        define( 'SHORTCODE_READER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
        define( 'SHORTCODE_READER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
        define( 'SHORTCODE_READER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
    }
    
    /**
     * Include required files
     */
    private function include_files() {
        // Security check for file inclusion
        $class_file = SHORTCODE_READER_PLUGIN_DIR . 'includes/class-shortcode-reader.php';
        if ( file_exists( $class_file ) ) {
            require_once $class_file;
        } else {
            add_action( 'admin_notices', array( $this, 'missing_files_notice' ) );
            return;
        }
    }
    
    /**
     * Missing files notice
     */
    public function missing_files_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e( 'Shortcode Reader is missing required files. Please reinstall the plugin.', 'shortcode-reader' ); ?></p>
        </div>
        <?php
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Make sure we're in the admin area
        if ( ! is_admin() ) {
            return;
        }
        
        // Verify the main class exists 
        if ( ! class_exists( 'Shortcode_Reader' ) ) {
            add_action( 'admin_notices', array( $this, 'missing_files_notice' ) );
            return;
        }
        
        // Initialize the main class
        $shortcode_reader = new Shortcode_Reader();
        $shortcode_reader->init();
        
        // Add settings link to plugins page
        add_filter( 'plugin_action_links_' . SHORTCODE_READER_PLUGIN_BASENAME, array( $this, 'add_plugin_links' ) );
    }
    
    /**
     * Add plugin action links
     *
     * @param array $links Existing links
     * @return array Modified links
     */
    public function add_plugin_links( $links ) {
        $plugin_links = array(
            '<a href="' . admin_url( 'tools.php?page=shortcode-reader' ) . '">' . __( 'Settings', 'shortcode-reader' ) . '</a>',
        );
        return array_merge( $plugin_links, $links );
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Check requirements before activation
        if ( ! $this->check_requirements() ) {
            // Deactivate the plugin if requirements are not met
            deactivate_plugins( SHORTCODE_READER_PLUGIN_BASENAME );
            wp_die( 
                __( 'Shortcode Reader requires WordPress 5.0 and PHP 7.0 or higher.', 'shortcode-reader' ),
                __( 'Plugin Activation Error', 'shortcode-reader' ),
                array( 'back_link' => true )
            );
        }
        
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