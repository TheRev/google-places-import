<?php
/**
 * Class GPD_Shortcodes
 *
 * Handles all shortcodes for the Google Places Directory plugin.
 * 
 * @since 2.5.0
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
    }

    private function init_hooks() {
        // Register shortcodes
        add_shortcode('gpd-photos', array($this, 'photos_shortcode'));
        add_shortcode('gpd-business-search', array($this, 'business_search_shortcode'));
        add_shortcode('gpd-business-map', array($this, 'business_map_shortcode'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Enqueue required assets for shortcodes
     */
    public function enqueue_assets() {
        // Register styles
        wp_register_style(
            'gpd-frontend', 
            plugin_dir_url(__FILE__) . '../assets/css/gpd-frontend.css',
            array(),
            '2.5.0'
        );
        
        // Register scripts
        wp_register_script(
            'gpd-lightbox',
            plugin_dir_url(__FILE__) . '../assets/js/gpd-lightbox.js',
            array('jquery'),
            '2.5.0',
            true
        );
        
        wp_register_script(
            'gpd-frontend',
            plugin_dir_url(__FILE__) . '../assets/js/gpd-frontend.js',
            array('jquery', 'gpd-lightbox'),
            '2.5.0',
            true
        );
    }

    /**
     * Photo gallery shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function photos_shortcode($atts) {
        // Enqueue required assets
        wp_enqueue_style('gpd-frontend');
        wp_enqueue_script('gpd-lightbox');
        wp_enqueue_script('gpd-frontend');
        
        // Extract attributes and set defaults
        $atts = shortcode_atts(array(
            'id' => 0,                   // Business ID (defaults to current post)
            'layout' => 'grid',          // Options: grid, masonry, carousel
            'columns' => 3,              // Number of columns for grid layout
            'limit' => 0,                // Max number of photos (0 = all)
            'size' => 'medium',          // Image size: thumbnail, medium, large, full
            'show_caption' => 'false',   // Show captions
            'class' => '',               // Custom CSS class
        ), $atts, 'gpd-photos');
        
        // Convert string to boolean for show_caption
        $atts['show_caption'] = filter_var($atts['show_caption'], FILTER_VALIDATE_BOOLEAN);
        
        // Get business ID
        $business_id = intval($atts['id']);
        if ($business_id === 0) {
            $business_id = get_the_ID();
        }
        
        // Check if this is a business
        if (!$business_id || get_post_type($business_id) !== 'business') {
            return '<p class="gpd-error">' . __('No business found to display photos.', 'google-places-directory') . '</p>';
        }
        
        // Get photos
        $photo_refs = get_post_meta($business_id, '_gpd_photo_references', true);
        if (!is_array($photo_refs) || empty($photo_refs)) {
            return '<p class="gpd-no-photos">' . __('No photos available for this business.', 'google-places-directory') . '</p>';
        }
        
        // Query for photo attachments
        $args = array(
            'post_type' => 'attachment',
            'posts_per_page' => intval($atts['limit']) > 0 ? intval($atts['limit']) : -1,
            'meta_query' => array(
                array(
                    'key' => '_gpd_photo_reference',
                    'value' => $photo_refs,
                    'compare' => 'IN',
                ),
            ),
            'orderby' => 'post__in',
            'post_status' => 'inherit',
        );
        
        $photos = get_posts($args);
        
        if (empty($photos)) {
            return '<p class="gpd-no-photos">' . __('No photos available for this business.', 'google-places-directory') . '</p>';
        }
        
        // Build CSS classes
        $css_classes = array(
            'gpd-photos-gallery',
            'gpd-layout-' . sanitize_html_class($atts['layout']),
            'gpd-columns-' . intval($atts['columns'])
        );
        
        if (!empty($atts['class'])) {
            $css_classes[] = sanitize_html_class($atts['class']);
        }
        
        // Start output buffering
        ob_start();
        
        // Output container
        echo '<div class="' . esc_attr(implode(' ', $css_classes)) . '" data-layout="' . esc_attr($atts['layout']) . '">';
        
        // Different layouts
        switch ($atts['layout']) {
            case 'carousel':
                $this->render_carousel_layout($photos, $atts);
                break;
                
            case 'masonry':
                $this->render_masonry_layout($photos, $atts);
                break;
                
            case 'grid':
            default:
                $this->render_grid_layout($photos, $atts);
                break;
        }
        
        echo '</div>';
        
        // Return the buffered output
        return ob_get_clean();
    }
    
    /**
     * Render grid layout for photos
     * 
     * @param array $photos Array of attachment posts
     * @param array $atts Shortcode attributes
     */
    private function render_grid_layout($photos, $atts) {
        echo '<div class="gpd-grid-container">';
        foreach ($photos as $photo) {
            $full_img_url = wp_get_attachment_image_url($photo->ID, 'full');
            $display_img_url = wp_get_attachment_image_url($photo->ID, $atts['size']);
            $caption = '';
            
            if ($atts['show_caption']) {
                $caption = $photo->post_excerpt ?: $photo->post_title;
            }
            
            echo '<div class="gpd-grid-item">';
            echo '<a href="' . esc_url($full_img_url) . '" class="gpd-lightbox" data-caption="' . esc_attr($caption) . '">';
            echo '<img src="' . esc_url($display_img_url) . '" alt="' . esc_attr($photo->post_title) . '" loading="lazy">';
            echo '</a>';
            
            if ($atts['show_caption'] && !empty($caption)) {
                echo '<div class="gpd-caption">' . esc_html($caption) . '</div>';
            }
            
            echo '</div>';
        }
        echo '</div>';
    }
    
    /**
     * Render masonry layout for photos
     * 
     * @param array $photos Array of attachment posts
     * @param array $atts Shortcode attributes
     */
    private function render_masonry_layout($photos, $atts) {
        echo '<div class="gpd-masonry-container">';
        foreach ($photos as $photo) {
            $full_img_url = wp_get_attachment_image_url($photo->ID, 'full');
            $display_img_url = wp_get_attachment_image_url($photo->ID, $atts['size']);
            $caption = '';
            
            if ($atts['show_caption']) {
                $caption = $photo->post_excerpt ?: $photo->post_title;
            }
            
            // Get image dimensions for proper masonry sizing
            $img_meta = wp_get_attachment_metadata($photo->ID);
            $aspect_ratio = 1;
            if (isset($img_meta['width']) && isset($img_meta['height']) && $img_meta['width'] > 0) {
                $aspect_ratio = $img_meta['height'] / $img_meta['width'];
            }
            
            echo '<div class="gpd-masonry-item" style="--aspect-ratio:' . esc_attr($aspect_ratio) . '">';
            echo '<a href="' . esc_url($full_img_url) . '" class="gpd-lightbox" data-caption="' . esc_attr($caption) . '">';
            echo '<img src="' . esc_url($display_img_url) . '" alt="' . esc_attr($photo->post_title) . '" loading="lazy">';
            echo '</a>';
            
            if ($atts['show_caption'] && !empty($caption)) {
                echo '<div class="gpd-caption">' . esc_html($caption) . '</div>';
            }
            
            echo '</div>';
        }
        echo '</div>';
    }
    
    /**
     * Render carousel layout for photos
     * 
     * @param array $photos Array of attachment posts
     * @param array $atts Shortcode attributes
     */
    private function render_carousel_layout($photos, $atts) {
        echo '<div class="gpd-carousel-container">';
        echo '<div class="gpd-carousel-track">';
        
        foreach ($photos as $photo) {
            $full_img_url = wp_get_attachment_image_url($photo->ID, 'full');
            $display_img_url = wp_get_attachment_image_url($photo->ID, $atts['size']);
            $caption = '';
            
            if ($atts['show_caption']) {
                $caption = $photo->post_excerpt ?: $photo->post_title;
            }
            
            echo '<div class="gpd-carousel-slide">';
            echo '<a href="' . esc_url($full_img_url) . '" class="gpd-lightbox" data-caption="' . esc_attr($caption) . '">';
            echo '<img src="' . esc_url($display_img_url) . '" alt="' . esc_attr($photo->post_title) . '" loading="lazy">';
            echo '</a>';
            
            if ($atts['show_caption'] && !empty($caption)) {
                echo '<div class="gpd-caption">' . esc_html($caption) . '</div>';
            }
            
            echo '</div>';
        }
        
        echo '</div>'; // End track
        
        // Navigation arrows
        echo '<button class="gpd-carousel-prev" aria-label="' . esc_attr__('Previous', 'google-places-directory') . '">&#10094;</button>';
        echo '<button class="gpd-carousel-next" aria-label="' . esc_attr__('Next', 'google-places-directory') . '">&#10095;</button>';
        
        echo '</div>'; // End container
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
     */
    public function business_map_shortcode($atts) {
        // Enqueue required assets
        wp_enqueue_style('gpd-frontend');
        wp_enqueue_script('gpd-frontend');
        
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
            <div id="<?php echo esc_attr($map_id); ?>" class="gpd-map-canvas" style="height:<?php echo esc_attr($atts['height']); ?>;"></div>
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
                    });
                } else {
                    console.error('Google Maps initialization function not found');
                }
            });
        })();
        </script>
        <?php
        
        // Return the buffered output
        return ob_get_clean();
    }
}

// Initialize the shortcodes
GPD_Shortcodes::instance();
