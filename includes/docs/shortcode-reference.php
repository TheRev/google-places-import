<?php
/**
 * Google Places Directory Shortcode Reference
 * Complete documentation for all available shortcodes and their parameters
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="gpd-docs-wrapper shortcode-reference">
    <h2><?php esc_html_e('Complete Shortcode Reference', 'google-places-directory'); ?></h2>
    <p><?php _e('This reference guide provides detailed information about all available shortcodes in Google Places Directory, including parameters, examples, and best practices.', 'google-places-directory'); ?></p>
    
    <div class="gpd-docs-toc">
        <h4><?php esc_html_e('Quick Navigation', 'google-places-directory'); ?></h4>
        <ul>
            <li><a href="#gpd-business-search">[gpd-business-search]</a> - <?php esc_html_e('Search and display businesses', 'google-places-directory'); ?></li>
            <li><a href="#gpd-business-map">[gpd-business-map]</a> - <?php esc_html_e('Display businesses on a map', 'google-places-directory'); ?></li>
            <li><a href="#gpd-business-info">[gpd-business-info]</a> - <?php esc_html_e('Display specific business information', 'google-places-directory'); ?></li>
            <li><a href="#gpd-meta">[gpd-meta]</a> - <?php esc_html_e('Display business metadata', 'google-places-directory'); ?></li>
            <li><a href="#gpd-photos">[gpd-photos]</a> - <?php esc_html_e('Display business photos', 'google-places-directory'); ?></li>
        </ul>
    </div>

    <!-- Business Search Shortcode -->
    <div class="shortcode-section">
        <h3 id="gpd-business-search">[gpd-business-search]</h3>
        <p class="shortcode-description"><?php _e('Creates an interactive search form and results display for businesses. This is the primary shortcode for creating a searchable business directory.', 'google-places-directory'); ?></p>
        
        <h4><?php esc_html_e('Parameters', 'google-places-directory'); ?></h4>
        <table class="gpd-docs-table">
            <thead>
                <tr>
                    <th><?php _e('Parameter', 'google-places-directory'); ?></th>
                    <th><?php _e('Description', 'google-places-directory'); ?></th>
                    <th><?php _e('Default', 'google-places-directory'); ?></th>
                    <th><?php _e('Options', 'google-places-directory'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>count</code></td>
                    <td><?php _e('Number of results to display per page', 'google-places-directory'); ?></td>
                    <td><code>10</code></td>
                    <td><?php _e('Any number (1-50)', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>radius</code></td>
                    <td><?php _e('Default search radius in kilometers', 'google-places-directory'); ?></td>
                    <td><code>25</code></td>
                    <td><?php _e('Any number (1-50)', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>category</code></td>
                    <td><?php _e('Pre-filter results by business category', 'google-places-directory'); ?></td>
                    <td><code>''</code> (empty)</td>
                    <td><?php _e('Any valid category slug', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>region</code></td>
                    <td><?php _e('Pre-filter results by region/location', 'google-places-directory'); ?></td>
                    <td><code>''</code> (empty)</td>
                    <td><?php _e('Any valid region slug', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>layout</code></td>
                    <td><?php _e('Layout style for search results', 'google-places-directory'); ?></td>
                    <td><code>grid</code></td>
                    <td><code>grid</code>, <code>list</code>, <code>compact</code></td>
                </tr>
                <tr>
                    <td><code>show_map</code></td>
                    <td><?php _e('Whether to show map with search results', 'google-places-directory'); ?></td>
                    <td><code>true</code></td>
                    <td><code>true</code>, <code>false</code></td>
                </tr>
                <tr>
                    <td><code>map_position</code></td>
                    <td><?php _e('Position of map relative to results', 'google-places-directory'); ?></td>
                    <td><code>above</code></td>
                    <td><code>above</code>, <code>below</code>, <code>right</code>, <code>left</code></td>
                </tr>
                <tr>
                    <td><code>filters</code></td>
                    <td><?php _e('Show/hide search filters', 'google-places-directory'); ?></td>
                    <td><code>true</code></td>
                    <td><code>true</code>, <code>false</code></td>
                </tr>
            </tbody>
        </table>
          <h4><?php esc_html_e('Examples', 'google-places-directory'); ?></h4>
        <div class="shortcode-examples">
            <p><strong><?php _e('Basic Usage:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-business-search]</code></pre>
            
            <p><strong><?php _e('Restaurant Search with Map on the Right:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-business-search category="restaurant" count="5" map_position="right" radius="10"]</code></pre>
            
            <p><strong><?php _e('Hotels in Downtown with List Layout and No Filters:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-business-search region="downtown" category="hotel" layout="list" filters="false"]</code></pre>
            
            <p><strong><?php _e('Coffee Shops with Compact Layout and Limited Results:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-business-search category="cafe" layout="compact" count="3" show_map="false"]</code></pre>
        </div>
        
        <h4><?php esc_html_e('Advanced Usage Tips', 'google-places-directory'); ?></h4>
        <div class="shortcode-tips">
            <ul>
                <li><?php _e('<strong>Caching Considerations:</strong> Search results are cached for 24 hours by default. If you want to display fresh results each time, add <code>cache="false"</code> to your shortcode (note: this may impact performance and API usage).', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Performance Tips:</strong> For better page load times, consider limiting results with the <code>count</code> parameter and enabling pagination with <code>pagination="true"</code>.', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Map Customization:</strong> You can customize map styles by adding custom JSON styling through the plugin settings under "Maps Settings".', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Combining with Other Shortcodes:</strong> For complex layouts, you can combine this shortcode with <code>[gpd-business-map]</code> and <code>[gpd-business-info]</code> in different areas of your page.', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Responsive Design:</strong> The <code>map_position</code> will automatically adjust on mobile devices for optimal viewing.', 'google-places-directory'); ?></li>
            </ul>
        </div>
            
            <p><strong><?php _e('Compact List without Map:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-business-search layout="compact" show_map="false" count="20"]</code></pre>
        </div>
    </div>    <!-- Business Map Shortcode -->
    <div class="shortcode-section">
        <h3 id="gpd-business-map">[gpd-business-map]</h3>
        <p class="shortcode-description"><?php _e('Displays businesses on an interactive Google Map with customizable options. This shortcode can be used independently or in conjunction with other shortcodes to create custom layouts.', 'google-places-directory'); ?></p>
        
        <h4><?php esc_html_e('Parameters', 'google-places-directory'); ?></h4>
        <table class="gpd-docs-table">
            <thead>
                <tr>
                    <th><?php _e('Parameter', 'google-places-directory'); ?></th>
                    <th><?php _e('Description', 'google-places-directory'); ?></th>
                    <th><?php _e('Default', 'google-places-directory'); ?></th>
                    <th><?php _e('Options', 'google-places-directory'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>height</code></td>
                    <td><?php _e('Height of the map in pixels', 'google-places-directory'); ?></td>
                    <td><code>400</code></td>
                    <td><?php _e('Any number', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>width</code></td>
                    <td><?php _e('Width of the map (pixels or percentage)', 'google-places-directory'); ?></td>
                    <td><code>100%</code></td>
                    <td><?php _e('Any valid CSS width', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>zoom</code></td>
                    <td><?php _e('Initial zoom level of the map', 'google-places-directory'); ?></td>
                    <td><code>12</code></td>
                    <td><?php _e('1-20 (1: world view, 20: building level)', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>category</code></td>
                    <td><?php _e('Filter businesses by category', 'google-places-directory'); ?></td>
                    <td><code>''</code> (empty)</td>
                    <td><?php _e('Any valid category slug', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>region</code></td>
                    <td><?php _e('Filter businesses by region/location', 'google-places-directory'); ?></td>
                    <td><code>''</code> (empty)</td>
                    <td><?php _e('Any valid region slug', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>center</code></td>
                    <td><?php _e('Center coordinates for the map (lat,lng)', 'google-places-directory'); ?></td>
                    <td><?php _e('Auto (based on businesses)', 'google-places-directory'); ?></td>
                    <td><?php _e('Comma-separated coordinates (e.g., "37.7749,-122.4194")', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>marker_style</code></td>
                    <td><?php _e('Style of map markers', 'google-places-directory'); ?></td>
                    <td><code>default</code></td>
                    <td><code>default</code>, <code>numbered</code>, <code>custom</code></td>
                </tr>
                <tr>
                    <td><code>count</code></td>
                    <td><?php _e('Maximum number of businesses to display', 'google-places-directory'); ?></td>
                    <td><code>20</code></td>
                    <td><?php _e('Any number (1-50)', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>info_layout</code></td>
                    <td><?php _e('Layout style for info windows', 'google-places-directory'); ?></td>
                    <td><code>basic</code></td>
                    <td><code>basic</code>, <code>detailed</code>, <code>minimal</code></td>
                </tr>                <tr>
                    <td><code>map_style</code></td>
                    <td><?php _e('Map visual style preset', 'google-places-directory'); ?></td>
                    <td><code>default</code></td>
                    <td><code>default</code>, <code>silver</code>, <code>retro</code>, <code>dark</code>, <code>night</code>, <code>custom</code></td>
                </tr>
            </tbody>
        </table>
        
        <h4><?php esc_html_e('Examples', 'google-places-directory'); ?></h4>
        <div class="shortcode-examples">
            <p><strong><?php _e('Basic Map with Default Settings:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-business-map]</code></pre>
            
            <p><strong><?php _e('Customized Map for Restaurants in Downtown:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-business-map category="restaurant" region="downtown" height="500" map_style="silver"]</code></pre>
            
            <p><strong><?php _e('Full-width Map with Custom Zoom and Detailed Info Windows:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-business-map width="100%" zoom="14" info_layout="detailed" marker_style="numbered"]</code></pre>
            
            <p><strong><?php _e('Dark-styled Map Centered on Specific Coordinates:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-business-map center="37.7749,-122.4194" map_style="dark" height="600"]</code></pre>
        </div>
        
        <h4><?php esc_html_e('Advanced Map Features', 'google-places-directory'); ?></h4>
        <div class="shortcode-tips">
            <ul>
                <li><?php _e('<strong>Custom Marker Icons:</strong> When using <code>marker_style="custom"</code>, you can upload custom marker icons in the plugin settings under "Maps Settings".', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Interactive Elements:</strong> Maps include zoom controls, Street View, and satellite view options by default. You can disable these in the plugin settings.', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Custom Map Styles:</strong> For advanced styling, set <code>map_style="custom"</code> and add JSON styles in the plugin settings.', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Clicking Behavior:</strong> When users click on markers, the info window shows business details with optional links to the single business page.', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Mobile Optimization:</strong> Maps automatically adapt to smaller screens with adjusted controls for better touch interaction.', 'google-places-directory'); ?></li>
            </ul>
        </div>
                    <td><?php _e('Initial zoom level of the map', 'google-places-directory'); ?></td>
                    <td><code>12</code></td>
                    <td><?php _e('1-20 (higher = more zoomed in)', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>category</code></td>
                    <td><?php _e('Filter businesses by category', 'google-places-directory'); ?></td>
                    <td><code>''</code> (empty)</td>
                    <td><?php _e('Any valid category slug', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>region</code></td>
                    <td><?php _e('Filter businesses by region', 'google-places-directory'); ?></td>
                    <td><code>''</code> (empty)</td>
                    <td><?php _e('Any valid region slug', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>id</code></td>
                    <td><?php _e('Show a specific business by ID', 'google-places-directory'); ?></td>
                    <td><code>''</code> (empty)</td>
                    <td><?php _e('Any valid business ID', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>count</code></td>
                    <td><?php _e('Maximum number of businesses to display', 'google-places-directory'); ?></td>
                    <td><code>25</code></td>
                    <td><?php _e('Any number', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>style</code></td>
                    <td><?php _e('Map style (predefined or custom JSON)', 'google-places-directory'); ?></td>
                    <td><code>default</code></td>
                    <td><code>default</code>, <code>silver</code>, <code>retro</code>, <code>dark</code>, <code>night</code>, <code>aubergine</code></td>
                </tr>
            </tbody>
        </table>
        
        <h4><?php esc_html_e('Examples', 'google-places-directory'); ?></h4>
        <div class="shortcode-examples">
            <p><strong><?php _e('Basic Usage:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-business-map]</code></pre>
            
            <p><strong><?php _e('Hotels in a Specific Region:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-business-map category="hotel" region="downtown" height="500" zoom="14" style="night"]</code></pre>
            
            <p><strong><?php _e('Single Business Map:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-business-map id="123" zoom="16" height="300" width="400px"]</code></pre>
        </div>
    </div>    <!-- Business Info Shortcode -->
    <div class="shortcode-section">
        <h3 id="gpd-business-info">[gpd-business-info]</h3>
        <p class="shortcode-description"><?php _e('Displays detailed information for a specific business. This versatile shortcode can be used on any page or post to display comprehensive business details pulled directly from the Google Places data.', 'google-places-directory'); ?></p>
        
        <h4><?php esc_html_e('Parameters', 'google-places-directory'); ?></h4>
        <table class="gpd-docs-table">
            <thead>
                <tr>
                    <th><?php _e('Parameter', 'google-places-directory'); ?></th>
                    <th><?php _e('Description', 'google-places-directory'); ?></th>
                    <th><?php _e('Default', 'google-places-directory'); ?></th>
                    <th><?php _e('Options', 'google-places-directory'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>id</code></td>
                    <td><?php _e('Business ID to display (required unless used on a business single page)', 'google-places-directory'); ?></td>
                    <td><?php _e('current post ID', 'google-places-directory'); ?></td>
                    <td><?php _e('Any valid business ID or "current" for current post', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>fields</code></td>
                    <td><?php _e('Specific fields to display', 'google-places-directory'); ?></td>
                    <td><code>all</code></td>
                    <td><?php _e('Comma-separated list: name,address,phone,website,hours,rating,price,email,types,description,attributes', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>layout</code></td>
                    <td><?php _e('Layout style for the information', 'google-places-directory'); ?></td>
                    <td><code>standard</code></td>
                    <td><code>standard</code>, <code>compact</code>, <code>detailed</code>, <code>card</code></td>
                </tr>
                <tr>
                    <td><code>show_map</code></td>
                    <td><?php _e('Whether to include a small map', 'google-places-directory'); ?></td>
                    <td><code>false</code></td>
                    <td><code>true</code>, <code>false</code></td>
                </tr>
                <tr>
                    <td><code>show_photos</code></td>
                    <td><?php _e('Whether to include photos', 'google-places-directory'); ?></td>
                    <td><code>false</code></td>
                    <td><code>true</code>, <code>false</code></td>
                </tr>
            </tbody>
        </table>
          <h4><?php esc_html_e('Examples', 'google-places-directory'); ?></h4>
        <div class="shortcode-examples">
            <p><strong><?php _e('Basic Usage:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-business-info id="123"]</code></pre>
            
            <p><strong><?php _e('Contact Information Only:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-business-info id="123" fields="name,address,phone,website"]</code></pre>
            
            <p><strong><?php _e('Detailed Card with Map:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-business-info id="123" layout="card" show_map="true" show_photos="true"]</code></pre>
            
            <p><strong><?php _e('Current Business Rating and Hours:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-business-info id="current" fields="name,rating,hours" layout="compact"]</code></pre>
            
            <p><strong><?php _e('Complete Business Profile with All Details:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-business-info id="123" layout="detailed" show_map="true" show_photos="true" fields="all"]</code></pre>
        </div>
        
        <h4><?php esc_html_e('Usage Tips', 'google-places-directory'); ?></h4>
        <div class="shortcode-tips">
            <ul>
                <li><?php _e('<strong>Single Business Pages:</strong> When used on a single business post, you can omit the <code>id</code> parameter to automatically display information for the current business.', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Custom Field Display:</strong> The <code>fields</code> parameter gives you granular control over what information is displayed and in what order.', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Styling:</strong> Each layout applies different CSS classes that you can target in your theme\'s stylesheet for custom styling.', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Integration with Other Shortcodes:</strong> Combine with <code>[gpd-photos]</code> and <code>[gpd-business-map]</code> to create comprehensive business profiles on any page.', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Dynamic Updates:</strong> Information displayed is automatically updated when the business data is refreshed from Google Places API.', 'google-places-directory'); ?></li>
            </ul>
        </div>
    </div>    <!-- Photos Shortcode -->
    <div class="shortcode-section">
        <h3 id="gpd-photos">[gpd-photos]</h3>
        <p class="shortcode-description"><?php _e('Displays photo gallery for a business with various layout options. This shortcode allows you to showcase high-quality photos from Google Places in responsive, interactive galleries that adapt to any theme.', 'google-places-directory'); ?></p>
        
        <h4><?php esc_html_e('Parameters', 'google-places-directory'); ?></h4>
        <table class="gpd-docs-table">
            <thead>
                <tr>
                    <th><?php _e('Parameter', 'google-places-directory'); ?></th>
                    <th><?php _e('Description', 'google-places-directory'); ?></th>
                    <th><?php _e('Default', 'google-places-directory'); ?></th>
                    <th><?php _e('Options', 'google-places-directory'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>id</code></td>
                    <td><?php _e('Business ID to display photos for', 'google-places-directory'); ?></td>
                    <td><?php _e('current post ID', 'google-places-directory'); ?></td>
                    <td><?php _e('Any valid business ID', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>count</code></td>
                    <td><?php _e('Number of photos to display', 'google-places-directory'); ?></td>
                    <td><code>9</code></td>
                    <td><?php _e('Any number', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>size</code></td>
                    <td><?php _e('Size of the photos', 'google-places-directory'); ?></td>
                    <td><code>medium</code></td>
                    <td><code>small</code>, <code>medium</code>, <code>large</code>, <code>original</code></td>
                </tr>
                <tr>
                    <td><code>layout</code></td>
                    <td><?php _e('Gallery layout style', 'google-places-directory'); ?></td>
                    <td><code>grid</code></td>
                    <td><code>grid</code>, <code>masonry</code>, <code>carousel</code>, <code>column</code></td>
                </tr>
                <tr>
                    <td><code>lightbox</code></td>
                    <td><?php _e('Enable lightbox for photo viewing', 'google-places-directory'); ?></td>
                    <td><code>true</code></td>
                    <td><code>true</code>, <code>false</code></td>
                </tr>
                <tr>
                    <td><code>columns</code></td>
                    <td><?php _e('Number of columns in grid/masonry layout', 'google-places-directory'); ?></td>
                    <td><code>3</code></td>
                    <td><?php _e('1-6', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>caption</code></td>
                    <td><?php _e('Show photo captions', 'google-places-directory'); ?></td>
                    <td><code>false</code></td>
                    <td><code>true</code>, <code>false</code></td>
                </tr>
                <tr>
                    <td><code>random</code></td>
                    <td><?php _e('Randomize photo order', 'google-places-directory'); ?></td>
                    <td><code>false</code></td>
                    <td><code>true</code>, <code>false</code></td>
                </tr>
            </tbody>
        </table>
        
        <h4><?php esc_html_e('Examples', 'google-places-directory'); ?></h4>
        <div class="shortcode-examples">
            <p><strong><?php _e('Basic Usage:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-photos id="123"]</code></pre>
            
            <p><strong><?php _e('Photo Carousel:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-photos id="123" layout="carousel" count="12" size="large" caption="true"]</code></pre>
            
            <p><strong><?php _e('Two-Column Masonry Gallery:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-photos id="123" layout="masonry" columns="2" count="8"]</code></pre>
        </div>
    </div>

    <!-- Meta Shortcode -->
    <div class="shortcode-section">
        <h3 id="gpd-meta">[gpd-meta]</h3>
        <p class="shortcode-description"><?php _e('Displays specific meta information for a business.', 'google-places-directory'); ?></p>
        
        <h4><?php esc_html_e('Parameters', 'google-places-directory'); ?></h4>
        <table class="gpd-docs-table">
            <thead>
                <tr>
                    <th><?php _e('Parameter', 'google-places-directory'); ?></th>
                    <th><?php _e('Description', 'google-places-directory'); ?></th>
                    <th><?php _e('Default', 'google-places-directory'); ?></th>
                    <th><?php _e('Options', 'google-places-directory'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>id</code></td>
                    <td><?php _e('Business ID', 'google-places-directory'); ?></td>
                    <td><?php _e('current post ID', 'google-places-directory'); ?></td>
                    <td><?php _e('Any valid business ID', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>field</code></td>
                    <td><?php _e('Meta field to display (required)', 'google-places-directory'); ?></td>
                    <td><code>''</code> (empty)</td>
                    <td><?php _e('name, address, phone, website, email, rating, price_level, business_type, hours, etc.', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>format</code></td>
                    <td><?php _e('Display format for the field', 'google-places-directory'); ?></td>
                    <td><code>raw</code></td>
                    <td><code>raw</code>, <code>html</code>, <code>icon</code></td>
                </tr>
                <tr>
                    <td><code>label</code></td>
                    <td><?php _e('Show field label', 'google-places-directory'); ?></td>
                    <td><code>false</code></td>
                    <td><code>true</code>, <code>false</code></td>
                </tr>
                <tr>
                    <td><code>default</code></td>
                    <td><?php _e('Default text if field is empty', 'google-places-directory'); ?></td>
                    <td><code>''</code> (empty)</td>
                    <td><?php _e('Any text', 'google-places-directory'); ?></td>
                </tr>
            </tbody>
        </table>
        
        <h4><?php esc_html_e('Examples', 'google-places-directory'); ?></h4>
        <div class="shortcode-examples">
            <p><strong><?php _e('Business Rating:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-meta id="123" field="rating" format="icon" label="true"]</code></pre>
            
            <p><strong><?php _e('Formatted Business Hours:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-meta id="123" field="hours" format="html"]</code></pre>
            
            <p><strong><?php _e('Phone Number with Custom Default:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-meta id="123" field="phone" default="No phone available"]</code></pre>
        </div>
    </div>

    <div class="gpd-docs-section">
        <h3 id="shortcode-tips"><?php esc_html_e('Shortcode Best Practices', 'google-places-directory'); ?></h3>
        
        <h4><?php esc_html_e('Performance Optimization', 'google-places-directory'); ?></h4>
        <ul>
            <li><?php _e('<strong>Limit API Calls:</strong> When displaying multiple businesses, use the category/region filters instead of using multiple individual shortcodes.', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Photo Size:</strong> Use appropriate photo sizes based on your layout to reduce loading times.', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Pagination:</strong> For large directories, enable pagination with reasonable page sizes (10-20 businesses).', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Cache Results:</strong> Enable the shortcode caching option in settings for better performance.', 'google-places-directory'); ?></li>
        </ul>
        
        <h4><?php esc_html_e('Layout Tips', 'google-places-directory'); ?></h4>
        <ul>
            <li><?php _e('<strong>Mobile Responsiveness:</strong> Use percentage widths and avoid fixed pixel dimensions when possible.', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Map Placement:</strong> On mobile devices, maps work better above or below results rather than side-by-side.', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Photo Galleries:</strong> For masonry layouts, 2-3 columns work best on mobile devices.', 'google-places-directory'); ?></li>
        </ul>
        
        <h4><?php esc_html_e('Advanced Usage', 'google-places-directory'); ?></h4>
        <ul>
            <li><?php _e('<strong>Dynamic IDs:</strong> You can use WordPress template tags with the do_shortcode() function to create dynamic content.', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Custom CSS:</strong> All shortcode outputs include specific CSS classes that you can target for custom styling.', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Nested Shortcodes:</strong> You can nest shortcodes within each other for complex layouts, but this may impact performance.', 'google-places-directory'); ?></li>
        </ul>
    </div>
</div>
