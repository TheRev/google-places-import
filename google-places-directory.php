<?php
/**
 * Plugin Name: Google Places Directory
 * Description: A directory plugin to search, import, and manage business listings via the Google Places API.
 * Version:     1.0.0
 * Author:      Your Name
 * Text Domain: google-places-directory
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define plugin paths
define( 'GPD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GPD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Require class files
require_once GPD_PLUGIN_DIR . 'includes/class-gpd-cpt.php';
require_once GPD_PLUGIN_DIR . 'includes/class-gpd-settings.php';
require_once GPD_PLUGIN_DIR . 'includes/class-gpd-importer.php';
require_once GPD_PLUGIN_DIR . 'includes/class-gpd-admin-ui.php';

/**
 * Initialize all plugin classes on plugins_loaded.
 */
function gpd_initialize_plugin() {
    // Register CPT and taxonomies
    GPD_CPT::instance();

    // Settings page
    GPD_Settings::instance();

    // Importer logic
    GPD_Importer::instance();

    // Admin UI
    GPD_Admin_UI::instance();
}
add_action( 'plugins_loaded', 'gpd_initialize_plugin' );
