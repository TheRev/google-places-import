<?php
/**
 * Class GPD_Docs
 *
 * Provides documentation and help pages for the plugin
 * 
 * @since 2.3.0
 * @updated 2.5.0
 * @date 2025-05-20
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GPD_Docs {
    private static $instance = null;

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
    }

    /**
     * Enqueue styles for the documentation page
     */
    public function enqueue_styles($hook) {
        if ($hook === 'business_page_gpd-docs') {
            wp_enqueue_style('gpd-admin-styles', plugin_dir_url(__FILE__) . '../assets/css/admin-style.css');
            
            // Add inline styles for docs page
            wp_add_inline_style('gpd-admin-styles', '
                .gpd-docs-nav {
                    display: flex;
                    margin-bottom: 20px;
                    border-bottom: 1px solid #ccc;
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
                .gpd-extension-card {
                    background: #fff;
                    border: 1px solid #ddd;
                    padding: 15px;
                    margin-bottom: 20px;
                    border-radius: 5px;
                }
                .gpd-extension-card h3 {
                    margin-top: 0;
                    color: #2271b1;
                }
            ');
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
        // Determine which tab to show
        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'shortcodes';
        ?>
        <div class="wrap gpd-docs">
            <h1><?php esc_html_e( 'Google Places Directory Documentation', 'google-places-directory' ); ?></h1>
            
            <div class="gpd-docs-intro">
                <p><?php _e( 'This plugin allows you to import businesses from Google Places API and display them on your website using various shortcodes.', 'google-places-directory' ); ?></p>
            </div>
            
            <div class="gpd-docs-nav">
                <a href="<?php echo esc_url(add_query_arg('tab', 'shortcodes')); ?>" class="<?php echo $active_tab === 'shortcodes' ? 'active' : ''; ?>">
                    <?php esc_html_e('Shortcodes', 'google-places-directory'); ?>
                </a>
                <a href="<?php echo esc_url(add_query_arg('tab', 'import')); ?>" class="<?php echo $active_tab === 'import' ? 'active' : ''; ?>">
                    <?php esc_html_e('Importing Businesses', 'google-places-directory'); ?>
                </a>
                <a href="<?php echo esc_url(add_query_arg('tab', 'settings')); ?>" class="<?php echo $active_tab === 'settings' ? 'active' : ''; ?>">
                    <?php esc_html_e('Settings', 'google-places-directory'); ?>
                </a>
                <a href="<?php echo esc_url(add_query_arg('tab', 'api')); ?>" class="<?php echo $active_tab === 'api' ? 'active' : ''; ?>">
                    <?php esc_html_e('API Information', 'google-places-directory'); ?>
                </a>
                <a href="<?php echo esc_url(add_query_arg('tab', 'extensions')); ?>" class="<?php echo $active_tab === 'extensions' ? 'active' : ''; ?>">
                    <?php esc_html_e('Extensions', 'google-places-directory'); ?>
                </a>
            </div>
            
            <?php
            // Load the appropriate tab content
            switch ($active_tab) {
                case 'shortcodes':
                    $this->render_shortcodes_tab();
                    break;
                case 'import':
                    $this->render_import_tab();
                    break;
                case 'settings':
                    $this->render_settings_tab();
                    break;
                case 'api':
                    $this->render_api_tab();
                    break;
                case 'extensions':
                    $this->render_extensions_tab();
                    break;
                default:
                    $this->render_shortcodes_tab();
            }
            ?>
        </div>
        <?php
    }
    
    /**
     * Render the shortcodes tab
     */
    private function render_shortcodes_tab() {
        ?>
        <div class="gpd-docs-section">
            <h2><?php esc_html_e( 'Available Shortcodes', 'google-places-directory' ); ?></h2>
            <p><?php esc_html_e( 'Use these shortcodes to display businesses on your website.', 'google-places-directory' ); ?></p>
            
            <h3><?php esc_html_e( 'Business Listing', 'google-places-directory' ); ?></h3>
            <p><?php esc_html_e( 'Display a list of businesses with various filtering options.', 'google-places-directory' ); ?></p>
            <div class="gpd-shortcode-example">[gpd-business-list category="restaurants" limit="10" layout="grid" columns="3"]</div>
            
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
                        <td><code>category</code></td>
                        <td><?php esc_html_e( 'Filter by business category slug', 'google-places-directory' ); ?></td>
                        <td><em><?php esc_html_e( 'empty (all categories)', 'google-places-directory' ); ?></em></td>
                        <td><?php esc_html_e( 'Any valid category slug', 'google-places-directory' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>limit</code></td>
                        <td><?php esc_html_e( 'Maximum number of businesses to display', 'google-places-directory' ); ?></td>
                        <td>10</td>
                        <td><?php esc_html_e( 'Any positive number', 'google-places-directory' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>layout</code></td>
                        <td><?php esc_html_e( 'Display layout for the businesses', 'google-places-directory' ); ?></td>
                        <td>grid</td>
                        <td>grid, list</td>
                    </tr>
                    <tr>
                        <td><code>columns</code></td>
                        <td><?php esc_html_e( 'Number of columns for grid layout', 'google-places-directory' ); ?></td>
                        <td>3</td>
                        <td>1-6</td>
                    </tr>
                    <tr>
                        <td><code>orderby</code></td>
                        <td><?php esc_html_e( 'Sort businesses by this field', 'google-places-directory' ); ?></td>
                        <td>date</td>
                        <td>date, title, rating, random</td>
                    </tr>
                    <tr>
                        <td><code>order</code></td>
                        <td><?php esc_html_e( 'Sort order', 'google-places-directory' ); ?></td>
                        <td>DESC</td>
                        <td>ASC, DESC</td>
                    </tr>
                </tbody>
            </table>
            
            <h3><?php esc_html_e( 'Business Search', 'google-places-directory' ); ?></h3>
            <p><?php esc_html_e( 'Display a search form to find businesses.', 'google-places-directory' ); ?></p>
            <div class="gpd-shortcode-example">[gpd-business-search location_search="true" category_filter="true" rating_filter="true"]</div>
            
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
                        <td><code>location_search</code></td>
                        <td><?php esc_html_e( 'Include location search field', 'google-places-directory' ); ?></td>
                        <td>true</td>
                        <td>true, false</td>
                    </tr>
                    <tr>
                        <td><code>category_filter</code></td>
                        <td><?php esc_html_e( 'Include category dropdown', 'google-places-directory' ); ?></td>
                        <td>true</td>
                        <td>true, false</td>
                    </tr>
                    <tr>
                        <td><code>rating_filter</code></td>
                        <td><?php esc_html_e( 'Include rating filter', 'google-places-directory' ); ?></td>
                        <td>false</td>
                        <td>true, false</td>
                    </tr>
                    <tr>
                        <td><code>results_layout</code></td>
                        <td><?php esc_html_e( 'Layout for search results', 'google-places-directory' ); ?></td>
                        <td>grid</td>
                        <td>grid, list</td>
                    </tr>
                </tbody>
            </table>
            
            <h3><?php esc_html_e( 'Business Photos', 'google-places-directory' ); ?></h3>
            <p><?php esc_html_e( 'Display photos for a specific business.', 'google-places-directory' ); ?></p>
            <div class="gpd-shortcode-example">[gpd-photos id="123" layout="grid" columns="4" limit="8"]</div>
            
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
                        <td><?php esc_html_e( 'Business post ID (defaults to current post)', 'google-places-directory' ); ?></td>
                        <td>0</td>
                        <td><?php esc_html_e( 'Any valid business post ID', 'google-places-directory' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>layout</code></td>
                        <td><?php esc_html_e( 'Photo display layout', 'google-places-directory' ); ?></td>
                        <td>grid</td>
                        <td>grid, carousel, masonry</td>
                    </tr>
                    <tr>
                        <td><code>columns</code></td>
                        <td><?php esc_html_e( 'Number of columns for grid layout', 'google-places-directory' ); ?></td>
                        <td>3</td>
                        <td>1-6</td>
                    </tr>
                    <tr>
                        <td><code>limit</code></td>
                        <td><?php esc_html_e( 'Maximum number of photos to display', 'google-places-directory' ); ?></td>
                        <td>10</td>
                        <td><?php esc_html_e( 'Any positive number', 'google-places-directory' ); ?></td>
                    </tr>
                </tbody>
            </table>
            
            <h3><?php esc_html_e( 'Business Reviews', 'google-places-directory' ); ?></h3>
            <p><?php esc_html_e( 'Display Google reviews for a business.', 'google-places-directory' ); ?></p>
            <div class="gpd-shortcode-example">[gpd-reviews id="123" limit="5" layout="grid"]</div>
            
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
                        <td><?php esc_html_e( 'Business post ID (defaults to current post)', 'google-places-directory' ); ?></td>
                        <td>0</td>
                        <td><?php esc_html_e( 'Any valid business post ID', 'google-places-directory' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>limit</code></td>
                        <td><?php esc_html_e( 'Maximum number of reviews to display', 'google-places-directory' ); ?></td>
                        <td>5</td>
                        <td><?php esc_html_e( 'Any positive number', 'google-places-directory' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>layout</code></td>
                        <td><?php esc_html_e( 'Review display layout', 'google-places-directory' ); ?></td>
                        <td>list</td>
                        <td>list, grid, carousel</td>
                    </tr>
                    <tr>
                        <td><code>min_rating</code></td>
                        <td><?php esc_html_e( 'Minimum rating to display (1-5)', 'google-places-directory' ); ?></td>
                        <td>0</td>
                        <td>0-5</td>
                    </tr>
                </tbody>
            </table>
            
            <?php do_action('gdp_docs_after_shortcodes'); ?>
        </div>
        <?php
    }
    
    /**
     * Render the import tab
     */
    private function render_import_tab() {
        ?>
        <div class="gpd-docs-section">
            <h2><?php esc_html_e( 'Importing Businesses', 'google-places-directory' ); ?></h2>
            <p><?php esc_html_e( 'Learn how to import businesses from Google Places API.', 'google-places-directory' ); ?></p>
            
            <h3><?php esc_html_e( 'API Setup', 'google-places-directory' ); ?></h3>
            <p><?php esc_html_e( 'Before importing businesses, you need to set up your Google Places API key.', 'google-places-directory' ); ?></p>
            <ol>
                <li><?php esc_html_e( 'Go to Settings > Google Places Directory and enter your API key.', 'google-places-directory' ); ?></li>
                <li><?php esc_html_e( 'Make sure the Places API is enabled in your Google Cloud Console.', 'google-places-directory' ); ?></li>
            </ol>
            
            <h3><?php esc_html_e( 'Import Methods', 'google-places-directory' ); ?></h3>
            <p><?php esc_html_e( 'There are several ways to import businesses:', 'google-places-directory' ); ?></p>
            
            <h4><?php esc_html_e( '1. Single Business Import', 'google-places-directory' ); ?></h4>
            <p><?php esc_html_e( 'Import a single business using its Place ID:', 'google-places-directory' ); ?></p>
            <ol>
                <li><?php esc_html_e( 'Go to Businesses > Import', 'google-places-directory' ); ?></li>
                <li><?php esc_html_e( 'Select "Single Business" tab', 'google-places-directory' ); ?></li>
                <li><?php esc_html_e( 'Enter the Place ID and click "Import"', 'google-places-directory' ); ?></li>
            </ol>
            
            <h4><?php esc_html_e( '2. Search and Import', 'google-places-directory' ); ?></h4>
            <p><?php esc_html_e( 'Search for businesses and import multiple results:', 'google-places-directory' ); ?></p>
            <ol>
                <li><?php esc_html_e( 'Go to Businesses > Import', 'google-places-directory' ); ?></li>
                <li><?php esc_html_e( 'Select "Search and Import" tab', 'google-places-directory' ); ?></li>
                <li><?php esc_html_e( 'Enter search terms, location, and radius', 'google-places-directory' ); ?></li>
                <li><?php esc_html_e( 'Select businesses from the results and click "Import Selected"', 'google-places-directory' ); ?></li>
            </ol>
            
            <h4><?php esc_html_e( '3. Bulk Import', 'google-places-directory' ); ?></h4>
            <p><?php esc_html_e( 'Import multiple businesses from a CSV file:', 'google-places-directory' ); ?></p>
            <ol>
                <li><?php esc_html_e( 'Go to Businesses > Import', 'google-places-directory' ); ?></li>
                <li><?php esc_html_e( 'Select "Bulk Import" tab', 'google-places-directory' ); ?></li>
                <li><?php esc_html_e( 'Upload a CSV file with Place IDs (one per line)', 'google-places-directory' ); ?></li>
                <li><?php esc_html_e( 'Click "Start Import"', 'google-places-directory' ); ?></li>
            </ol>
            
            <h3><?php esc_html_e( 'Data that Gets Imported', 'google-places-directory' ); ?></h3>
            <p><?php esc_html_e( 'The following data is imported for each business:', 'google-places-directory' ); ?></p>
            <ul>
                <li><?php esc_html_e( 'Business name', 'google-places-directory' ); ?></li>
                <li><?php esc_html_e( 'Address and contact information', 'google-places-directory' ); ?></li>
                <li><?php esc_html_e( 'Business hours', 'google-places-directory' ); ?></li>
                <li><?php esc_html_e( 'Photos (if enabled in settings)', 'google-places-directory' ); ?></li>
                <li><?php esc_html_e( 'Reviews (if enabled in settings)', 'google-places-directory' ); ?></li>
                <li><?php esc_html_e( 'Ratings', 'google-places-directory' ); ?></li>
                <li><?php esc_html_e( 'Geographic coordinates', 'google-places-directory' ); ?></li>
                <li><?php esc_html_e( 'Google Maps URL', 'google-places-directory' ); ?></li>
            </ul>
            
            <h3><?php esc_html_e( 'Automatic Updates', 'google-places-directory' ); ?></h3>
            <p><?php esc_html_e( 'You can configure the plugin to automatically update business information:', 'google-places-directory' ); ?></p>
            <ol>
                <li><?php esc_html_e( 'Go to Settings > Google Places Directory', 'google-places-directory' ); ?></li>
                <li><?php esc_html_e( 'Under "Business Updates", enable "Automatic Updates"', 'google-places-directory' ); ?></li>
                <li><?php esc_html_e( 'Set the update frequency (daily, weekly, monthly)', 'google-places-directory' ); ?></li>
                <li><?php esc_html_e( 'Select which data to update', 'google-places-directory' ); ?></li>
            </ol>
        </div>
        <?php
    }
    
    /**
     * Render the settings tab
     */
    private function render_settings_tab() {
        ?>
        <div class="gpd-docs-section">
            <h2><?php esc_html_e( 'Plugin Settings', 'google-places-directory' ); ?></h2>
            <p><?php esc_html_e( 'Configuration options for Google Places Directory.', 'google-places-directory' ); ?></p>
            
            <h3><?php esc_html_e( 'API Settings', 'google-places-directory' ); ?></h3>
            <ul>
                <li><strong><?php esc_html_e( 'API Key', 'google-places-directory' ); ?></strong>: <?php esc_html_e( 'Your Google Places API key', 'google-places-directory' ); ?></li>
                <li><strong><?php esc_html_e( 'API Request Limit', 'google-places-directory' ); ?></strong>: <?php esc_html_e( 'Maximum API requests per day', 'google-places-directory' ); ?></li>
            </ul>
            
            <h3><?php esc_html_e( 'Business Settings', 'google-places-directory' ); ?></h3>
            <ul>
                <li><strong><?php esc_html_e( 'Business Slug', 'google-places-directory' ); ?></strong>: <?php esc_html_e( 'URL slug for business posts', 'google-places-directory' ); ?></li>
                <li><strong><?php esc_html_e( 'Category Slug', 'google-places-directory' ); ?></strong>: <?php esc_html_e( 'URL slug for business categories', 'google-places-directory' ); ?></li>
                <li><strong><?php esc_html_e( 'Default Category', 'google-places-directory' ); ?></strong>: <?php esc_html_e( 'Default category for imported businesses', 'google-places-directory' ); ?></li>
                <li><strong><?php esc_html_e( 'Auto-assign Categories', 'google-places-directory' ); ?></strong>: <?php esc_html_e( 'Automatically create and assign categories based on Google data', 'google-places-directory' ); ?></li>
            </ul>
            
            <h3><?php esc_html_e( 'Import Settings', 'google-places-directory' ); ?></h3>
            <ul>
                <li><strong><?php esc_html_e( 'Import Photos', 'google-places-directory' ); ?></strong>: <?php esc_html_e( 'Enable to import business photos', 'google-places-directory' ); ?></li>
                <li><strong><?php esc_html_e( 'Import Reviews', 'google-places-directory' ); ?></strong>: <?php esc_html_e( 'Enable to import business reviews', 'google-places-directory' ); ?></li>
                <li><strong><?php esc_html_e( 'Max Photos', 'google-places-directory' ); ?></strong>: <?php esc_html_e( 'Maximum number of photos to import per business', 'google-places-directory' ); ?></li>
                <li><strong><?php esc_html_e( 'Max Reviews', 'google-places-directory' ); ?></strong>: <?php esc_html_e( 'Maximum number of reviews to import per business', 'google-places-directory' ); ?></li>
                <li><strong><?php esc_html_e( 'Photo Size', 'google-places-directory' ); ?></strong>: <?php esc_html_e( 'Size of imported photos (maxwidth parameter)', 'google-places-directory' ); ?></li>
            </ul>
            
            <h3><?php esc_html_e( 'Update Settings', 'google-places-directory' ); ?></h3>
            <ul>
                <li><strong><?php esc_html_e( 'Automatic Updates', 'google-places-directory' ); ?></strong>: <?php esc_html_e( 'Enable regular updates of business data', 'google-places-directory' ); ?></li>
                <li><strong><?php esc_html_e( 'Update Frequency', 'google-places-directory' ); ?></strong>: <?php esc_html_e( 'How often to update business data', 'google-places-directory' ); ?></li>
                <li><strong><?php esc_html_e( 'Update Data', 'google-places-directory' ); ?></strong>: <?php esc_html_e( 'Which data to update (details, photos, reviews)', 'google-places-directory' ); ?></li>
            </ul>
            
            <h3><?php esc_html_e( 'Display Settings', 'google-places-directory' ); ?></h3>
            <ul>
                <li><strong><?php esc_html_e( 'Business Archive Layout', 'google-places-directory' ); ?></strong>: <?php esc_html_e( 'Layout for the business archive page', 'google-places-directory' ); ?></li>
                <li><strong><?php esc_html_e( 'Results Per Page', 'google-places-directory' ); ?></strong>: <?php esc_html_e( 'Number of businesses to show per page', 'google-places-directory' ); ?></li>
                <li><strong><?php esc_html_e( 'Default Sort Order', 'google-places-directory' ); ?></strong>: <?php esc_html_e( 'How to sort businesses by default', 'google-places-directory' ); ?></li>
            </ul>
            
            <h3><?php esc_html_e( 'Template Settings', 'google-places-directory' ); ?></h3>
            <ul>
                <li><strong><?php esc_html_e( 'Custom Templates', 'google-places-directory' ); ?></strong>: <?php esc_html_e( 'Use custom templates for business pages', 'google-places-directory' ); ?></li>
                <li><strong><?php esc_html_e( 'Override Archive', 'google-places-directory' ); ?></strong>: <?php esc_html_e( 'Override theme archive templates', 'google-places-directory' ); ?></li>
                <li><strong><?php esc_html_e( 'Override Single', 'google-places-directory' ); ?></strong>: <?php esc_html_e( 'Override theme single business templates', 'google-places-directory' ); ?></li>
            </ul>
        </div>
        <?php
    }
    
    /**
     * Render the API tab
     */
    private function render_api_tab() {
        ?>
        <div class="gpd-docs-section">
            <h2><?php esc_html_e( 'API Information', 'google-places-directory' ); ?></h2>
            <p><?php esc_html_e( 'Information about setting up and using the Google Places API.', 'google-places-directory' ); ?></p>
            
            <h3><?php esc_html_e( 'Getting an API Key', 'google-places-directory' ); ?></h3>
            <p><?php esc_html_e( 'To use this plugin, you need a Google Places API key. Here\'s how to get one:', 'google-places-directory' ); ?></p>
            <ol>
                <li><?php esc_html_e( 'Go to the Google Cloud Platform Console at https://console.cloud.google.com/', 'google-places-directory' ); ?></li>
                <li><?php esc_html_e( 'Create a new project or select an existing one', 'google-places-directory' ); ?></li>
                <li><?php esc_html_e( 'Navigate to "APIs & Services > Library"', 'google-places-directory' ); ?></li>
                <li><?php esc_html_e( 'Search for "Places API" and enable it', 'google-places-directory' ); ?></li>
                <li><?php esc_html_e( 'Go to "APIs & Services > Credentials"', 'google-places-directory' ); ?></li>
                <li><?php esc_html_e( 'Click "Create credentials" and select "API key"', 'google-places-directory' ); ?></li>
                <li><?php esc_html_e( 'Copy your new API key and paste it in the plugin settings', 'google-places-directory' ); ?></li>
                <li><?php esc_html_e( 'Recommended: Restrict the API key to only the Places API', 'google-places-directory' ); ?></li>
            </ol>
            
            <h3><?php esc_html_e( 'API Usage Limits', 'google-places-directory' ); ?></h3>
            <p><?php esc_html_e( 'Google Places API has usage limits that you should be aware of:', 'google-places-directory' ); ?></p>
            <ul>
                <li><?php esc_html_e( 'Free tier includes $200 of free usage per month', 'google-places-directory' ); ?></li>
                <li><?php esc_html_e( 'Basic data costs approximately $0.017 per request', 'google-places-directory' ); ?></li>
                <li><?php esc_html_e( 'Photo requests cost approximately $0.007 per request', 'google-places-directory' ); ?></li>
                <li><?php esc_html_e( 'Contact details requests cost approximately $0.003 per request', 'google-places-directory' ); ?></li>
            </ul>
            <p><em><?php esc_html_e( 'Note: Prices may change. Refer to Google\'s official documentation for current pricing.', 'google-places-directory' ); ?></em></p>
            
            <h3><?php esc_html_e( 'Plugin API Usage', 'google-places-directory' ); ?></h3>
            <p><?php esc_html_e( 'This plugin uses the following API endpoints:', 'google-places-directory' ); ?></p>
            <ul>
                <li><strong><?php esc_html_e( 'Places Search', 'google-places-directory' ); ?></strong>: <?php esc_html_e( 'For finding businesses by keywords', 'google-places-directory' ); ?></li>
                <li><strong><?php esc_html_e( 'Place Details', 'google-places-directory' ); ?></strong>: <?php esc_html_e( 'For getting detailed business information', 'google-places-directory' ); ?></li>
                <li><strong><?php esc_html_e( 'Place Photos', 'google-places-directory' ); ?></strong>: <?php esc_html_e( 'For retrieving business photos', 'google-places-directory' ); ?></li>
            </ul>
            
            <h3><?php esc_html_e( 'API Troubleshooting', 'google-places-directory' ); ?></h3>
            <p><?php esc_html_e( 'If you\'re experiencing issues with the API, check the following:', 'google-places-directory' ); ?></p>
            <ul>
                <li><?php esc_html_e( 'Verify your API key is correct and properly restricted', 'google-places-directory' ); ?></li>
                <li><?php esc_html_e( 'Confirm the Places API is enabled in your Google Cloud Console', 'google-places-directory' ); ?></li>
                <li><?php esc_html_e( 'Check your billing is set up (required even for free tier)', 'google-places-directory' ); ?></li>
                <li><?php esc_html_e( 'Monitor your API usage in the Google Cloud Console', 'google-places-directory' ); ?></li>
                <li><?php esc_html_e( 'Check the plugin\'s error log for specific API error messages', 'google-places-directory' ); ?></li>
            </ul>
        </div>
        <?php
    }

    /**
     * Render the extensions tab
     */
    private function render_extensions_tab() {
        ?>
        <div class="gpd-docs-section">
            <h2><?php esc_html_e( 'Extensions and Add-ons', 'google-places-directory' ); ?></h2>
            <p><?php esc_html_e( 'Enhance your business directory with these companion plugins.', 'google-places-directory' ); ?></p>
            
            <div class="gpd-extension-card">
                <h3><?php esc_html_e( 'GPD Business Maps', 'google-places-directory' ); ?></h3>
                <p><?php esc_html_e( 'Add interactive maps to your business listings using the free Leaflet maps library.', 'google-places-directory' ); ?></p>
                <p><strong><?php esc_html_e( 'Features:', 'google-places-directory' ); ?></strong></p>
                <ul>
                    <li><?php esc_html_e( 'Display business locations on interactive maps', 'google-places-directory' ); ?></li>
                    <li><?php esc_html_e( 'Show multiple businesses with marker clustering', 'google-places-directory' ); ?></li>
                    <li><?php esc_html_e( 'Link directly to Google Maps for directions', 'google-places-directory' ); ?></li>
                    <li><?php esc_html_e( 'Free alternative to Google Maps API', 'google-places-directory' ); ?></li>
                </ul>
                <p><em><?php esc_html_e( 'Note: This plugin uses the _gpd_latitude, _gpd_longitude, and _gpd_maps_uri fields from your business data.', 'google-places-directory' ); ?></em></p>
            </div>
            
            <div class="gpd-extension-card">
                <h3><?php esc_html_e( 'Gemini2 AI Business Lookup', 'google-places-directory' ); ?></h3>
                <p><?php esc_html_e( 'Generate AI-powered reviews and insights for imported businesses.', 'google-places-directory' ); ?></p>
                <p><strong><?php esc_html_e( 'Features:', 'google-places-directory' ); ?></strong></p>
                <ul>
                    <li><?php esc_html_e( 'Create AI-generated reviews for any business', 'google-places-directory' ); ?></li>
                    <li><?php esc_html_e( 'Analyze business data with AI', 'google-places-directory' ); ?></li>
                    <li><?php esc_html_e( 'Generate content suggestions based on business type', 'google-places-directory' ); ?></li>
                    <li><?php esc_html_e( 'Enhance your business listings with AI insights', 'google-places-directory' ); ?></li>
                </ul>
            </div>
            
            <div class="gpd-extension-card">
                <h3><?php esc_html_e( 'GPD Advanced Search', 'google-places-directory' ); ?></h3>
                <p><?php esc_html_e( 'Add advanced search capabilities to your business directory.', 'google-places-directory' ); ?></p>
                <p><strong><?php esc_html_e( 'Features:', 'google-places-directory' ); ?></strong></p>
                <ul>
                    <li><?php esc_html_e( 'Search businesses by distance from location', 'google-places-directory' ); ?></li>
                    <li><?php esc_html_e( 'Filter by multiple criteria simultaneously', 'google-places-directory' ); ?></li>
                    <li><?php esc_html_e( 'AJAX-powered real-time search results', 'google-places-directory' ); ?></li>
                    <li><?php esc_html_e( 'Save and share search results', 'google-places-directory' ); ?></li>
                </ul>
                <p><em><?php esc_html_e( 'Coming soon!', 'google-places-directory' ); ?></em></p>
            </div>
        </div>
        <?php
    }
}

// Initialize the docs
GPD_Docs::instance();
