<?php
/**
 * Class GPD_Docs
 *
 * Provides documentation and help pages for the plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class GPD_Docs {
    private static $instance = null;
    private $tabs = array();
    private $registered_plugins = array();
    private $sections = array();

    public static function instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
            self::$instance->init_hooks();
        }
        return self::$instance;
    }

    private function init_hooks() {
        add_action( 'admin_menu', array( $this, 'add_docs_pages' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        
        // Register default tabs
        $this->register_default_tabs();
        
        // Allow add-ons to register their tabs (priority 20 gives add-ons time to initialize)
        add_action( 'init', array( $this, 'finalize_tabs' ), 20 );
    }

} // End GPD_Docs class

// Initialize the docs
GPD_Docs::instance();
