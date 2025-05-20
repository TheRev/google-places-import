<?php
/**
 * Class GPD_Photo_Manager
 *
 * Manages photo operations for batch processing and metadata
 * 
 * @since 2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GPD_Photo_Manager {
    private static $instance = null;

    public static function instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
            self::$instance->init_hooks();
        }
        return self::$instance;
    }

    private function init_hooks() {
    // Add admin menu for photo management
    add_action( 'admin_menu', array( $this, 'add_photo_management_page' ) );
    
    // Add bulk action for refreshing photos
    add_filter( 'bulk_actions-edit-business', array( $this, 'add_bulk_actions' ) );
    add_filter( 'handle_bulk_actions-edit-business', array( $this, 'handle_bulk_actions' ), 10, 3 );
    
    // Add admin notices
    add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
    
    // Add Ajax handlers
    add_action( 'wp_ajax_gpd_refresh_business_photos', array( $this, 'ajax_refresh_photos' ) );
    add_action( 'wp_ajax_gpd_get_businesses_without_photos', array( $this, 'ajax_get_businesses_without_photos' ) );
}

    /**
     * Add the photo management page to the admin menu
     */
    public function add_photo_management_page() {
        add_submenu_page(
            'edit.php?post_type=business',
            __( 'Photo Management', 'google-places-directory' ),
            __( 'Photo Management', 'google-places-directory' ),
            'manage_options',
            'gpd-photo-management',
            array( $this, 'render_photo_management_page' )
        );
    }

    /**
     * Add bulk actions for business photos
     *
     * @param array $bulk_actions Available bulk actions
     * @return array Modified bulk actions
     */
    public function add_bulk_actions( $bulk_actions ) {
        $bulk_actions['gpd_refresh_photos'] = __( 'Refresh Photos from Google', 'google-places-directory' );
        return $bulk_actions;
    }

    /**
     * Handle bulk actions for business photos
     *
     * @param string $redirect_to Redirect URL
     * @param string $doaction Action being performed
     * @param array $post_ids Array of post IDs to process
     * @return string Modified redirect URL
     */
    public function handle_bulk_actions( $redirect_to, $doaction, $post_ids ) {
        if ( $doaction !== 'gpd_refresh_photos' ) {
            return $redirect_to;
        }

        $processed = 0;
        $updated = 0;
        $failed = 0;
        $place_ids = array();

        // Process each selected business
        foreach ( $post_ids as $post_id ) {
            $place_id = get_post_meta( $post_id, '_gpd_place_id', true );
            
            if ( !$place_id ) {
                $failed++;
                continue;
            }
            
            $place_ids[] = array(
                'post_id' => $post_id,
                'place_id' => $place_id
            );
            
            $processed++;
        }
        
        // Store the list of place IDs in a transient for processing
        if (!empty($place_ids)) {
            set_transient('gpd_batch_photo_refresh', $place_ids, 12 * HOUR_IN_SECONDS);
        }

        // Add query args for admin notice
        $redirect_to = add_query_arg(
            array(
                'gpd_photo_refresh' => '1',
                'processed' => $processed,
                'updated' => $updated,
                'failed' => $failed,
            ),
            $redirect_to
        );

        return $redirect_to;
    }

    /**
     * Display admin notices for photo refresh operations
     */
    public function display_admin_notices() {
        $screen = get_current_screen();
        
        // Check if this is the business listing screen
        if ( $screen && $screen->id === 'edit-business' && isset( $_GET['gpd_photo_refresh'] ) ) {
            $processed = isset( $_GET['processed'] ) ? intval( $_GET['processed'] ) : 0;
            
            if ($processed > 0) {
                // Get the stored place IDs for batch processing
                $place_ids = get_transient('gpd_batch_photo_refresh');
                
                if (!empty($place_ids)) {
                    // Render the batch processing UI
                    ?>
                    <div class="notice notice-info gpd-batch-process-notice">
                        <h3><?php echo sprintf(
                            _n(
                                'Photo Refresh: Processing %d Business',
                                'Photo Refresh: Processing %d Businesses',
                                count($place_ids),
                                'google-places-directory'
                            ),
                            count($place_ids)
                        ); ?></h3>
                        
                        <div class="gpd-batch-progress">
                            <div class="gpd-progress-bar">
                                <div class="gpd-progress-complete" style="width: 0%"></div>
                            </div>
                            <div class="gpd-progress-status">
                                <span class="gpd-processed">0</span> / <span class="gpd-total"><?php echo count($place_ids); ?></span>
                            </div>
                        </div>
                        
                        <div class="gpd-batch-results">
                            <p><?php _e('Processing photos...', 'google-places-directory'); ?></p>
                        </div>
                        
                        <script>
                        jQuery(document).ready(function($) {
                            var totalItems = <?php echo count($place_ids); ?>;
                            var processedItems = 0;
                            var successCount = 0;
                            var failCount = 0;
                            var photoCount = 0;
                            
                            function processNextItem(items, index) {
                                if (index >= items.length) {
                                    // All done
                                    $('.gpd-batch-results').html(
                                        '<p>' + 
                                        '<?php _e('Processing complete!', 'google-places-directory'); ?>' +
                                        '</p><p>' +
                                        '<?php _e('Results:', 'google-places-directory'); ?> ' +
                                        successCount + ' <?php _e('businesses updated with', 'google-places-directory'); ?> ' +
                                        photoCount + ' <?php _e('total photos', 'google-places-directory'); ?>. ' +
                                        failCount + ' <?php _e('businesses failed.', 'google-places-directory'); ?>' +
                                        '</p>'
                                    );
                                    return;
                                }
                                
                                var item = items[index];
                                
                                // Call AJAX to process this item
                                $.ajax({
                                    url: ajaxurl,
                                    type: 'POST',
                                    data: {
                                        action: 'gpd_refresh_business_photos',
                                        post_id: item.post_id,
                                        place_id: item.place_id,
                                        nonce: '<?php echo wp_create_nonce('gpd_refresh_photos'); ?>'
                                    },
                                    success: function(response) {
                                        processedItems++;
                                        
                                        if (response.success) {
                                            successCount++;
                                            photoCount += response.data.photos_added || 0;
                                        } else {
                                            failCount++;
                                        }
                                        
                                        // Update progress
                                        var percent = Math.round((processedItems / totalItems) * 100);
                                        $('.gpd-progress-complete').css('width', percent + '%');
                                        $('.gpd-processed').text(processedItems);
                                        
                                        // Process next item
                                        processNextItem(items, index + 1);
                                    },
                                    error: function() {
                                        processedItems++;
                                        failCount++;
                                        
                                        // Update progress
                                        var percent = Math.round((processedItems / totalItems) * 100);
                                        $('.gpd-progress-complete').css('width', percent + '%');
                                        $('.gpd-processed').text(processedItems);
                                        
                                        // Process next item
                                        processNextItem(items, index + 1);
                                    }
                                });
                            }
                            
                            // Start processing
                            processNextItem(<?php echo json_encode($place_ids); ?>, 0);
                        });
                        </script>
                        
                        <style>
                        .gpd-batch-process-notice {
                            padding: 15px;
                        }
                        .gpd-batch-process-notice h3 {
                            margin-top: 0;
                        }
                        .gpd-batch-progress {
                            margin: 20px 0;
                        }
                        .gpd-progress-bar {
                            width: 100%;
                            height: 20px;
                            background-color: #f0f0f0;
                            border-radius: 3px;
                            margin-bottom: 10px;
                            overflow: hidden;
                        }
                        .gpd-progress-complete {
                            height: 100%;
                            background-color: #0073aa;
                            transition: width 0.3s ease;
                        }
                        .gpd-progress-status {
                            text-align: center;
                            font-weight: bold;
                        }
                        .gpd-batch-results {
                            margin-top: 15px;
                            padding-top: 15px;
                            border-top: 1px solid #eee;
                        }
                        </style>
                    </div>
                    <?php
                } else {
                    // Just show a simple notice
                    echo '<div class="notice notice-success is-dismissible"><p>' . 
                        sprintf(
                            __( 'Selected %d businesses for photo refresh. Processing in background.', 'google-places-directory' ),
                            $processed
                        ) . 
                    '</p></div>';
                }
            }
        }
        
        // Check if this is the photo management page
        if ( $screen && $screen->id === 'business_page_gpd-photo-management' ) {
            if ( isset( $_GET['gpd_action'] ) && $_GET['gpd_action'] === 'refresh_all' && isset( $_GET['count'] ) ) {
                $count = intval( $_GET['count'] );
                
                echo '<div class="notice notice-success is-dismissible"><p>' . 
                    sprintf(
                        _n(
                            'Refreshing photos for %d business with missing photos.',
                            'Refreshing photos for %d businesses with missing photos.',
                            $count,
                            'google-places-directory'
                        ),
                        $count
                    ) . 
                '</p></div>';
            }
        }
    }
    
    /**
 * Ajax handler for getting businesses without photos
 */
