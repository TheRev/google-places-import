<?php
/**
 * Google Places Directory Developer Guide
 * This file contains comprehensive documentation for developers integrating with the plugin
 * 
 * Version: 2.3.0
 * Updated: May 2025
 */

/**
 * This guide provides extensive documentation for developers who want to:
 * - Create add-ons for Google Places Directory
 * - Customize the plugin's functionality
 * - Integrate with the plugin's data and features
 * - Extend the plugin with new shortcodes and features
 * - Access Google Places API data efficiently
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
 * Key Developer Features Available
 * 
 * 1. Filters and Actions - Modify plugin behavior without editing core files
 * 2. Custom Post Type Access - Extend the business post type with custom fields and taxonomies
 * 3. API Data Integration - Access Google Places data through the plugin's API wrapper
 * 4. Shortcode Extensions - Create custom shortcodes that integrate with the plugin
 * 5. Template Customization - Override templates for custom display of business data
 */

/**
 * Available Hooks and Filters
 * 
 * These hooks allow you to modify the plugin's behavior without editing core files.
 */
function gpd_document_available_hooks() {
    return array(
        // Data Filters
        'gpd_business_data' => 'Modify imported business data before saving',
        'gpd_display_business' => 'Filter business data before display in templates',
        'gpd_before_api_request' => 'Modify API request parameters',
        
        // Display Filters
        'gpd_map_options' => 'Customize Google Map display options',
        'gpd_search_results' => 'Filter business search results',
        'gpd_business_fields' => 'Modify available fields for display',
        
        // Action Hooks
        'gpd_after_business_import' => 'Runs after a business is imported',
        'gpd_before_places_api_request' => 'Runs before making a Places API request',
        'gpd_after_places_api_request' => 'Runs after receiving Places API response'
    );
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
        'description' => 'Advanced features for Google Places Directory including custom photo management, enhanced search capabilities, and business data extensions',
        'author'      => 'Your Company Name',
        'author_url'  => 'https://example.com',
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
/**
 * Real-World Integration Examples
 * 
 * This section contains comprehensive examples of extending Google Places Directory
 */
function my_gpd_show_integration_examples() {
    ?>
    <div class="gpd-docs-content gpd-developer-examples">
        <h3>Complete Integration Examples</h3>
        
        <ul class="gpd-example-nav">
            <li><a href="#custom-shortcodes">Creating Custom Shortcodes</a></li>
            <li><a href="#custom-fields">Adding Custom Fields</a></li>
            <li><a href="#custom-importer">Extending the Importer</a></li>
            <li><a href="#custom-templates">Creating Custom Templates</a></li>
        </ul>
        
        <div id="custom-shortcodes" class="gpd-example-section">
            <h4>Example 1: Creating a Custom Shortcode</h4>
            <p>This example shows how to create a custom shortcode that displays businesses near a specific location:</p>
            <pre><?php echo htmlspecialchars(file_get_contents(__DIR__ . '/sections/custom-shortcode-example.php')); ?></pre>
        </div>
        
        <div id="custom-fields" class="gpd-example-section">
            <h4>Example 2: Adding Custom Fields</h4>
            <p>This example demonstrates how to add custom fields to business posts and integrate them with the Google Places API import process:</p>
            <pre><?php echo htmlspecialchars(file_get_contents(__DIR__ . '/sections/custom-fields-example.php')); ?></pre>
        </div>
        
        <div id="custom-importer" class="gpd-example-section">
            <h4>Example 3: Extending the Importer</h4>
            <p>This example shows how to add a custom import source to integrate with external business directories:</p>
            <pre><?php echo htmlspecialchars(file_get_contents(__DIR__ . '/sections/custom-importer-example.php')); ?></pre>
        </div>
        
        <div id="custom-templates" class="gpd-example-section">
            <h4>Example 4: Creating Custom Templates</h4>
            <p>To create custom templates for businesses, add your template files to your theme in a folder named <code>gpd-templates</code>. The plugin will automatically look for these template files:</p>
            <ul>
                <li><code>single-business.php</code> - Single business display</li>
                <li><code>archive-business.php</code> - Business directory archive</li>
                <li><code>taxonomy-business_category.php</code> - Category archive</li>
                <li><code>taxonomy-business_region.php</code> - Region archive</li>
            </ul>
        </div>
    </div>
    <?php
}

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
