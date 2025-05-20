<?php
/**
 * Class GPD_Shortcodes
 *
 * Handles shortcodes for displaying business photos and information
 * 
 * @since 2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GPD_Shortcodes {
    private static $instance = null;

    public static function instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }

    public function init() {
        add_shortcode( 'gpd_photos', array( $this, 'photos_shortcode' ) );
        add_shortcode( 'gpd_business', array( $this, 'business_shortcode' ) );
        
        // Register styles for the frontend
        add_action( 'wp_enqueue_scripts', array( $this, 'register_styles' ) );
    }
    
    /**
     * Register frontend styles
     */
    public function register_styles() {
        wp_register_style(
            'gpd-frontend', 
            plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/frontend.css',
            array(),
            filemtime( plugin_dir_path( dirname( __FILE__ ) ) . 'assets/css/frontend.css' )
        );
    }

    /**
     * Shortcode to display business photos
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function photos_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'id' => 0,
            'business' => '',
            'layout' => 'grid', // grid, slider, masonry
            'size' => 'medium', // thumbnail, medium, large, full
            'limit' => 10,
        ), $atts, 'gpd_photos' );

        // Get post ID either from ID parameter or by business name
        $post_id = $this->get_business_id( $atts );
        
        if ( ! $post_id ) {
            return '<p class="gpd-error">' . __( 'Business not found', 'google-places-directory' ) . '</p>';
        }

        // Get photo references from post meta
        $photo_refs = get_post_meta( $post_id, '_gpd_photo_references', true );
        
        if ( empty( $photo_refs ) ) {
            return '<p class="gpd-notice">' . __( 'No photos available for this business', 'google-places-directory' ) . '</p>';
        }

        // Get attachments based on stored references
        $attachments = array();
        foreach ( $photo_refs as $ref ) {
            $args = array(
                'post_type' => 'attachment',
                'posts_per_page' => 1,
                'meta_key' => '_gpd_photo_reference',
                'meta_value' => $ref,
                'post_status' => 'any',
            );
            
            $query = new WP_Query( $args );
            
            if ( $query->have_posts() ) {
                $attachments[] = $query->posts[0]->ID;
            }
            
            // Limit to specified number
            if ( count( $attachments ) >= intval( $atts['limit'] ) ) {
                break;
            }
        }
        
        // Return early if no attachments found
        if ( empty( $attachments ) ) {
            return '<p class="gpd-notice">' . __( 'No photos available for this business', 'google-places-directory' ) . '</p>';
        }
        
        // Enqueue stylesheet
        wp_enqueue_style( 'gpd-frontend' );
        
        // Begin output
        $output = '<div class="gpd-photos gpd-layout-' . esc_attr( $atts['layout'] ) . '">';
        
        // Generate gallery based on layout type
        switch ( $atts['layout'] ) {
            case 'slider':
                $output .= $this->generate_slider( $attachments, $atts['size'] );
                break;
                
            case 'masonry':
                $output .= $this->generate_masonry( $attachments, $atts['size'] );
                break;
                
            case 'grid':
            default:
                $output .= $this->generate_grid( $attachments, $atts['size'] );
                break;
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Shortcode to display business information with photos
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function business_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'id' => 0,
            'business' => '',
            'show_photos' => 'yes', // yes, no
            'show_map' => 'yes', // yes, no
            'photo_size' => 'medium', // thumbnail, medium, large, full
            'layout' => 'card', // card, details
        ), $atts, 'gpd_business' );
        
        // Get post ID either from ID parameter or by business name
        $post_id = $this->get_business_id( $atts );
        
        if ( ! $post_id ) {
            return '<p class="gpd-error">' . __( 'Business not found', 'google-places-directory' ) . '</p>';
        }
        
        // Get business data
        $business_name = get_the_title( $post_id );
        $address = get_post_meta( $post_id, '_gpd_address', true );
        $phone = get_post_meta( $post_id, '_gpd_phone_number', true );
        $website = get_post_meta( $post_id, '_gpd_website', true );
        $rating = get_post_meta( $post_id, '_gpd_rating', true );
        $lat = get_post_meta( $post_id, '_gpd_latitude', true );
        $lng = get_post_meta( $post_id, '_gpd_longitude', true );
        $maps_uri = get_post_meta( $post_id, '_gpd_maps_uri', true );
        
        // Enqueue stylesheet
        wp_enqueue_style( 'gpd-frontend' );
        
        // Begin output
        $output = '<div class="gpd-business gpd-layout-' . esc_attr( $atts['layout'] ) . '">';
        
        // Business header
        $output .= '<div class="gpd-business-header">';
        $output .= '<h3 class="gpd-business-name">' . esc_html( $business_name ) . '</h3>';
        
        if ( ! empty( $rating ) ) {
            $output .= '<div class="gpd-business-rating">';
            $output .= '<span class="gpd-rating-value">' . number_format( (float) $rating, 1 ) . '</span>';
            $output .= '<span class="gpd-rating-stars">';
            $full_stars = floor( $rating );
            $half_star = ( $rating - $full_stars ) >= 0.5;
            
            for ( $i = 1; $i <= 5; $i++ ) {
                if ( $i <= $full_stars ) {
                    $output .= '<span class="gpd-star gpd-star-full">★</span>';
                } elseif ( $half_star && $i === $full_stars + 1 ) {
                    $output .= '<span class="gpd-star gpd-star-half">★</span>';
                } else {
                    $output .= '<span class="gpd-star gpd-star-empty">☆</span>';
                }
            }
            $output .= '</span>';
            $output .= '</div>';
        }
        $output .= '</div>';
        
        // Photos section
        if ( $atts['show_photos'] === 'yes' ) {
            $photo_shortcode = '[gpd_photos id="' . $post_id . '" layout="grid" size="' . $atts['photo_size'] . '" limit="3"]';
            $output .= do_shortcode( $photo_shortcode );
        }
        
        // Business details
        $output .= '<div class="gpd-business-details">';
        if ( ! empty( $address ) ) {
            $output .= '<div class="gpd-business-address">';
            $output .= '<span class="gpd-icon">📍</span>';
            if ( ! empty( $maps_uri ) ) {
                $output .= '<a href="' . esc_url( $maps_uri ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $address ) . '</a>';
            } else {
                $output .= esc_html( $address );
            }
            $output .= '</div>';
        }
        
        if ( ! empty( $phone ) ) {
            $output .= '<div class="gpd-business-phone">';
            $output .= '<span class="gpd-icon">📞</span>';
            $output .= '<a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ) . '">' . esc_html( $phone ) . '</a>';
            $output .= '</div>';
        }
        
        if ( ! empty( $website ) ) {
            $output .= '<div class="gpd-business-website">';
            $output .= '<span class="gpd-icon">🌐</span>';
            $output .= '<a href="' . esc_url( $website ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( preg_replace( '#^https?://#', '', $website ) ) . '</a>';
            $output .= '</div>';
        }
        $output .= '</div>';
        
        // Map section
        if ( $atts['show_map'] === 'yes' && ! empty( $lat ) && ! empty( $lng ) ) {
            $api_key = get_option( 'gpd_api_key' );
            if ( ! empty( $api_key ) ) {
                $output .= '<div class="gpd-business-map">';
                $output .= '<iframe 
                    width="100%" 
                    height="250" 
                    frameborder="0" 
                    style="border:0" 
                    src="https://www.google.com/maps/embed/v1/place?key=' . esc_attr( $api_key ) . '&q=' . esc_attr( urlencode( $business_name . ' ' . $address ) ) . '&center=' . esc_attr( $lat ) . ',' . esc_attr( $lng ) . '&zoom=15" 
                    allowfullscreen>
                </iframe>';
                $output .= '</div>';
            }
        }
        
        $output .= '<div class="gpd-attribution">' . __( 'Business data from Google Places', 'google-places-directory' ) . '</div>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Find business ID by name or ID
     *
     * @param array $atts Shortcode attributes
     * @return int|false Post ID or false if not found
     */
    private function get_business_id( $atts ) {
        if ( ! empty( $atts['id'] ) && is_numeric( $atts['id'] ) ) {
            return absint( $atts['id'] );
        }
        
        if ( ! empty( $atts['business'] ) ) {
            // Try to find by exact name first
            $args = array(
                'post_type' => 'business',
                'posts_per_page' => 1,
                'title' => $atts['business'],
                'fields' => 'ids',
            );
            
            $query = new WP_Query( $args );
            
            if ( $query->have_posts() ) {
                return $query->posts[0];
            }
            
            // Try with LIKE search
            $args = array(
                'post_type' => 'business',
                'posts_per_page' => 1,
                's' => $atts['business'],
                'fields' => 'ids',
            );
            
            $query = new WP_Query( $args );
            
            if ( $query->have_posts() ) {
                return $query->posts[0];
            }
        }
        
        return false;
    }
    
    /**
     * Generate a grid layout for photos
     *
     * @param array $attachments Attachment IDs
     * @param string $size Image size
     * @return string HTML output
     */
    private function generate_grid( $attachments, $size ) {
        $output = '<div class="gpd-photo-grid">';
        
        foreach ( $attachments as $id ) {
            $image = wp_get_attachment_image( $id, $size );
            $full_image_url = wp_get_attachment_url( $id );
            
            $output .= '<div class="gpd-photo-item">';
            $output .= '<a href="' . esc_url( $full_image_url ) . '" class="gpd-photo-link">';
            $output .= $image;
            $output .= '</a>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Generate a slider layout for photos
     *
     * @param array $attachments Attachment IDs
     * @param string $size Image size
     * @return string HTML output
     */
    private function generate_slider( $attachments, $size ) {
        $output = '<div class="gpd-photo-slider">';
        $output .= '<div class="gpd-slider-track">';
        
        foreach ( $attachments as $id ) {
            $image = wp_get_attachment_image( $id, $size );
            $output .= '<div class="gpd-slider-item">' . $image . '</div>';
        }
        
        $output .= '</div>';
        
        if ( count( $attachments ) > 1 ) {
            $output .= '<div class="gpd-slider-controls">';
            $output .= '<button class="gpd-slider-prev" aria-label="Previous">◀</button>';
            $output .= '<button class="gpd-slider-next" aria-label="Next">▶</button>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Generate a masonry layout for photos
     *
     * @param array $attachments Attachment IDs
     * @param string $size Image size
     * @return string HTML output
     */
    private function generate_masonry( $attachments, $size ) {
        $output = '<div class="gpd-photo-masonry">';
        
        foreach ( $attachments as $id ) {
            $image = wp_get_attachment_image( $id, $size );
            $full_image_url = wp_get_attachment_url( $id );
            
            $output .= '<div class="gpd-photo-item">';
            $output .= '<a href="' . esc_url( $full_image_url ) . '" class="gpd-photo-link">';
            $output .= $image;
            $output .= '</a>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
}
