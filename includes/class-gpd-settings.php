<?php
/**
 * class-gpd-settings.php
 *
 * Handles the API Key settings page.
 * Updated for Google Places API (New) in May 2025.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GPD_Settings {
    private static $instance = null;

    public static function instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
            self::$instance->init_hooks();
        }
        return self::$instance;
    }

    private function init_hooks() {
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_post_gpd_save_settings', array( $this, 'save_settings' ) );
    }

    public function add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=business',
            __( 'Settings', 'google-places-directory' ),
            __( 'Settings', 'google-places-directory' ),
            'manage_options',
            'gpd-settings',
            array( $this, 'render_settings_page' )
        );
    }

    public function render_settings_page() {
        $api_key = get_option( 'gpd_api_key', '' );
        $photo_limit = get_option( 'gpd_photo_limit', 3 ); // Default to 3 photos

        if ( isset( $_GET['settings-updated'] ) ) {
            add_settings_error( 'gpd_messages', 'gpd_message', __( 'Settings saved.', 'google-places-directory' ), 'updated' );
        }

        settings_errors( 'gpd_messages' );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Google Places Directory Settings', 'google-places-directory' ); ?></h1>
            
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'gpd_save_settings_action', 'gpd_save_settings_nonce' ); ?>
                <input type="hidden" name="action" value="gpd_save_settings">

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="gpd_api_key"><?php esc_html_e( 'API Key', 'google-places-directory' ); ?></label></th>
                        <td>
                            <input name="gpd_api_key" type="text" id="gpd_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" required>
                            <p class="description">
                                <?php esc_html_e( 'Enter your Google API key with Places API (New) enabled.', 'google-places-directory' ); ?>
                                <a href="https://console.cloud.google.com/apis/library/places.googleapis.com" target="_blank"><?php esc_html_e( 'Enable API', 'google-places-directory' ); ?></a>
                            </p>
                        </td>
                    </tr>
                    <!-- New setting for photo limit -->
                    <tr>
                        <th scope="row"><label for="gpd_photo_limit"><?php esc_html_e( 'Photos to Import', 'google-places-directory' ); ?></label></th>
                        <td>
                            <select name="gpd_photo_limit" id="gpd_photo_limit">
                                <option value="0" <?php selected( $photo_limit, 0 ); ?>><?php esc_html_e( 'None', 'google-places-directory' ); ?></option>
                                <option value="1" <?php selected( $photo_limit, 1 ); ?>><?php esc_html_e( '1 (Featured Image Only)', 'google-places-directory' ); ?></option>
                                <option value="3" <?php selected( $photo_limit, 3 ); ?>><?php esc_html_e( '3 (Default)', 'google-places-directory' ); ?></option>
                                <option value="5" <?php selected( $photo_limit, 5 ); ?>><?php esc_html_e( '5', 'google-places-directory' ); ?></option>
                                <option value="10" <?php selected( $photo_limit, 10 ); ?>><?php esc_html_e( '10 (Maximum)', 'google-places-directory' ); ?></option>
                            </select>
                            <p class="description">
                                <?php esc_html_e( 'Maximum number of photos to import per business. The first photo will be set as the featured image.', 'google-places-directory' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <h2><?php esc_html_e( 'API Key Requirements', 'google-places-directory' ); ?></h2>
                <p><?php esc_html_e( 'For your API key to work properly with this plugin, make sure:', 'google-places-directory' ); ?></p>
                <ul style="list-style: disc; margin-left: 2em;">
                    <li>
                        <?php esc_html_e( 'Places API (New) is enabled in your Google Cloud Console', 'google-places-directory' ); ?> - 
                        <a href="https://console.cloud.google.com/apis/library/places.googleapis.com" target="_blank">
                            <?php esc_html_e( 'Enable Places API (New)', 'google-places-directory' ); ?>
                        </a>
                    </li>
                    <li>
                        <?php esc_html_e( 'Billing is properly set up in your Google Cloud account', 'google-places-directory' ); ?> - 
                        <a href="https://console.cloud.google.com/billing" target="_blank">
                            <?php esc_html_e( 'Configure Billing', 'google-places-directory' ); ?>
                        </a>
                    </li>
                    <li>
                        <?php esc_html_e( 'Your API key has proper restrictions set (if any)', 'google-places-directory' ); ?> - 
                        <a href="https://console.cloud.google.com/apis/credentials" target="_blank">
                            <?php esc_html_e( 'Manage API Keys', 'google-places-directory' ); ?>
                        </a>
                    </li>
                    <li>
                        <?php esc_html_e( 'Review Google Places API pricing information', 'google-places-directory' ); ?> - 
                        <a href="https://developers.google.com/maps/documentation/places/web-service/usage-and-billing" target="_blank">
                            <?php esc_html_e( 'Usage and Billing', 'google-places-directory' ); ?>
                        </a>
                    </li>
                </ul>

                <?php submit_button( __( 'Save Settings', 'google-places-directory' ) ); ?>
            </form>
            
            <hr>
            <h2><?php esc_html_e( 'Test API Connection', 'google-places-directory' ); ?></h2>
            <p><?php esc_html_e( 'Click this button to verify your API key works with the Places API:', 'google-places-directory' ); ?></p>
            <button id="gpd-test-api" class="button button-secondary"><?php esc_html_e( 'Test Connection', 'google-places-directory' ); ?></button>
            <div id="gpd-api-test-result" style="margin-top: 10px; padding: 10px; display: none;"></div>
            
            <script>
            jQuery(document).ready(function($) {
                $('#gpd-test-api').on('click', function(e) {
                    e.preventDefault();
                    
                    var $button = $(this);
                    var $result = $('#gpd-api-test-result');
                    
                    $button.prop('disabled', true).text('<?php echo esc_js(__('Testing...', 'google-places-directory')); ?>');
                    $result.hide();
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'gpd_test_api',
                            nonce: '<?php echo wp_create_nonce('gpd_test_api'); ?>'
                        },
                        success: function(response) {
                            $button.prop('disabled', false).text('<?php echo esc_js(__('Test Connection', 'google-places-directory')); ?>');
                            
                            if (response.success) {
                                $result.html('<div class="notice notice-success inline"><p>' + response.data + '</p></div>').show();
                            } else {
                                $result.html('<div class="notice notice-error inline"><p>' + response.data + '</p></div>').show();
                            }
                        },
                        error: function() {
                            $button.prop('disabled', false).text('<?php echo esc_js(__('Test Connection', 'google-places-directory')); ?>');
                            $result.html('<div class="notice notice-error inline"><p><?php echo esc_js(__('Connection error. Please try again.', 'google-places-directory')); ?></p></div>').show();
                        }
                    });
                });
            });
            </script>
            
            <div class="gpd-additional-resources" style="margin-top: 30px; background: #f8f9fa; padding: 15px; border: 1px solid #ddd;">
                <h3><?php esc_html_e('Documentation Resources', 'google-places-directory'); ?></h3>
                <ul style="list-style: square; margin-left: 2em;">
                    <li>
                        <a href="https://developers.google.com/maps/documentation/places/web-service/overview" target="_blank">
                            <?php esc_html_e('Places API Documentation', 'google-places-directory'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="https://developers.google.com/maps/documentation/places/web-service/search-text" target="_blank">
                            <?php esc_html_e('Text Search Documentation', 'google-places-directory'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="https://developers.google.com/maps/documentation/places/web-service/place-id" target="_blank">
                            <?php esc_html_e('Understanding Place IDs', 'google-places-directory'); ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <?php
    }

    public function save_settings() {
        if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'gpd_save_settings_action', 'gpd_save_settings_nonce' ) ) {
            wp_die( __( 'Permission denied', 'google-places-directory' ) );
        }

        if ( isset( $_POST['gpd_api_key'] ) ) {
            update_option( 'gpd_api_key', sanitize_text_field( wp_unslash( $_POST['gpd_api_key'] ) ) );
        }

        // Save photo limit setting
        if ( isset( $_POST['gpd_photo_limit'] ) ) {
            $photo_limit = intval( $_POST['gpd_photo_limit'] );
            // Ensure it's within valid range (0-10)
            $photo_limit = min( 10, max( 0, $photo_limit ) );
            update_option( 'gpd_photo_limit', $photo_limit );
        }

        $redirect_url = add_query_arg(
            array(
                'post_type'       => 'business',
                'page'            => 'gpd-settings',
                'settings-updated'=> 'true',
            ),
            admin_url( 'edit.php' )
        );

        wp_redirect( $redirect_url );
        exit;
    }
}

/**
 * Add AJAX handlers for API testing
 */
