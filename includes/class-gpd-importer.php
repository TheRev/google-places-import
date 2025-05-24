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
    private $error_log = [];
    private $batch_progress = [];
    
    // API endpoints
    private $text_endpoint    = 'https://places.googleapis.com/v1/places:searchText';
    private $details_endpoint = 'https://places.googleapis.com/v1/places/';

    // Batch settings
    const BATCH_SIZE = 5; // Maximum places to process per batch
    const RETRY_DELAY = 2; // Seconds to wait between retries
    const MAX_RETRIES = 3; // Maximum number of retries per request

    // Error codes
    const ERROR_API_KEY_MISSING = 'api_key_missing';
    const ERROR_API_REQUEST_FAILED = 'api_request_failed';
    const ERROR_INVALID_RESPONSE = 'invalid_response';
    const ERROR_RATE_LIMIT = 'rate_limit';
    const ERROR_PERMISSION_DENIED = 'permission_denied';
    const ERROR_INVALID_REQUEST = 'invalid_request';

    public static function instance() {
        if ( self::$instance === null ) {
            self::$instance          = new self();
            self::$instance->api_key = get_option( 'gpd_api_key' );
        }
        return self::$instance;
    }

    /**
     * Log an error for the current import process
     */
    private function log_error($code, $message, $context = []) {
        $this->error_log[] = [
            'code' => $code,
            'message' => $message,
            'context' => $context,
            'timestamp' => current_time('mysql')
        ];
        
        error_log(sprintf(
            '[Google Places Directory] Error %s: %s. Context: %s',
            $code,
            $message,
            wp_json_encode($context)
        ));
    }

    /**
     * Get all errors logged during the current import process
     */
    public function get_import_errors() {
        return $this->error_log;
    }

    /**
     * Handle API response and standardize error handling
     */
    private function handle_api_response($response, $endpoint) {
        if (is_wp_error($response)) {
            $this->log_error(
                self::ERROR_API_REQUEST_FAILED,
                $response->get_error_message(),
                ['endpoint' => $endpoint]
            );
            return new WP_Error(self::ERROR_API_REQUEST_FAILED, $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($response_code !== 200) {
            $error_message = isset($body['error']['message']) ? $body['error']['message'] : "API Error: HTTP {$response_code}";
            $error_code = self::ERROR_API_REQUEST_FAILED;

            // Map specific API error codes
            if (isset($body['error']['status'])) {
                switch ($body['error']['status']) {
                    case 'PERMISSION_DENIED':
                        $error_code = self::ERROR_PERMISSION_DENIED;
                        break;
                    case 'RESOURCE_EXHAUSTED':
                        $error_code = self::ERROR_RATE_LIMIT;
                        break;
                    case 'INVALID_ARGUMENT':
                        $error_code = self::ERROR_INVALID_REQUEST;
                        break;
                }
            }

            $this->log_error($error_code, $error_message, [
                'endpoint' => $endpoint,
                'response_code' => $response_code,
                'api_error' => $body['error'] ?? null
            ]);

            return new WP_Error($error_code, $error_message);
        }

        if (!is_array($body)) {
            $this->log_error(
                self::ERROR_INVALID_RESPONSE,
                'Invalid API response format',
                ['endpoint' => $endpoint]
            );
            return new WP_Error(self::ERROR_INVALID_RESPONSE, 'Invalid API response format');
        }

        return $body;
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

        $response = $this->handle_api_response($response, 'Text Search');

        if (is_wp_error($response)) {
            return $response;
        }

        $body = $response;
        
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
    }    /**
     * Normalize place data from the new API format to match the expected format in the rest of the plugin
     * 
     * @param array $place Place data from API
     * @return array Normalized place data
     * @throws Exception When the place data is incomplete or malformed
     */
    private function normalize_place_data($place) {
        // Validate required fields are present
        if (empty($place['id'])) {
            $this->log_error(self::ERROR_INVALID_RESPONSE, 'Missing place ID in API response');
            throw new Exception('Invalid place data: Missing place ID');
        }
        
        // Build normalized array with extensive validation
        $normalized = [
            'place_id' => sanitize_text_field($place['id'] ?? ''),
            'name' => isset($place['displayName']['text']) ? sanitize_text_field($place['displayName']['text']) : '',
            'formatted_address' => sanitize_textarea_field($place['formattedAddress'] ?? ''),
            'types' => $this->sanitize_place_types($place['types'] ?? []),
            'rating' => $this->validate_rating($place['rating'] ?? 0),
            'business_status' => sanitize_text_field($place['businessStatus'] ?? 'OPERATIONAL'),
            'url' => esc_url_raw($place['googleMapsUri'] ?? ''),
            'website' => isset($place['websiteUri']) ? esc_url_raw($place['websiteUri']) : '',
            'phone_number' => isset($place['internationalPhoneNumber']) ? sanitize_text_field($place['internationalPhoneNumber']) : '',
            'api_version' => 'v1', // Mark as imported with Places API v1
        ];
        
        // Handle address components 
        if (isset($place['addressComponents'])) {
            $normalized['address_components'] = $this->normalize_address_components($place['addressComponents']);
        }
        
        // Handle location/geometry
        if (isset($place['location'])) {
            $normalized['geometry'] = [
                'location' => [
                    'lat' => $this->validate_coordinate($place['location']['latitude'] ?? 0),
                    'lng' => $this->validate_coordinate($place['location']['longitude'] ?? 0)
                ]
            ];
        } else {
            // Set default coordinates if not present
            $normalized['geometry'] = [
                'location' => [
                    'lat' => 0,
                    'lng' => 0
                ]
            ];
            
            // Log a warning about missing coordinates
            $this->log_error('missing_coordinates', sprintf(
                'Missing coordinates for place %s (%s)',
                $normalized['place_id'],
                $normalized['name']
            ));
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

        $response = $this->handle_api_response($response, 'Place Details');

        if (is_wp_error($response)) {
            return false;
        }
        
        $body = $response;

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
                        
                        // Process in batches to avoid rate limits
                        $batch_results = $this->process_photos_batch($photos_data, $current_post_id, $name, $photo_limit);
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

    /**
     * Get the current batch import progress
     */
    public function get_batch_progress($batch_id) {
        return $this->batch_progress[$batch_id] ?? false;
    }

    /**
     * Process batch import with progress tracking
     */
    public function process_batch_import($places, $batch_id = null) {
        if (!$batch_id) {
            $batch_id = uniqid('gpd_import_');
        }

        $results = [
            'batch_id' => $batch_id,
            'total' => count($places),
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'failed' => 0,
            'errors' => [],
            'retry_queue' => []
        ];

        // Store initial batch status
        $this->batch_progress[$batch_id] = $results;

        // Process in smaller chunks to avoid timeouts
        $chunks = array_chunk($places, self::BATCH_SIZE);
        
        foreach ($chunks as $chunk) {
            // Add delay between chunks to respect rate limits
            if (isset($previous_chunk)) {
                sleep(self::RETRY_DELAY);
            }

            foreach ($chunk as $place) {
                $import_result = $this->process_single_place($place);
                $results['processed']++;

                if ($import_result['success']) {
                    if ($import_result['is_update']) {
                        $results['updated']++;
                    } else {
                        $results['created']++;
                    }
                } else {
                    $results['failed']++;
                    $results['errors'][] = [
                        'place_id' => $place['place_id'] ?? 'unknown',
                        'error' => $import_result['error']
                    ];

                    // Add to retry queue if eligible
                    if ($import_result['can_retry'] && count($results['retry_queue']) < 50) {
                        $results['retry_queue'][] = $place;
                    }
                }

                // Update progress
                $this->batch_progress[$batch_id] = $results;
            }
            $previous_chunk = true;
        }

        // Handle retries if any
        if (!empty($results['retry_queue'])) {
            $this->process_retry_queue($results);
        }

        // Clear progress data
        unset($this->batch_progress[$batch_id]);

        return $results;
    }

    /**
     * Process a single place with retries
     */
    private function process_single_place($place) {
        $result = [
            'success' => false,
            'is_update' => false,
            'error' => null,
            'can_retry' => false
        ];

        $retry_count = 0;
        $should_retry = true;

        while ($should_retry && $retry_count < self::MAX_RETRIES) {
            if ($retry_count > 0) {
                sleep(self::RETRY_DELAY * $retry_count);
            }

            $place_id = sanitize_text_field($place['place_id'] ?? '');
            if (!$place_id) {
                $result['error'] = new WP_Error('invalid_place', 'Invalid place data');
                break;
            }

            // Get full details
            $details = $this->get_place_details($place_id);
            if (!$details) {
                $retry_count++;
                continue;
            }

            // Import the place
            $import_result = $this->import_single_place_details($details);
            
            if (is_wp_error($import_result)) {
                $error_code = $import_result->get_error_code();
                $result['error'] = $import_result;
                
                // Determine if error is retryable
                $result['can_retry'] = in_array($error_code, [
                    self::ERROR_API_REQUEST_FAILED,
                    self::ERROR_RATE_LIMIT
                ], true);

                if ($result['can_retry']) {
                    $retry_count++;
                    continue;
                }
                break;
            }

            // Success
            $result['success'] = true;
            $result['is_update'] = $import_result['is_update'] ?? false;
            $should_retry = false;
        }

        return $result;
    }

    /**
     * Process the retry queue for failed imports
     */
    private function process_retry_queue(&$results) {
        if (empty($results['retry_queue'])) {
            return;
        }

        // Wait longer before retrying failed requests
        sleep(self::RETRY_DELAY * 2);

        foreach ($results['retry_queue'] as $i => $place) {
            $retry_result = $this->process_single_place($place);
            
            // Update counters based on retry result
            if ($retry_result['success']) {
                $results['failed']--;
                if ($retry_result['is_update']) {
                    $results['updated']++;
                } else {
                    $results['created']++;
                }
                
                // Remove successfully processed items
                unset($results['retry_queue'][$i]);
                
                // Remove the error entry
                foreach ($results['errors'] as $j => $error) {
                    if ($error['place_id'] === ($place['place_id'] ?? '')) {
                        unset($results['errors'][$j]);
                        break;
                    }
                }
            }
        }

        // Clean up arrays
        $results['retry_queue'] = array_values($results['retry_queue']);
        $results['errors'] = array_values($results['errors']);
    }

    /**
     * Import a single place from its details
     */
    private function import_single_place_details($details) {
        try {
            $place_id = sanitize_text_field($details['place_id'] ?? '');
            if (!$place_id) {
                return new WP_Error('invalid_place', 'Missing place ID');
            }

            // Extract and validate required fields
            $name = sanitize_text_field($details['name'] ?? '');
            if (!$name) {
                return new WP_Error('invalid_place', 'Missing business name');
            }

            // Prepare meta input
            $meta_input = $this->prepare_place_meta($details);

            // Find locality
            $locality = $this->extract_locality($details['address_components'] ?? []);
            
            // Create/update the post
            $post_data = [
                'post_type' => 'business',
                'post_status' => 'publish',
                'post_title' => $name,
                'meta_input' => $meta_input
            ];

            // Check for existing post
            $existing_post_id = $this->find_existing_place($place_id);
            $is_update = false;

            if ($existing_post_id) {
                $post_data['ID'] = $existing_post_id;
                $post_id = wp_update_post($post_data, true);
                $is_update = true;
            } else {
                $post_id = wp_insert_post($post_data, true);
            }

            if (is_wp_error($post_id)) {
                return $post_id;
            }

            // Handle taxonomy
            if ($locality) {
                $this->handle_locality_taxonomy($post_id, $locality);
            }            // Handle photos if enabled
            if (get_option('gpd_photo_limit', 3) > 0) {
                $this->handle_photos($post_id, $details);
                
                // Double-check that a featured image was set
                if (!get_post_thumbnail_id($post_id)) {
                    // Try to find the first valid attachment for this post
                    $attachments = get_posts(array(
                        'post_type' => 'attachment',
                        'posts_per_page' => 1,
                        'post_parent' => $post_id,
                        'meta_key' => '_gpd_photo_reference',
                        'orderby' => 'ID',
                        'order' => 'ASC'
                    ));
                    
                    if (!empty($attachments)) {
                        error_log('Setting featured image through fallback method for business ' . $post_id);
                        set_post_thumbnail($post_id, $attachments[0]->ID);
                    }
                }
            }

            return [
                'post_id' => $post_id,
                'is_update' => $is_update
            ];

        } catch (Exception $e) {
            return new WP_Error('import_failed', $e->getMessage());
        }
    }

    /**
     * Helper: Extract locality from address components
     */
    private function extract_locality($components) {
        foreach ($components as $comp) {
            if (in_array('locality', (array)($comp['types'] ?? []), true)) {
                return sanitize_text_field($comp['long_name']);
            }
        }
        return '';
    }

    /**
     * Helper: Handle locality taxonomy terms
     */
    private function handle_locality_taxonomy($post_id, $locality) {
        if (!term_exists($locality, 'destination')) {
            wp_insert_term($locality, 'destination');
        }
        wp_set_object_terms($post_id, $locality, 'destination', false);
        clean_object_term_cache($post_id, 'destination');
    }

    /**
     * Helper: Find existing place by place_id
     */
    private function find_existing_place($place_id) {
        $existing = get_posts([
            'post_type' => 'business',
            'meta_key' => '_gpd_place_id',
            'meta_value' => $place_id,
            'fields' => 'ids',
            'posts_per_page' => 1
        ]);
        return !empty($existing) ? $existing[0] : 0;
    }

    /**
     * Helper: Prepare place meta data
     */
    private function prepare_place_meta($details) {
        return [
            '_gpd_place_id' => $details['place_id'] ?? '',
            '_gpd_display_name' => $details['name'] ?? '',
            '_gpd_address' => $details['formatted_address'] ?? '',
            '_gpd_latitude' => $details['geometry']['location']['lat'] ?? 0,
            '_gpd_longitude' => $details['geometry']['location']['lng'] ?? 0,
            '_gpd_types' => wp_json_encode($details['types'] ?? []),
            '_gpd_rating' => floatval($details['rating'] ?? 0),
            '_gpd_business_status' => $details['business_status'] ?? '',
            '_gpd_maps_uri' => esc_url_raw($details['url'] ?? ''),
            '_gpd_website' => esc_url_raw($details['website'] ?? ''),
            '_gpd_phone_number' => sanitize_text_field($details['international_phone_number'] ?? ''),
            '_gpd_api_version' => 'places_v1'
        ];
    }
    
    /**
     * Helper: Handle photos for a business
     */
    private function handle_photos($post_id, $details) {
        if (empty($details['photos'])) {
            return;
        }
        
        $photo_limit = (int)get_option('gpd_photo_limit', 3);
        if ($photo_limit <= 0) {
            return;
        }

        $featured_image_id = 0;
        $photo_refs = [];
        
        // Process up to the limit
        $count = 0;
        foreach ($details['photos'] as $photo_data) {
            if ($count >= $photo_limit) {
                break;
            }
            
            if (!isset($photo_data['name'])) {
                continue;
            }
            
            $photo_refs[] = $photo_data['name'];
            $photo_ref = $photo_data['name'];
            
            // Check if photo already exists
            $existing_photo = get_posts([
                'post_type' => 'attachment',
                'posts_per_page' => 1,
                'meta_key' => '_gpd_photo_reference',
                'meta_value' => $photo_ref,
                'fields' => 'ids'
            ]);
            
            $attach_id = 0;
            if (!empty($existing_photo)) {
                $attach_id = $existing_photo[0];
            } else {
                $attach_id = $this->import_photo($photo_ref, $post_id, $details['name'], $count + 1);
            }
            
            // Always set first successfully imported photo as featured image
            if ($attach_id && !$featured_image_id) {
                $result = set_post_thumbnail($post_id, $attach_id);
                if ($result) {
                    $featured_image_id = $attach_id;
                    // Store the featured photo ID as backup
                    update_post_meta($post_id, '_gpd_featured_photo_id', $attach_id);
                } else {
                    error_log('GPD: Failed to set featured image for business ' . $post_id . ' using attachment ' . $attach_id);
                }
            }
            
            $count++;
        }
        
        // Update photo references
        if (!empty($photo_refs)) {
            update_post_meta($post_id, '_gpd_photo_references', $photo_refs);
        }
    }
      /**
     * Import a single photo from the Places API
     */
    private function import_photo($photo_reference, $post_id, $business_name, $photo_number) {
        if (empty($photo_reference) || empty($this->api_key)) {
            error_log('Missing photo reference or API key for business ' . $post_id);
            return false;
        }
        
        // Ensure business name is valid for filename
        $business_name = !empty($business_name) ? sanitize_title($business_name) : 'business';
        
        // Build the photo URL using the Places API v1 format
        $url = "{$this->details_endpoint}{$photo_reference}/media";
        
        // Set up headers for the photo request
        $headers = [
            'X-Goog-Api-Key' => $this->api_key,
            'Accept' => 'image/*'  // Ensure we get an image back
        ];
        
        error_log('Downloading photo ' . $photo_number . ' for business ' . $post_id . ' using reference: ' . $photo_reference);
        
        // Make the request to get the photo
        $response = wp_remote_get($url, [
            'headers' => $headers,
            'timeout' => 30,
            'sslverify' => true
        ]);
        
        // Check for errors
        if (is_wp_error($response)) {
            error_log('Failed to download photo: ' . $response->get_error_message());
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            error_log('Failed to download photo. HTTP Status: ' . $status_code);
            return false;
        }
        
        // Get the image data
        $image_data = wp_remote_retrieve_body($response);
        if (empty($image_data) || strlen($image_data) < 100) { // Basic check for valid image data
            error_log('Empty or invalid image data received');
            return false;
        }
        
        // Get the content type to determine file extension
        $content_type = wp_remote_retrieve_header($response, 'content-type');
        $ext = $this->get_file_extension_from_content_type($content_type);
        
        // Generate a unique filename with sanitized business name
        $filename = sanitize_file_name($business_name . '-photo-' . $photo_number . $ext);
        
        // Create temp file
        $temp_file = wp_tempnam($filename);
        if (!$temp_file || !file_put_contents($temp_file, $image_data)) {
            error_log('Failed to create or write to temp file');
            return false;
        }
        
        // Prepare file array for wp_handle_sideload
        $file = [
            'name' => $filename,
            'type' => $content_type,
            'tmp_name' => $temp_file,
            'error' => 0,
            'size' => filesize($temp_file)
        ];
        
        // Handle the upload
        $overrides = [
            'test_form' => false,
            'test_size' => true,
        ];
        
        // Move the file to uploads directory
        $file_data = wp_handle_sideload($file, $overrides);
        
        // Check for errors in sideloading
        if (isset($file_data['error'])) {
            error_log('Failed to handle photo sideload: ' . $file_data['error']);
            @unlink($temp_file);
            return false;
        }
        
        // Make sure required file data exists
        if (empty($file_data['file']) || empty($file_data['type'])) {
            error_log('Invalid file data after sideload');
            @unlink($temp_file);
            return false;
        }
        
        // Prepare attachment data
        $attachment = [
            'post_mime_type' => $file_data['type'],
            'post_title' => $business_name . ' - Photo ' . $photo_number,
            'post_content' => '',
            'post_status' => 'inherit',
            'post_parent' => $post_id
        ];
        
        // Include image functions if needed
        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        // Insert attachment
        $attach_id = wp_insert_attachment($attachment, $file_data['file'], $post_id);
        
        if (is_wp_error($attach_id)) {
            error_log('Failed to insert attachment: ' . $attach_id->get_error_message());
            @unlink($temp_file);
            return false;
        }
        
        if (!$attach_id || $attach_id == 0) {
            error_log('Zero or invalid attachment ID returned by wp_insert_attachment');
            @unlink($temp_file);
            return false;
        }
        
        // Generate attachment metadata
        $attach_data = wp_generate_attachment_metadata($attach_id, $file_data['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);
        
        // Store the photo reference as attachment meta
        update_post_meta($attach_id, '_gpd_photo_reference', $photo_reference);
        update_post_meta($attach_id, '_gpd_photo_attribution', 'Google Places API');
        
        // Clean up temp file
        @unlink($temp_file);
        
        error_log('Successfully created attachment ID ' . $attach_id . ' for business ' . $post_id . ' photo ' . $photo_number);
        
        return $attach_id;
    }
    
    /**
     * Helper: Get file extension from content type
     */
    private function get_file_extension_from_content_type($content_type) {
        switch ($content_type) {
            case 'image/jpeg':
                return '.jpg';
            case 'image/png':
                return '.png';
            case 'image/gif':
                return '.gif';
            case 'image/webp':
                return '.webp';
            default:
                return '.jpg'; // Default to jpg
        }
    }
    
    /**
     * Filter to remove upload size limit for photos from Google Places API
     */
    public function remove_upload_size_limit() {
        return PHP_INT_MAX;
    }

    /**
     * Validate rating value
     *
     * @param mixed $rating Rating value from API
     * @return float Validated rating between 0 and 5
     */
    private function validate_rating($rating) {
        $rating = floatval($rating);
        
        // Ensure rating is between 0 and 5
        if ($rating < 0) {
            return 0;
        }
        
        if ($rating > 5) {
            return 5;
        }
        
        return round($rating, 1);
    }
    
    /**
     * Validate a coordinate value
     *
     * @param mixed $coordinate Latitude or longitude value
     * @return float Validated coordinate
     */
    private function validate_coordinate($coordinate) {
        $coordinate = floatval($coordinate);
        
        // Basic validation to ensure coordinates are within possible Earth values
        // Latitude: -90 to 90, Longitude: -180 to 180
        if ($coordinate < -180) {
            return -180;
        }
        
        if ($coordinate > 180) {
            return 180;
        }
        
        return $coordinate;
    }
    
    /**
     * Sanitize place types array
     *
     * @param array $types Array of place types from API
     * @return array Sanitized array of place types
     */
    private function sanitize_place_types($types) {
        if (!is_array($types)) {
            return [];
        }
        
        $sanitized = [];
        
        foreach ($types as $type) {
            if (is_string($type)) {
                $sanitized[] = sanitize_text_field($type);
            }
        }
        
        return $sanitized;
    }

    /**
     * Process a batch of photos for a business
     * 
     * @param array $photos_data Array of photo data from the Places API
     * @param int $post_id The post ID of the business
     * @param string $business_name Name of the business
     * @param int $photo_limit Maximum number of photos to process
     * @return array Results of the photo processing
     */
    private function process_photos_batch($photos_data, $post_id, $business_name, $photo_limit) {
        if (empty($photos_data) || empty($post_id) || $photo_limit <= 0) {
            return [
                'success' => false,
                'photos_processed' => 0,
                'message' => 'Invalid input data'
            ];
        }
        
        $featured_image_id = 0;
        $photo_refs = [];
        $photos_added = 0;
        $results = [];
        
        // Process photos up to the limit
        $photos = array_slice($photos_data, 0, $photo_limit);
        
        foreach ($photos as $index => $photo_data) {
            if (!isset($photo_data['name'])) {
                $results[] = [
                    'status' => 'error',
                    'message' => 'No photo name/reference found',
                    'photo_data' => $photo_data
                ];
                continue;
            }
            
            $photo_refs[] = $photo_data['name'];
            $photo_ref = $photo_data['name'];
            
            // Check if photo already exists
            $existing_photo = get_posts([
                'post_type' => 'attachment',
                'posts_per_page' => 1,
                'meta_key' => '_gpd_photo_reference',
                'meta_value' => $photo_ref,
                'fields' => 'ids'
            ]);
            
            $attach_id = 0;
            if (!empty($existing_photo)) {
                $attach_id = $existing_photo[0];
                $results[] = [
                    'status' => 'success',
                    'message' => 'Using existing photo',
                    'attachment_id' => $attach_id,
                    'photo_reference' => $photo_ref
                ];
            } else {
                $attach_id = $this->import_photo($photo_ref, $post_id, $business_name, $index + 1);
                
                if ($attach_id) {
                    $photos_added++;
                    $results[] = [
                        'status' => 'success',
                        'message' => 'Imported new photo',
                        'attachment_id' => $attach_id,
                        'photo_reference' => $photo_ref
                    ];
                } else {
                    $results[] = [
                        'status' => 'error',
                        'message' => 'Failed to download or attach photo',
                        'photo_reference' => $photo_ref
                    ];
                }
            }
            
        // Set first photo as featured image
            if ($attach_id && $index === 0) {
                // First try the WordPress function
                $result = set_post_thumbnail($post_id, $attach_id);
                
                // If that fails, try direct database update
                if (!$result) {
                    global $wpdb;
                    update_post_meta($post_id, '_thumbnail_id', $attach_id);
                    error_log('Used direct update for featured image on post ' . $post_id . ' with attachment ' . $attach_id);
                }
                
                // Store the featured image ID
                $featured_image_id = $attach_id;
                
                // Check if it was actually set
                $check_id = get_post_thumbnail_id($post_id);
                if (!$check_id) {
                    error_log('Failed to set featured image for post ' . $post_id . ' with attachment ' . $attach_id);
                } else {
                    error_log('Successfully set featured image for post ' . $post_id . ' with attachment ' . $attach_id);
                }
            }
        }
        
        // Save photo references to post meta
        if (!empty($photo_refs)) {
            update_post_meta($post_id, '_gpd_photo_references', $photo_refs);
        }
        
        // Save featured image reference
        if ($featured_image_id) {
            // Store our own reference in case we need it later
            update_post_meta($post_id, '_gpd_featured_photo_id', $featured_image_id);
            
            // Double check the thumbnail was set
            $current_thumbnail_id = get_post_thumbnail_id($post_id);
            if (!$current_thumbnail_id) {
                // Try one more time directly
                update_post_meta($post_id, '_thumbnail_id', $featured_image_id);
            }
        }
        
        return [
            'success' => true,
            'photos_processed' => count($photos),
            'photos_added' => $photos_added,
            'photo_references' => $photo_refs,
            'featured_image_id' => $featured_image_id,
            'results' => $results
        ];
    }
}
