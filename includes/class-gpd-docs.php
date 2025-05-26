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
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->init_hooks();
        }
        return self::$instance;
    }    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_docs_pages'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        
        // Register default tabs on init to ensure translations are loaded first
        add_action('init', array($this, 'register_default_tabs'), 8);
        
        // Allow add-ons to register their tabs (priority 20 gives add-ons time to initialize)
        add_action('init', array($this, 'finalize_tabs'), 20);
    }
    
    /**
     * Register default documentation tabs
     */
    public function register_default_tabs() {
        $this->register_tab('general', __('Getting Started', 'google-places-directory'), array($this, 'render_general_docs'), 10);
        $this->register_tab('photos', __('Business Photos', 'google-places-directory'), array($this, 'render_photos_docs'), 20);
        $this->register_tab('settings', __('Settings', 'google-places-directory'), array($this, 'render_settings_docs'), 30);
        $this->register_tab('fse', __('Full Site Editing', 'google-places-directory'), array($this, 'render_fse_docs'), 40);
        $this->register_tab('api', __('API Information', 'google-places-directory'), array($this, 'render_api_docs'), 50);
    }
    
    /**
     * Register a documentation tab
     *
     * @param string $slug Tab slug/ID
     * @param string $title Tab display title
     * @param callable|null $callback Function to render tab content
     * @param int $priority Tab display order (lower = earlier)
     * @return bool Success
     */
    public function register_tab($slug, $title, $callback = null, $priority = 10) {
        if (empty($slug) || empty($title)) {
            return false;
        }
        
        $this->tabs[$slug] = array(
            'title' => $title,
            'callback' => $callback,
            'priority' => $priority
        );
        
        return true;
    }
    
    /**
     * Finalize tab registration and apply filters
     */
    public function finalize_tabs() {
        // Allow plugins to modify the tabs
        $this->tabs = apply_filters('gpd_docs_tabs', $this->tabs);
        
        // Sort tabs by priority
        uasort($this->tabs, function($a, $b) {
            return $a['priority'] - $b['priority']; 
        });
    }
    
    /**
     * Add documentation pages to the admin menu
     */
    public function add_docs_pages() {
        add_submenu_page(
            'edit.php?post_type=business',
            __('Documentation', 'google-places-directory'),
            __('Documentation', 'google-places-directory'),
            'manage_options',
            'gpd-docs',
            array($this, 'render_docs_page')
        );
    }
      /**
     * Enqueue styles for the documentation pages
     */
    public function enqueue_styles($hook) {
        if ('business_page_gpd-docs' === $hook) {
            // Enqueue the dedicated documentation stylesheet
            wp_enqueue_style(
                'gpd-documentation',
                plugin_dir_url(dirname(__FILE__)) . 'assets/css/gpd-documentation.css',
                array('gpd-admin'),
                GPD_VERSION
            );
            
            // Add any additional inline styles
            wp_add_inline_style('gpd-admin', '
                .gpd-docs-content {
                    max-width: 800px;
                    margin: 20px 0;
                }
                .gpd-docs table {
                    border-collapse: collapse;
                    margin: 1em 0;
                    background: white;
                    border: 1px solid #ccc;
                }
                .gpd-docs-intro {
                    margin-bottom: 20px;
                }
                .gpd-shortcode-example {
                    background: #f0f0f0;
                    padding: 10px;
                    margin: 10px 0;
                    font-family: monospace;
                    border-left: 3px solid #2271b1;
                }
                .gpd-docs h3 {
                    margin-top: 1.5em;
                }
            ');
            
            // Allow add-on plugins to add their own styles
            do_action('gpd_docs_enqueue_styles');
        }
    }
    
    /**
     * Render the documentation page
     */
    public function render_docs_page() {
        // Get tab keys for easier handling
        $tab_keys = array_keys($this->tabs);
        
        // Determine which tab to show
        $active_tab = isset($_GET['tab']) && array_key_exists($_GET['tab'], $this->tabs) 
            ? sanitize_key($_GET['tab']) 
            : reset($tab_keys); // First tab is default
        ?>
        <div class="wrap gpd-docs">
            <h1><?php esc_html_e('Google Places Directory Documentation', 'google-places-directory'); ?></h1>
            
            <div class="gpd-docs-intro">
                <p><?php _e('This plugin allows you to import businesses from Google Places API and display them on your website using various shortcodes.', 'google-places-directory'); ?></p>
                
                <?php do_action('gpd_docs_after_intro'); ?>
            </div>
            
            <div class="gpd-docs-nav">
                <nav class="nav-tab-wrapper">
                    <?php foreach ($this->tabs as $tab_key => $tab): ?>
                        <a href="<?php echo esc_url(add_query_arg('tab', $tab_key)); ?>" 
                           class="nav-tab <?php echo $active_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                            <?php echo esc_html($tab['title']); ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>
            
            <div class="gpd-docs-content">
                <?php
                if (isset($this->tabs[$active_tab]['callback']) && is_callable($this->tabs[$active_tab]['callback'])) {
                    call_user_func($this->tabs[$active_tab]['callback']);
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render general documentation
     */    private function render_general_docs() {
        ?>
        <div class="gpd-docs-section">
            <h2><?php esc_html_e('Getting Started', 'google-places-directory'); ?></h2>
            <p><?php _e('Welcome to Google Places Directory! This plugin helps you create a comprehensive business directory using data from Google Places API. Follow our detailed guide below to set up your business listings quickly and efficiently.', 'google-places-directory'); ?></p>
            
            <h3><?php esc_html_e('Quick Start Guide', 'google-places-directory'); ?></h3>
            <ol>
                <li>
                    <strong><?php _e('Configure your Google API key', 'google-places-directory'); ?></strong>
                    <p><?php _e('Start by obtaining a Google Places API key from the <a href="https://console.cloud.google.com/apis/dashboard" target="_blank">Google Cloud Console</a>. Enable both "Places API" and "Maps JavaScript API" for full functionality.', 'google-places-directory'); ?></p>
                    <p><?php _e('Once you have your API key, enter it in the plugin\'s Settings page (Business → Settings).', 'google-places-directory'); ?></p>
                </li>
                <li>
                    <strong><?php _e('Import Businesses', 'google-places-directory'); ?></strong>
                    <p><?php _e('Navigate to Business → Import to search for and import businesses from Google Places. You can search by:', 'google-places-directory'); ?></p>
                    <ul>
                        <li><?php _e('Location (city, address, region)', 'google-places-directory'); ?></li>
                        <li><?php _e('Business type or category', 'google-places-directory'); ?></li>
                        <li><?php _e('Specific business name', 'google-places-directory'); ?></li>
                        <li><?php _e('Keyword search terms', 'google-places-directory'); ?></li>
                    </ul>
                    <p><?php _e('Select businesses from search results to import all their details including name, address, phone, website, hours, photos, and reviews.', 'google-places-directory'); ?></p>
                </li>
                <li>
                    <strong><?php _e('Display Business Information', 'google-places-directory'); ?></strong>
                    <p><?php _e('Use our collection of shortcodes to display business information on your website. You can create:', 'google-places-directory'); ?></p>
                    <ul>
                        <li><?php _e('Business directories with search functionality', 'google-places-directory'); ?></li>
                        <li><?php _e('Interactive maps showing multiple businesses', 'google-places-directory'); ?></li>
                        <li><?php _e('Detailed business profiles with photos, hours, and contact information', 'google-places-directory'); ?></li>
                    </ul>
                </li>
                <li>
                    <strong><?php _e('Monitor API Usage', 'google-places-directory'); ?></strong>
                    <p><?php _e('Keep track of your Google API usage in the Settings page to avoid exceeding quotas. The plugin automatically tracks your API calls and can alert you when approaching limits.', 'google-places-directory'); ?></p>
                </li>
            </ol>
            
            <div class="gpd-notice gpd-notice-info">
                <p>
                    <strong><?php _e('Important:', 'google-places-directory'); ?></strong>
                    <?php _e('Google Places API has usage quotas and billing requirements. Please review Google\'s <a href="https://developers.google.com/maps/documentation/places/web-service/usage-and-billing" target="_blank">pricing and usage policies</a> to understand potential costs.', 'google-places-directory'); ?>
                </p>
            </div>
        </div>
        
        <div class="gpd-docs-section">
            <h2><?php esc_html_e('Available Shortcodes', 'google-places-directory'); ?></h2>
            <p><?php _e('Google Places Directory provides a comprehensive set of shortcodes to display business information on your website:', 'google-places-directory'); ?></p>
            
            <table class="gpd-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Shortcode', 'google-places-directory'); ?></th>
                        <th><?php _e('Description', 'google-places-directory'); ?></th>
                        <th><?php _e('Basic Parameters', 'google-places-directory'); ?></th>
                        <th><?php _e('Example', 'google-places-directory'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>[gpd-business-search]</code></td>
                        <td><?php _e('Creates an interactive business search form with filters and results display', 'google-places-directory'); ?></td>
                        <td><code>radius</code>, <code>count</code>, <code>category</code>, <code>region</code></td>
                        <td><code>[gpd-business-search count="10" radius="25"]</code></td>
                    </tr>
                    <tr>
                        <td><code>[gpd-business-map]</code></td>
                        <td><?php _e('Displays businesses on an interactive Google Map', 'google-places-directory'); ?></td>
                        <td><code>height</code>, <code>zoom</code>, <code>category</code>, <code>region</code></td>
                        <td><code>[gpd-business-map height="450" zoom="12"]</code></td>
                    </tr>
                    <tr>
                        <td><code>[gpd-business-info]</code></td>
                        <td><?php _e('Shows detailed information for a specific business', 'google-places-directory'); ?></td>
                        <td><code>id</code>, <code>fields</code>, <code>layout</code></td>
                        <td><code>[gpd-business-info id="123" fields="name,address,phone,hours"]</code></td>
                    </tr>
                    <tr>
                        <td><code>[gpd-photos]</code></td>
                        <td><?php _e('Displays photo gallery for businesses', 'google-places-directory'); ?></td>
                        <td><code>id</code>, <code>count</code>, <code>size</code>, <code>layout</code></td>
                        <td><code>[gpd-photos id="123" count="6" layout="grid"]</code></td>
                    </tr>
                    <tr>
                        <td><code>[gpd-meta]</code></td>
                        <td><?php _e('Shows specific meta information for a business', 'google-places-directory'); ?></td>
                        <td><code>id</code>, <code>field</code>, <code>format</code></td>
                        <td><code>[gpd-meta id="123" field="rating"]</code></td>
                    </tr>
                </tbody>
            </table>
            
            <p>
                <?php _e('For full shortcode documentation including all available parameters and usage examples, please see the dedicated sections below.', 'google-places-directory'); ?>
            </p>
            
            <h3><?php esc_html_e('Advanced Shortcode Usage', 'google-places-directory'); ?></h3>
            <p><?php _e('Shortcodes can be combined and nested for complex layouts. For example:', 'google-places-directory'); ?></p>
            <pre><code>[gpd-business-search count="5" radius="10"]

[gpd-business-info id="123"]
    [gpd-photos id="123" count="3" size="medium"]
[/gpd-business-info]</code></pre>
            
            <?php do_action('gpd_docs_shortcodes_overview'); ?>
        </div>
        <?php
    }
    
    /**
     * Render photos documentation
     */
    private function render_photos_docs() {
        ?>
        <div class="gpd-docs-section">
            <h2><?php esc_html_e('Business Photos', 'google-places-directory'); ?></h2>
            <p><?php _e('The <code>[gpd-photos]</code> shortcode allows you to display photos for a business.', 'google-places-directory'); ?></p>
            
            <h3><?php esc_html_e('Layouts', 'google-places-directory'); ?></h3>
            <p><?php _e('The photos shortcode supports multiple layout options:', 'google-places-directory'); ?></p>
            
            <ul>
                <li><strong><?php esc_html_e('Grid Layout:', 'google-places-directory'); ?></strong> <?php _e('Displays photos in a responsive grid with even spacing (default).', 'google-places-directory'); ?></li>
                <li><strong><?php esc_html_e('Masonry Layout:', 'google-places-directory'); ?></strong> <?php _e('Creates a Pinterest-style layout that preserves image aspect ratios.', 'google-places-directory'); ?></li>
                <li><strong><?php esc_html_e('Carousel Layout:', 'google-places-directory'); ?></strong> <?php _e('Shows photos in a scrollable slideshow with navigation controls.', 'google-places-directory'); ?></li>
                <li><strong><?php esc_html_e('Column Layout:', 'google-places-directory'); ?></strong> <?php _e('Displays photos in a single vertical column with customizable width.', 'google-places-directory'); ?></li>
            </ul>
        </div>
        <?php
    }
      /**
     * Render settings documentation
     */
    private function render_settings_docs() {
        ?>
        <div class="gpd-docs-section">
            <h2><?php esc_html_e('Plugin Configuration & Settings', 'google-places-directory'); ?></h2>
            <p><?php _e('Configure your Google Places Directory plugin to optimize performance, control API usage, and customize the behavior and appearance of business listings.', 'google-places-directory'); ?></p>
            
            <h3><?php esc_html_e('API Key Configuration', 'google-places-directory'); ?></h3>
            <p><?php _e('Configure your Google API key at <strong>Businesses → Settings</strong>', 'google-places-directory'); ?></p>
            
            <table class="gpd-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Setting', 'google-places-directory'); ?></th>
                        <th><?php _e('Description', 'google-places-directory'); ?></th>
                        <th><?php _e('Recommended Value', 'google-places-directory'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php _e('API Key', 'google-places-directory'); ?></td>
                        <td><?php _e('Your Google Cloud API key with Places API and Maps JavaScript API enabled', 'google-places-directory'); ?></td>
                        <td><?php _e('A key with HTTP referrer restrictions', 'google-places-directory'); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Photos to Import', 'google-places-directory'); ?></td>
                        <td><?php _e('Maximum number of photos to import per business (affects API usage)', 'google-places-directory'); ?></td>
                        <td><?php _e('5-10 for optimal performance', 'google-places-directory'); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Default Import Options', 'google-places-directory'); ?></td>
                        <td><?php _e('Pre-selected import options when adding new businesses', 'google-places-directory'); ?></td>
                        <td><?php _e('Enable all for complete listings', 'google-places-directory'); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Cache Duration', 'google-places-directory'); ?></td>
                        <td><?php _e('How long to cache API data before refreshing (in days)', 'google-places-directory'); ?></td>
                        <td><?php _e('7-30 days recommended', 'google-places-directory'); ?></td>
                    </tr>
                </tbody>
            </table>
            
            <p class="gpd-tip">
                <?php _e('<strong>Tip:</strong> Use the "Test Connection" button to verify your API key works correctly before importing businesses.', 'google-places-directory'); ?>
            </p>
            
            <h3><?php esc_html_e('API Usage Monitoring & Alerts', 'google-places-directory'); ?></h3>
            <p><?php _e('The plugin includes integrated API usage tracking to help you monitor costs and avoid unexpected charges.', 'google-places-directory'); ?></p>
            
            <table class="gpd-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Setting', 'google-places-directory'); ?></th>
                        <th><?php _e('Description', 'google-places-directory'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php _e('Daily Cost Threshold', 'google-places-directory'); ?></td>
                        <td><?php _e('Receive alerts when estimated daily cost exceeds this amount', 'google-places-directory'); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Request Limit', 'google-places-directory'); ?></td>
                        <td><?php _e('Receive alerts when total daily requests exceed this number', 'google-places-directory'); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Alert Email', 'google-places-directory'); ?></td>
                        <td><?php _e('Email address to receive usage alerts (defaults to admin email)', 'google-places-directory'); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Alert Frequency', 'google-places-directory'); ?></td>
                        <td><?php _e('How often to send alerts when thresholds are exceeded', 'google-places-directory'); ?></td>
                    </tr>
                </tbody>
            </table>
            
            <h3><?php esc_html_e('Display Settings', 'google-places-directory'); ?></h3>
            <p><?php _e('Customize how business listings appear on your website.', 'google-places-directory'); ?></p>
            
            <ul>
                <li><?php _e('<strong>Default Map Zoom:</strong> Controls the initial zoom level for maps (1-20)', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Default Search Radius:</strong> Set the default search radius in kilometers', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Results Per Page:</strong> Number of businesses to show in search results', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Image Size:</strong> Default size for business photos (small, medium, large)', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Map Height:</strong> Default height for map displays in pixels', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Map Style:</strong> Custom JSON styling for your Google Maps', 'google-places-directory'); ?></li>
            </ul>
            
            <div class="gpd-notice gpd-notice-info">
                <p>
                    <?php _e('<strong>Note:</strong> Most display settings can be overridden in individual shortcodes for granular control over each instance.', 'google-places-directory'); ?>
                </p>
            </div>
        </div>
        <?php
    }
      /**
     * Render FSE documentation
     */
    private function render_fse_docs() {
        ?>
        <div class="gpd-docs-section">
            <h2><?php esc_html_e('Full Site Editor Integration', 'google-places-directory'); ?></h2>
            <p><?php _e('Google Places Directory integrates seamlessly with WordPress Full Site Editor (FSE), allowing you to incorporate business listings and maps into your site templates and theme designs.', 'google-places-directory'); ?></p>
            
            <h3><?php esc_html_e('Using Shortcodes in the Block Editor', 'google-places-directory'); ?></h3>
            <p><?php _e('There are several ways to add Google Places Directory elements to your FSE templates:', 'google-places-directory'); ?></p>
            
            <h4><?php esc_html_e('Method 1: Shortcode Block', 'google-places-directory'); ?></h4>
            <ol>
                <li><?php _e('In the block editor, add a "Shortcode" block', 'google-places-directory'); ?></li>
                <li><?php _e('Insert any GPD shortcode with desired parameters', 'google-places-directory'); ?></li>
                <li><?php _e('Example: <code>[gpd-business-map height="400" zoom="12" category="restaurant"]</code>', 'google-places-directory'); ?></li>
            </ol>
            
            <h4><?php esc_html_e('Method 2: Custom HTML Block', 'google-places-directory'); ?></h4>
            <ol>
                <li><?php _e('Add a "Custom HTML" block to your template', 'google-places-directory'); ?></li>
                <li><?php _e('Combine shortcodes with custom HTML for more complex layouts', 'google-places-directory'); ?></li>
                <li><?php _e('Example:', 'google-places-directory'); ?></li>
            </ol>
            <pre><code>&lt;div class="business-showcase"&gt;
  &lt;h2&gt;Featured Restaurants&lt;/h2&gt;
  [gpd-business-search category="restaurant" count="5"]
&lt;/div&gt;</code></pre>
            
            <h4><?php esc_html_e('Method 3: Using Theme Templates', 'google-places-directory'); ?></h4>
            <p><?php _e('For more advanced integration, you can add GPD shortcodes directly to theme template files:', 'google-places-directory'); ?></p>
            <pre><code>&lt;?php echo do_shortcode('[gpd-business-search count="10"]'); ?&gt;</code></pre>
            
            <h3><?php esc_html_e('Creating Business Directory Templates', 'google-places-directory'); ?></h3>
            <p><?php _e('You can use the Full Site Editor to create custom templates for your business directory:', 'google-places-directory'); ?></p>
            
            <h4><?php esc_html_e('Business Archive Template', 'google-places-directory'); ?></h4>
            <ol>
                <li><?php _e('In Site Editor, create a new template for "Archive: Business"', 'google-places-directory'); ?></li>
                <li><?php _e('Add blocks for header, navigation, content, sidebar, and footer', 'google-places-directory'); ?></li>
                <li><?php _e('In the content area, add a Shortcode block with <code>[gpd-business-search]</code>', 'google-places-directory'); ?></li>
                <li><?php _e('Add a Query Loop block to display businesses from the "business" post type', 'google-places-directory'); ?></li>
            </ol>
            
            <h4><?php esc_html_e('Single Business Template', 'google-places-directory'); ?></h4>
            <ol>
                <li><?php _e('Create a new template for "Single: Business"', 'google-places-directory'); ?></li>
                <li><?php _e('Create a two-column layout with business info and photos', 'google-places-directory'); ?></li>
                <li><?php _e('In the content blocks, use shortcodes like:', 'google-places-directory'); ?></li>
            </ol>
            <pre><code>[gpd-business-info layout="detailed"]
[gpd-photos size="medium" layout="grid"]
[gpd-business-map zoom="15" height="350"]</code></pre>
            
            <div class="gpd-notice gpd-notice-info">
                <p>
                    <?php _e('<strong>Tip:</strong> For the best performance with FSE, you can enable the "Cache Shortcode Output" option in the plugin settings to reduce the number of API calls.', 'google-places-directory'); ?>
                </p>
            </div>
            
            <h3><?php esc_html_e('Creating Custom Block Patterns', 'google-places-directory'); ?></h3>
            <p><?php _e('You can create reusable block patterns combining GPD shortcodes with other blocks:', 'google-places-directory'); ?></p>
            <ol>
                <li><?php _e('Create a layout combining multiple blocks and GPD shortcodes', 'google-places-directory'); ?></li>
                <li><?php _e('Select all blocks in the layout', 'google-places-directory'); ?></li>
                <li><?php _e('Click the options menu (three dots) and choose "Create pattern"', 'google-places-directory'); ?></li>
                <li><?php _e('Name your pattern and choose a category', 'google-places-directory'); ?></li>
                <li><?php _e('Your custom business directory pattern is now available in the pattern inserter', 'google-places-directory'); ?></li>
            </ol>
        </div>
        <?php
    }
    
    /**
     * Get API usage data safely
     * 
     * @return array API usage data including history, daily counts, and thresholds
     */
    private function get_api_usage_data() {
        $api_usage = GPD_API_Usage::instance();
        
        $default_data = array(
            'history' => array(),
            'daily_counts' => array(
                'text_search' => 0,
                'place_details' => 0,
                'photos' => 0
            ),
            'thresholds' => array(
                'daily_cost' => 50,
                'request_limit' => 1000
            )
        );
        
        if (!$api_usage) {
            return $default_data;
        }
        
        return array(
            'history' => method_exists($api_usage, 'get_usage_history') ? $api_usage->get_usage_history() : $default_data['history'],
            'daily_counts' => method_exists($api_usage, 'get_daily_counts') ? $api_usage->get_daily_counts() : $default_data['daily_counts'],
            'thresholds' => method_exists($api_usage, 'get_thresholds') ? $api_usage->get_thresholds() : $default_data['thresholds']
        );
    }
    
    /**
     * Calculate current API usage costs
     * 
     * @param array $daily_counts Daily API request counts
     * @return array Costs for each API type and total
     */
    private function calculate_api_costs($daily_counts) {
        $request_counts = $daily_counts;
        // Remove the date key if present
        unset($request_counts['date']);
        
        $costs = array(
            'text_search' => ($request_counts['text_search'] / 1000) * 5, // $5 per 1000 requests
            'place_details' => ($request_counts['place_details'] / 1000) * 5, // $5 per 1000 requests
            'photos' => ($request_counts['photos'] / 1000) * 7 // $7 per 1000 requests
        );
        
        $costs['total'] = array_sum($costs);
        return $costs;
    }
      /**
     * Render API documentation
     */
    private function render_api_docs() {
        $data = $this->get_api_usage_data();
        $costs = $this->calculate_api_costs($data['daily_counts']);
        $api_usage = GPD_API_Usage::instance();

        ?>
        <div class="gpd-docs-section">
            <h2><?php esc_html_e('Google Places API Integration Guide', 'google-places-directory'); ?></h2>
            
            <h3><?php esc_html_e('Setting Up Your Google API Key', 'google-places-directory'); ?></h3>
            <p><?php _e('To use Google Places Directory, you need to obtain an API key from Google Cloud Platform. Follow these steps:', 'google-places-directory'); ?></p>
            
            <ol>
                <li>
                    <strong><?php _e('Create a Google Cloud Project', 'google-places-directory'); ?></strong>
                    <p><?php _e('Visit the <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a> and create a new project (or select an existing one).', 'google-places-directory'); ?></p>
                </li>
                <li>
                    <strong><?php _e('Enable Required APIs', 'google-places-directory'); ?></strong>
                    <p><?php _e('In your Google Cloud project, go to "APIs & Services" → "Library" and enable the following APIs:', 'google-places-directory'); ?></p>
                    <ul>
                        <li><?php _e('<strong>Places API</strong> - Required for business search and details', 'google-places-directory'); ?></li>
                        <li><?php _e('<strong>Maps JavaScript API</strong> - Required for maps and location features', 'google-places-directory'); ?></li>
                        <li><?php _e('<strong>Geocoding API</strong> - (Optional) For address search features', 'google-places-directory'); ?></li>
                    </ul>
                </li>
                <li>
                    <strong><?php _e('Create API Key', 'google-places-directory'); ?></strong>
                    <p><?php _e('Go to "APIs & Services" → "Credentials" and click "Create Credentials" → "API Key".', 'google-places-directory'); ?></p>
                </li>
                <li>
                    <strong><?php _e('Set API Key Restrictions (Recommended)', 'google-places-directory'); ?></strong>
                    <p><?php _e('For security, restrict your API key:', 'google-places-directory'); ?></p>
                    <ul>
                        <li><?php _e('<strong>HTTP referrers:</strong> Add your website domain', 'google-places-directory'); ?></li>
                        <li><?php _e('<strong>API restrictions:</strong> Restrict to only the APIs you enabled', 'google-places-directory'); ?></li>
                    </ul>
                </li>
                <li>
                    <strong><?php _e('Set Up Billing (Required)', 'google-places-directory'); ?></strong>
                    <p><?php _e('Google Places API requires billing to be enabled. Add a payment method to your Google Cloud account.', 'google-places-directory'); ?></p>
                </li>
                <li>
                    <strong><?php _e('Enter API Key in Plugin Settings', 'google-places-directory'); ?></strong>
                    <p><?php _e('Copy your API key and enter it in the plugin\'s Settings page (Business → Settings).', 'google-places-directory'); ?></p>
                </li>
            </ol>

            <div class="gpd-notice gpd-notice-warning">
                <p>
                    <strong><?php _e('Important Security Notice:', 'google-places-directory'); ?></strong>
                    <?php _e('Always apply restrictions to your API key to prevent unauthorized use. If your key is used without restrictions, unauthorized parties could use it and incur charges to your Google Cloud billing account.', 'google-places-directory'); ?>
                </p>
            </div>

            <h3><?php esc_html_e('Places API (New) Information', 'google-places-directory'); ?></h3>
            <p><?php _e('This plugin uses Google\'s new Places API, which was updated in May 2025.', 'google-places-directory'); ?></p>
            
            <div class="gpd-api-stats" style="background: #f8f9fa; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 4px;">
                <h3><?php esc_html_e('Today\'s API Usage', 'google-places-directory'); ?></h3>
                <table class="widefat" style="width: 100%; margin-top: 10px;">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('API Feature', 'google-places-directory'); ?></th>
                            <th><?php esc_html_e('Requests', 'google-places-directory'); ?></th>
                            <th><?php esc_html_e('Cost', 'google-places-directory'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php esc_html_e('Text Search', 'google-places-directory'); ?></td>
                            <td><?php echo number_format($data['daily_counts']['text_search']); ?></td>
                            <td>$<?php echo number_format($costs['text_search'], 2); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e('Place Details', 'google-places-directory'); ?></td>
                            <td><?php echo number_format($data['daily_counts']['place_details']); ?></td>
                            <td>$<?php echo number_format($costs['place_details'], 2); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e('Photos', 'google-places-directory'); ?></td>
                            <td><?php echo number_format($data['daily_counts']['photos']); ?></td>
                            <td>$<?php echo number_format($costs['photos'], 2); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('Total', 'google-places-directory'); ?></strong></td>
                            <td><strong><?php 
                                $request_counts = $data['daily_counts'];
                                // Remove the date key if present
                                unset($request_counts['date']); 
                                echo number_format(array_sum($request_counts)); 
                            ?></strong></td>
                            <td><strong>$<?php echo number_format($costs['total'], 2); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
                <p class="description" style="margin-top: 10px;">
                    <?php esc_html_e('Usage stats reset daily at midnight. View detailed usage in Google Cloud Console.', 'google-places-directory'); ?>
                </p>
            </div>
            
            <?php if ($api_usage && method_exists($api_usage, 'get_usage_history')): 
                $usage_history = $api_usage->get_usage_history();
                if (!empty($usage_history)):
            ?>
            <div class="gpd-usage-graph-container" style="margin: 20px 0;">
                <h3><?php esc_html_e('30-Day Usage Trends', 'google-places-directory'); ?></h3>
                <canvas id="gpd-usage-graph" width="800" height="400" data-usage='<?php echo esc_attr(json_encode($usage_history)); ?>'></canvas>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script src="<?php echo esc_url(GPD_PLUGIN_URL . 'assets/js/gpd-usage-graph.js'); ?>"></script>
            <?php endif; endif; ?>

            <h3><?php esc_html_e('API Features Used', 'google-places-directory'); ?></h3>
            <ul>
                <li><?php _e('<strong>Text Search</strong>: Used for finding businesses by name or type', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Place Details</strong>: Used to get comprehensive information about a specific business', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Place Photos</strong>: Used to retrieve business photos', 'google-places-directory'); ?></li>
            </ul>
            
            <h3><?php esc_html_e('API Usage and Billing', 'google-places-directory'); ?></h3>
            <p><?php _e('The Places API is a billing-required API with the following pricing structure:', 'google-places-directory'); ?></p>
            <ul>
                <li><?php _e('<strong>Text Search</strong>: $5 per 1,000 requests', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Place Details</strong>: $4 per 1,000 requests', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Photos</strong>: $7 per 1,000 requests', 'google-places-directory'); ?></li>
            </ul>
        </div>
        <?php
    }
}

// Initialize the docs
GPD_Docs::instance();
