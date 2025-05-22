<?php
/**
 * Plugin Name: Google Places Directory
 * Description: Import businesses from Google Places API (New) into your WordPress site
 * Version: 2.6.0
 * Author: TheRev
 * Text Domain: google-places-directory
 * Domain Path: /languages
 * 
 * Updated for Google Places API v1 in May 2025
 * Documentation reorganized and enhanced in May 2025
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'GPD_VERSION', '2.6.0' );
define( 'GPD_DOCS_VERSION', '2.6.0' );
define( 'GPD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GPD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include core files
require_once GPD_PLUGIN_DIR . 'includes/class-gpd-cpt.php';
require_once GPD_PLUGIN_DIR . 'includes/class-gpd-settings.php';
require_once GPD_PLUGIN_DIR . 'includes/class-gpd-importer.php';
require_once GPD_PLUGIN_DIR . 'includes/class-gpd-admin-ui.php';
require_once GPD_PLUGIN_DIR . 'includes/class-gpd-shortcodes.php';
require_once GPD_PLUGIN_DIR . 'includes/class-gpd-docs.php';
require_once GPD_PLUGIN_DIR . 'includes/class-gpd-photo-shortcodes.php';
require_once GPD_PLUGIN_DIR . 'includes/class-gpd-import-export.php';

// Load documentation files
require_once GPD_PLUGIN_DIR . 'includes/docs/shortcodes.php';
require_once GPD_PLUGIN_DIR . 'includes/docs/custom-fields.php';
require_once GPD_PLUGIN_DIR . 'includes/docs/developer.php';

// Load SEO functionality
require_once GPD_PLUGIN_DIR . 'includes/class-gpd-seo.php';

// Only include photo manager if the extension plugin is not active
if ( ! function_exists('gpdpm_is_active') || ! gpdpm_is_active() ) {
    require_once GPD_PLUGIN_DIR . 'includes/class-gpd-photo-manager.php';
}

// Initialize the plugin
function gpd_init() {
    // Register post types and taxonomies
    GPD_CPT::instance();
    
    // Load settings
    GPD_Settings::instance();
      // Load importer, import/export, and SEO functionality
    GPD_Importer::instance();
    GPD_Import_Export::instance();
    GPD_SEO::instance();
    
    // Load admin UI for import page
    GPD_Admin_UI::instance();
    
    // Load shortcodes
    GPD_Shortcodes::instance();
    
    // Load photo manager only if extension plugin is not active
    if ( ! function_exists('gpdpm_is_active') || ! gpdpm_is_active() ) {
        GPD_Photo_Manager::instance();
    }
    
    // Load text domain
    load_plugin_textdomain( 'google-places-directory', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'gpd_init' );

// Plugin activation hook
register_activation_hook( __FILE__, 'gpd_activate' );

// Plugin activation function
function gpd_activate() {
    // Make sure post types are registered for flushing
    GPD_CPT::instance();
    
    // Flush rewrite rules
    flush_rewrite_rules();
}
/**
 * Enqueue frontend template styles
 */
function gpd_enqueue_template_styles() {
    // Only enqueue on business post type or when using the template
    if (is_singular('business') || (is_page() && get_page_template_slug() === 'template-business.php')) {
        wp_enqueue_style(
            'gpd-frontend-template',
            plugin_dir_url(__FILE__) . 'assets/css/frontend-template.css',
            array('gpd-frontend'), // Make it dependent on your main frontend CSS
            '1.0.0'
        );
    }
}
add_action('wp_enqueue_scripts', 'gpd_enqueue_template_styles');
