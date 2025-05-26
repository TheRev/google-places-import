<?php
/**
 * Class GPD_Photo_Shortcodes
 *
 * Handles all photo-related shortcodes for the Google Places Directory plugin. * 
 * @since 2.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class GPD_Photo_Shortcodes {
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
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }    /**
     * Enqueue required assets for shortcodes
     */
    public function enqueue_assets() {
        $css_file = plugin_dir_path(__FILE__) . '../assets/css/gpd-frontend.css';
        $lightbox_file = plugin_dir_path(__FILE__) . '../assets/js/gpd-lightbox.js';
        $js_file = plugin_dir_path(__FILE__) . '../assets/js/gpd-frontend.js';
        
        // Register styles
        wp_register_style(
            'gpd-frontend', 
            plugin_dir_url(__FILE__) . '../assets/css/gpd-frontend.css',
            array(),
            file_exists($css_file) ? filemtime($css_file) : GPD_VERSION
        );        
        // Register scripts
        wp_register_script(
            'gpd-lightbox',
            plugin_dir_url(__FILE__) . '../assets/js/gpd-lightbox.js',
            array('jquery'),
            file_exists($lightbox_file) ? filemtime($lightbox_file) : GPD_VERSION,
            true
        );
        
        wp_register_script(
            'gpd-frontend',
            plugin_dir_url(__FILE__) . '../assets/js/gpd-frontend.js',
            array('jquery', 'gpd-lightbox'),
            file_exists($js_file) ? filemtime($js_file) : GPD_VERSION,
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
            'layout' => 'grid',          // Options: grid, masonry, carousel, column
            'columns' => 3,              // Number of columns for grid layout
            'limit' => 0,                // Max number of photos (0 = all)
            'size' => 'medium',          // Image size: thumbnail, medium, large, full
            'show_caption' => 'false',   // Show captions
            'class' => '',               // Custom CSS class
            'max_width' => '800px',      // Maximum width for column layout
            'spacing' => '20px',         // Spacing between photos in column layout
            'alignment' => 'center',     // Alignment of column (left, center, right)
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
                
            case 'column':
                $this->render_column_layout($photos, $atts);
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
        foreach ($photos as $index => $photo) {
            $full_img_url = wp_get_attachment_image_url($photo->ID, 'full');
            $display_img_url = wp_get_attachment_image_url($photo->ID, $atts['size']);
            $caption = '';
            
            if ($atts['show_caption']) {
                $caption = $photo->post_excerpt ?: $photo->post_title;
            }
            
            echo '<div class="gpd-grid-item" data-index="' . esc_attr($index) . '">';
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
        foreach ($photos as $index => $photo) {
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
            
            echo '<div class="gpd-masonry-item" style="--aspect-ratio:' . esc_attr($aspect_ratio) . '" data-index="' . esc_attr($index) . '">';
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
        
        foreach ($photos as $index => $photo) {
            $full_img_url = wp_get_attachment_image_url($photo->ID, 'full');
            $display_img_url = wp_get_attachment_image_url($photo->ID, $atts['size']);
            $caption = '';
            
            if ($atts['show_caption']) {
                $caption = $photo->post_excerpt ?: $photo->post_title;
            }
            
            echo '<div class="gpd-carousel-slide" data-index="' . esc_attr($index) . '">';
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
     * Render column layout for photos - displays photos in a single vertical column
     * 
     * @param array $photos Array of attachment posts
     * @param array $atts Shortcode attributes
     */
    private function render_column_layout($photos, $atts) {
        // Process alignment
        $alignment = in_array($atts['alignment'], ['left', 'center', 'right']) ? $atts['alignment'] : 'center';
        
        // Build inline styles for container
        $container_style = 'max-width: ' . esc_attr($atts['max_width']) . ';';
        
        if ($alignment === 'center') {
            $container_style .= ' margin-left: auto; margin-right: auto;';
        } elseif ($alignment === 'right') {
            $container_style .= ' margin-left: auto; margin-right: 0;';
        } else {
            $container_style .= ' margin-left: 0; margin-right: auto;';
        }
        
        echo '<div class="gpd-column-container" style="' . $container_style . '">';
        
        foreach ($photos as $index => $photo) {
            $full_img_url = wp_get_attachment_image_url($photo->ID, 'full');
            $display_img_url = wp_get_attachment_image_url($photo->ID, $atts['size']);
            $caption = '';
            
            if ($atts['show_caption']) {
                $caption = $photo->post_excerpt ?: $photo->post_title;
            }
            
            // Style for item
            $item_style = 'margin-bottom: ' . esc_attr($atts['spacing']) . ';';
            
            echo '<div class="gpd-column-item" style="' . $item_style . '" data-index="' . esc_attr($index) . '">';
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
}

// Initialize the photo shortcodes
GPD_Photo_Shortcodes::instance();
