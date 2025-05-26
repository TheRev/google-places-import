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
                    <td><?php _e('Pre-filter results by region', 'google-places-directory'); ?></td>
                    <td><code>''</code> (empty)</td>
                    <td><?php _e('Any valid region slug', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>layout</code></td>
                    <td><?php _e('Results display layout', 'google-places-directory'); ?></td>
                    <td><code>list</code></td>
                    <td><code>list</code>, <code>grid</code>, <code>compact</code></td>
                </tr>
                <tr>
                    <td><code>show_map</code></td>
                    <td><?php _e('Display a map above results', 'google-places-directory'); ?></td>
                    <td><code>false</code></td>
                    <td><code>true</code>, <code>false</code></td>
                </tr>
                <tr>
                    <td><code>search_placeholder</code></td>
                    <td><?php _e('Custom placeholder text for search input', 'google-places-directory'); ?></td>
                    <td><?php _e('Search for businesses...', 'google-places-directory'); ?></td>
                    <td><?php _e('Any text', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>enable_location</code></td>
                    <td><?php _e('Show use my location button', 'google-places-directory'); ?></td>
                    <td><code>true</code></td>
                    <td><code>true</code>, <code>false</code></td>
                </tr>
                <tr>
                    <td><code>show_distance</code></td>
                    <td><?php _e('Show distance from search location', 'google-places-directory'); ?></td>
                    <td><code>true</code></td>
                    <td><code>true</code>, <code>false</code></td>
                </tr>
            </tbody>
        </table>
        
        <h4><?php esc_html_e('Examples', 'google-places-directory'); ?></h4>
        <p><strong><?php _e('Basic usage:', 'google-places-directory'); ?></strong></p>
        <pre><code>[gpd-business-search]</code></pre>
        
        <p><strong><?php _e('Grid layout with map:', 'google-places-directory'); ?></strong></p>
        <pre><code>[gpd-business-search layout="grid" show_map="true" count="12"]</code></pre>
        
        <p><strong><?php _e('Pre-filtered by category:', 'google-places-directory'); ?></strong></p>
        <pre><code>[gpd-business-search category="restaurant" radius="10" search_placeholder="Find a restaurant..."]</code></pre>
        
        <h4><?php esc_html_e('Best Practices', 'google-places-directory'); ?></h4>
        <ul>
            <li><?php _e('Place this shortcode on a dedicated page for your business directory', 'google-places-directory'); ?></li>
            <li><?php _e('If using multiple instances on different pages, give each a specific category or region focus', 'google-places-directory'); ?></li>
            <li><?php _e('For best performance, limit results to 15-20 per page, especially when showing maps', 'google-places-directory'); ?></li>
        </ul>
    </div>

    <!-- Business Map Shortcode -->
    <div class="shortcode-section">
        <h3 id="gpd-business-map">[gpd-business-map]</h3>
        <p class="shortcode-description"><?php _e('Displays businesses on an interactive Google Map. Allows users to click markers to see business details.', 'google-places-directory'); ?></p>
        
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
                    <td><?php _e('Width of the map (with unit)', 'google-places-directory'); ?></td>
                    <td><code>100%</code></td>
                    <td><?php _e('Any CSS width value', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>category</code></td>
                    <td><?php _e('Filter businesses by category', 'google-places-directory'); ?></td>
                    <td><code>''</code> (all)</td>
                    <td><?php _e('Any valid category slug', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>region</code></td>
                    <td><?php _e('Filter businesses by region', 'google-places-directory'); ?></td>
                    <td><code>''</code> (all)</td>
                    <td><?php _e('Any valid region slug', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>count</code></td>
                    <td><?php _e('Maximum number of businesses to display', 'google-places-directory'); ?></td>
                    <td><code>50</code></td>
                    <td><?php _e('Any number', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>center</code></td>
                    <td><?php _e('Center map on location (address or coordinates)', 'google-places-directory'); ?></td>
                    <td><?php _e('Auto', 'google-places-directory'); ?></td>
                    <td><?php _e('Address or lat,lng', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>zoom</code></td>
                    <td><?php _e('Initial zoom level', 'google-places-directory'); ?></td>
                    <td><code>12</code></td>
                    <td><?php _e('1-20', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>info_window</code></td>
                    <td><?php _e('Info window display style', 'google-places-directory'); ?></td>
                    <td><code>basic</code></td>
                    <td><code>basic</code>, <code>detailed</code>, <code>none</code></td>
                </tr>
            </tbody>
        </table>
        
        <h4><?php esc_html_e('Examples', 'google-places-directory'); ?></h4>
        <p><strong><?php _e('Basic map:', 'google-places-directory'); ?></strong></p>
        <pre><code>[gpd-business-map height="500"]</code></pre>
        
        <p><strong><?php _e('Filtered map with custom center:', 'google-places-directory'); ?></strong></p>
        <pre><code>[gpd-business-map category="cafe" center="Seattle, WA" zoom="14" info_window="detailed"]</code></pre>
        
        <h4><?php esc_html_e('Best Practices', 'google-places-directory'); ?></h4>
        <ul>
            <li><?php _e('For better performance, limit the number of markers (businesses) displayed on the map', 'google-places-directory'); ?></li>
            <li><?php _e('Set a specific category or region to create focused maps', 'google-places-directory'); ?></li>
            <li><?php _e('Use height parameter to ensure the map is appropriately sized for your page layout', 'google-places-directory'); ?></li>
        </ul>
    </div>

    <!-- Business Info Shortcode -->
    <div class="shortcode-section">
        <h3 id="gpd-business-info">[gpd-business-info]</h3>
        <p class="shortcode-description"><?php _e('Displays detailed information about a specific business or the current business post.', 'google-places-directory'); ?></p>
        
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
                    <td><?php _e('Business post ID', 'google-places-directory'); ?></td>
                    <td><?php _e('Current post', 'google-places-directory'); ?></td>
                    <td><?php _e('Any valid business post ID', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>layout</code></td>
                    <td><?php _e('Information layout style', 'google-places-directory'); ?></td>
                    <td><code>standard</code></td>
                    <td><code>standard</code>, <code>compact</code>, <code>detailed</code></td>
                </tr>
                <tr>
                    <td><code>show_map</code></td>
                    <td><?php _e('Display a map with the business location', 'google-places-directory'); ?></td>
                    <td><code>true</code></td>
                    <td><code>true</code>, <code>false</code></td>
                </tr>
                <tr>
                    <td><code>show_hours</code></td>
                    <td><?php _e('Display business hours', 'google-places-directory'); ?></td>
                    <td><code>true</code></td>
                    <td><code>true</code>, <code>false</code></td>
                </tr>
                <tr>
                    <td><code>show_photos</code></td>
                    <td><?php _e('Display business photos', 'google-places-directory'); ?></td>
                    <td><code>true</code></td>
                    <td><code>true</code>, <code>false</code></td>
                </tr>
                <tr>
                    <td><code>show_rating</code></td>
                    <td><?php _e('Display rating and reviews', 'google-places-directory'); ?></td>
                    <td><code>true</code></td>
                    <td><code>true</code>, <code>false</code></td>
                </tr>
                <tr>
                    <td><code>photo_count</code></td>
                    <td><?php _e('Number of photos to display', 'google-places-directory'); ?></td>
                    <td><code>5</code></td>
                    <td><?php _e('Any number', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>map_height</code></td>
                    <td><?php _e('Height of the map in pixels', 'google-places-directory'); ?></td>
                    <td><code>300</code></td>
                    <td><?php _e('Any number', 'google-places-directory'); ?></td>
                </tr>
            </tbody>
        </table>
        
        <h4><?php esc_html_e('Examples', 'google-places-directory'); ?></h4>
        <p><strong><?php _e('Basic usage (on a business post):', 'google-places-directory'); ?></strong></p>
        <pre><code>[gpd-business-info]</code></pre>
        
        <p><strong><?php _e('Display specific business anywhere:', 'google-places-directory'); ?></strong></p>
        <pre><code>[gpd-business-info id="123" layout="detailed"]</code></pre>
        
        <p><strong><?php _e('Compact business info without photos or map:', 'google-places-directory'); ?></strong></p>
        <pre><code>[gpd-business-info layout="compact" show_map="false" show_photos="false"]</code></pre>
        
        <h4><?php esc_html_e('Best Practices', 'google-places-directory'); ?></h4>
        <ul>
            <li><?php _e('Use on single business pages or to feature specific businesses on other pages', 'google-places-directory'); ?></li>
            <li><?php _e('For a full business page experience, combine with [gpd-photos] to display a complete photo gallery', 'google-places-directory'); ?></li>
            <li><?php _e('Use the compact layout when space is limited or when displaying multiple businesses on one page', 'google-places-directory'); ?></li>
        </ul>
    </div>

    <!-- Meta Shortcode -->
    <div class="shortcode-section">
        <h3 id="gpd-meta">[gpd-meta]</h3>
        <p class="shortcode-description"><?php _e('Displays specific metadata fields from a business.', 'google-places-directory'); ?></p>
        
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
                    <td><?php _e('Business post ID', 'google-places-directory'); ?></td>
                    <td><?php _e('Current post', 'google-places-directory'); ?></td>
                    <td><?php _e('Any valid business post ID', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>field</code> (required)</td>
                    <td><?php _e('Meta field to display', 'google-places-directory'); ?></td>
                    <td><?php _e('None', 'google-places-directory'); ?></td>
                    <td><?php _e('See valid fields list below', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>before</code></td>
                    <td><?php _e('Content to display before the field', 'google-places-directory'); ?></td>
                    <td><code>''</code> (empty)</td>
                    <td><?php _e('Any text/HTML', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>after</code></td>
                    <td><?php _e('Content to display after the field', 'google-places-directory'); ?></td>
                    <td><code>''</code> (empty)</td>
                    <td><?php _e('Any text/HTML', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>format</code></td>
                    <td><?php _e('Format for specific field types', 'google-places-directory'); ?></td>
                    <td><?php _e('Varies by field', 'google-places-directory'); ?></td>
                    <td><?php _e('See field-specific formats below', 'google-places-directory'); ?></td>
                </tr>
            </tbody>
        </table>
        
        <h4><?php esc_html_e('Available Meta Fields', 'google-places-directory'); ?></h4>
        <ul>
            <li><code>address</code> - <?php _e('Full formatted address', 'google-places-directory'); ?></li>
            <li><code>phone</code> - <?php _e('Phone number', 'google-places-directory'); ?></li>
            <li><code>email</code> - <?php _e('Email address (if available)', 'google-places-directory'); ?></li>
            <li><code>website</code> - <?php _e('Website URL', 'google-places-directory'); ?></li>
            <li><code>rating</code> - <?php _e('Average rating (1-5)', 'google-places-directory'); ?></li>
            <li><code>reviews</code> - <?php _e('Number of reviews', 'google-places-directory'); ?></li>
            <li><code>price_level</code> - <?php _e('Price level (1-4 dollar signs)', 'google-places-directory'); ?></li>
            <li><code>business_type</code> - <?php _e('Primary business type/category', 'google-places-directory'); ?></li>
            <li><code>lat</code> - <?php _e('Latitude coordinate', 'google-places-directory'); ?></li>
            <li><code>lng</code> - <?php _e('Longitude coordinate', 'google-places-directory'); ?></li>
            <li><code>hours</code> - <?php _e('Opening hours', 'google-places-directory'); ?></li>
            <li><code>place_id</code> - <?php _e('Google Place ID', 'google-places-directory'); ?></li>
        </ul>
        
        <h4><?php esc_html_e('Examples', 'google-places-directory'); ?></h4>
        <p><strong><?php _e('Display business phone number:', 'google-places-directory'); ?></strong></p>
        <pre><code>[gpd-meta field="phone" before="Call us: "]</code></pre>
        
        <p><strong><?php _e('Display formatted address:', 'google-places-directory'); ?></strong></p>
        <pre><code>[gpd-meta field="address" before="<div class='location'>Address: " after="</div>"]</code></pre>
        
        <p><strong><?php _e('Display rating with custom format:', 'google-places-directory'); ?></strong></p>
        <pre><code>[gpd-meta field="rating" format="stars" before="Rating: "]</code></pre>
        
        <h4><?php esc_html_e('Field-Specific Formats', 'google-places-directory'); ?></h4>
        <ul>
            <li><strong>rating:</strong> <code>stars</code> (displays star icons), <code>decimal</code> (numeric with 1 decimal), <code>plain</code> (numeric)</li>
            <li><strong>website:</strong> <code>link</code> (displays as clickable link), <code>url</code> (plain text URL)</li>
            <li><strong>price_level:</strong> <code>symbol</code> (dollar signs), <code>text</code> (descriptive text)</li>
            <li><strong>hours:</strong> <code>list</code> (full list format), <code>today</code> (today's hours only), <code>status</code> (open/closed now)</li>
        </ul>
    </div>

    <!-- Photos Shortcode -->
    <div class="shortcode-section">
        <h3 id="gpd-photos">[gpd-photos]</h3>
        <p class="shortcode-description"><?php _e('Displays a gallery of photos for a business.', 'google-places-directory'); ?></p>
        
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
                    <td><?php _e('Business post ID', 'google-places-directory'); ?></td>
                    <td><?php _e('Current post', 'google-places-directory'); ?></td>
                    <td><?php _e('Any valid business post ID', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>count</code></td>
                    <td><?php _e('Number of photos to display', 'google-places-directory'); ?></td>
                    <td><code>10</code></td>
                    <td><?php _e('Any number or "all"', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>size</code></td>
                    <td><?php _e('Photo display size', 'google-places-directory'); ?></td>
                    <td><code>medium</code></td>
                    <td><code>thumbnail</code>, <code>medium</code>, <code>large</code>, <code>original</code></td>
                </tr>
                <tr>
                    <td><code>layout</code></td>
                    <td><?php _e('Gallery layout style', 'google-places-directory'); ?></td>
                    <td><code>grid</code></td>
                    <td><code>grid</code>, <code>slider</code>, <code>masonry</code></td>
                </tr>
                <tr>
                    <td><code>columns</code></td>
                    <td><?php _e('Number of columns for grid layout', 'google-places-directory'); ?></td>
                    <td><code>3</code></td>
                    <td><code>1-6</code></td>
                </tr>
                <tr>
                    <td><code>lightbox</code></td>
                    <td><?php _e('Enable lightbox for photo viewing', 'google-places-directory'); ?></td>
                    <td><code>true</code></td>
                    <td><code>true</code>, <code>false</code></td>
                </tr>
                <tr>
                    <td><code>caption</code></td>
                    <td><?php _e('Show photo captions/attribution', 'google-places-directory'); ?></td>
                    <td><code>false</code></td>
                    <td><code>true</code>, <code>false</code></td>
                </tr>
            </tbody>
        </table>
        
        <h4><?php esc_html_e('Examples', 'google-places-directory'); ?></h4>
        <p><strong><?php _e('Basic photo gallery:', 'google-places-directory'); ?></strong></p>
        <pre><code>[gpd-photos]</code></pre>
        
        <p><strong><?php _e('Slider layout with large images:', 'google-places-directory'); ?></strong></p>
        <pre><code>[gpd-photos layout="slider" size="large" count="5"]</code></pre>
        
        <p><strong><?php _e('Photo grid with captions:', 'google-places-directory'); ?></strong></p>
        <pre><code>[gpd-photos layout="grid" columns="4" caption="true"]</code></pre>
        
        <h4><?php esc_html_e('Best Practices', 'google-places-directory'); ?></h4>
        <ul>
            <li><?php _e('Use the slider layout for featured photos at the top of a page', 'google-places-directory'); ?></li>
            <li><?php _e('Grid layouts work well for displaying multiple photos in a compact format', 'google-places-directory'); ?></li>
            <li><?php _e('Keep photo count reasonable (under 20) for better page performance', 'google-places-directory'); ?></li>
            <li><?php _e('Enable lightbox for better user experience when viewing full-size photos', 'google-places-directory'); ?></li>
        </ul>
        
        <div class="gpd-notice gpd-notice-info">
            <p><?php _e('<strong>Note:</strong> Google Places photos are subject to Google\'s attribution requirements. Always display proper attribution when showing photos.', 'google-places-directory'); ?></p>
        </div>
    </div>

    <div class="gpd-docs-section">
        <h3><?php esc_html_e('Advanced Shortcode Usage', 'google-places-directory'); ?></h3>
        
        <h4><?php esc_html_e('Combining Shortcodes', 'google-places-directory'); ?></h4>
        <p><?php _e('You can combine multiple shortcodes to create comprehensive business display pages:', 'google-places-directory'); ?></p>
        
        <pre><code>&lt;div class="business-directory-page"&gt;
    &lt;h2&gt;Find a Business Near You&lt;/h2&gt;
    [gpd-business-search layout="grid" count="6" show_map="true"]
    
    &lt;h2&gt;Featured Business&lt;/h2&gt;
    [gpd-business-info id="123" layout="detailed"]
    
    &lt;h2&gt;Photo Gallery&lt;/h2&gt;
    [gpd-photos id="123" layout="slider" count="5"]
&lt;/div&gt;</code></pre>
        
        <h4><?php esc_html_e('Using Shortcodes in Templates', 'google-places-directory'); ?></h4>
        <p><?php _e('You can also use shortcodes directly in your theme templates with the do_shortcode function:', 'google-places-directory'); ?></p>
        
        <pre><code>&lt;?php 
// Display a business search in your template
echo do_shortcode('[gpd-business-search]');

// Display business info with specific parameters
echo do_shortcode('[gpd-business-info id="' . get_the_ID() . '" layout="compact"]');
?&gt;</code></pre>
        
        <h4><?php esc_html_e('Block Editor Integration', 'google-places-directory'); ?></h4>
        <p><?php _e('All shortcodes are also available as blocks in the WordPress block editor. Look for the "Google Places Directory" category when adding a new block.', 'google-places-directory'); ?></p>
    </div>
</div>
