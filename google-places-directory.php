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

// Include core files
require_once GPD_PLUGIN_DIR . 'includes/class-gpd-cpt.php';
require_once GPD_PLUGIN_DIR . 'includes/class-gpd-settings.php';
require_once GPD_PLUGIN_DIR . 'includes/class-gpd-importer.php';
require_once GPD_PLUGIN_DIR . 'includes/class-gpd-admin-ui.php';
require_once GPD_PLUGIN_DIR . 'includes/class-gpd-shortcodes.php';
require_once GPD_PLUGIN_DIR . 'includes/class-gpd-shortcodes.php';
require_once GPD_PLUGIN_DIR . 'includes/class-gpd-docs.php';

// Initialize the plugin
function gpd_init() {
    // Register post types and taxonomies
    GPD_CPT::instance();
    
    // Load settings
    GPD_Settings::instance();
    
    // Load importer
    GPD_Importer::instance();
    
    // Load admin UI for import page
    GPD_Admin_UI::instance();
    
    // Load shortcodes
    GPD_Shortcodes::instance();
    
    // Load documentation pages
    GPD_Docs::instance();
    
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
