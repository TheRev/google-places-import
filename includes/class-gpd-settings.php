<?php
/**
 * class-gpd-settings.php
 *
 * Handles the API Key settings page.
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
                            <p class="description"><?php esc_html_e( 'Enter your Google Places API v1 key.', 'google-places-directory' ); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button( __( 'Save Settings', 'google-places-directory' ) ); ?>
            </form>
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
