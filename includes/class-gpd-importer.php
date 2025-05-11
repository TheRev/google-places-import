<?php
/**
 * class-gpd-importer.php
 *
 * Handles Google Places API v1 Text Search + Place Details requests
 * and imports businesses into the Business CPT, ensuring correct
 * extraction of city (locality) for the Destination taxonomy.
 * Supports pagination tokens and caching for back/forward navigation.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GPD_Importer {
    private static $instance = null;
    private $api_key;
    private $text_endpoint    = 'https://maps.googleapis.com/maps/api/place/textsearch/json';
    private $details_endpoint = 'https://maps.googleapis.com/maps/api/place/details/json';

    public static function instance() {
        if ( self::$instance === null ) {
            self::$instance          = new self();
            self::$instance->api_key = get_option( 'gpd_api_key' );
        }
        return self::$instance;
    }

    /**
     * Run a Places Text Search and return limited results + next_page_token,
     * with transient caching to allow back/forward without repeated API hits.
     *
     * @param string $query
     * @param int    $radius          in meters
     * @param int    $limit           number of results to return
     * @param string &$next_page_token returned token for next page
     * @param string $incoming_token  token passed in for paging
     * @return array|WP_Error
     */
    public function import_places( $query, $radius, $limit, &$next_page_token = '', $incoming_token = '' ) {
        // Build a unique cache key based on search params + page token
        $cache_key = 'gpd_' . md5( $query . '|' . $radius . '|' . $limit . '|' . $incoming_token );

        // Attempt to fetch from cache
        if ( false !== ( $cached = get_transient( $cache_key ) ) ) {
            $next_page_token = $cached['next_page_token'];
            return $cached['places'];
        }

        // No cache: build request params
        $params = [
            'query'  => $query,
            'radius' => $radius,
            'key'    => $this->api_key,
        ];
        if ( $incoming_token ) {
            $params['pagetoken'] = $incoming_token;
        }

        // Fetch from Google
        $response = wp_remote_get( add_query_arg( $params, $this->text_endpoint ) );
        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'api_error', $response->get_error_message() );
        }
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $body ) ) {
            return new WP_Error( 'api_parse_error', 'Invalid Places API response.' );
        }

        // Extract results and next token
        $places          = ! empty( $body['results'] ) ? $body['results'] : [];
        $next_page_token = $body['next_page_token'] ?? '';

        // Trim to limit
        $places = array_slice( $places, 0, $limit );

        // Cache the page for 1 hour
        set_transient( $cache_key, [
            'places'          => $places,
            'next_page_token' => $next_page_token,
        ], HOUR_IN_SECONDS );

        return $places;
    }

    /**
     * Fetch full Place Details for accurate address_components
     */
    private function get_place_details( $place_id ) {
        $params = [
            'place_id' => $place_id,
            'fields'   => 'name,formatted_address,address_components,geometry,types,website,international_phone_number,url,rating,business_status',
            'key'      => $this->api_key,
        ];
        $response = wp_remote_get( add_query_arg( $params, $this->details_endpoint ) );
        if ( is_wp_error( $response ) ) {
            return false;
        }
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        return $body['result'] ?? false;
    }

    /**
     * Process import: ensure locality from details, attach term
     */
    public function process_import( $places ) {
        $created = $updated = 0;

        foreach ( $places as $place ) {
            $place_id = sanitize_text_field( $place['place_id'] ?? '' );

            // 1. Get full details (guarantees address_components exist)
            $details = $this->get_place_details( $place_id );
            if ( ! $details ) {
                continue;
            }

            // 2. Extract fields
            $name              = sanitize_text_field( $details['name'] ?? '' );
            $formatted_address = $details['formatted_address'] ?? '';
            $components        = $details['address_components'] ?? [];
            $lat               = floatval( $details['geometry']['location']['lat'] ?? 0 );
            $lng               = floatval( $details['geometry']['location']['lng'] ?? 0 );
            $types             = $details['types'] ?? [];
            $rating            = floatval( $details['rating'] ?? 0 );
            $status            = sanitize_text_field( $details['business_status'] ?? '' );
            $maps_url          = esc_url_raw( $details['url'] ?? '' );

            // 3. Parse address_components to find locality (city)
            $locality = '';
            foreach ( $components as $comp ) {
                if ( in_array( 'locality', (array) ( $comp['types'] ?? [] ), true ) ) {
                    $locality = sanitize_text_field( $comp['long_name'] );
                    break;
                }
            }

            // 4. Build meta_input
            $meta_input = [
                '_gpd_place_id'        => $place_id,
                '_gpd_display_name'    => $name,
                '_gpd_address'         => $formatted_address,
                '_gpd_locality'        => $locality,
                '_gpd_latitude'        => $lat,
                '_gpd_longitude'       => $lng,
                '_gpd_types'           => wp_json_encode( $types ),
                '_gpd_rating'          => $rating,
                '_gpd_business_status' => $status,
                '_gpd_maps_uri'        => $maps_url,
            ];

            // 5. Ensure term exists
            if ( $locality && ! term_exists( $locality, 'destination' ) ) {
                wp_insert_term( $locality, 'destination' );
            }

            // 6. Check existing post
            $existing = get_posts([
                'post_type'  => 'business',
                'meta_key'   => '_gpd_place_id',
                'meta_value' => $place_id,
                'fields'     => 'ids',
            ]);

            // 7. Prepare post data
            $post_data = [
                'post_type'   => 'business',
                'post_status' => 'publish',
                'post_title'  => $name,
                'meta_input'  => $meta_input,
            ];

            // 8. Insert or update
            if ( ! empty( $existing ) ) {
                $post_data['ID'] = $existing[0];
                wp_update_post( $post_data );
                $post_id = $existing[0];
                $updated++;
            } else {
                $post_id = wp_insert_post( $post_data );
                $created++;
            }

            // 9. Attach term explicitly
            if ( $locality && ! is_wp_error( $post_id ) ) {
                wp_set_object_terms( $post_id, $locality, 'destination', false );
                clean_object_term_cache( $post_id, 'destination' );
            }
        }

        return [ 'created' => $created, 'updated' => $updated ];
    }
}
