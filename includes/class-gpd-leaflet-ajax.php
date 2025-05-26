<?php
/**
 * Google Places Directory - AJAX Handler for Leaflet Maps
 * Version: 2.3.0
 * Date: 2025-05-26
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GPD_Leaflet_Ajax {
    
    public function __construct() {
        add_action( 'wp_ajax_gpd_get_surrounding_businesses', array( $this, 'get_surrounding_businesses' ) );
        add_action( 'wp_ajax_nopriv_gpd_get_surrounding_businesses', array( $this, 'get_surrounding_businesses' ) );
    }
    
    /**
     * AJAX handler for getting surrounding businesses
     */
    public function get_surrounding_businesses() {
        // Verify nonce for security
        if ( ! wp_verify_nonce( $_POST['nonce'], 'gpd_leaflet_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
            return;
        }
        
        // Get parameters
        $center_lat = floatval( $_POST['lat'] );
        $center_lng = floatval( $_POST['lng'] );
        $radius = intval( $_POST['radius'] ); // in meters
        $exclude_ids = isset( $_POST['exclude_ids'] ) ? array_map( 'intval', $_POST['exclude_ids'] ) : array();
        $limit = isset( $_POST['limit'] ) ? intval( $_POST['limit'] ) : 20;
        
        // Validate parameters
        if ( empty( $center_lat ) || empty( $center_lng ) || empty( $radius ) ) {
            wp_send_json_error( array( 'message' => 'Invalid parameters' ) );
            return;
        }
        
        // Limit radius to reasonable bounds (max 50km)
        $radius = min( $radius, 50000 );
        $limit = min( $limit, 50 );
        
        try {
            $businesses = $this->find_businesses_within_radius( $center_lat, $center_lng, $radius, $exclude_ids, $limit );
            wp_send_json_success( array( 'businesses' => $businesses ) );
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => 'Database error: ' . $e->getMessage() ) );
        }
    }
    
    /**
     * Find businesses within a specific radius using Haversine formula
     */
    private function find_businesses_within_radius( $center_lat, $center_lng, $radius_meters, $exclude_ids = array(), $limit = 20 ) {
        global $wpdb;
        
        // Convert radius from meters to degrees (approximate)
        $radius_degrees = $radius_meters / 111320; // roughly 111,320 meters per degree
        
        // Build exclude clause
        $exclude_clause = '';
        if ( ! empty( $exclude_ids ) ) {
            $exclude_ids_string = implode( ',', array_map( 'intval', $exclude_ids ) );
            $exclude_clause = "AND p.ID NOT IN ($exclude_ids_string)";
        }
        
        // Query to find businesses within radius using Haversine formula
        $query = $wpdb->prepare( "
            SELECT 
                p.ID,
                p.post_title as name,
                lat_meta.meta_value as latitude,
                lng_meta.meta_value as longitude,
                address_meta.meta_value as address,
                phone_meta.meta_value as phone,
                rating_meta.meta_value as rating,
                category_meta.meta_value as category,
                google_id_meta.meta_value as google_place_id,
                (
                    6371000 * acos(
                        cos(radians(%f)) * cos(radians(lat_meta.meta_value)) * 
                        cos(radians(lng_meta.meta_value) - radians(%f)) + 
                        sin(radians(%f)) * sin(radians(lat_meta.meta_value))
                    )
                ) as distance
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} lat_meta ON p.ID = lat_meta.post_id AND lat_meta.meta_key = 'latitude'
            INNER JOIN {$wpdb->postmeta} lng_meta ON p.ID = lng_meta.post_id AND lng_meta.meta_key = 'longitude'
            LEFT JOIN {$wpdb->postmeta} address_meta ON p.ID = address_meta.post_id AND address_meta.meta_key = 'address'
            LEFT JOIN {$wpdb->postmeta} phone_meta ON p.ID = phone_meta.post_id AND phone_meta.meta_key = 'phone'
            LEFT JOIN {$wpdb->postmeta} rating_meta ON p.ID = rating_meta.post_id AND rating_meta.meta_key = 'rating'
            LEFT JOIN {$wpdb->postmeta} category_meta ON p.ID = category_meta.post_id AND category_meta.meta_key = 'category'
            LEFT JOIN {$wpdb->postmeta} google_id_meta ON p.ID = google_id_meta.post_id AND google_id_meta.meta_key = 'google_place_id'
            WHERE p.post_type = 'business' 
            AND p.post_status = 'publish'
            AND lat_meta.meta_value IS NOT NULL 
            AND lng_meta.meta_value IS NOT NULL
            AND lat_meta.meta_value != ''
            AND lng_meta.meta_value != ''
            AND ABS(lat_meta.meta_value - %f) <= %f
            AND ABS(lng_meta.meta_value - %f) <= %f
            $exclude_clause
            HAVING distance <= %d
            ORDER BY distance ASC
            LIMIT %d
        ", 
            $center_lat, $center_lng, $center_lat, // for Haversine formula
            $center_lat, $radius_degrees, // for bounding box filter
            $center_lng, $radius_degrees, // for bounding box filter
            $radius_meters, // for HAVING clause
            $limit
        );
        
        $results = $wpdb->get_results( $query );
        
        $businesses = array();
        foreach ( $results as $result ) {
            $businesses[] = array(
                'id' => intval( $result->ID ),
                'name' => $result->name,
                'latitude' => floatval( $result->latitude ),
                'longitude' => floatval( $result->longitude ),
                'address' => $result->address ?: '',
                'phone' => $result->phone ?: '',
                'rating' => $result->rating ? floatval( $result->rating ) : 0,
                'category' => $result->category ?: '',
                'google_place_id' => $result->google_place_id ?: '',
                'distance' => round( floatval( $result->distance ) ), // distance in meters
                'permalink' => get_permalink( $result->ID )
            );
        }
        
        return $businesses;
    }
    
    /**
     * Get business data for map markers
     */
    public static function get_business_marker_data( $business_id ) {
        $business = get_post( $business_id );
        if ( ! $business || $business->post_type !== 'business' ) {
            return false;
        }
        
        $latitude = get_post_meta( $business_id, 'latitude', true );
        $longitude = get_post_meta( $business_id, 'longitude', true );
        
        if ( empty( $latitude ) || empty( $longitude ) ) {
            return false;
        }
        
        return array(
            'id' => $business_id,
            'name' => $business->post_title,
            'latitude' => floatval( $latitude ),
            'longitude' => floatval( $longitude ),
            'address' => get_post_meta( $business_id, 'address', true ) ?: '',
            'phone' => get_post_meta( $business_id, 'phone', true ) ?: '',
            'rating' => get_post_meta( $business_id, 'rating', true ) ? floatval( get_post_meta( $business_id, 'rating', true ) ) : 0,
            'category' => get_post_meta( $business_id, 'category', true ) ?: '',
            'google_place_id' => get_post_meta( $business_id, 'google_place_id', true ) ?: '',
            'permalink' => get_permalink( $business_id )
        );
    }
    
    /**
     * Get businesses for initial map load
     */
    public static function get_businesses_for_map( $args = array() ) {
        $defaults = array(
            'post_type' => 'business',
            'post_status' => 'publish',
            'posts_per_page' => 100,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'latitude',
                    'value' => '',
                    'compare' => '!='
                ),
                array(
                    'key' => 'longitude',
                    'value' => '',
                    'compare' => '!='
                )
            )
        );
        
        $query_args = wp_parse_args( $args, $defaults );
        $businesses = get_posts( $query_args );
        
        $business_data = array();
        foreach ( $businesses as $business ) {
            $data = self::get_business_marker_data( $business->ID );
            if ( $data ) {
                $business_data[] = $data;
            }
        }
        
        return $business_data;
    }
}

// Initialize the AJAX handler
new GPD_Leaflet_Ajax();
