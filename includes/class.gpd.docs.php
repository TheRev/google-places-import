<?php
/**
 * Class GPD_Docs
 *
 * Provides documentation and help pages for the plugin
 * 
 * @since 2.3.0
 * @updated 2.5.1
 */

if ( ! defined( 'ABSPATH' ) ) {
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
    
    /**
     * Register a documentation tab
     *
     * @param string $slug Tab slug/ID
     * @param string $title Tab display title
     * @param callable|null $callback Function to render tab content (null to use action hooks)
     * @param int $priority Tab display order (lower = earlier)
     * @return bool Success
     */
    public function register_tab( $slug, $title, $callback = null, $priority = 10 ) {
        if ( empty( $slug ) || empty( $title ) ) {
            return false;
        }
        
        $this->tabs[$slug] = array(
            'title'    => $title,
            'callback' => $callback,
            'priority' => $priority
        );
        
        return true;
    }
    
    /**
     * Register built-in documentation tabs
     */
    private function register_default_tabs() {
        // Register core tabs
        $this->register_tab( 'shortcodes', __('Shortcodes', 'google-places-directory'), array( $this, 'render_shortcodes_docs' ), 10 );
        $this->register_tab( 'photos', __('Business Photos', 'google-places-directory'), array( $this, 'render_photos_docs' ), 20 );
        $this->register_tab( 'settings', __('Settings', 'google-places-directory'), array( $this, 'render_settings_docs' ), 30 );
        $this->register_tab( 'fse', __('Full Site Editing', 'google-places-directory'), array( $this, 'render_fse_docs' ), 40 );
        $this->register_tab( 'api', __('API Information', 'google-places-directory'), array( $this, 'render_api_docs' ), 50 );
    }
    
    /**
     * Finalize tab registration and apply filters
     */
    public function finalize_tabs() {
        // Allow plugins to modify the tabs
        $this->tabs = apply_filters( 'gpd_docs_tabs', $this->tabs );
        
        // Sort tabs by priority
        uasort( $this->tabs, function( $a, $b ) {
            return $a['priority'] - $b['priority']; 
        });
    }

    /**
     * Enqueue styles for the documentation page
     */
    public function enqueue_styles($hook) {
        if ($hook === 'business_page_gpd-docs') {
            wp_enqueue_style('gpd-admin-styles', plugin_dir_url(__FILE__) . '../assets/admin-style.css');
            
            // Add inline styles for docs page
            wp_add_inline_style('gpd-admin-styles', '
                .gpd-docs-nav {
                    display: flex;
                    margin-bottom: 20px;
                    border-bottom: 1px solid #ccc;
                    flex-wrap: wrap;
                }
                .gpd-docs-nav a {
                    padding: 10px 15px;
                    text-decoration: none;
                    font-weight: 500;
                }
                .gpd-docs-nav a.active {
                    border-bottom: 2px solid #2271b1;
                    color: #2271b1;
                }
                .gpd-docs-section {
                    background: #fff;
                    padding: 20px;
                    margin-bottom: 20px;
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
            do_action( 'gpd_docs_enqueue_styles' );
        }
    }

    /**
     * Add documentation pages to the admin menu
     */
    public function add_docs_pages() {
        add_submenu_page(
            'edit.php?post_type=business',
            __( 'Documentation', 'google-places-directory' ),
            __( 'Documentation', 'google-places-directory' ),
            'manage_options',
            'gpd-docs',
            array( $this, 'render_docs_page' )
        );
    }

    /**
     * Render the documentation page
     */
    public function render_docs_page() {
        // Get tab keys for easier handling
        $tab_keys = array_keys( $this->tabs );
        
        // Determine which tab to show
        $active_tab = isset($_GET['tab']) && array_key_exists($_GET['tab'], $this->tabs) 
            ? sanitize_key($_GET['tab']) 
            : reset($tab_keys); // First tab is default
        ?>
        <div class="wrap gpd-docs">
            <h1><?php esc_html_e( 'Google Places Directory Documentation', 'google-places-directory' ); ?></h1>
            
            <div class="gpd-docs-intro">
                <p><?php _e( 'This plugin allows you to import businesses from Google Places API and display them on your website using various shortcodes.', 'google-places-directory' ); ?></p>
                
                <?php do_action( 'gpd_docs_after_intro' ); ?>
            </div>
            
            <div class="gpd-docs-nav">
                <?php foreach ( $this->tabs as $slug => $tab ) : ?>
                    <a href="<?php echo esc_url(add_query_arg('tab', $slug)); ?>" class="<?php echo $active_tab === $slug ? 'active' : ''; ?>">
                        <?php echo esc_html($tab['title']); ?>
                    </a>
                <?php endforeach; ?>
                
                <?php do_action( 'gpd_docs_after_tabs' ); ?>
            </div>
            
            <?php
            // Allow actions before tab content
            do_action( 'gpd_docs_before_tab_content', $active_tab );
            
            // Render the active tab content
            $this->render_tab_content( $active_tab );
            
            // Allow actions after tab content
            do_action( 'gpd_docs_after_tab_content', $active_tab );
            ?>
        </div>
        <?php
    }
    
    /**
     * Render the content for a specific tab
     * 
     * @param string $tab Tab slug
     */
    private function render_tab_content( $tab ) {
        if ( !isset($this->tabs[$tab]) ) {
            return;
        }

        // If the tab has a callback, use it
        if ( !empty($this->tabs[$tab]['callback']) && is_callable($this->tabs[$tab]['callback']) ) {
            call_user_func( $this->tabs[$tab]['callback'] );
            return;
        }
        
        // Otherwise, fire action for add-ons to hook into
        do_action( "gpd_docs_tab_{$tab}" );
    }
    
    /**
     * Render photos documentation - NEW TAB with dedicated photos content
     */
    public function render_photos_docs() {
        ?>
        <div class="gpd-docs-section">
            <h2><?php esc_html_e( 'Display Business Photos', 'google-places-directory' ); ?></h2>
            <p><?php _e( 'Use the <code>[gpd-photos]</code> shortcode to display photos for a specific business.', 'google-places-directory' ); ?></p>
            
            <h3><?php esc_html_e( 'Layouts', 'google-places-directory' ); ?></h3>
            <p><?php _e('The photos shortcode supports multiple layout options:', 'google-places-directory'); ?></p>
            
            <ul>
                <li><strong><?php esc_html_e('Grid Layout:', 'google-places-directory'); ?></strong> <?php _e('Displays photos in a responsive grid with even spacing (default).', 'google-places-directory'); ?></li>
                <li><strong><?php esc_html_e('Masonry Layout:', 'google-places-directory'); ?></strong> <?php _e('Creates a Pinterest-style layout that preserves image aspect ratios.', 'google-places-directory'); ?></li>
                <li><strong><?php esc_html_e('Carousel Layout:', 'google-places-directory'); ?></strong> <?php _e('Shows photos in a scrollable slideshow with navigation controls.', 'google-places-directory'); ?></li>
                <li><strong><?php esc_html_e('Column Layout:', 'google-places-directory'); ?></strong> <?php _e('Displays photos in a single vertical column with customizable width.', 'google-places-directory'); ?></li>
            </ul>
            
            <h3><?php esc_html_e( 'Parameters', 'google-places-directory' ); ?></h3>
            <table class="widefat" style="width: 95%">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Parameter', 'google-places-directory' ); ?></th>
                        <th><?php esc_html_e( 'Description', 'google-places-directory' ); ?></th>
                        <th><?php esc_html_e( 'Default', 'google-places-directory' ); ?></th>
                        <th><?php esc_html_e( 'Options', 'google-places-directory' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>id</code></td>
                        <td><?php esc_html_e( 'The business post ID', 'google-places-directory' ); ?></td>
                        <td>0 (current post)</td>
                        <td><?php esc_html_e( 'Any valid business post ID', 'google-places-directory' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>layout</code></td>
                        <td><?php esc_html_e( 'The gallery layout style', 'google-places-directory' ); ?></td>
                        <td>grid</td>
                        <td>grid, masonry, carousel, column</td>
                    </tr>
                    <tr>
                        <td><code>columns</code></td>
                        <td><?php esc_html_e( 'Number of columns for grid/masonry layout', 'google-places-directory' ); ?></td>
                        <td>3</td>
                        <td>1, 2, 3, 4</td>
                    </tr>
                    <tr>
                        <td><code>size</code></td>
                        <td><?php esc_html_e( 'The image size to use', 'google-places-directory' ); ?></td>
                        <td>medium</td>
                        <td>thumbnail, medium, large, full</td>
                    </tr>
                    <tr>
                        <td><code>limit</code></td>
                        <td><?php esc_html_e( 'Maximum number of photos to display (0 = all)', 'google-places-directory' ); ?></td>
                        <td>0</td>
                        <td><?php esc_html_e( 'Any positive number', 'google-places-directory' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>show_caption</code></td>
                        <td><?php esc_html_e( 'Whether to show photo captions', 'google-places-directory' ); ?></td>
                        <td>false</td>
                        <td>true, false</td>
                    </tr>
                    <tr>
                        <td><code>class</code></td>
                        <td><?php esc_html_e( 'Additional CSS classes', 'google-places-directory' ); ?></td>
                        <td>empty</td>
                        <td><?php esc_html_e( 'Any CSS class name', 'google-places-directory' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>max_width</code></td>
                        <td><?php esc_html_e( 'Maximum width of the column layout', 'google-places-directory' ); ?></td>
                        <td>800px</td>
                        <td><?php esc_html_e( 'Any valid CSS width value', 'google-places-directory' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>spacing</code></td>
                        <td><?php esc_html_e( 'Spacing between photos in column layout', 'google-places-directory' ); ?></td>
                        <td>20px</td>
                        <td><?php esc_html_e( 'Any valid CSS size value', 'google-places-directory' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>alignment</code></td>
                        <td><?php esc_html_e( 'Horizontal alignment of the column layout', 'google-places-directory' ); ?></td>
                        <td>center</td>
                        <td>left, center, right</td>
                    </tr>
                </tbody>
            </table>
            
            <?php do_action( 'gpd_docs_photos_after_parameters' ); ?>
            
            <h3><?php esc_html_e( 'Examples', 'google-places-directory' ); ?></h3>
            <div class="gpd-shortcode-example">[gpd-photos id="123" layout="grid" columns="3" size="medium" limit="6"]</div>
            <div class="gpd-shortcode-example">[gpd-photos layout="carousel" size="large" show_caption="true"]</div>
            <div class="gpd-shortcode-example">[gpd-photos layout="masonry" columns="4" class="my-custom-gallery"]</div>
            <div class="gpd-shortcode-example">[gpd-photos layout="column" max_width="800px" spacing="20px" alignment="center"]</div>
            
            <?php do_action( 'gpd_docs_photos_after_examples' ); ?>
        </div>
        <?php
    }
    
    /**
     * Render shortcodes documentation
     */
    private function render_shortcodes_docs() {
        ?>
        <div class="gpd-docs-section">
            <h2><?php esc_html_e( 'Available Shortcodes', 'google-places-directory' ); ?></h2>
            <p><?php _e( 'Google Places Directory provides several shortcodes to display business information on your website:', 'google-places-directory' ); ?></p>
            
            <ul>
                <li><code>[gpd-photos]</code> - <?php _e('Display business photos (see the Business Photos tab for details)', 'google-places-directory'); ?></li>
                <li><code>[gpd-business-search]</code> - <?php _e('Create a search form for businesses', 'google-places-directory'); ?></li>
                <li><code>[gpd-business-map]</code> - <?php _e('Display businesses on a map', 'google-places-directory'); ?></li>
            </ul>
            
            <?php do_action( 'gpd_docs_shortcodes_overview' ); ?>
        </div>
        
        <div class="gpd-docs-section">
            <h2><?php esc_html_e( 'Business Search', 'google-places-directory' ); ?></h2>
            <p><?php _e( 'Use the <code>[gpd-business-search]</code> shortcode to create a search form for businesses.', 'google-places-directory' ); ?></p>
            
            <h3><?php esc_html_e( 'Parameters', 'google-places-directory' ); ?></h3>
            <table class="widefat" style="width: 95%">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Parameter', 'google-places-directory' ); ?></th>
                        <th><?php esc_html_e( 'Description', 'google-places-directory' ); ?></th>
                        <th><?php esc_html_e( 'Default', 'google-places-directory' ); ?></th>
                        <th><?php esc_html_e( 'Options', 'google-places-directory' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>show_map</code></td>
                        <td><?php esc_html_e( 'Show map with search results', 'google-places-directory' ); ?></td>
                        <td>false</td>
                        <td>true, false</td>
                    </tr>
                    <tr>
                        <td><code>location_search</code></td>
                        <td><?php esc_html_e( 'Enable location-based search', 'google-places-directory' ); ?></td>
                        <td>true</td>
                        <td>true, false</td>
                    </tr>
                    <tr>
                        <td><code>results_page</code></td>
                        <td><?php esc_html_e( 'URL to results page (empty for AJAX)', 'google-places-directory' ); ?></td>
                        <td>empty</td>
                        <td><?php esc_html_e( 'Any valid URL', 'google-places-directory' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>default_radius</code></td>
                        <td><?php esc_html_e( 'Default search radius in km', 'google-places-directory' ); ?></td>
                        <td>25</td>
                        <td><?php esc_html_e( 'Any positive number', 'google-places-directory' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>default_limit</code></td>
                        <td><?php esc_html_e( 'Default number of results', 'google-places-directory' ); ?></td>
                        <td>10</td>
                        <td><?php esc_html_e( 'Any positive number', 'google-places-directory' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>placeholder</code></td>
                        <td><?php esc_html_e( 'Placeholder text for search field', 'google-places-directory' ); ?></td>
                        <td>Search for businesses...</td>
                        <td><?php esc_html_e( 'Any text', 'google-places-directory' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>class</code></td>
                        <td><?php esc_html_e( 'Additional CSS classes', 'google-places-directory' ); ?></td>
                        <td>empty</td>
                        <td><?php esc_html_e( 'Any CSS class name', 'google-places-directory' ); ?></td>
                    </tr>
                </tbody>
            </table>
            
            <?php do_action( 'gpd_docs_business_search_after_parameters' ); ?>
            
            <h3><?php esc_html_e( 'Examples', 'google-places-directory' ); ?></h3>
            <div class="gpd-shortcode-example">[gpd-business-search show_map="true" location_search="true"]</div>
            <div class="gpd-shortcode-example">[gpd-business-search results_page="/business-results/" default_radius="50"]</div>
            
            <?php do_action( 'gpd_docs_business_search_after_examples' ); ?>
        </div>
        
        <div class="gpd-docs-section">
            <h2><?php esc_html_e( 'Business Map', 'google-places-directory' ); ?></h2>
            <p><?php _e( 'Use the <code>[gpd-business-map]</code> shortcode to display businesses on a map.', 'google-places-directory' ); ?></p>
            
            <h3><?php esc_html_e( 'Parameters', 'google-places-directory' ); ?></h3>
            <table class="widefat" style="width: 95%">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Parameter', 'google-places-directory' ); ?></th>
                        <th><?php esc_html_e( 'Description', 'google-places-directory' ); ?></th>
                        <th><?php esc_html_e( 'Default', 'google-places-directory' ); ?></th>
                        <th><?php esc_html_e( 'Options', 'google-places-directory' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>id</code></td>
                        <td><?php esc_html_e( 'The business post ID (0 for all businesses)', 'google-places-directory' ); ?></td>
                        <td>0</td>
                        <td><?php esc_html_e( 'Any valid business post ID', 'google-places-directory' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>category</code></td>
                        <td><?php esc_html_e( 'Filter by business category slug', 'google-places-directory' ); ?></td>
                        <td>empty</td>
                        <td><?php esc_html_e( 'Any valid category slug', 'google-places-directory' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>limit</code></td>
                        <td><?php esc_html_e( 'Maximum number of businesses to show', 'google-places-directory' ); ?></td>
                        <td>100</td>
                        <td><?php esc_html_e( 'Any positive number', 'google-places-directory' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>height</code></td>
                        <td><?php esc_html_e( 'Map height', 'google-places-directory' ); ?></td>
                        <td>400px</td>
                        <td><?php esc_html_e( 'Any valid CSS height value', 'google-places-directory' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>zoom</code></td>
                        <td><?php esc_html_e( 'Default zoom level', 'google-places-directory' ); ?></td>
                        <td>14</td>
                        <td>1-20</td>
                    </tr>
                    <tr>
                        <td><code>clustering</code></td>
                        <td><?php esc_html_e( 'Use marker clustering for multiple markers', 'google-places-directory' ); ?></td>
                        <td>true</td>
                        <td>true, false</td>
                    </tr>
                    <tr>
                        <td><code>class</code></td>
                        <td><?php esc_html_e( 'Additional CSS classes', 'google-places-directory' ); ?></td>
                        <td>empty</td>
                        <td><?php esc_html_e( 'Any CSS class name', 'google-places-directory' ); ?></td>
                    </tr>
                </tbody>
            </table>
            
            <?php do_action( 'gpd_docs_business_map_after_parameters' ); ?>
            
            <h3><?php esc_html_e( 'Examples', 'google-places-directory' ); ?></h3>
            <div class="gpd-shortcode-example">[gpd-business-map id="123" height="500px" zoom="15"]</div>
            <div class="gpd-shortcode-example">[gpd-business-map category="restaurants" limit="50" clustering="true"]</div>
            
            <?php do_action( 'gpd_docs_business_map_after_examples' ); ?>
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
            
            <h3><?php esc_html_e('Obtaining a Google API Key', 'google-places-directory'); ?></h3>
            <ol>
                <li><?php _e('Go to the <a href="https://console.cloud.google.com/apis/dashboard" target="_blank">Google Cloud Console</a>', 'google-places-directory'); ?></li>
                <li><?php _e('Create a new project or select an existing one', 'google-places-directory'); ?></li>
                <li><?php _e('Enable the <strong>Places API (New)</strong> for your project', 'google-places-directory'); ?></li>
                <li><?php _e('Create credentials to get your API key', 'google-places-directory'); ?></li>
                <li><?php _e('Set up billing (required for the Places API)', 'google-places-directory'); ?></li>
                <li><?php _e('Optional: Add API key restrictions for security', 'google-places-directory'); ?></li>
            </ol>
            
            <h3><?php esc_html_e('Plugin Settings', 'google-places-directory'); ?></h3>
            <p><?php _e('Configure your plugin settings at <strong>Businesses → Settings</strong>', 'google-places-directory'); ?></p>
            <ul>
                <li><?php _e('<strong>API Key</strong>: Enter your Google API key with Places API (New) enabled', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Photos to Import</strong>: Set the maximum number of photos to import per business', 'google-places-directory'); ?></li>
                <li><?php _e('Use the "Test Connection" button to verify your API key works correctly', 'google-places-directory'); ?></li>
            </ul>
            
            <?php do_action( 'gpd_docs_settings_api_after' ); ?>
        </div>
        
        <div class="gpd-docs-section">
            <h2><?php esc_html_e('Photo Management', 'google-places-directory'); ?></h2>
            <p><?php _e('The plugin provides several features for managing business photos:', 'google-places-directory'); ?></p>
            
            <ul>
                <li><?php _e('<strong>Photo Import</strong>: Photos are automatically imported when you import businesses', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Photo Limit</strong>: Control how many photos are imported per business in the plugin settings', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Photo Refresh</strong>: Refresh photos for individual businesses from their edit screen', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Featured Image</strong>: The first imported photo is automatically set as the featured image', 'google-places-directory'); ?></li>
            </ul>
            
            <h3><?php esc_html_e('Business Photo Column', 'google-places-directory'); ?></h3>
            <p><?php _e('The Businesses list includes a Photos column that shows:', 'google-places-directory'); ?></p>
            <ul>
                <li><?php _e('Number of photos attached to each business', 'google-places-directory'); ?></li>
                <li><?php _e('Star icon for businesses with a featured image', 'google-places-directory'); ?></li>
                <li><?php _e('Thumbnail preview on hover', 'google-places-directory'); ?></li>
                <li><?php _e('"Add Photos" link for businesses without photos', 'google-places-directory'); ?></li>
            </ul>
            
            <p><?php _e('You can sort and filter businesses by their photo status using the column header and filter dropdown.', 'google-places-directory'); ?></p>
            
            <?php do_action( 'gpd_docs_settings_photos_after' ); ?>
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
            
            <h3><?php esc_html_e('Adding Shortcodes to Templates', 'google-places-directory'); ?></h3>
            <ol>
                <li><?php _e('Edit a template in the Full Site Editor', 'google-places-directory'); ?></li>
                <li><?php _e('Add a "Shortcode" block where you want the business content to appear', 'google-places-directory'); ?></li>
                <li><?php _e('Enter one of the plugin\'s shortcodes with your desired parameters', 'google-places-directory'); ?></li>
                <li><?php _e('Save the template', 'google-places-directory'); ?></li>
            </ol>
            
            <h3><?php esc_html_e('Single Business Template Example', 'google-places-directory'); ?></h3>
            <p><?php _e('For a single business template, you might want to add:', 'google-places-directory'); ?></p>
            <ol>
                <li><?php _e('The business title (using WordPress core Title block)', 'google-places-directory'); ?></li>
                <li><?php _e('The business content (using WordPress core Content block)', 'google-places-directory'); ?></li>
                <li><?php _e('A Shortcode block with <code>[gpd-photos columns="3" layout="grid"]</code> to show business photos', 'google-places-directory'); ?></li>
                <li><?php _e('A Shortcode block with <code>[gpd-business-map height="400px"]</code> to show the business location', 'google-places-directory'); ?></li>
            </ol>
            
            <?php do_action( 'gpd_docs_fse_business_template_after' ); ?>
            
            <h3><?php esc_html_e('Archive Template Example', 'google-places-directory'); ?></h3>
            <p><?php _e('For a business archive or search results page:', 'google-places-directory'); ?></p>
            <ol>
                <li><?php _e('Add a Shortcode block with <code>[gpd-business-search show_map="true"]</code> at the top', 'google-places-directory'); ?></li>
                <li><?php _e('Use WordPress core Query Loop block to display business posts', 'google-places-directory'); ?></li>
                <li><?php _e('Optionally add a Shortcode block with <code>[gpd-business-map height="500px"]</code> to show all businesses on a map', 'google-places-directory'); ?></li>
            </ol>
            
            <?php do_action( 'gpd_docs_fse_archive_template_after' ); ?>
        </div>
        
        <div class="gpd-docs-section">
            <h2><?php esc_html_e('Template Parts for Custom Styling', 'google-places-directory'); ?></h2>
            <p><?php _e('For more customized styling and layout, you can create template parts specifically for businesses:', 'google-places-directory'); ?></p>
            
            <h3><?php esc_html_e('Creating a Business Card Template Part', 'google-places-directory'); ?></h3>
            <ol>
                <li><?php _e('Go to Appearance → Editor → Template Parts', 'google-places-directory'); ?></li>
                <li><?php _e('Add a new Template Part (e.g., "Business Card")', 'google-places-directory'); ?></li>
                <li><?php _e('Design your template part using WordPress blocks', 'google-places-directory'); ?></li>
                <li><?php _e('Include shortcodes where needed (e.g., a small photo gallery)', 'google-places-directory'); ?></li>
                <li><?php _e('Use this template part in your business templates', 'google-places-directory'); ?></li>
            </ol>
            
            <h3><?php esc_html_e('Responsive Considerations', 'google-places-directory'); ?></h3>
            <p><?php _e('The plugin\'s shortcodes are designed to be responsive and work well across different screen sizes:', 'google-places-directory'); ?></p>
            <ul>
                <li><?php _e('Photo galleries adjust from 3 columns to 2 on tablets and 1 on mobile', 'google-places-directory'); ?></li>
                <li><?php _e('Maps maintain aspect ratio across screen sizes', 'google-places-directory'); ?></li>
                <li><?php _e('Search forms adapt to available width', 'google-places-directory'); ?></li>
            </ul>
            <p><?php _e('You can further customize responsiveness using block editor settings and custom CSS.', 'google-places-directory'); ?></p>
            
            <?php do_action( 'gpd_docs_fse_template_parts_after' ); ?>
        </div>
        <?php
    }
    
    /**
     * Render API documentation
     */
    private function render_api_docs() {
        ?>
        <div class="gpd-docs-section">
            <h2><?php esc_html_e('Places API (New) Information', 'google-places-directory'); ?></h2>
            <p><?php _e('This plugin uses Google\'s new Places API, which was updated in May 2025.', 'google-places-directory'); ?></p>
            
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
            <p><?php _e('Google provides a monthly free credit that often covers moderate usage. Monitor your usage in the Google Cloud Console.', 'google-places-directory'); ?></p>
            
            <h3><?php esc_html_e('Optimizing API Usage', 'google-places-directory'); ?></h3>
            <p><?php _e('To reduce API costs and improve performance:', 'google-places-directory'); ?></p>
            <ul>
                <li><?php _e('Set a reasonable photo limit in settings (3-5 is recommended)', 'google-places-directory'); ?></li>
                <li><?php _e('Import businesses in batches rather than all at once', 'google-places-directory'); ?></li>
                <li><?php _e('Only refresh photos when necessary', 'google-places-directory'); ?></li>
            </ul>
            
            <?php do_action( 'gpd_docs_api_info_after' ); ?>
        </div>
        
        <div class="gpd-docs-section">
            <h2><?php esc_html_e('Troubleshooting API Issues', 'google-places-directory'); ?></h2>
            <p><?php _e('If you encounter problems with the Google Places API:', 'google-places-directory'); ?></p>
            
            <h3><?php esc_html_e('Common Issues and Solutions', 'google-places-directory'); ?></h3>
            <table class="widefat" style="width: 95%">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Issue', 'google-places-directory'); ?></th>
                        <th><?php esc_html_e('Possible Solutions', 'google-places-directory'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php esc_html_e('API Key Error', 'google-places-directory'); ?></td>
                        <td>
                            <ul>
                                <li><?php _e('Verify your API key is correct', 'google-places-directory'); ?></li>
                                <li><?php _e('Check that Places API (New) is enabled for your key', 'google-places-directory'); ?></li>
                                <li><?php _e('Ensure billing is properly set up', 'google-places-directory'); ?></li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('Photos Not Importing', 'google-places-directory'); ?></td>
                        <td>
                            <ul>
                                <li><?php _e('Make sure photo limit is greater than 0 in settings', 'google-places-directory'); ?></li>
                                <li><?php _e('Check if business has photos available on Google', 'google-places-directory'); ?></li>
                                <li><?php _e('Try refreshing photos from the business edit screen', 'google-places-directory'); ?></li>
                                <li><?php _e('Enable WordPress debug logging to see specific errors', 'google-places-directory'); ?></li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('Quota Exceeded', 'google-places-directory'); ?></td>
                        <td>
                            <ul>
                                <li><?php _e('Check your usage in Google Cloud Console', 'google-places-directory'); ?></li>
                                <li><?php _e('Increase your quota or wait until it resets', 'google-places-directory'); ?></li>
                                <li><?php _e('Optimize your API usage by limiting requests', 'google-places-directory'); ?></li>
                            </ul>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <h3><?php esc_html_e('Testing Your API Connection', 'google-places-directory'); ?></h3>
            <p><?php _e('Use the "Test Connection" button on the Settings page to verify your API key is working properly.', 'google-places-directory'); ?></p>
            <p><?php _e('For more detailed troubleshooting, check the WordPress debug log for specific error messages.', 'google-places-directory'); ?></p>
            
            <?php do_action( 'gpd_docs_api_troubleshooting_after' ); ?>
        </div>
        <?php
    }
    
    /**
     * Static helper method to register a tab from outside the class
     * 
     * @param string $slug Tab slug/ID
     * @param string $title Tab display title
     * @param callable|null $callback Function to render tab content (null to use action hooks)
     * @param int $priority Tab display order (lower = earlier)
     * @return bool Success
     */
    public static function add_tab( $slug, $title, $callback = null, $priority = 50 ) {
        $instance = self::instance();
        return $instance->register_tab( $slug, $title, $callback, $priority );
    }

    /**
     * Register a related plugin with the documentation system
     *
     * @param string $plugin_slug Unique plugin identifier
     * @param array  $args {
     *     Plugin registration arguments
     *     @type string $title       Plugin display title
     *     @type string $version     Plugin version
     *     @type string $description Plugin description
     *     @type array  $sections    Documentation sections provided by this plugin
     *     @type string $path        Path to plugin documentation files
     * }
     * @return bool Success
     */
    public function register_plugin($plugin_slug, $args) {
        if (empty($plugin_slug)) {
            return false;
        }

        $defaults = array(
            'title'       => '',
            'version'     => '',
            'description' => '',
            'sections'    => array(),
            'path'        => '',
        );

        $args = wp_parse_args($args, $defaults);
        
        // Store plugin information
        $this->registered_plugins[$plugin_slug] = $args;

        // Register any provided documentation sections
        if (!empty($args['sections'])) {
            foreach ($args['sections'] as $section_slug => $section) {
                $this->register_section($section_slug, $section, $plugin_slug);
            }
        }

        return true;
    }

    /**
     * Register a documentation section
     *
     * @param string $section_slug Unique section identifier
     * @param array  $section {
     *     Section configuration
     *     @type string   $title      Section display title
     *     @type string   $content    Section content (HTML or markdown)
     *     @type callable $callback   Optional callback to render content
     *     @type int      $priority   Display order within tab (default: 10)
     *     @type array    $related    Related section slugs
     *     @type string   $tab        Tab to display in (default: 'general')
     * }
     * @param string $plugin_slug Plugin that provides this section
     * @return bool Success
     */
    public function register_section($section_slug, $section, $plugin_slug) {
        if (empty($section_slug)) {
            return false;
        }

        $defaults = array(
            'title'       => '',
            'content'     => '',
            'callback'    => null,
            'priority'    => 10,
            'related'     => array(),
            'tab'         => 'general',
        );

        $section = wp_parse_args($section, $defaults);
        $section['plugin'] = $plugin_slug;

        // Store the section
        $this->sections[$section_slug] = $section;

        return true;
    }

    /**
     * Get documentation for a specific plugin
     *
     * @param string $plugin_slug Plugin identifier
     * @return array Plugin documentation data
     */
    public function get_plugin_docs($plugin_slug) {
        if (!isset($this->registered_plugins[$plugin_slug])) {
            return array();
        }

        $plugin_data = $this->registered_plugins[$plugin_slug];
        $sections = array_filter($this->sections, function($section) use ($plugin_slug) {
            return $section['plugin'] === $plugin_slug;
        });

        return array(
            'info' => $plugin_data,
            'sections' => $sections
        );
    }

    /**
     * Get related documentation sections
     *
     * @param string $section_slug Section identifier
     * @return array Related documentation sections
     */
    public function get_related_sections($section_slug) {
        if (!isset($this->sections[$section_slug])) {
            return array();
        }

        $section = $this->sections[$section_slug];
        $related = array();

        if (!empty($section['related'])) {
            foreach ($section['related'] as $related_slug) {
                if (isset($this->sections[$related_slug])) {
                    $related[$related_slug] = $this->sections[$related_slug];
                }
            }
        }

        return $related;
    }

    /**
     * Render a documentation tab
     *
     * @param string $tab_slug Tab identifier
     */
    private function render_tab( $tab_slug ) {
        if ( !isset($this->tabs[$tab_slug]) ) {
            return;
        }

        $tab = $this->tabs[$tab_slug];

        echo '<div class="gpd-docs-tab-content">';

        // If there's a direct callback, use it
        if ( is_callable($tab['callback']) ) {
            call_user_func( $tab['callback'] );
        }

        // Get and render any sections for this tab
        $sections = array_filter( $this->sections, function( $section ) use ( $tab_slug ) {
            return $section['tab'] === $tab_slug;
        });

        if ( !empty($sections) ) {
            // Sort sections by priority
            uasort( $sections, function( $a, $b ) {
                return $a['priority'] - $b['priority'];
            });

            foreach ( $sections as $section_slug => $section ) {
                $this->render_section( $section_slug, $section );
            }
        }

        // Allow plugins to add content after the tab
        do_action("gpd_docs_tab_{$tab_slug}_content");

        echo '</div>';
    }

    /**
     * Render a documentation section
     *
     * @param string $section_slug Section identifier
     * @param array  $section Section data
     */
    private function render_section( $section_slug, $section ) {
        $plugin_data = isset($this->registered_plugins[$section['plugin']]) 
            ? $this->registered_plugins[$section['plugin']] 
            : null;

        echo '<div class="gpd-docs-section" id="section-' . esc_attr($section_slug) . '">';
        
        // Section header with plugin attribution if available
        echo '<div class="gpd-docs-section-header">';
        echo '<h3>' . esc_html($section['title']) . '</h3>';
        if ( $plugin_data ) {
            echo '<span class="gpd-docs-plugin-attribution">';
            echo sprintf(
                /* translators: %s: Plugin name */
                esc_html__('Provided by %s', 'google-places-directory'),
                '<a href="#plugin-' . esc_attr($section['plugin']) . '">' . 
                esc_html($plugin_data['title']) . '</a>'
            );
            echo '</span>';
        }
        echo '</div>';

        // Section content
        echo '<div class="gpd-docs-section-content">';
        if ( is_callable($section['callback']) ) {
            call_user_func($section['callback']);
        } else {
            echo wp_kses_post($section['content']);
        }
        echo '</div>';

        // Related sections if any
        $related = $this->get_related_sections($section_slug);
        if ( !empty($related) ) {
            echo '<div class="gpd-docs-related-sections">';
            echo '<h4>' . esc_html__('Related Topics', 'google-places-directory') . '</h4>';
            echo '<ul>';
            foreach ( $related as $related_slug => $related_section ) {
                printf(
                    '<li><a href="#section-%s">%s</a></li>',
                    esc_attr($related_slug),
                    esc_html($related_section['title'])
                );
            }
            echo '</ul>';
            echo '</div>';
        }

        echo '</div>';
    }
}

// Initialize the docs
GPD_Docs::instance();
