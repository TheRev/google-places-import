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
        <h4><?php esc_html_e('Quick Navigation', 'google-places-directory'); ?></h4>        <ul>
            <li><a href="#gpd-business-search">[gpd-business-search]</a> - <?php esc_html_e('Interactive business directory and search', 'google-places-directory'); ?></li>
            <li><a href="#gpd-business-info">[gpd-business-info]</a> - <?php esc_html_e('Display comprehensive business details', 'google-places-directory'); ?></li>
            <li><a href="#gpd-meta">[gpd-meta]</a> - <?php esc_html_e('Display specific business fields', 'google-places-directory'); ?></li>
            <li><a href="#gpd-photos">[gpd-photos]</a> - <?php esc_html_e('Display business photo galleries', 'google-places-directory'); ?></li>
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
                    <td><code>advanced_search</code></td>
                    <td><?php _e('Enable advanced search interface with filters', 'google-places-directory'); ?></td>
                    <td><code>false</code></td>
                    <td><code>true</code>, <code>false</code></td>
                </tr>
                <tr>
                    <td><code>filters</code></td>
                    <td><?php _e('Show/hide category and region filters', 'google-places-directory'); ?></td>
                    <td><code>true</code></td>
                    <td><code>true</code>, <code>false</code></td>
                </tr>
                <tr>
                    <td><code>search_placeholder</code></td>
                    <td><?php _e('Custom placeholder text for search input', 'google-places-directory'); ?></td>
                    <td><code>Search businesses...</code></td>
                    <td><?php _e('Any text', 'google-places-directory'); ?></td>
                </tr>
            </tbody>
        </table>
          <h4><?php esc_html_e('Examples', 'google-places-directory'); ?></h4>
        <div class="shortcode-examples">
            <p><strong><?php _e('Basic Usage:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-business-search]</code></pre>
              <p><strong><?php _e('Search for Restaurants:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-business-search category="restaurant" count="5" radius="10"]</code></pre>
              <p><strong><?php _e('Hotels with List Layout:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-business-search category="hotel" layout="list" advanced_search="true"]</code></pre>
            
            <p><strong><?php _e('Filtered Directory with Custom Search:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-business-search region="downtown" category="cafe" layout="grid" search_placeholder="Find a coffee shop..."]</code></pre>
            
            <p><strong><?php _e('Compact Directory with Pagination:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-business-search layout="compact" count="10" filters="true"]</code></pre>
        </div>
        
        <h4><?php esc_html_e('Advanced Usage Tips', 'google-places-directory'); ?></h4>
        <div class="shortcode-tips">            <ul>
                <li><?php _e('<strong>Caching Considerations:</strong> Search results are cached for 24 hours by default. If you need fresh results each time, add <code>cache="false"</code> to your shortcode (note: this may impact performance and API usage).', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Performance Tips:</strong> For better page load times, consider limiting results with the <code>count</code> parameter and enabling pagination with <code>pagination="true"</code>.', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Advanced Search:</strong> Use category and region filters together to create focused business directories for specific areas or business types.', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Combining with Other Shortcodes:</strong> For complex layouts, you can combine this shortcode with <code>[gpd-business-info]</code> in different areas of your page.', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Responsive Design:</strong> The layout will automatically adjust on mobile devices for optimal viewing.', 'google-places-directory'); ?></li>
            </ul>
        </div>
              <p><strong><?php _e('Compact List View:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-business-search layout="compact" count="20"]</code></pre>
        </div>
    </div>    <!-- Business Info Shortcode -->
    <div class="shortcode-section">
        <h3 id="gpd-business-info">[gpd-business-info]</h3>        <p class="shortcode-description"><?php _e('Displays detailed business information and contact details in customizable layouts. Use this shortcode to create rich business profiles with important details like contact information, hours, ratings, and photos.', 'google-places-directory'); ?></p>
        
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
                    <td><?php _e('Fields to display (comma-separated)', 'google-places-directory'); ?></td>
                    <td><code>all</code></td>
                    <td><?php _e('name, address, phone, website, hours, rating, price, email, description, attributes', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>layout</code></td>
                    <td><?php _e('Layout style for the information', 'google-places-directory'); ?></td>
                    <td><code>standard</code></td>
                    <td><code>standard</code>, <code>compact</code>, <code>detailed</code>, <code>card</code></td>
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
              <p><strong><?php _e('Detailed Card View:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-business-info id="123" layout="card" show_photos="true"]</code></pre>
            
            <p><strong><?php _e('Current Business Rating and Hours:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-business-info id="current" fields="name,rating,hours" layout="compact"]</code></pre>
            
            <p><strong><?php _e('Complete Business Profile with All Details:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-business-info id="123" layout="detailed" show_photos="true" fields="all"]</code></pre>
        </div>
        
        <h4><?php esc_html_e('Usage Tips', 'google-places-directory'); ?></h4>
        <div class="shortcode-tips">
            <ul>
                <li><?php _e('<strong>Single Business Pages:</strong> When used on a single business post, you can omit the <code>id</code> parameter to automatically display information for the current business.', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Custom Field Display:</strong> The <code>fields</code> parameter gives you granular control over what information is displayed and in what order.', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Styling:</strong> Each layout applies different CSS classes that you can target in your theme\'s stylesheet for custom styling.', 'google-places-directory'); ?></li>                <li><?php _e('<strong>Integration with Other Shortcodes:</strong> Combine with <code>[gpd-photos]</code> to create comprehensive business profiles on any page.', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Dynamic Updates:</strong> Information displayed is automatically updated when the business data is refreshed from Google Places API.', 'google-places-directory'); ?></li>
            </ul>
        </div>
    </div>    <!-- Photos Shortcode -->    <div class="shortcode-section">
        <h3 id="gpd-photos">[gpd-photos]</h3>
        <p class="shortcode-description"><?php _e('Displays photo gallery for a business with various layout options. This shortcode allows you to showcase high-quality photos in responsive, interactive galleries that adapt to any theme.', 'google-places-directory'); ?></p>
        
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
                    <td><code>layout</code></td>
                    <td><?php _e('Gallery layout style. Each layout has unique features for different presentation needs.', 'google-places-directory'); ?></td>
                    <td><code>grid</code></td>
                    <td><code>grid</code> (uniform grid), <code>masonry</code> (Pinterest-style), <code>carousel</code> (slideshow), <code>column</code> (vertical)</td>
                </tr>
                <tr>
                    <td><code>columns</code></td>
                    <td><?php _e('Number of columns for grid/masonry layouts. Only applies to grid and masonry layouts.', 'google-places-directory'); ?></td>
                    <td><code>3</code></td>
                    <td><?php _e('1-6', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>limit</code></td>
                    <td><?php _e('Maximum number of photos to display (0 = show all)', 'google-places-directory'); ?></td>
                    <td><code>0</code></td>
                    <td><?php _e('Any number', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>size</code></td>
                    <td><?php _e('Image size to display', 'google-places-directory'); ?></td>
                    <td><code>medium</code></td>
                    <td><code>thumbnail</code>, <code>medium</code>, <code>large</code>, <code>full</code></td>
                </tr>
                <tr>
                    <td><code>show_caption</code></td>
                    <td><?php _e('Whether to display photo captions', 'google-places-directory'); ?></td>
                    <td><code>false</code></td>
                    <td><code>true</code>, <code>false</code></td>
                </tr>
                <tr>
                    <td><code>class</code></td>
                    <td><?php _e('Custom CSS class to add to the gallery wrapper', 'google-places-directory'); ?></td>
                    <td><code>''</code></td>
                    <td><?php _e('Any valid CSS class name', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>max_width</code></td>
                    <td><?php _e('Maximum width for column layout', 'google-places-directory'); ?></td>
                    <td><code>800px</code></td>
                    <td><?php _e('Any valid CSS width value', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>spacing</code></td>
                    <td><?php _e('Space between photos in column layout', 'google-places-directory'); ?></td>
                    <td><code>20px</code></td>
                    <td><?php _e('Any valid CSS measurement', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>alignment</code></td>
                    <td><?php _e('Horizontal alignment of column layout', 'google-places-directory'); ?></td>
                    <td><code>center</code></td>
                    <td><code>left</code>, <code>center</code>, <code>right</code></td>
                </tr>
            </tbody>
        </table>
        
        <h4><?php esc_html_e('Examples', 'google-places-directory'); ?></h4>
        <div class="shortcode-examples">
            <p><strong><?php _e('Basic Grid Layout:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-photos id="123" layout="grid" columns="3" limit="9"]</code></pre>
            
            <p><strong><?php _e('Masonry Layout with Captions:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-photos id="123" layout="masonry" columns="4" show_caption="true"]</code></pre>
            
            <p><strong><?php _e('Interactive Carousel:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-photos id="123" layout="carousel" size="large" show_caption="true"]</code></pre>
            
            <p><strong><?php _e('Single Column Display:', 'google-places-directory'); ?></strong></p>
            <pre><code>[gpd-photos id="123" layout="column" max_width="600px" spacing="15px" alignment="center"]</code></pre>
        </div>

        <h4><?php esc_html_e('Features', 'google-places-directory'); ?></h4>
        <ul class="shortcode-features">
            <li><?php _e('<strong>Automatic Lightbox:</strong> Full-size photo viewing with lightbox integration', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Lazy Loading:</strong> Images load as they come into view for better performance', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Responsive Design:</strong> Galleries adapt to different screen sizes', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Caption Support:</strong> Optional captions can be displayed with photos', 'google-places-directory'); ?></li>
        </ul>
        
        <h4><?php esc_html_e('Best Practices', 'google-places-directory'); ?></h4>
        <div class="shortcode-tips">
            <ul>
                <li><?php _e('<strong>Image Sizes:</strong> Use "thumbnail" or "medium" for grid layouts with many photos, "large" for featured photos or carousels.', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Performance:</strong> Use the limit parameter when displaying many photos and choose appropriate image sizes.', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Layout Selection:</strong> Use grid for uniform displays, masonry for preserving proportions, carousel for slideshows, column for simple vertical displays.', 'google-places-directory'); ?></li>
                <li><?php _e('<strong>Accessibility:</strong> Photo titles are automatically used as alt text. Enable captions when additional context is helpful.', 'google-places-directory'); ?></li>
            </ul>
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
            <li><?php _e('<strong>Efficient Querying:</strong> Use category and region filters to pre-filter results instead of loading all businesses and filtering client-side.', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Photo Management:</strong> Use appropriate photo sizes and limit the number of photos loaded per business to reduce page load times.', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Smart Pagination:</strong> For large directories, enable pagination and set reasonable page sizes (10-20 businesses per page).', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Caching Strategy:</strong> Enable the shortcode caching option in settings and use transients effectively for better performance.', 'google-places-directory'); ?></li>
        </ul>
        
        <h4><?php esc_html_e('Layout Tips', 'google-places-directory'); ?></h4>
        <ul>
            <li><?php _e('<strong>Mobile-First Design:</strong> Use responsive layouts and mobile-friendly components to ensure a great experience across all devices.', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Search Interface:</strong> Place search filters in easily accessible locations and use clear labels for better user experience.', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Photo Galleries:</strong> For masonry layouts, 2-3 columns work best on mobile devices.', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Business Cards:</strong> Keep business information concise and focus on the most important details in list views.', 'google-places-directory'); ?></li>
        </ul>
        
        <h4><?php esc_html_e('Advanced Usage', 'google-places-directory'); ?></h4>
        <ul>
            <li><?php _e('<strong>Dynamic Content:</strong> Use WordPress template tags with the do_shortcode() function to create context-aware business listings.', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Custom Styling:</strong> All shortcode outputs include specific CSS classes that you can target for custom styling and branding.', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Integration Tips:</strong> Combine shortcodes strategically to create rich business profiles while maintaining performance.', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Search Optimization:</strong> Use targeted categories and region filters to create focused, user-friendly business directories.', 'google-places-directory'); ?></li>
        </ul>
    </div>
</div>
