<?php
/**
 * class-gpd-importer.php
 *
 * Handles Google Places API v1 Text Search + Place Details requests
 * and imports businesses into the Business CPT, ensuring correct
 * extraction of city (locality) for the Destination taxonomy.
 * Supports pagination tokens and caching for back/forward navigation.
 * 
 * Updated for Google Places API in May 2025
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GPD_Importer {
    private static $instance = null;
    private $api_key;
    
    // API endpoints
    private $text_endpoint    = 'https://places.googleapis.com/v1/places:searchText';
    private $details_endpoint = 'https://places.googleapis.com/v1/places/';

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

        // No cache: prepare request headers
        $headers = [
            'Content-Type'     => 'application/json',
            'X-Goog-Api-Key'   => $this->api_key,
            'X-Goog-FieldMask' => 'places.id,places.displayName,places.formattedAddress,places.types,places.rating,places.businessStatus,places.location,places.googleMapsUri,places.addressComponents,nextPageToken'
        ];

        // Build request body according to API format
        $request_body = [
            'textQuery' => $query,
            'maxResultCount' => $limit
        ];
        
        // Add radius parameter
        if ($radius > 0) {
            $request_body['rankPreference'] = 'DISTANCE';
        }

        // Add page token if available
        if ( $incoming_token ) {
            $request_body['pageToken'] = $incoming_token;
        }

        // Fetch from Google using POST
        $response = wp_remote_post(
            $this->text_endpoint,
            [
                'headers' => $headers,
                'body' => json_encode($request_body),
                'timeout' => 30 // Increase timeout for potentially slower responses
            ]
        );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'api_error', $response->get_error_message() );
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        if ( $response_code !== 200 ) {
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            $error_message = isset($body['error']['message']) ? $body['error']['message'] : "API Error: HTTP {$response_code}";
            return new WP_Error( 'api_http_error', $error_message );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $body ) ) {
            return new WP_Error( 'api_parse_error', 'Invalid Places API response.' );
        }

        // Extract results and next token
        $places = !empty( $body['places'] ) ? $body['places'] : [];
        $next_page_token = $body['nextPageToken'] ?? '';

        // Process places for compatibility with the rest of the plugin
        $processed_places = [];
        foreach ($places as $place) {
            $processed_place = $this->normalize_place_data($place);
            $processed_places[] = $processed_place;
        }

        // Cache the page for 1 hour
        set_transient( $cache_key, [
            'places'          => $processed_places,
            'next_page_token' => $next_page_token,
        ], HOUR_IN_SECONDS );

        return $processed_places;
    }

    /**
     * Normalize place data from the new API format to match the expected format in the rest of the plugin
     * 
     * @param array $place Place data from API
     * @return array Normalized place data
     */
    private function normalize_place_data($place) {
        $normalized = [
            'place_id' => $place['id'] ?? '',
            'name' => isset($place['displayName']) ? $place['displayName']['text'] : '',
            'formatted_address' => $place['formattedAddress'] ?? '',
            'types' => $place['types'] ?? [],
            'rating' => $place['rating'] ?? 0,
            'business_status' => $place['businessStatus'] ?? '',
            'url' => $place['googleMapsUri'] ?? '',
        ];
        
        // Handle address components 
        if (isset($place['addressComponents'])) {
            $normalized['address_components'] = $this->normalize_address_components($place['addressComponents']);
        }
        
        // Handle location/geometry
        if (isset($place['location'])) {
            $normalized['geometry'] = [
                'location' => [
                    'lat' => $place['location']['latitude'] ?? 0,
                    'lng' => $place['location']['longitude'] ?? 0
                ]
            ];
        }
        
        return $normalized;
    }

    /**
     * Fetch full Place Details for accurate address_components
     */
    private function get_place_details( $place_id ) {
        // Headers for authentication and field selection - Added photos to the field mask
        $headers = [
            'X-Goog-Api-Key'   => $this->api_key,
            'X-Goog-FieldMask' => 'id,displayName,formattedAddress,addressComponents,types,websiteUri,internationalPhoneNumber,googleMapsUri,rating,businessStatus,location,photos'
        ];

        // Endpoint format is https://places.googleapis.com/v1/places/{place_id}
        $url = $this->details_endpoint . $place_id;
        
        $response = wp_remote_get($url, [
            'headers' => $headers,
            'timeout' => 30 // Longer timeout for potentially slower responses
        ]);

        if ( is_wp_error( $response ) ) {
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code( $response );
        if ( $response_code !== 200 ) {
            // Log error if needed
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if (!is_array($body)) {
            return false;
        }

        // Transform the response to match the expected structure in the process_import method
        return $this->normalize_details_data($body);
    }

    /**
     * Normalize details data from API format to match expected format
     */
    private function normalize_details_data($details) {
        $normalized = [
            'place_id' => $details['id'] ?? '',
            'name' => isset($details['displayName']) ? $details['displayName']['text'] : '',
            'formatted_address' => $details['formattedAddress'] ?? '',
            'types' => $details['types'] ?? [],
            'rating' => $details['rating'] ?? 0,
            'business_status' => $details['businessStatus'] ?? '',
            'url' => $details['googleMapsUri'] ?? '',
            'website' => $details['websiteUri'] ?? '',
            'international_phone_number' => $details['internationalPhoneNumber'] ?? '',
            'photos' => $details['photos'] ?? [], // Add photos to normalized data
        ];
        
        // Handle address components
        if (isset($details['addressComponents'])) {
            $normalized['address_components'] = $this->normalize_address_components($details['addressComponents']);
        }
        
        // Handle location/geometry
        if (isset($details['location'])) {
            $normalized['geometry'] = [
                'location' => [
                    'lat' => $details['location']['latitude'] ?? 0,
                    'lng' => $details['location']['longitude'] ?? 0
                ]
            ];
        }
        
        return $normalized;
    }

    /**
     * Normalize address components from API format
     */
    private function normalize_address_components($components) {
        $normalized = [];
        foreach ($components as $component) {
            $normalized[] = [
                'long_name' => $component['longText'] ?? '',
                'short_name' => $component['shortText'] ?? '',
                'types' => $component['types'] ?? [],
            ];
        }
        return $normalized;
    }

    /**
     * Import a photo from Google Places API to WordPress Media Library
     *
     * @param string $photo_reference Photo reference from Places API
     * @param int    $post_id         Post ID to attach the photo to
     * @param string $business_name   Business name for the photo title
     * @param int    $photo_index     Index number for the photo (to create unique filenames)
     * @return int|false              Attachment ID if successful, false otherwise
     */
    private function import_photo($photo_reference, $post_id, $business_name, $photo_index = 1) {
        if (empty($photo_reference) || empty($post_id)) {
            return false;
        }

        // Build the photo URL with the reference path
        $url = "https://places.googleapis.com/v1/{$photo_reference}/media";
        
        // Add size parameters - adjust these values based on your needs
        $params = [
            'maxHeightPx' => 800,  // Standard height for featured images
            'maxWidthPx'  => 1200, // Standard width for featured images
        ];
        
        $url = add_query_arg($params, $url);
        
        // Set up headers for the request
        $headers = [
            'X-Goog-Api-Key' => $this->api_key,
        ];
        
        // Make the request to get the photo
        $response = wp_remote_get($url, [
            'headers' => $headers,
            'timeout' => 30
        ]);
        
        // Check for errors
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            if (is_wp_error($response)) {
                error_log('Failed to download Places photo: ' . $response->get_error_message());
            } else {
                error_log('Failed to download Places photo. HTTP Status: ' . wp_remote_retrieve_response_code($response));
            }
            return false;
        }
        
        // Get the image data
        $image_data = wp_remote_retrieve_body($response);
        if (empty($image_data)) {
            error_log('Empty image data received from Places API');
            return false;
        }
        
        // Get the content type to determine file extension
        $content_type = wp_remote_retrieve_header($response, 'content-type');
        
        // Determine file extension based on content type
        $extension = '';
        switch ($content_type) {
            case 'image/jpeg':
                $extension = 'jpg';
                break;
            case 'image/png':
                $extension = 'png';
                break;
            case 'image/webp':
                $extension = 'webp';
                break;
            default:
                $extension = 'jpg'; // Default to jpg
        }
        
        // Create a unique filename
        $sanitized_name = sanitize_title($business_name);
        if (strlen($sanitized_name) > 40) {
            $sanitized_name = substr($sanitized_name, 0, 40);
        }
        $filename = $sanitized_name . '-photo-' . $photo_index . '-' . substr(md5($photo_reference), 0, 8) . '.' . $extension;
        
        // Save temp file
        $upload_dir = wp_upload_dir();
        $temp_file = $upload_dir['path'] . '/' . $filename;
        file_put_contents($temp_file, $image_data);
        
        // Check the filetype
        $filetype = wp_check_filetype($filename, null);
        
        // Prepare attachment data
        $attachment = [
            'post_mime_type' => $filetype['type'],
            'post_title'     => sanitize_text_field($business_name . ' - Google Places Photo ' . $photo_index),
            'post_content'   => sprintf(
                __('Photo of %1$s provided by Google Places API. Photo attribution: Google Maps. Reference: %2$s', 'google-places-directory'),
                $business_name,
                $photo_reference
            ),
            'post_status'    => 'inherit'
        ];
        
        // Insert the attachment
        $attach_id = wp_insert_attachment($attachment, $temp_file, $post_id);
        
        if (!is_wp_error($attach_id)) {
            // Required for WordPress to create image thumbnails
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            
            // Generate metadata for the attachment
            $attach_data = wp_generate_attachment_metadata($attach_id, $temp_file);
            wp_update_attachment_metadata($attach_id, $attach_data);
            
            // Store the photo reference as attachment meta
            update_post_meta($attach_id, '_gpd_photo_reference', $photo_reference);
            update_post_meta($attach_id, '_gpd_photo_attribution', 'Google Places API');
            
            // Clean up the temporary file
            @unlink($temp_file);
            
            return $attach_id;
        } else {
            // Clean up on error
            @unlink($temp_file);
            error_log('Failed to insert attachment: ' . $attach_id->get_error_message());
            return false;
        }
    }

    /**
     * Process import: ensure locality from details, attach term
     */
    public function process_import( $places ) {
        $created = $updated = 0;

        foreach ( $places as $place ) {
            $place_id_from_search = sanitize_text_field( $place['place_id'] ?? '' );
            if ( ! $place_id_from_search ) {
                continue;
            }

            // 1. Get full details (guarantees address_components exist and gets potentially more fields)
            $details = $this->get_place_details( $place_id_from_search );
            if ( ! $details ) {
                continue;
            }
            
            // Use place_id from details response as it's the definitive one
            $place_id = sanitize_text_field( $details['place_id'] ?? $place_id_from_search );

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
            $website_url       = esc_url_raw( $details['website'] ?? '' );
            $phone_number      = sanitize_text_field( $details['international_phone_number'] ?? '' );

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
                '_gpd_website'         => $website_url,
                '_gpd_phone_number'    => $phone_number,
                '_gpd_api_version'     => 'places_v1', // Mark as imported with new API
            ];

            // 5. Ensure term exists
            if ( $locality && ! term_exists( $locality, 'destination' ) ) {
                wp_insert_term( $locality, 'destination' );
            }

            // 6. Check existing post
            $existing_post_query = new WP_Query([
                'post_type'  => 'business',
                'meta_key'   => '_gpd_place_id',
                'meta_value' => $place_id,
                'fields'     => 'ids',
                'posts_per_page' => 1,
            ]);
            $existing_post_id = 0;
            if ( $existing_post_query->have_posts() ) {
                $existing_post_id = $existing_post_query->posts[0];
            }

            // 7. Prepare post data
            $post_data = [
                'post_type'   => 'business',
                'post_status' => 'publish',
                'post_title'  => $name,
                'meta_input'  => $meta_input,
            ];

            $current_post_id = 0;
            $is_update = false;

            // 8. Insert or update
            if ( $existing_post_id > 0 ) {
                $post_data['ID'] = $existing_post_id;
                $current_post_id = wp_update_post( $post_data, true );
                if ( ! is_wp_error( $current_post_id ) ) {
                    $updated++;
                    $is_update = true;
                }
            } else {
                $current_post_id = wp_insert_post( $post_data, true );
                if ( ! is_wp_error( $current_post_id ) ) {
                    $created++;
                }
            }

            // 9. Attach term explicitly
            if ( ! is_wp_error( $current_post_id ) && $current_post_id > 0 && $locality ) {
                wp_set_object_terms( $current_post_id, $locality, 'destination', false );
                clean_object_term_cache( $current_post_id, 'destination' );
            }

            // 10. Process Photos - NEW SECTION
            if (!is_wp_error($current_post_id) && $current_post_id > 0) {
                /**
                 * Allow plugins to handle photo processing themselves
                 * 
                 * @param bool $should_process_internally Whether the main plugin should process photos
                 * @param int $post_id The business post ID
                 * @param array $details The place details from API
                 * @return bool
                 */
                $should_process_internally = apply_filters('gpd_should_process_photos_internally', true, $current_post_id, $details);
                
                if ($should_process_internally) {
                    // Get photo limit from settings
                    $photo_limit = (int) get_option('gpd_photo_limit', 3);
                    
                    // Only process photos if limit is greater than 0
                    if ($photo_limit > 0 && !empty($details['photos'])) {
                        // Store the array of photo references
                        $photos_data = $details['photos'];
                        $featured_image_id = 0;
                        
                        // Store references for future use
                        $photo_refs = [];
                        
                        // Process up to the limit
                        $count = 0;
                        foreach ($photos_data as $photo_data) {
                            if ($count >= $photo_limit) {
                                break;
                            }
                            
                            if (isset($photo_data['name'])) {
                                $photo_refs[] = $photo_data['name'];
                                $photo_ref = $photo_data['name'];
                                
                                // Check if we already imported this photo
                                $existing_photo = get_posts([
                                    'post_type' => 'attachment',
                                    'posts_per_page' => 1,
                                    'meta_key' => '_gpd_photo_reference',
                                    'meta_value' => $photo_ref,
                                    'fields' => 'ids',
                                ]);
                                
                                if (!empty($existing_photo)) {
                                    // Photo already exists, use this ID
                                    $attach_id = $existing_photo[0];
                                } else {
                                    // Import the photo
                                    $attach_id = $this->import_photo($photo_ref, $current_post_id, $name, $count + 1);
                                }
                                
                                // Set the first photo as featured image
                                if ($attach_id && $count === 0) {
                                    set_post_thumbnail($current_post_id, $attach_id);
                                    $featured_image_id = $attach_id;
                                }
                                
                                $count++;
                            }
                        }
                        
                        // Store photo references as post meta
                        if (!empty($photo_refs)) {
                            update_post_meta($current_post_id, '_gpd_photo_references', $photo_refs);
                        }
                        
                        // Store featured image reference
                        if ($featured_image_id) {
                            update_post_meta($current_post_id, '_gpd_featured_photo_id', $featured_image_id);
                        }
                    }
                }
                
                /**
                 * Fires after photo processing should occur, whether handled internally or by an extension
                 * 
                 * @param int $post_id The business post ID
                 * @param array $details The place details from API
                 * @param bool $is_update Whether this was an update
                 */
                do_action('gpd_after_photo_processing', $current_post_id, $details, $is_update);
            }

            // Action hook
            if ( ! is_wp_error( $current_post_id ) && $current_post_id > 0 ) {
                /**
                 * Fires after a business has been successfully imported or updated by GPD_Importer.
                 *
                 * @param int   $post_id The ID of the business post that was created or updated.
                 * @param array $details The full place details array used for the import.
                 * @param bool  $is_update True if an existing post was updated, false if a new post was created.
                 */
                do_action( 'gpd_after_business_processed', $current_post_id, $details, $is_update );
            }
        }

        return [ 'created' => $created, 'updated' => $updated ];
    }
}
