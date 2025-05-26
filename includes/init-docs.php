<?php
/**
 * Initialize the documentation system
 */

// Don't allow direct access
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('gpd_init_docs')) {
    function gpd_init_docs() {
        return GPD_Docs::instance();
    }
}

// Initialize the docs
add_action('plugins_loaded', 'gpd_init_docs');