public function ajax_get_businesses_without_photos() {
    // Check permissions
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Permission denied.', 'google-places-directory' ) ) );
    }

    // Verify nonce
    if ( ! check_ajax_referer( 'gpd_photo_management', 'nonce', false ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed.', 'google-places-directory' ) ) );
    }

    // Get businesses without photos
    $businesses = $this->get_businesses_without_photos();
    $business_data = array();

    foreach ( $businesses as $business ) {
        $business_data[] = array(
            'id' => $business->ID,
            'title' => $business->post_title,
            'place_id' => get_post_meta( $business->ID, '_gpd_place_id', true ),
        );
    }

    wp_send_json_success( array(
        'businesses' => $business_data,
        'count' => count( $business_data ),
    ) );
}

/**
 * Ajax handler for refreshing photos for a business
 */
public function ajax_refresh_photos() {
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Permission denied.', 'google-places-directory')));
    }

    // Verify nonce
    if (!check_ajax_referer('gpd_refresh_photos', 'nonce', false)) {
        wp_send_json_error(array('message' => __('Security check failed.', 'google-places-directory')));
    }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $place_id = isset($_POST['place_id']) ? sanitize_text_field($_POST['place_id']) : '';

    if (!$post_id || !$place_id) {
        wp_send_json_error(array(
            'message' => __('Invalid business data.', 'google-places-directory'),
            'post_id' => $post_id,
            'place_id' => $place_id
        ));
    }

    // Get photo limit setting
    $photo_limit = (int)get_option('gpd_photo_limit', 3);
    
    if ($photo_limit <= 0) {
        wp_send_json_error(array(
            'message' => __('Photo importing is disabled in settings. Please set a photo limit greater than 0.', 'google-places-directory'),
            'post_id' => $post_id,
            'photo_limit' => $photo_limit
        ));
    }

    // Fetch the business details from Google
    $api_key = get_option('gpd_api_key');
    
    if (empty($api_key)) {
        wp_send_json_error(array(
            'message' => __('Google API Key is not configured.', 'google-places-directory'),
            'post_id' => $post_id
        ));
    }
    
    // Debug info
    $debug_info = array(
        'place_id' => $place_id,
        'photo_limit' => $photo_limit
    );

    // In Places API v1, we need to use either /places/{place_id} or search for the place
    // For this example, we'll assume place_id already has the proper format
    // If not, we might need to convert from legacy place_id to v1 format
    
    // Check if place_id starts with "places/"
    if (strpos($place_id, 'places/') !== 0) {
        // This is likely a legacy place_id, construct the new format
        $place_resource_name = 'places/' . $place_id;
    } else {
        // Already in the correct format
        $place_resource_name = $place_id;
    }
    
    // Request to get place details including photos
    $response = wp_remote_get(
        'https://places.googleapis.com/v1/' . $place_resource_name . '?fields=id,displayName,photos&key=' . urlencode($api_key),
        array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-Goog-Api-Key' => $api_key,
            )
        )
    );

    if (is_wp_error($response)) {
        wp_send_json_error(array(
            'message' => 'API Request Error: ' . $response->get_error_message(),
            'post_id' => $post_id,
            'debug' => $debug_info
        ));
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    error_log('Google Places API Response: ' . $body);
    
    if ($status_code !== 200) {
        wp_send_json_error(array(
            'message' => 'API Error: HTTP Status ' . $status_code,
            'post_id' => $post_id,
            'debug' => $debug_info,
            'response' => $body
        ));
    }

    $data = json_decode($body, true);

    if (empty($data)) {
        wp_send_json_error(array(
            'message' => __('Failed to parse API response.', 'google-places-directory'),
            'post_id' => $post_id,
            'debug' => $debug_info,
            'response_body' => $body
        ));
    }
    
    if (isset($data['error'])) {
        $error = isset($data['error']['message']) ? $data['error']['message'] : __('Unknown API error', 'google-places-directory');
        wp_send_json_error(array(
            'message' => 'API Error: ' . $error,
            'post_id' => $post_id,
            'debug' => $debug_info,
            'error_details' => $data['error']
        ));
    }

    // Check if photos are available
    if (empty($data['photos'])) {
        wp_send_json_success(array(
            'message' => __('No photos available for this business in Google Places.', 'google-places-directory'),
            'post_id' => $post_id,
            'photos_added' => 0,
            'debug' => $debug_info
        ));
    }

    // Process the photos
    $photos = array_slice($data['photos'], 0, $photo_limit);
    $photo_references = array();
    $photos_added = 0;

    // Delete any existing photos for this business
    $this->remove_business_photos($post_id);

    // Import each photo
    $photo_results = array();
    foreach ($photos as $photo) {
        if (empty($photo['name'])) {
            $photo_results[] = array(
                'status' => 'error',
                'message' => 'No photo name/reference found',
                'photo_data' => $photo
            );
            continue;
        }

        // In Places API v1, the photo reference is the full resource name
        $photo_reference = $photo['name'];
        $photo_references[] = $photo_reference;
        
        // Download and attach the photo
            $attachment_id = $this->download_and_attach_photo($photo_reference, $post_id, $photo_reference);

        if ($attachment_id) {
            $photos_added++;
            $photo_results[] = array(
                'status' => 'success',
                'attachment_id' => $attachment_id,
                'photo_reference' => $photo_reference
            );
            
            // Set the first photo as the featured image
            if (count($photo_references) === 1) {
                set_post_thumbnail($post_id, $attachment_id);
            }
        } else {
            $photo_results[] = array(
                'status' => 'error',
                'message' => 'Failed to download or attach photo',
                'photo_reference' => $photo_reference
            );
        }
    }

    // Save photo references
    if (!empty($photo_references)) {
        update_post_meta($post_id, '_gpd_photo_references', $photo_references);
    }

    // Return success
    if ($photos_added > 0) {
        wp_send_json_success(array(
            'message' => sprintf(
                _n(
                    'Successfully added %d photo.',
                    'Successfully added %d photos.',
                    $photos_added,
                    'google-places-directory'
                ),
                $photos_added
            ),
            'post_id' => $post_id,
            'photos_added' => $photos_added,
            'debug' => array_merge($debug_info, array(
                'photo_results' => $photo_results,
                'total_photos_found' => count($data['photos']),
                'photos_processed' => count($photos)
            ))
        ));
    } else {
        wp_send_json_error(array(
            'message' => __('Failed to download any photos.', 'google-places-directory'),
            'post_id' => $post_id,
            'debug' => array_merge($debug_info, array(
                'photo_results' => $photo_results,
                'total_photos_found' => isset($data['photos']) ? count($data['photos']) : 0
            ))
        ));
    }
}

    /**
     * Render the photo management page
     */
    public function render_photo_management_page() {
        // Get statistics
        $total_businesses = $this->count_businesses();
        $businesses_with_photos = $this->count_businesses_with_photos();
        $businesses_without_photos = $total_businesses - $businesses_with_photos;
        $total_photos = $this->count_total_photos();
        $photo_limit = (int) get_option( 'gpd_photo_limit', 3 );
        
        ?>
        <div class="wrap gpd-photo-management">
            <h1><?php esc_html_e( 'Photo Management', 'google-places-directory' ); ?></h1>
            
            <div class="gpd-photo-stats">
                <div class="gpd-stat-card">
                    <h2><?php echo number_format( $total_businesses ); ?></h2>
                    <p><?php esc_html_e( 'Total Businesses', 'google-places-directory' ); ?></p>
                </div>
                
                <div class="gpd-stat-card">
                    <h2><?php echo number_format( $businesses_with_photos ); ?></h2>
                    <p><?php esc_html_e( 'Businesses with Photos', 'google-places-directory' ); ?></p>
                </div>
                
                <div class="gpd-stat-card">
                    <h2><?php echo number_format( $businesses_without_photos ); ?></h2>
                    <p><?php esc_html_e( 'Businesses without Photos', 'google-places-directory' ); ?></p>
                </div>
                
                <div class="gpd-stat-card">
                    <h2><?php echo number_format( $total_photos ); ?></h2>
                    <p><?php esc_html_e( 'Total Photos', 'google-places-directory' ); ?></p>
                </div>
            </div>
            
            <div class="gpd-photo-actions">
                <div class="gpd-action-card">
                    <h3><?php esc_html_e( 'Batch Photo Operations', 'google-places-directory' ); ?></h3>
                    
                    <?php if ( $photo_limit <= 0 ) : ?>
                        <div class="notice notice-warning inline">
                            <p><?php esc_html_e( 'Photo importing is currently disabled in settings. Please set a photo limit greater than 0 to enable photo importing.', 'google-places-directory' ); ?></p>
                            <p><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=business&page=gpd-settings' ) ); ?>" class="button"><?php esc_html_e( 'Go to Settings', 'google-places-directory' ); ?></a></p>
                        </div>
                    <?php else : ?>
                        <p><?php esc_html_e( 'These actions allow you to refresh photos for businesses in bulk.', 'google-places-directory' ); ?></p>
                        
                        <div class="gpd-action-buttons">
                            <?php if ( $businesses_without_photos > 0 ) : ?>
                                <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 
                                    'gpd_action' => 'refresh_all',
                                    'count' => $businesses_without_photos
                                ) ), 'gpd_refresh_all', 'gpd_nonce' ) ); ?>" class="button button-primary gpd-batch-button" data-businesses="<?php echo esc_attr( $businesses_without_photos ); ?>">
                                    <?php echo sprintf(
                                        _n(
                                            'Add Photos to %d Business',
                                            'Add Photos to %d Businesses',
                                            $businesses_without_photos,
                                            'google-places-directory'
                                        ),
                                        $businesses_without_photos
                                    ); ?>
                                </a>
                            <?php else : ?>
                                <button class="button" disabled><?php esc_html_e( 'All Businesses Have Photos', 'google-places-directory' ); ?></button>
                            <?php endif; ?>
                            
                            <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=business' ) ); ?>" class="button">
                                <?php esc_html_e( 'Select Specific Businesses', 'google-places-directory' ); ?>
                            </a>
                        </div>
                        
                        <div class="gpd-batch-processing" style="display: none;">
                            <h4><?php esc_html_e( 'Processing Photos', 'google-places-directory' ); ?></h4>
                            
                            <div class="gpd-batch-progress">
                                <div class="gpd-progress-bar">
                                    <div class="gpd-progress-complete" style="width: 0%"></div>
                                </div>
                                <div class="gpd-progress-status">
                                    <span class="gpd-processed">0</span> / <span class="gpd-total">0</span>
                                </div>
                            </div>
                            
                            <div class="gpd-batch-results">
                                <p><?php esc_html_e( 'Preparing batch process...', 'google-places-directory' ); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="gpd-action-card">
                    <h3><?php esc_html_e( 'Tips and Information', 'google-places-directory' ); ?></h3>
                    
                    <ul class="gpd-tips-list">
                        <li><?php printf(
                            __( 'Current photo limit is set to %d per business.', 'google-places-directory' ),
                            $photo_limit
                        ); ?></li>
                        <li><?php esc_html_e( 'The first photo is automatically set as the featured image.', 'google-places-directory' ); ?></li>
                        <li><?php esc_html_e( 'Photos are cached in your media library for better performance.', 'google-places-directory' ); ?></li>
                        <li><?php esc_html_e( 'Use the shortcode [gpd_photos id="123"] to display a business\'s photos.', 'google-places-directory' ); ?></li>
                        <li><?php esc_html_e( 'Refreshing photos will replace any existing imported photos.', 'google-places-directory' ); ?></li>
                    </ul>
                    
                    <p>
                        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=business&page=gpd-docs' ) ); ?>" class="button">
                            <?php esc_html_e( 'View Full Documentation', 'google-places-directory' ); ?>
                        </a>
                    </p>
                </div>
            </div>
            
            <div id="gpd-businesses-without-photos" class="gpd-business-list" style="display: none;">
                <h3><?php esc_html_e( 'Businesses Without Photos', 'google-places-directory' ); ?></h3>
                
                <?php
                // Get businesses without photos
                $businesses = $this->get_businesses_without_photos();
                
                if ( empty( $businesses ) ) {
                    echo '<p>' . esc_html__( 'All businesses have photos.', 'google-places-directory' ) . '</p>';
                } else {
                    echo '<table class="widefat striped">';
                    echo '<thead><tr>';
                    echo '<th>' . esc_html__( 'Business Name', 'google-places-directory' ) . '</th>';
                    echo '<th>' . esc_html__( 'Place ID', 'google-places-directory' ) . '</th>';
                    echo '<th>' . esc_html__( 'Actions', 'google-places-directory' ) . '</th>';
                    echo '</tr></thead>';
                    echo '<tbody class="gpd-batch-items" data-processed="0">';
                    
                    foreach ( $businesses as $business ) {
                        echo '<tr data-id="' . esc_attr( $business->ID ) . '" data-place-id="' . esc_attr( get_post_meta( $business->ID, '_gpd_place_id', true ) ) . '">';
                        echo '<td>' . esc_html( $business->post_title ) . '</td>';
                        echo '<td>' . esc_html( get_post_meta( $business->ID, '_gpd_place_id', true ) ) . '</td>';
                        echo '<td><a href="#" class="gpd-refresh-photos button button-small">' . esc_html__( 'Refresh Photos', 'google-places-directory' ) . '</a></td>';
                        echo '</tr>';
                    }
                    
                    echo '</tbody></table>';
                }
                ?>
            </div>
        </div>
        
        <style>
        .gpd-photo-stats {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .gpd-stat-card {
            background: white;
            border: 1px solid #ccd0d4;
            padding: 20px;
            text-align: center;
            box-shadow: 0 1px 1px rgba(0,0,0,0.04);
        }
        
        .gpd-stat-card h2 {
            margin: 0;
            font-size: 32px;
            line-height: 1.2;
            color: #0073aa;
        }
        
        .gpd-photo-actions {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .gpd-action-card {
            background: white;
            border: 1px solid #ccd0d4;
            padding: 20px;
            box-shadow: 0 1px 1px rgba(0,0,0,0.04);
        }
        
        .gpd-action-card h3 {
            margin-top: 0;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 10px;
        }
        
        .gpd-action-buttons {
            display: flex;
            gap: 10px;
            margin: 20px 0;
        }
        
        .gpd-batch-progress {
            margin: 20px 0;
        }
        
        .gpd-progress-bar {
            width: 100%;
            height: 20px;
            background-color: #f0f0f0;
            border-radius: 3px;
            margin-bottom: 10px;
            overflow: hidden;
        }
        
        .gpd-progress-complete {
            height: 100%;
            background-color: #0073aa;
            transition: width 0.3s ease;
        }
        
        .gpd-progress-status {
            text-align: center;
            font-weight: bold;
        }
        
        .gpd-batch-results {
            margin-top: 15px;
            padding: 10px;
            background: #f9f9f9;
            border-left: 4px solid #ccc;
        }
        
        .gpd-business-list {
            margin-top: 30px;
        }
        
        .gpd-tips-list {
            background: #f9f9f9;
            padding: 15px 15px 15px 35px;
            border-left: 4px solid #0073aa;
        }
        
        .gpd-tips-list li {
            margin-bottom: 8px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Batch processing
            $('.gpd-batch-button').on('click', function(e) {
                e.preventDefault();
                
                var businessCount = $(this).data('businesses');
                var confirmed = confirm(
                    '<?php esc_html_e( 'This will attempt to refresh photos for', 'google-places-directory' ); ?> ' + 
                    businessCount + ' <?php esc_html_e( 'businesses. This operation may take some time and use API quota. Continue?', 'google-places-directory' ); ?>'
                );
                
                if (!confirmed) {
                    return;
                }
                
                // Show the processing UI
                $('.gpd-batch-processing').show();
                $('.gpd-action-buttons').hide();
                
                // Load businesses without photos
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'gpd_get_businesses_without_photos',
                        nonce: '<?php echo wp_create_nonce('gpd_photo_management'); ?>'
                    },
                    success: function(response) {
                        if (response.success && response.data.businesses) {
                            processBatchPhotos(response.data.businesses);
                        } else {
                            $('.gpd-batch-results').html('<p><?php esc_html_e( 'Failed to load businesses.', 'google-places-directory' ); ?></p>');
                        }
                    },
                    error: function() {
                        $('.gpd-batch-results').html('<p><?php esc_html_e( 'Failed to load businesses.', 'google-places-directory' ); ?></p>');
                    }
                });
            });
            
            function processBatchPhotos(businesses) {
                var totalItems = businesses.length;
                var processedItems = 0;
                var successCount = 0;
                var failCount = 0;
                var photoCount = 0;
                
                // Update UI
                $('.gpd-total').text(totalItems);
                
                function processNextBusiness(index) {
    if (index >= businesses.length) {
        // All done
        var resultHtml = 
            '<p><strong><?php esc_html_e("Processing complete!", "google-places-directory"); ?></strong></p>' +
            '<p><?php esc_html_e("Results:", "google-places-directory"); ?> ' +
            successCount + ' <?php esc_html_e("businesses updated with", "google-places-directory"); ?> ' +
            photoCount + ' <?php esc_html_e("total photos", "google-places-directory"); ?>. ' +
            failCount + ' <?php esc_html_e("businesses failed.", "google-places-directory"); ?></p>';
            
        // Add error details section if there were failures
        if (failCount > 0) {
            resultHtml += 
                '<div class="gpd-error-details">' +
                '<h4><?php esc_html_e("Error Details", "google-places-directory"); ?></h4>' +
                '<div id="gpd-error-list"></div>' +
                '</div>';
        }
        
        resultHtml += 
            '<p><a href="<?php echo esc_url(admin_url("edit.php?post_type=business&page=gpd-photo-management")); ?>" class="button button-primary">' +
            '<?php esc_html_e("Refresh Page", "google-places-directory"); ?></a></p>';
        
        $('.gpd-batch-results').html(resultHtml);
        return;
    }
    
    var business = businesses[index];
    
    // Call AJAX to process this item
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'gpd_refresh_business_photos',
            post_id: business.id,
            place_id: business.place_id,
            nonce: '<?php echo wp_create_nonce("gpd_refresh_photos"); ?>'
        },
        success: function(response) {
            processedItems++;
            
            if (response.success) {
                successCount++;
                photoCount += response.data.photos_added || 0;
                
                // Log success in console for debugging
                console.log('Success for business: ' + business.title, response.data);
            } else {
                failCount++;
                
                // Log error in console
                console.error('Error for business: ' + business.title, response.data);
                
                // Store error details
                var errorMsg = response.data.message || '<?php esc_html_e("Unknown error", "google-places-directory"); ?>';
                
                // Add to error list if it exists
                if ($('#gpd-error-list').length) {
                    var errorDetails = '';
                    
                    // Add debug info if available
                    if (response.data.debug) {
                        var debugInfo = response.data.debug;
                        errorDetails += '<details><summary><?php esc_html_e("Show Technical Details", "google-places-directory"); ?></summary>';
                        
                        if (debugInfo.api_url) {
                            errorDetails += '<p><strong>API URL:</strong> ' + debugInfo.api_url + '</p>';
                        }
                        
                        if (debugInfo.place_id) {
                            errorDetails += '<p><strong>Place ID:</strong> ' + debugInfo.place_id + '</p>';
                        }
                        
                        if (response.data.response_body) {
                            errorDetails += '<p><strong>Response:</strong> ' + 
                                $('<div/>').text(response.data.response_body).html() + '</p>';
                        }
                        
                        errorDetails += '</details>';
                    }
                    
                    $('#gpd-error-list').append(
                        '<div class="gpd-error-item">' +
                        '<p><strong>' + business.title + ' (ID: ' + business.id + ')</strong>: ' + errorMsg + '</p>' +
                        errorDetails +
                        '</div>'
                    );
                }
            }
            
            // Update progress
            var percent = Math.round((processedItems / totalItems) * 100);
            $('.gpd-progress-complete').css('width', percent + '%');
            $('.gpd-processed').text(processedItems);
            
            // Update status message
            var statusHtml = 
                '<p><?php esc_html_e("Processing...", "google-places-directory"); ?> ' + 
                processedItems + ' / ' + totalItems + ' <?php esc_html_e("businesses", "google-places-directory"); ?></p>' +
                '<p><?php esc_html_e("Current totals:", "google-places-directory"); ?> ' + 
                successCount + ' <?php esc_html_e("updated with", "google-places-directory"); ?> ' +
                photoCount + ' <?php esc_html_e("photos", "google-places-directory"); ?>';
                
            // Add simple error counter if failures
            if (failCount > 0) {
                statusHtml += ' | <span class="gpd-error-count">' + failCount + 
                    ' <?php esc_html_e("failed", "google-places-directory"); ?></span>';
            }
            
            statusHtml += '</p>';
            
            $('.gpd-batch-results').html(statusHtml);
            
            // Process next item with a slight delay to avoid API rate limits
            setTimeout(function() {
                processNextBusiness(index + 1);
            }, 500);
        },
        error: function(xhr, status, error) {
            processedItems++;
            failCount++;
            
            // Log AJAX error
            console.error('AJAX error for business: ' + business.title, {xhr: xhr, status: status, error: error});
            
            // Add to error list if it exists
            if ($('#gpd-error-list').length) {
                $('#gpd-error-list').append(
                    '<div class="gpd-error-item">' +
                    '<p><strong>' + business.title + ' (ID: ' + business.id + ')</strong>: ' + 
                    '<?php esc_html_e("AJAX request failed", "google-places-directory"); ?> - ' + status + ' ' + error + '</p>' +
                    '</div>'
                );
            }
            
            // Update progress
            var percent = Math.round((processedItems / totalItems) * 100);
            $('.gpd-progress-complete').css('width', percent + '%');
            $('.gpd-processed').text(processedItems);
            
            // Process next item with a delay
            setTimeout(function() {
                processNextBusiness(index + 1);
            }, 500);
        }
    });
}
        });
        </script>
        <?php
    }

    /**
     * Count total number of businesses
     *
     * @return int Number of businesses
     */
    private function count_businesses() {
        $query = new WP_Query( array(
            'post_type' => 'business',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ) );
        
        return $query->post_count;
    }

    /**
     * Count number of businesses with photos
     *
     * @return int Number of businesses with photos
     */
    private function count_businesses_with_photos() {
        $query = new WP_Query( array(
            'post_type' => 'business',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'meta_query' => array(
                array(
                    'key' => '_gpd_photo_references',
                    'compare' => 'EXISTS',
                ),
            ),
        ) );
        
        return $query->post_count;
    }

    /**
     * Count total number of photos
     *
     * @return int Number of photos
     */
    private function count_total_photos() {
        $query = new WP_Query( array(
            'post_type' => 'attachment',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'meta_query' => array(
                array(
                    'key' => '_gpd_photo_reference',
                    'compare' => 'EXISTS',
                ),
            ),
        ) );
        
        return $query->post_count;
    }

    /**
     * Get businesses without photos
     *
     * @param int $limit Maximum number of businesses to return (0 for all)
     * @return array Array of business posts without photos
     */
    private function get_businesses_without_photos( $limit = 0 ) {
        $args = array(
            'post_type' => 'business',
            'posts_per_page' => $limit > 0 ? $limit : -1,
            'meta_query' => array(
                array(
                    'relation' => 'OR',
                    array(
                        'key' => '_gpd_photo_references',
                        'compare' => 'NOT EXISTS',
                    ),
                    array(
                        'key' => '_gpd_photo_references',
                        'value' => '',
                        'compare' => '=',
                    ),
                    array(
                        'key' => '_gpd_photo_references',
                        'value' => 'a:0:{}',
                        'compare' => '=',
                    ),
                ),
                array(
                    'key' => '_gpd_place_id',
                    'compare' => 'EXISTS',
                ),
            ),
        );
        
        return get_posts( $args );
    }

