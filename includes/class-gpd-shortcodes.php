<?php
/**
 * Class GPD_Shortcodes
 *
 * Handles shortcodes for the Google Places Directory plugin. * Photo-related shortcodes have been moved to class-gpd-photo-shortcodes.php
 * 
 * @since 2.3.0
 * @modified 2.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class GPD_Shortcodes {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->init_hooks();
        }
        return self::$instance;
    }    private function init_hooks() {
        // Register shortcodes
        add_shortcode('gpd-business-search', array($this, 'business_search_shortcode'));
        add_shortcode('gpd-business-info', array($this, 'business_info_shortcode'));
        add_shortcode('gpd-business-map', array($this, 'business_map_shortcode'));
        add_shortcode('gpd-meta', array($this, 'meta_shortcode'));
        
        // Enqueue scripts and styles        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Enqueue required assets for shortcodes
     */
    public function enqueue_assets() {
        $css_file = plugin_dir_path(__FILE__) . '../assets/css/gpd-frontend.css';
        $js_file = plugin_dir_path(__FILE__) . '../assets/js/gpd-frontend.js';
        $leaflet_css_file = plugin_dir_path(__FILE__) . '../assets/css/gpd-leaflet-maps.css';
        $leaflet_js_file = plugin_dir_path(__FILE__) . '../assets/js/gpd-leaflet-maps.js';
        
        // Register Leaflet core library
        wp_register_style(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            array(),
            '1.9.4'
        );
        
        wp_register_script(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            array(),
            '1.9.4',
            true
        );
        
        // Register Leaflet Marker Cluster plugin
        wp_register_style(
            'leaflet-markercluster',
            'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css',
            array('leaflet'),
            '1.5.3'
        );
        
        wp_register_script(
            'leaflet-markercluster',
            'https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js',
            array('leaflet'),
            '1.5.3',
            true
        );
        
        // Register our Leaflet styles
        wp_register_style(
            'gpd-leaflet-maps',
            plugin_dir_url(__FILE__) . '../assets/css/gpd-leaflet-maps.css',
            array('leaflet', 'leaflet-markercluster'),
            file_exists($leaflet_css_file) ? filemtime($leaflet_css_file) : GPD_VERSION
        );
        
        // Register our Leaflet script
        wp_register_script(
            'gpd-leaflet-maps',
            plugin_dir_url(__FILE__) . '../assets/js/gpd-leaflet-maps.js',
            array('jquery', 'leaflet', 'leaflet-markercluster'),
            file_exists($leaflet_js_file) ? filemtime($leaflet_js_file) : GPD_VERSION,
            true
        );
        
        // Register styles
        wp_register_style(
            'gpd-frontend', 
            plugin_dir_url(__FILE__) . '../assets/css/gpd-frontend.css',
            array(),
            file_exists($css_file) ? filemtime($css_file) : GPD_VERSION
        );
        
        // Register scripts with Leaflet dependency
        wp_register_script(
            'gpd-frontend',
            plugin_dir_url(__FILE__) . '../assets/js/gpd-frontend.js',
            array('jquery', 'gpd-leaflet-maps'),
            file_exists($js_file) ? filemtime($js_file) : GPD_VERSION,
            true
        );        // Localize script with AJAX data for frontend functionality
        wp_localize_script('gpd-frontend', 'gpdFrontendVars', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gpd_leaflet_nonce')
        ));
    }

    /**
     * Meta field display shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function meta_shortcode($atts) {
        // Extract attributes and set defaults
        $atts = shortcode_atts(array(
            'field'    => '',                  // Meta field key (required)
            'label'    => '',                  // Optional label to display
            'format'   => 'text',              // Format: text, url, email, tel, date, price, or html
            'default'  => '',                  // Default value if meta is empty
            'bold'     => 'false',             // Make value bold (true/false)
            'size'     => '',                  // Font size (e.g., 16px, 1.2em, etc.)
            'color'    => '',                  // Text color (hex or color name)
            'align'    => '',                  // Text alignment (left, center, right)
            'class'    => '',                  // Custom CSS class
            'before'   => '',                  // Content to display before the value
            'after'    => '',                  // Content to display after the value
            'limit'    => 0,                   // Limit text length (0 = no limit)
            'ellipsis' => 'true',              // Show ellipsis when text is limited
            'icon'     => '',                  // Icon name (if supported by theme)
        ), $atts, 'gpd-meta');
        
        // Return nothing if no field is specified
        if (empty($atts['field'])) {
            return '';
        }
        
        // Get post ID (defaults to current post)
        $post_id = get_the_ID();
        
        // Get meta value
        $value = get_post_meta($post_id, $atts['field'], true);
        
        // Return default or nothing if value is empty
        if (empty($value) && $value !== '0') {
            return !empty($atts['default']) ? $atts['default'] : '';
        }
        
        // Format the value based on the format parameter
        $formatted_value = $this->format_meta_value($value, $atts);
        
        // Build inline style based on provided attributes
        $styles = array();
        
        if (filter_var($atts['bold'], FILTER_VALIDATE_BOOLEAN)) {
            $styles[] = 'font-weight: bold';
        }
        
        if (!empty($atts['size'])) {
            $styles[] = 'font-size: ' . esc_attr($atts['size']);
        }
        
        if (!empty($atts['color'])) {
            $styles[] = 'color: ' . esc_attr($atts['color']);
        }
        
        if (!empty($atts['align'])) {
            $styles[] = 'text-align: ' . esc_attr($atts['align']);
        }
        
        // Combine styles
        $style_attr = !empty($styles) ? ' style="' . esc_attr(implode('; ', $styles)) . '"' : '';
        
        // Build CSS classes
        $css_classes = array('gpd-meta-item');
        
        if (!empty($atts['class'])) {
            $css_classes[] = sanitize_html_class($atts['class']);
        }
        
        $class_attr = ' class="' . esc_attr(implode(' ', $css_classes)) . '"';
        
        // Build the output
        $output = '<div' . $class_attr . $style_attr . '>';
        
        // Add icon if specified
        if (!empty($atts['icon'])) {
            $output .= '<span class="gpd-meta-icon gpd-icon-' . esc_attr($atts['icon']) . '"></span>';
        }
        
        // Add label if specified
        if (!empty($atts['label'])) {
            $output .= '<span class="gpd-meta-label">' . esc_html($atts['label']) . ': </span>';
        }
        
        // Add before text
        if (!empty($atts['before'])) {
            $output .= '<span class="gpd-meta-before">' . esc_html($atts['before']) . '</span>';
        }
        
        // Add value
        $output .= '<span class="gpd-meta-value">' . $formatted_value . '</span>';
        
        // Add after text
        if (!empty($atts['after'])) {
            $output .= '<span class="gpd-meta-after">' . esc_html($atts['after']) . '</span>';
        }
        
        $output .= '</div>';
        
        return $output;
    }

    /**
     * Helper function to format meta values based on type
     * 
     * @param mixed $value The meta value to format
     * @param array $atts Shortcode attributes
     * @return string Formatted value
     */
    private function format_meta_value($value, $atts) {
        // Limit text if specified
        $limit = intval($atts['limit']);
        if ($limit > 0 && is_string($value) && strlen($value) > $limit) {
            $value = substr($value, 0, $limit);
            if (filter_var($atts['ellipsis'], FILTER_VALIDATE_BOOLEAN)) {
                $value .= '&hellip;';
            }
        }
        
        switch ($atts['format']) {
case 'url':
    $link_style = !empty($atts['color']) ? ' style="color: ' . esc_attr($atts['color']) . ';"' : '';
    return '<a href="' . esc_url($value) . '" target="_blank" rel="noopener noreferrer"' . $link_style . '>' . esc_html(preg_replace('#^https?://#', '', $value)) . '</a>';

case 'email':
    $link_style = !empty($atts['color']) ? ' style="color: ' . esc_attr($atts['color']) . ';"' : '';
    return '<a href="mailto:' . esc_attr($value) . '"' . $link_style . '>' . esc_html($value) . '</a>';

case 'tel':
    $link_style = !empty($atts['color']) ? ' style="color: ' . esc_attr($atts['color']) . ';"' : '';
    return '<a href="tel:' . esc_attr(preg_replace('/[^0-9+]/', '', $value)) . '"' . $link_style . '>' . esc_html($value) . '</a>';

case 'date':
    $timestamp = strtotime($value);
    return $timestamp ? date_i18n(get_option('date_format'), $timestamp) : $value;

case 'price':
    return '$' . number_format((float)$value, 2);

case 'html':
    // Use with caution - only for trusted content!
    return wp_kses_post($value);
            
            default:
                // Handle multiline text (convert newlines to <br>)
                if (strpos($value, "\n") !== false) {
                    return nl2br(esc_html($value));
                }
                return esc_html($value);
        }
    }

    /**
     * Business search shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function business_search_shortcode($atts) {
        // Enqueue required assets
        wp_enqueue_style('gpd-frontend');
        wp_enqueue_script('gpd-frontend');
        
        // Extract attributes and set defaults
        $atts = shortcode_atts(array(
            'show_map' => 'false',        // Show map with results
            'location_search' => 'true',   // Enable location-based search
            'results_page' => '',          // URL to results page (leave empty for AJAX results)
            'default_radius' => 25,        // Default search radius in km
            'default_limit' => 10,         // Default number of results
            'placeholder' => __('Search for businesses...', 'google-places-directory'),
            'class' => '',                 // Custom CSS class
        ), $atts, 'gpd-business-search');
        
        // Convert string attributes to proper types
        $atts['show_map'] = filter_var($atts['show_map'], FILTER_VALIDATE_BOOLEAN);
        $atts['location_search'] = filter_var($atts['location_search'], FILTER_VALIDATE_BOOLEAN);
        
        // Build CSS classes
        $css_classes = array('gpd-business-search-form');
        
        if (!empty($atts['class'])) {
            $css_classes[] = sanitize_html_class($atts['class']);
        }
        
        // Start output buffering
        ob_start();
        
        // Search form
        ?>
        <div class="<?php echo esc_attr(implode(' ', $css_classes)); ?>">
            <form action="<?php echo esc_url($atts['results_page'] ? $atts['results_page'] : '#'); ?>" method="get" class="gpd-search-form">
                <?php if (empty($atts['results_page'])): ?>
                    <input type="hidden" name="gpd_ajax_search" value="1">
                <?php endif; ?>
                
                <div class="gpd-search-fields">
                    <div class="gpd-search-input-wrap">
                        <input type="text" name="gpd_query" placeholder="<?php echo esc_attr($atts['placeholder']); ?>" required>
                    </div>
                    
                    <?php if ($atts['location_search']): ?>
                    <div class="gpd-location-wrap">
                        <button type="button" class="gpd-location-button" title="<?php esc_attr_e('Use my location', 'google-places-directory'); ?>">
                            <span class="gpd-icon gpd-icon-location"></span>
                        </button>
                        <input type="hidden" name="gpd_lat" value="">
                        <input type="hidden" name="gpd_lng" value="">
                    </div>
                    <?php endif; ?>
                    
                    <div class="gpd-search-button-wrap">
                        <button type="submit" class="gpd-search-button"><?php esc_html_e('Search', 'google-places-directory'); ?></button>
                    </div>
                </div>
                
                <div class="gpd-search-options">
                    <div class="gpd-search-radius">
                        <label for="gpd-radius"><?php esc_html_e('Radius:', 'google-places-directory'); ?></label>
                        <select name="gpd_radius" id="gpd-radius">
                            <option value="5"><?php esc_html_e('5 km', 'google-places-directory'); ?></option>
                            <option value="10"><?php esc_html_e('10 km', 'google-places-directory'); ?></option>
                            <option value="25" selected><?php esc_html_e('25 km', 'google-places-directory'); ?></option>
                            <option value="50"><?php esc_html_e('50 km', 'google-places-directory'); ?></option>
                            <option value="100"><?php esc_html_e('100 km', 'google-places-directory'); ?></option>
                        </select>
                    </div>
                    
                    <div class="gpd-search-limit">
                        <label for="gpd-limit"><?php esc_html_e('Results:', 'google-places-directory'); ?></label>
                        <select name="gpd_limit" id="gpd-limit">
                            <option value="5"><?php esc_html_e('5', 'google-places-directory'); ?></option>
                            <option value="10" selected><?php esc_html_e('10', 'google-places-directory'); ?></option>
                            <option value="20"><?php esc_html_e('20', 'google-places-directory'); ?></option>
                            <option value="50"><?php esc_html_e('50', 'google-places-directory'); ?></option>
                        </select>
                    </div>
                </div>
            </form>
            
            <?php if (empty($atts['results_page'])): ?>
            <div class="gpd-ajax-results" style="display:none;">
                <div class="gpd-results-header">
                    <h3><?php esc_html_e('Search Results', 'google-places-directory'); ?></h3>
                    <span class="gpd-results-count"></span>
                </div>
                
                <?php if ($atts['show_map']): ?>
                <div class="gpd-results-map" id="gpd-results-map"></div>
                <?php endif; ?>
                
                <div class="gpd-results-list"></div>
                <div class="gpd-results-loading" style="display:none;">
                    <div class="gpd-spinner"></div>
                    <p><?php esc_html_e('Searching...', 'google-places-directory'); ?></p>
                </div>
                <div class="gpd-results-error" style="display:none;"></div>
            </div>
            <?php endif; ?>
        </div>
        <?php
        
        // Return the buffered output
        return ob_get_clean();
    }
    

    /**
     * Business map shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */    public function business_map_shortcode($atts) {
        // Enqueue required assets
        wp_enqueue_style('gpd-frontend');
        wp_enqueue_style('gpd-leaflet-maps');
        wp_enqueue_script('gpd-frontend');
        wp_enqueue_script('gpd-leaflet-maps');
        
        // Extract attributes and set defaults
        $atts = shortcode_atts(array(
            'id' => 0,                   // Single business ID (defaults to current post)
            'category' => '',            // Filter by category slug
            'limit' => 100,              // Max number of businesses to show
            'height' => '400px',         // Map height
            'zoom' => 14,                // Default zoom level
            'clustering' => 'true',      // Use marker clustering
            'class' => '',               // Custom CSS class
        ), $atts, 'gpd-business-map');
        
        // Convert string attributes to proper types
        $atts['clustering'] = filter_var($atts['clustering'], FILTER_VALIDATE_BOOLEAN);
        
        // Generate unique ID for this map
        $map_id = 'gpd-map-' . uniqid();
        
        // Build CSS classes
        $css_classes = array('gpd-business-map');
        
        if (!empty($atts['class'])) {
            $css_classes[] = sanitize_html_class($atts['class']);
        }
        
        // Get business data
        $businesses = array();
        $center_lat = 0;
        $center_lng = 0;
        
        // Single business mode
        if (intval($atts['id']) > 0) {
            $business_id = intval($atts['id']);
            $lat = get_post_meta($business_id, '_gpd_latitude', true);
            $lng = get_post_meta($business_id, '_gpd_longitude', true);
            
            if ($lat && $lng) {
                $businesses[] = array(
                    'id' => $business_id,
                    'title' => get_the_title($business_id),
                    'lat' => $lat,
                    'lng' => $lng,
                    'url' => get_permalink($business_id),
                    'address' => get_post_meta($business_id, '_gpd_address', true),
                    'thumbnail' => get_the_post_thumbnail_url($business_id, 'thumbnail'),
                );
                
                $center_lat = $lat;
                $center_lng = $lng;
            }
        }
        // Multiple businesses mode
        else {
            $args = array(
                'post_type' => 'business',
                'posts_per_page' => intval($atts['limit']),
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => '_gpd_latitude',
                        'compare' => 'EXISTS',
                    ),
                    array(
                        'key' => '_gpd_longitude',
                        'compare' => 'EXISTS',
                    ),
                ),
            );
            
            // Filter by category if specified
            if (!empty($atts['category'])) {
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => 'business_category',
                        'field' => 'slug',
                        'terms' => sanitize_title($atts['category']),
                    ),
                );
            }
            
            $query = new WP_Query($args);
            
            if ($query->have_posts()) {
                $lat_sum = 0;
                $lng_sum = 0;
                $count = 0;
                
                while ($query->have_posts()) {
                    $query->the_post();
                    $business_id = get_the_ID();
                    $lat = get_post_meta($business_id, '_gpd_latitude', true);
                    $lng = get_post_meta($business_id, '_gpd_longitude', true);
                    
                    if ($lat && $lng) {
                        $businesses[] = array(
                            'id' => $business_id,
                            'title' => get_the_title(),
                            'lat' => $lat,
                            'lng' => $lng,
                            'url' => get_permalink(),
                            'address' => get_post_meta($business_id, '_gpd_address', true),
                            'thumbnail' => get_the_post_thumbnail_url($business_id, 'thumbnail'),
                        );
                        
                        $lat_sum += (float) $lat;
                        $lng_sum += (float) $lng;
                        $count++;
                    }
                }
                
                wp_reset_postdata();
                
                if ($count > 0) {
                    $center_lat = $lat_sum / $count;
                    $center_lng = $lng_sum / $count;
                }
            }
        }
        
        // No businesses with coordinates found
        if (empty($businesses)) {
            return '<p class="gpd-error">' . __('No businesses with location data found.', 'google-places-directory') . '</p>';
        }
        
        // Start output buffering
        ob_start();
          // Output map container
        ?>
        <div class="<?php echo esc_attr(implode(' ', $css_classes)); ?>">
            <div class="gpd-map-container">
                <div id="<?php echo esc_attr($map_id); ?>" class="gpd-leaflet-map" style="height:<?php echo esc_attr($atts['height']); ?>;"></div>
            </div>
        </div>
        
        <script type="text/javascript">
        (function() {
            // Initialize the map once the DOM is ready
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof gpdInitMap === 'function') {
                    gpdInitMap('<?php echo esc_js($map_id); ?>', {
                        center: {
                            lat: <?php echo floatval($center_lat); ?>,
                            lng: <?php echo floatval($center_lng); ?>
                        },
                        zoom: <?php echo intval($atts['zoom']); ?>,
                        clustering: <?php echo $atts['clustering'] ? 'true' : 'false'; ?>,
                        businesses: <?php echo json_encode($businesses); ?>
                    });                } else {
                    console.error('Leaflet map initialization function not found');
                }
            });
        })();
        </script>
        <?php
        
        // Return the buffered output
        return ob_get_clean();
    }
    
    /**
     * Business info shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function business_info_shortcode($atts) {
        // Enqueue required assets
        wp_enqueue_style('gpd-frontend');
          // Extract attributes and set defaults
        $atts = shortcode_atts(array(
            'id' => 0,                   // Business ID (defaults to current post)
            'layout' => 'standard',      // Layout style (standard, compact, detailed)
            'fields' => 'all',           // Fields to display
            'show_photos' => 'false',    // Include photo gallery
            'show_name' => 'true',       // Show business name
            'show_hours' => 'true',      // Show business hours
            'show_rating' => 'true',     // Show rating
            'class' => '',               // Custom CSS class
        ), $atts, 'gpd-business-info');
        
        // Convert string attributes to proper types
        $atts['show_name'] = filter_var($atts['show_name'], FILTER_VALIDATE_BOOLEAN);
        $atts['show_hours'] = filter_var($atts['show_hours'], FILTER_VALIDATE_BOOLEAN);
        $atts['show_rating'] = filter_var($atts['show_rating'], FILTER_VALIDATE_BOOLEAN);
        
        // Get business ID
        $business_id = intval($atts['id']);
        if ($business_id === 0) {
            $business_id = get_the_ID();
        }
        
        // Check if this is a business
        if (!$business_id || get_post_type($business_id) !== 'business') {
            return '<p class="gpd-error">' . __('No business found to display information.', 'google-places-directory') . '</p>';
        }
        
        // Get business metadata
        $business_name = get_the_title($business_id);
        $address = get_post_meta($business_id, '_gpd_address', true);
        $phone = get_post_meta($business_id, '_gpd_phone', true);
        $website = get_post_meta($business_id, '_gpd_website', true);
        $rating = get_post_meta($business_id, '_gpd_rating', true);
        $review_count = get_post_meta($business_id, '_gpd_review_count', true);
        $hours = get_post_meta($business_id, '_gpd_hours', true);
        $price_level = get_post_meta($business_id, '_gpd_price_level', true);
        
        // Build CSS classes
        $css_classes = array('gpd-business-info-wrapper');
        
        if (!empty($atts['class'])) {
            $css_classes[] = sanitize_html_class($atts['class']);
        }
        
        // Start output buffering
        ob_start();
        
        // Output container
        echo '<div class="' . esc_attr(implode(' ', $css_classes)) . '">';
        
        // Business name (if enabled)
        if ($atts['show_name']) {
            echo '<h3 class="gpd-business-name">' . esc_html($business_name) . '</h3>';
        }
        
        // Address
        if (!empty($address)) {
            echo '<div class="gpd-info-row">';
            echo '<div class="gpd-info-label">' . esc_html__('Address', 'google-places-directory') . ':</div>';
            echo '<div class="gpd-info-value">' . nl2br(esc_html($address)) . '</div>';
            echo '</div>';
        }
        
        // Phone
        if (!empty($phone)) {
            echo '<div class="gpd-info-row">';
            echo '<div class="gpd-info-label">' . esc_html__('Phone', 'google-places-directory') . ':</div>';
            echo '<div class="gpd-info-value">';
            echo '<a href="tel:' . esc_attr($phone) . '">' . esc_html($phone) . '</a>';
            echo '</div>';
            echo '</div>';
        }
        
        // Website
        if (!empty($website)) {
            echo '<div class="gpd-info-row">';
            echo '<div class="gpd-info-label">' . esc_html__('Website', 'google-places-directory') . ':</div>';
            echo '<div class="gpd-info-value">';
            echo '<a href="' . esc_url($website) . '" target="_blank" rel="noopener noreferrer">';
            echo esc_html(preg_replace('#^https?://#', '', $website));
            echo '</a>';
            echo '</div>';
            echo '</div>';
        }
        
        // Rating and review count
        if ($atts['show_rating'] && !empty($rating)) {
            echo '<div class="gpd-info-row">';
            echo '<div class="gpd-info-label">' . esc_html__('Rating', 'google-places-directory') . ':</div>';
            echo '<div class="gpd-info-value">';
            echo '<div class="gpd-stars">';
            
            // Display star rating
            $this->render_star_rating($rating);
            
            echo '</div>';
            echo '<span class="gpd-rating-value">' . esc_html(number_format($rating, 1)) . '</span>';
            
            if (!empty($review_count)) {
                echo '<span class="gpd-review-count">(' . esc_html($review_count) . ' ' . 
                     esc_html(_n('review', 'reviews', $review_count, 'google-places-directory')) . ')</span>';
            }
            
            echo '</div>';
            echo '</div>';
        }
        
        // Price level
        if (!empty($price_level)) {
            echo '<div class="gpd-info-row">';
            echo '<div class="gpd-info-label">' . esc_html__('Price', 'google-places-directory') . ':</div>';
            echo '<div class="gpd-info-value"><span class="gpd-price-level">';
            
            $price_symbols = str_repeat('$', intval($price_level));
            echo esc_html($price_symbols);
            
            echo '</span></div>';
            echo '</div>';
        }
        
        // Hours (if enabled)
        if ($atts['show_hours'] && !empty($hours) && is_array($hours)) {
            echo '<div class="gpd-info-row">';
            echo '<div class="gpd-info-label">' . esc_html__('Hours', 'google-places-directory') . ':</div>';
            echo '<div class="gpd-info-value gpd-hours">';
            
            foreach ($hours as $day => $time) {
                echo '<div class="gpd-hour-row">';
                echo '<span class="gpd-day">' . esc_html($day) . ':</span>';
                echo '<span class="gpd-time">' . esc_html($time) . '</span>';
                echo '</div>';
            }
            
            echo '</div>';
            echo '</div>';
        }
        
        // Additional custom fields
        $custom_meta_keys = apply_filters('gpd_info_shortcode_custom_fields', array(
            '_gpd_email' => __('Email', 'google-places-directory'),
            '_gpd_business_type' => __('Business Type', 'google-places-directory'),
        ), $business_id);
        
        foreach ($custom_meta_keys as $meta_key => $label) {
            $value = get_post_meta($business_id, $meta_key, true);
            
            if (!empty($value)) {
                echo '<div class="gpd-info-row">';
                echo '<div class="gpd-info-label">' . esc_html($label) . ':</div>';
                
                // Email fields get special treatment
                if (strpos($meta_key, 'email') !== false) {
                    echo '<div class="gpd-info-value">';
                    echo '<a href="mailto:' . esc_attr($value) . '">' . esc_html($value) . '</a>';
                    echo '</div>';
                } else {
                    echo '<div class="gpd-info-value">' . esc_html($value) . '</div>';
                }
                
                echo '</div>';
            }
        }
        
        echo '</div>'; // Close wrapper
        
        // Return the buffered output
        return ob_get_clean();
    }
    
    /**
     * Helper function to render star rating
     * 
     * @param float $rating Rating value (0-5)
     */
    private function render_star_rating($rating) {
        $rating = floatval($rating);
        $full_stars = floor($rating);
        $half_star = ($rating - $full_stars) >= 0.5;
        $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
        
        // Output full stars
        for ($i = 0; $i < $full_stars; $i++) {
            echo '<span class="gpd-star-full">★</span>';
        }
        
        // Output half star if needed
        if ($half_star) {
            echo '<span class="gpd-star-half">⯪</span>';
        }
        
        // Output empty stars
        for ($i = 0; $i < $empty_stars; $i++) {
            echo '<span class="gpd-star-empty">☆</span>';
        }
    }
}

// Initialize the shortcodes
GPD_Shortcodes::instance();
