<?php
/**
 * Example of creating a custom shortcode that integrates with GPD
 */

/**
 * [gpd-nearby-businesses] - Displays businesses near a specific location
 * 
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function my_gpd_nearby_businesses_shortcode( $atts ) {
    // Parse attributes
    $atts = shortcode_atts( array(
        'latitude' => '',
        'longitude' => '',
        'radius' => '5', // in kilometers
        'count' => '5',
        'category' => '',
        'show_distance' => 'true',
        'sort' => 'distance', // distance, rating, name
    ), $atts );
    
    // Initialize output
    $output = '<div class="gpd-nearby-businesses">';
    
    // Get current coordinates if not specified
    if ( empty( $atts['latitude'] ) || empty( $atts['longitude'] ) ) {
        // Default to a specific location if user location isn't available
        $default_location = apply_filters( 'my_gpd_default_location', array(
            'latitude' => '37.7749',
            'longitude' => '-122.4194',
        ) );
        
        $atts['latitude'] = $default_location['latitude'];
        $atts['longitude'] = $default_location['longitude'];
    }
    
    // Query for nearby businesses
    $args = array(
        'post_type' => 'business',
        'posts_per_page' => intval( $atts['count'] ),
        'meta_query' => array(
            array(
                'key' => '_gpd_coordinates',
                'compare' => 'EXISTS',
            ),
        ),
    );
    
    // Add category filter if specified
    if ( ! empty( $atts['category'] ) ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'business_category',
                'field' => 'slug',
                'terms' => explode( ',', $atts['category'] ),
            ),
        );
    }
    
    // Filter businesses by distance
    add_filter( 'posts_clauses', function( $clauses ) use ( $atts ) {
        global $wpdb;
        
        $latitude = floatval( $atts['latitude'] );
        $longitude = floatval( $atts['longitude'] );
        $radius = floatval( $atts['radius'] );
        
        // Haversine formula to calculate distance
        $distance_formula = "
            6371 * acos(
                cos( radians( {$latitude} ) ) * 
                cos( radians( SUBSTRING_INDEX(meta_coordinates.meta_value, ',', 1) ) ) * 
                cos( 
                    radians( SUBSTRING_INDEX(meta_coordinates.meta_value, ',', -1) ) - 
                    radians( {$longitude} ) 
                ) + 
                sin( radians( {$latitude} ) ) * 
                sin( radians( SUBSTRING_INDEX(meta_coordinates.meta_value, ',', 1) ) )
            )
        ";
        
        $clauses['join'] .= " INNER JOIN {$wpdb->postmeta} AS meta_coordinates ON ({$wpdb->posts}.ID = meta_coordinates.post_id AND meta_coordinates.meta_key = '_gpd_coordinates')";
        $clauses['where'] .= " AND ({$distance_formula} <= {$radius})";
        
        // Order by distance if needed
        if ( $atts['sort'] === 'distance' ) {
            $clauses['orderby'] = "{$distance_formula} ASC";
        }
        
        // Add distance to SELECT for display
        $clauses['fields'] .= ", {$distance_formula} AS distance";
        
        return $clauses;
    } );
    
    $query = new WP_Query( $args );
    
    // Display results
    if ( $query->have_posts() ) {
        $output .= '<ul class="gpd-nearby-list">';
        
        while ( $query->have_posts() ) {
            $query->the_post();
            
            $business_id = get_the_ID();
            $distance = isset( $query->post->distance ) ? round( $query->post->distance, 1 ) : '';
            
            $output .= '<li class="gpd-nearby-business">';
            $output .= '<a href="' . get_permalink() . '">' . get_the_title() . '</a>';
            
            if ( $atts['show_distance'] === 'true' && $distance !== '' ) {
                $output .= ' <span class="gpd-distance">(' . $distance . ' km)</span>';
            }
            
            // Add basic business info
            $address = get_post_meta( $business_id, '_gpd_address', true );
            $rating = get_post_meta( $business_id, '_gpd_rating', true );
            
            if ( $address ) {
                $output .= '<div class="gpd-address">' . esc_html( $address ) . '</div>';
            }
            
            if ( $rating ) {
                $output .= '<div class="gpd-rating">';
                $output .= 'Rating: ' . esc_html( $rating ) . '/5';
                $output .= '</div>';
            }
            
            $output .= '</li>';
        }
        
        $output .= '</ul>';
    } else {
        $output .= '<p>' . esc_html__( 'No businesses found nearby.', 'my-gpd-addon' ) . '</p>';
    }
    
    wp_reset_postdata();
    
    $output .= '</div>';
    
    return $output;
}
add_shortcode( 'gpd-nearby-businesses', 'my_gpd_nearby_businesses_shortcode' );
