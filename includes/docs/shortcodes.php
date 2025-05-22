<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Documentation for shortcodes
 * 
 * @since 2.6.0
 */
function gpd_render_shortcodes_docs() {
    ?>
    <div class="gpd-docs-section">
        <h2><?php esc_html_e('Available Shortcodes', 'google-places-directory'); ?></h2>
        <p><?php _e('Google Places Directory provides several shortcodes to display business information on your website:', 'google-places-directory'); ?></p>
        
        <ul>
            <li><code>[gpd-photos]</code> - <?php _e('Display business photos (see the Business Photos tab for details)', 'google-places-directory'); ?></li>
            <li><code>[gpd-business-search]</code> - <?php _e('Create a search form for businesses', 'google-places-directory'); ?></li>
        </ul>
        
        <?php do_action('gpd_docs_shortcodes_overview'); ?>
    </div>
    
    <div class="gpd-docs-section">
        <h2><?php esc_html_e('Business Search', 'google-places-directory'); ?></h2>
        <p><?php _e('Use the <code>[gpd-business-search]</code> shortcode to create a search form for businesses.', 'google-places-directory'); ?></p>
        
        <h3><?php esc_html_e('Parameters', 'google-places-directory'); ?></h3>
        <table class="widefat" style="width: 95%">
            <thead>
                <tr>
                    <th><?php esc_html_e('Parameter', 'google-places-directory'); ?></th>
                    <th><?php esc_html_e('Description', 'google-places-directory'); ?></th>
                    <th><?php esc_html_e('Default', 'google-places-directory'); ?></th>
                    <th><?php esc_html_e('Options', 'google-places-directory'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>location_search</code></td>
                    <td><?php esc_html_e('Enable location-based search', 'google-places-directory'); ?></td>
                    <td>true</td>
                    <td>true, false</td>
                </tr>
                <tr>
                    <td><code>results_page</code></td>
                    <td><?php esc_html_e('URL to results page (empty for AJAX)', 'google-places-directory'); ?></td>
                    <td>empty</td>
                    <td><?php esc_html_e('Any valid URL', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>default_radius</code></td>
                    <td><?php esc_html_e('Default search radius in km', 'google-places-directory'); ?></td>
                    <td>25</td>
                    <td><?php esc_html_e('Any positive number', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>default_limit</code></td>
                    <td><?php esc_html_e('Default number of results', 'google-places-directory'); ?></td>
                    <td>10</td>
                    <td><?php esc_html_e('Any positive number', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>placeholder</code></td>
                    <td><?php esc_html_e('Placeholder text for search field', 'google-places-directory'); ?></td>
                    <td>Search for businesses...</td>
                    <td><?php esc_html_e('Any text', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>class</code></td>
                    <td><?php esc_html_e('Additional CSS classes', 'google-places-directory'); ?></td>
                    <td>empty</td>
                    <td><?php esc_html_e('Any CSS class name', 'google-places-directory'); ?></td>
                </tr>
            </tbody>
        </table>
        
        <?php do_action('gpd_docs_business_search_after_parameters'); ?>
        
        <h3><?php esc_html_e('Examples', 'google-places-directory'); ?></h3>
        <div class="gpd-shortcode-example">[gpd-business-search location_search="true" default_radius="50"]</div>
        <div class="gpd-shortcode-example">[gpd-business-search results_page="/business-results/" default_limit="20"]</div>
        
        <?php do_action('gpd_docs_business_search_after_examples'); ?>
    </div>
    <?php
}