add_action('wp_ajax_gpd_test_api', function() {
    check_ajax_referer('gpd_test_api', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Permission denied.', 'google-places-directory'));
        return;
    }
    
    $api_key = get_option('gpd_api_key', '');
    if (empty($api_key)) {
        wp_send_json_error(__('API key is not set. Please save your API key first.', 'google-places-directory'));
        return;
    }
    
    // Test the API with a simple request
    $headers = [
        'Content-Type' => 'application/json',
        'X-Goog-Api-Key' => $api_key,
        'X-Goog-FieldMask' => 'places.displayName'
    ];
    
    $body = [
        'textQuery' => 'cafe',
        'maxResultCount' => 1
    ];
    
    $response = wp_remote_post(
        'https://places.googleapis.com/v1/places:searchText',
        [
            'headers' => $headers,
            'body' => json_encode($body),
            'timeout' => 30
        ]
    );
    
    if (is_wp_error($response)) {
        wp_send_json_error(sprintf(
            __('API Error: %s', 'google-places-directory'), 
            $response->get_error_message()
        ));
        return;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    if ($response_code !== 200) {
        $error_message = isset($body['error']['message']) ? $body['error']['message'] : "HTTP Error: {$response_code}";
        wp_send_json_error(sprintf(
            __('API Error: %s', 'google-places-directory'),
            $error_message
        ));
        return;
    }
    
    if (empty($body['places'])) {
        wp_send_json_error(__('API returned successfully but no places were found. Your API key appears to be working with Places API.', 'google-places-directory'));
        return;
    }
    
    wp_send_json_success(__('Success! Your API key is working correctly with the Places API.', 'google-places-directory'));
});
