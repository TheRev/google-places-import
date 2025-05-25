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
     */
    private function render_general_docs() {
        ?>
        <div class="gpd-docs-section">
            <h2><?php esc_html_e('Getting Started', 'google-places-directory'); ?></h2>
            <p><?php _e('Welcome to Google Places Directory! This plugin helps you create a business directory using data from Google Places API.', 'google-places-directory'); ?></p>
            
            <h3><?php esc_html_e('Quick Start Guide', 'google-places-directory'); ?></h3>
            <ol>
                <li><?php _e('Configure your API key in the Settings page', 'google-places-directory'); ?></li>
                <li><?php _e('Use the Business Import page to search for and import businesses', 'google-places-directory'); ?></li>
                <li><?php _e('Add business listings to your pages using shortcodes', 'google-places-directory'); ?></li>
            </ol>
        </div>
        
        <div class="gpd-docs-section">
            <h2><?php esc_html_e('Available Shortcodes', 'google-places-directory'); ?></h2>
            <p><?php _e('Google Places Directory provides several shortcodes to display business information on your website:', 'google-places-directory'); ?></p>
            
            <ul>
                <li><code>[gpd-photos]</code> - <?php _e('Display business photos (see the Business Photos tab for details)', 'google-places-directory'); ?></li>
                <li><code>[gpd-business-search]</code> - <?php _e('Create a search form for businesses', 'google-places-directory'); ?></li>
                <li><code>[gpd-business-map]</code> - <?php _e('Display businesses on a map', 'google-places-directory'); ?></li>
            </ul>
            
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
            <h2><?php esc_html_e('API Key Settings', 'google-places-directory'); ?></h2>
            <p><?php _e('The plugin requires a valid Google API key with the Places API (New) enabled.', 'google-places-directory'); ?></p>
            
            <h3><?php esc_html_e('Plugin Settings', 'google-places-directory'); ?></h3>
            <p><?php _e('Configure your plugin settings at <strong>Businesses → Settings</strong>', 'google-places-directory'); ?></p>
            <ul>
                <li><?php _e('<strong>API Key</strong>: Enter your Google API key with Places API (New) enabled', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Photos to Import</strong>: Set the maximum number of photos to import per business', 'google-places-directory'); ?></li>
                <li><?php _e('Use the "Test Connection" button to verify your API key works correctly', 'google-places-directory'); ?></li>
            </ul>
        </div>
        <?php
    }
    
    /**
     * Render FSE documentation
     */
    private function render_fse_docs() {
        ?>
        <div class="gpd-docs-section">
            <h2><?php esc_html_e('Using Shortcodes in Full Site Editor (FSE)', 'google-places-directory'); ?></h2>
            <p><?php _e('The plugin\'s shortcodes work seamlessly with the WordPress Full Site Editor. Here\'s how to use them in your FSE templates:', 'google-places-directory'); ?></p>
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
            <h2><?php esc_html_e('Places API (New) Information', 'google-places-directory'); ?></h2>
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
