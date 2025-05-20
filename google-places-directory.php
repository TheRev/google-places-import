// Add to the includes section
require_once GPD_PLUGIN_DIR . 'includes/class-gpd-shortcodes.php';
require_once GPD_PLUGIN_DIR . 'includes/class-gpd-docs.php';

// Add to the initialization function
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
