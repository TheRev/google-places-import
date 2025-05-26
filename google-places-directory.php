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
 * 
 * @param string $class_name The name of the class to load
 * @return bool True if the class file was loaded successfully, false otherwise
 */
function gpd_load_class($class_name) {
    // Normalize directory separators for cross-platform compatibility
    $includes_dir = rtrim(GPD_PLUGIN_DIR, '/\\') . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;
    $file = $includes_dir . 'class-' . strtolower(str_replace('_', '-', $class_name)) . '.php';
    
    // Also load any initialization file if it exists
    $init_file = $includes_dir . 'init-' . strtolower(str_replace('_', '-', $class_name)) . '.php';
    
    // Log the attempted file path and whether it exists for debugging
    $debug_info = array(
        'Plugin Dir' => GPD_PLUGIN_DIR,
        'Includes Dir' => $includes_dir,
        'Full Path' => $file,
        'File Exists' => file_exists($file) ? 'Yes' : 'No',
        'Is Readable' => is_readable($file) ? 'Yes' : 'No'
    );
    error_log('Google Places Directory Debug - Loading class ' . $class_name . ': ' . print_r($debug_info, true));    $class_loaded = false;
    
    // Load the class file
    if (file_exists($file) && is_readable($file)) {
        require_once $file;
        if (class_exists($class_name)) {
            $class_loaded = true;
        } else {
            error_log("Google Places Directory: Class {$class_name} not found in file: {$file}");
        }
    } else {
        error_log("Google Places Directory: Class file not found or not readable: {$file}");
    }

    // Load the initialization file if it exists
    if (file_exists($init_file) && is_readable($init_file)) {
        require_once $init_file;
    }
    
    return $class_loaded;
}

/**
 * Initialize core plugin components after translations are loaded.
 */
function gpd_init() {    // Load and initialize CPT first as it sets up core functionality
    gpd_load_class('GPD_CPT');
    if (class_exists('GPD_CPT')) {
        GPD_CPT::instance();
    }

    // Load API Usage tracking and Settings next
    $core_classes = array(
        'GPD_API_Usage',
        'GPD_Settings'
    );

    foreach ($core_classes as $class) {
        gpd_load_class($class);
        if (class_exists($class)) {
            $class::instance();
        }
    }    // Load remaining core classes
    $additional_classes = array(
        'GPD_Importer',
        'GPD_Admin_UI',
        'GPD_Shortcodes',
        'GPD_Docs',
        'GPD_Photo_Shortcodes',
        'GPD_Photo_Manager'
    );

    foreach ($additional_classes as $class) {
        gpd_load_class($class);
        if (class_exists($class)) {
            $class::instance();
        }
    }

    // Enqueue the usage graph JavaScript if we're on the settings page
    add_action('admin_enqueue_scripts', function($hook) {
        if ('business_page_gpd-settings' === $hook) {
            wp_enqueue_script('gpd-usage-graph', 
                GPD_PLUGIN_URL . 'assets/js/gpd-usage-graph.js',
                array('jquery', 'wp-element'),
                GPD_VERSION,
                true
            );
        }
    });
}

// Load translations - use lowest priority to ensure it loads first thing during init
function gpd_load_textdomain() {
    // Load main plugin textdomain
    load_plugin_textdomain('google-places-directory', false, dirname(plugin_basename(__FILE__)) . '/languages');
    
    // Load any addon textdomains used in docs
    load_plugin_textdomain('gpd-advanced-features', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('init', 'gpd_load_textdomain', 1); // Lowest priority to ensure it runs first

// Initialize the plugin - ensure we load classes AFTER translations are loaded
add_action('init', 'gpd_init', 5); // Higher priority than textdomain loading (init at priority 1) but lower than default (10)

// Check if we just activated the plugin and need to do additional setup
add_action('init', 'gpd_check_activation_tasks', 30);

/**
 * Check for any post-activation tasks that need to be run after translations are loaded
 */
function gpd_check_activation_tasks() {
    // Check if we just activated and need to complete initialization
    if (get_option('gpd_just_activated')) {
        // Delete the flag
        delete_option('gpd_just_activated');
        
        // Initialize API Usage now that translations are properly loaded
        gpd_load_class('GPD_API_Usage');
        if (class_exists('GPD_API_Usage')) {
            GPD_API_Usage::instance()->install();
        }
    }
}

// Plugin activation hook
register_activation_hook( __FILE__, 'gpd_activate' );

// Plugin activation function
function gpd_activate() {
    // Just flag that we need to flush rewrite rules
    update_option('gpd_flush_rewrite_rules', true);
}

// Add activation hook for initial setup
register_activation_hook(__FILE__, 'gpd_activate_plugin');

function gpd_activate_plugin() {
    // Load translation domains during activation to prevent JIT loading errors
    $domain_path = dirname(plugin_basename(__FILE__)) . '/languages';
    load_plugin_textdomain('google-places-directory', false, $domain_path);
    load_plugin_textdomain('gpd-advanced-features', false, $domain_path);
    
    // Very simplified post type registration for activation
    $args = array();
    $args['public'] = true;
    $args['has_archive'] = true;
    $args['supports'] = array('title', 'editor', 'thumbnail', 'excerpt');
    $args['show_in_rest'] = true;
    
    // Register the post type
    register_post_type('business', $args);
    
    // Clear permalinks
    flush_rewrite_rules();
    
    // Store flag for initialization of other components on next page load
    update_option('gpd_just_activated', true);
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
