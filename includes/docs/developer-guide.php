<?php
/**
 * Google Places Directory Developer Guide
 * This file contains documentation for developers integrating with the plugin
 */

/**
 * Basic Integration Example
 * Shows how to register a plugin with basic documentation
 */
function gpd_example_basic_integration() {
    $docs = GPD_Docs::instance();
    
    // Register your plugin
    $docs->register_plugin('my-gpd-addon', array(
        'title'       => 'My GPD Addon',
        'version'     => '1.0.0',
        'description' => 'Adds custom features to Google Places Directory',
        'path'        => plugin_dir_path(__FILE__),
        'sections'    => array(
            'my-addon-basics' => array(
                'title'    => 'Getting Started',
                'tab'      => 'settings', // Add to existing Settings tab
                'content'  => '<p>Configure the addon in the Settings page.</p>',
                'priority' => 15,
            ),
        ),
    ));
}

/**
 * Advanced Integration Example
 * Shows how to integrate with multiple tabs and dynamic content
 */
function gpd_example_advanced_integration() {
    $docs = GPD_Docs::instance();
    
    // Register your plugin
    $docs->register_plugin('gpd-advanced-features', array(
        'title'       => 'GPD Advanced Features',
        'version'     => '2.0.0',
        'description' => 'Advanced features for Google Places Directory',
        'path'        => plugin_dir_path(__FILE__),
        'sections'    => array(
            // Add to existing Photos tab
            'advanced-photo-features' => array(
                'title'    => 'Advanced Photo Features',
                'tab'      => 'photos',
                'callback' => 'gpd_render_advanced_photo_docs',
                'priority' => 25,
                'related'  => array('photo-settings', 'photo-api'),
            ),
            // Add to existing API tab
            'photo-api' => array(
                'title'    => 'Photo API Integration',
                'tab'      => 'api',
                'content'  => file_get_contents(__DIR__ . '/sections/photo-api.html'),
                'priority' => 20,
                'related'  => array('advanced-photo-features'),
            ),
            // Add to Settings tab
            'photo-settings' => array(
                'title'    => 'Photo Settings',
                'tab'      => 'settings',
                'callback' => 'gpd_render_photo_settings_docs',
                'priority' => 35,
                'related'  => array('advanced-photo-features'),
            ),
        ),
    ));
}

/**
 * Example of rendering dynamic documentation content
 */
function gpd_render_advanced_photo_docs() {
    ?>
    <div class="gpd-docs-content">
        <h3><?php esc_html_e('Advanced Photo Features', 'gpd-advanced-features'); ?></h3>
        
        <h4><?php esc_html_e('Available Features', 'gpd-advanced-features'); ?></h4>
        <ul>
            <li><?php esc_html_e('Automatic photo optimization', 'gpd-advanced-features'); ?></li>
            <li><?php esc_html_e('Custom watermarking', 'gpd-advanced-features'); ?></li>
            <li><?php esc_html_e('Advanced gallery layouts', 'gpd-advanced-features'); ?></li>
        </ul>

        <h4><?php esc_html_e('Integration Examples', 'gpd-advanced-features'); ?></h4>
        <pre><code>
// Enable automatic optimization
add_filter('gpd_photo_optimize', '__return_true');

// Add custom watermark
add_filter('gpd_photo_watermark', function($image_path) {
    // Your watermarking code here
    return $image_path;
});
        </code></pre>
    </div>
    <?php
}

/**
 * Example of rendering settings documentation
 */
function gpd_render_photo_settings_docs() {
    ?>
    <div class="gpd-docs-content">
        <h3><?php esc_html_e('Photo Settings Configuration', 'gpd-advanced-features'); ?></h3>
        
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php esc_html_e('Setting', 'gpd-advanced-features'); ?></th>
                    <th><?php esc_html_e('Description', 'gpd-advanced-features'); ?></th>
                    <th><?php esc_html_e('Default', 'gpd-advanced-features'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>optimization_level</code></td>
                    <td><?php esc_html_e('Level of photo optimization', 'gpd-advanced-features'); ?></td>
                    <td>medium</td>
                </tr>
                <tr>
                    <td><code>watermark_position</code></td>
                    <td><?php esc_html_e('Position of the watermark', 'gpd-advanced-features'); ?></td>
                    <td>bottom-right</td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php
}
