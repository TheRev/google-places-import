<?php
/**
 * Class GPD_Docs
 *
 * Provides documentation and help pages for the plugin
 * 
 * @since 2.3.0
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
        ?>
        <div class="wrap gpd-docs">
            <h1><?php esc_html_e( 'Google Places Directory Documentation', 'google-places-directory' ); ?></h1>
            
            <div class="gpd-docs-intro">
                <p><?php _e( 'This plugin allows you to import businesses from Google Places API and display them on your website.', 'google-places-directory' ); ?></p>
                <p><?php _e( 'Below you\'ll find documentation for the available shortcodes and features.', 'google-places-directory' ); ?></p>
            </div>
            
            <div class="gpd-docs-section">
                <h2><?php esc_html_e( 'Display Business Photos', 'google-places-directory' ); ?></h2>
                <p><?php _e( 'Use the <code>[gpd_photos]</code> shortcode to display photos for a specific business.', 'google-places-directory' ); ?></p>
                
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
                            <td>0</td>
                            <td><?php esc_html_e( 'Any valid business post ID', 'google-places-directory' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>business</code></td>
                            <td><?php esc_html_e( 'The business name to search for (alternative to ID)', 'google-places-directory' ); ?></td>
                            <td><?php esc_html_e( 'empty', 'google-places-directory' ); ?></td>
                            <td><?php esc_html_e( 'Any business name', 'google-places-directory' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>layout</code></td>
                            <td><?php esc_html_e( 'The gallery layout style', 'google-places-directory' ); ?></td>
                            <td>grid</td>
                            <td>grid, slider, masonry</td>
                        </tr>
                        <tr>
                            <td><code>size</code></td>
                            <td><?php esc_html_e( 'The image size to use', 'google-places-directory' ); ?></td>
                            <td>medium</td>
                            <td>thumbnail, medium, large, full</td>
                        </tr>
                        <tr>
                            <td><code>limit</code></td>
                            <td><?php esc_html_e( 'Maximum number of photos to display', 'google-places-directory' ); ?></td>
                            <td>10</td>
                            <td><?php esc_html_e( 'Any positive number', 'google-places-directory' ); ?></td>
                        </tr>
                    </tbody>
                </table>
                
                <h3><?php esc_html_e( 'Examples', 'google-places-directory' ); ?></h3>
                <p><code>[gpd_photos id="123" layout="grid" size="medium" limit="6"]</code></p>
                <p><code>[gpd_photos business="Coffee Shop" layout="slider" size="large" limit="3"]</code></p>
            </div>
            
            <div class="gpd-docs-section">
                <h2><?php esc_html_e( 'Display Business Information', 'google-places-directory' ); ?></h2>
                <p><?php _e( 'Use the <code>[gpd_business]</code> shortcode to display information about a specific business.', 'google-places-directory' ); ?></p>
                
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
                            <td>0</td>
                            <td><?php esc_html_e( 'Any valid business post ID', 'google-places-directory' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>business</code></td>
                            <td><?php esc_html_e( 'The business name to search for (alternative to ID)', 'google-places-directory' ); ?></td>
                            <td><?php esc_html_e( 'empty', 'google-places-directory' ); ?></td>
                            <td><?php esc_html_e( 'Any business name', 'google-places-directory' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>show_photos</code></td>
                            <td><?php esc_html_e( 'Whether to display photos', 'google-places-directory' ); ?></td>
                            <td>yes</td>
                            <td>yes, no</td>
                        </tr>
                        <tr>
                            <td><code>show_map</code></td>
                            <td><?php esc_html_e( 'Whether to display the map', 'google-places-directory' ); ?></td>
                            <td>yes</td>
                            <td>yes, no</td>
                        </tr>
                        <tr>
                            <td><code>photo_size</code></td>
                            <td><?php esc_html_e( 'The image size for photos', 'google-places-directory' ); ?></td>
                            <td>medium</td>
                            <td>thumbnail, medium, large, full</td>
                        </tr>
                        <tr>
                            <td><code>layout</code></td>
                            <td><?php esc_html_e( 'The display layout style', 'google-places-directory' ); ?></td>
                            <td>card</td>
                            <td>card, details</td>
                        </tr>
                    </tbody>
                </table>
                
                <h3><?php esc_html_e( 'Examples', 'google-places-directory' ); ?></h3>
                <p><code>[gpd_business id="123" layout="card" show_map="yes"]</code></p>
                <p><code>[gpd_business business="Coffee Shop" layout="details" show_photos="yes" photo_size="large"]</code></p>
            </div>
        </div>
        <?php
    }
}