/**
 * Download and attach a photo to a business
 *
 * @param string $photo_url URL of the photo
 * @param int $post_id Post ID to attach to
 * @param string $photo_reference Photo reference ID
 * @return int|bool Attachment ID on success, false on failure
 */
private function download_and_attach_photo($photo_url, $post_id, $photo_reference) {
    // Check if we already have this photo
    $existing = new WP_Query(array(
        'post_type' => 'attachment',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => '_gpd_photo_reference',
                'value' => $photo_reference,
            ),
        ),
    ));
    
    if ($existing->have_posts()) {
        return $existing->posts[0];
    }
    
    // Get business name for image title
    $business_name = get_the_title($post_id);
    
    // Include required files for media handling
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    
    $api_key = get_option('gpd_api_key');
    
    // For Places API v1, the correct format is to request from the media endpoint
    $url = 'https://places.googleapis.com/v1/' . $photo_reference . '/media?key=' . $api_key . '&maxHeightPx=1200&maxWidthPx=1200';
    
    // Make the request with proper headers
    $response = wp_remote_get($url, array(
        'timeout' => 30,
        'headers' => array(
            // IMPORTANT: The "Accept" header for binary data
            'Accept' => 'image/*',
            'X-Goog-Api-Key' => $api_key,
            'X-Goog-FieldMask' => 'name'
        )
    ));
    
    if (is_wp_error($response)) {
        error_log('Google Places Directory: Failed to download photo - ' . $response->get_error_message());
        return false;
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        error_log('Google Places Directory: API error - Status ' . $status_code . ' - ' . wp_remote_retrieve_body($response));
        return false;
    }
    
    // Get the image data
    $image_data = wp_remote_retrieve_body($response);
    
    // Check if downloaded file is valid
    if (empty($image_data) || strlen($image_data) < 100) { 
        error_log('Google Places Directory: Invalid image file downloaded - too small or empty');
        return false;
    }
    
    // Generate a unique filename
    $filename = sanitize_file_name($business_name . '-' . substr(md5($photo_reference), 0, 8) . '.jpg');
    
    // Create a temporary file
    $upload_dir = wp_upload_dir();
    $tmp_file = $upload_dir['basedir'] . '/gpd-temp-' . md5($filename) . '.jpg';
    
    // Write image data to temporary file
    if (file_put_contents($tmp_file, $image_data) === false) {
        error_log('Google Places Directory: Failed to write temporary image file');
        return false;
    }
    
    $file_array = array(
        'name' => $filename,
        'tmp_name' => $tmp_file,
    );
    
    // Upload and attach the image
    $attachment_id = media_handle_sideload($file_array, $post_id, $business_name . ' - ' . __('Google Places Photo', 'google-places-directory'));
    
    // Remove temporary file
    @unlink($tmp_file);
    
    if (is_wp_error($attachment_id)) {
        error_log('Google Places Directory: Failed to create attachment - ' . $attachment_id->get_error_message());
        return false;
    }
    
    // Store photo reference as attachment meta
    update_post_meta($attachment_id, '_gpd_photo_reference', $photo_reference);
    
    return $attachment_id;
}

    /**
     * Remove all photos for a business
     *
     * @param int $post_id Post ID
     */
    private function remove_business_photos( $post_id ) {
        $photo_refs = get_post_meta( $post_id, '_gpd_photo_references', true );
        
        if ( !empty( $photo_refs ) ) {
            foreach ( $photo_refs as $ref ) {
                $args = array(
                    'post_type' => 'attachment',
                    'posts_per_page' => 1,
                    'meta_key' => '_gpd_photo_reference',
                    'meta_value' => $ref,
                );
                
                $query = new WP_Query( $args );
                
                if ( $query->have_posts() ) {
                    $attachment_id = $query->posts[0]->ID;
                    wp_delete_attachment( $attachment_id, true );
                }
            }
        }
        
        // Clear photo references
        delete_post_meta( $post_id, '_gpd_photo_references' );
        
        // Clear featured image
        delete_post_thumbnail( $post_id );
    }
}
