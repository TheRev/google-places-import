<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Documentation for developers
 * 
 * @since 2.6.0
 */
function gpd_render_developer_docs() {
    ?>
    <div class="gpd-docs-section">
        <h2><?php esc_html_e('Developer Guide', 'google-places-directory'); ?></h2>
        <p><?php _e('Google Places Directory provides several ways to extend and customize its functionality:', 'google-places-directory'); ?></p>
        
        <h3><?php esc_html_e('Actions & Filters', 'google-places-directory'); ?></h3>
        <table class="widefat" style="width: 95%">
            <thead>
                <tr>
                    <th><?php esc_html_e('Hook', 'google-places-directory'); ?></th>
                    <th><?php esc_html_e('Type', 'google-places-directory'); ?></th>
                    <th><?php esc_html_e('Description', 'google-places-directory'); ?></th>
                    <th><?php esc_html_e('Parameters', 'google-places-directory'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>gpd_before_import_business</code></td>
                    <td>Action</td>
                    <td><?php _e('Before importing a business', 'google-places-directory'); ?></td>
                    <td><code>$place_id, $data</code></td>
                </tr>
                <tr>
                    <td><code>gpd_after_import_business</code></td>
                    <td>Action</td>
                    <td><?php _e('After importing a business', 'google-places-directory'); ?></td>
                    <td><code>$post_id, $place_id, $data</code></td>
                </tr>
                <tr>
                    <td><code>gpd_business_meta</code></td>
                    <td>Filter</td>
                    <td><?php _e('Modify business meta before save', 'google-places-directory'); ?></td>
                    <td><code>$meta, $post_id, $data</code></td>
                </tr>
                <tr>
                    <td><code>gpd_photo_import_args</code></td>
                    <td>Filter</td>
                    <td><?php _e('Modify photo import settings', 'google-places-directory'); ?></td>
                    <td><code>$args, $post_id</code></td>
                </tr>
                <tr>
                    <td><code>gpd_search_query_args</code></td>
                    <td>Filter</td>
                    <td><?php _e('Modify search query arguments', 'google-places-directory'); ?></td>
                    <td><code>$args, $params</code></td>
                </tr>
            </tbody>
        </table>
        
        <h3><?php esc_html_e('Custom Field API', 'google-places-directory'); ?></h3>
        <p><?php _e('Register custom fields programmatically:', 'google-places-directory'); ?></p>
        <div class="gpd-shortcode-example">
add_filter('gpd_custom_fields', 'my_custom_fields');

function my_custom_fields($fields) {
    $fields['my-field'] = array(
        'label' => 'My Field',
        'type'  => 'text',
        'description' => 'My custom field description'
    );
    return $fields;
}
        </div>
        
        <h3><?php esc_html_e('Template Hierarchy', 'google-places-directory'); ?></h3>
        <p><?php _e('The plugin follows WordPress template hierarchy with additional templates:', 'google-places-directory'); ?></p>
        <ul>
            <li><code>single-business.php</code> - <?php _e('Single business view', 'google-places-directory'); ?></li>
            <li><code>archive-business.php</code> - <?php _e('Business archive/search results', 'google-places-directory'); ?></li>
            <li><code>taxonomy-business_category.php</code> - <?php _e('Business category archive', 'google-places-directory'); ?></li>
        </ul>
        
        <h3><?php esc_html_e('REST API Endpoints', 'google-places-directory'); ?></h3>
        <table class="widefat" style="width: 95%">
            <thead>
                <tr>
                    <th><?php esc_html_e('Endpoint', 'google-places-directory'); ?></th>
                    <th><?php esc_html_e('Method', 'google-places-directory'); ?></th>
                    <th><?php esc_html_e('Description', 'google-places-directory'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>/gpd/v1/search</code></td>
                    <td>GET</td>
                    <td><?php _e('Search businesses', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>/gpd/v1/business/&lt;id&gt;</code></td>
                    <td>GET</td>
                    <td><?php _e('Get business details', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>/gpd/v1/photos/&lt;id&gt;</code></td>
                    <td>GET</td>
                    <td><?php _e('Get business photos', 'google-places-directory'); ?></td>
                </tr>
            </tbody>
        </table>
        
        <h3><?php esc_html_e('JavaScript Events', 'google-places-directory'); ?></h3>
        <p><?php _e('Listen for plugin events in your JavaScript code:', 'google-places-directory'); ?></p>
        <div class="gpd-shortcode-example">
document.addEventListener('gpd:businessLoaded', function(e) {
    console.log('Business loaded:', e.detail);
});

document.addEventListener('gpd:searchComplete', function(e) {
    console.log('Search results:', e.detail.results);
});
        </div>
        
        <?php do_action('gpd_docs_developer_after'); ?>
    </div>
    <?php
}
