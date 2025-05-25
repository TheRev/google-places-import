<?php
/**
 * Plugin Name: Google Places Directory
 * Description: Import businesses from Google Places API (New) into your WordPress site
 * Version: 2.3.0
 * Author: TheRev
 * Text Domain: google-places-directory
 * Domain Path: /languages
 * 
 * Updated for Google Places API v1 in May 2025
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'GPD_VERSION', '2.3.0' );
define( 'GPD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GPD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load a class file from the includes directory
 */
function gpd_load_class($class_name) {
    $file = GPD_PLUGIN_DIR . 'includes/class-' . strtolower(str_replace('_', '-', $class_name)) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
}

/**
 * Initialize the plugin's text domain for translations.
 */
function gpd_load_textdomain() {
    load_plugin_textdomain( 'google-places-directory', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

/**
 * Initialize core plugin components after translations are loaded.
 */
function gpd_init() {
    // Load core classes first
    gpd_load_class('GPD_CPT');
    gpd_load_class('GPD_Settings');
    gpd_load_class('GPD_Importer');
    gpd_load_class('GPD_Admin_UI');
    gpd_load_class('GPD_Shortcodes');
    gpd_load_class('GPD_Docs');
    gpd_load_class('GPD_Photo_Shortcodes');
    
    // Load photo manager only if extension plugin is not active
    if ( ! function_exists('gpdpm_is_active') || ! gpdpm_is_active() ) {
        gpd_load_class('GPD_Photo_Manager');
    }

    // Get CPT instance and initialize it
    $cpt = GPD_CPT::instance();
    $cpt->init();
    
    // Then initialize other plugin components
    GPD_Settings::instance();
    GPD_Importer::instance();
    GPD_Admin_UI::instance();
    GPD_Shortcodes::instance();
    
    // Load photo manager only if extension plugin is not active
    if ( ! function_exists('gpdpm_is_active') || ! gpdpm_is_active() ) {
        GPD_Photo_Manager::instance();
    }

    // Check if we need to flush rewrite rules
    if (get_option('gpd_flush_rewrite_rules')) {
        flush_rewrite_rules();
        delete_option('gpd_flush_rewrite_rules');
    }
}

// Load translations at init with priority 0 to ensure they're loaded before any plugin code
add_action('init', 'gpd_load_textdomain', 0);

// Initialize plugin components after translations are loaded
add_action('init', 'gpd_init', 15);

// Plugin activation hook
register_activation_hook( __FILE__, 'gpd_activate' );

// Plugin activation function
function gpd_activate() {
    // Just flag that we need to flush rewrite rules
    update_option('gpd_flush_rewrite_rules', true);
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
