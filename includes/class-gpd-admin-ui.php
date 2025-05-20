<?php
/**
 * class-gpd-admin-ui.php
 *
 * Manages the admin UI: search form, results table, import actions,
 * pagination (Prev/Next), and flags already‑imported businesses.
 * 
 * Updated for Google Places API (New) in May 2025
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
add_action( 'admin_enqueue_scripts', function() {
    wp_enqueue_style( 'gpd-admin-styles', plugin_dir_url( __FILE__ ) . '../assets/admin-style.css' );
} );

class GPD_Admin_UI {
    private static $instance = null;

    public static function instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
            self::$instance->init_hooks();
        }
        return self::$instance;
    }

    private function init_hooks() {
        add_action( 'admin_menu', [ $this, 'add_admin_pages' ] );
        add_action( 'admin_post_gpd_import', [ $this, 'handle_import' ] );
        add_action( 'admin_notices', [ $this, 'display_import_notices' ] );
    }

    /**
     * Display notices about imported businesses
     */
    public function display_import_notices() {
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'business_page_gpd-import') {
            return;
        }
        
        // Show success message if businesses were imported
        if (isset($_GET['created']) || isset($_GET['updated'])) {
            $created = isset($_GET['created']) ? intval($_GET['created']) : 0;
            $updated = isset($_GET['updated']) ? intval($_GET['updated']) : 0;
            $total = $created + $updated;
            
            if ($total > 0) {
                $message = sprintf(
                    _n(
                        '%d business imported successfully.', 
                        '%d businesses imported successfully.',
                        $total, 
                        'google-places-directory'
                    ),
                    $total
                );
                
                // Add details about created vs updated
                if ($created > 0 && $updated > 0) {
                    $message .= ' ' . sprintf(
                        __('%d new, %d updated.', 'google-places-directory'),
                        $created,
                        $updated
                    );
                }
                
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
            }
        }
        
        // Show API migration notice only once per session
        if (!isset($_COOKIE['gpd_api_notice_shown'])) {
            ?>
            <div class="notice notice-info is-dismissible">
                <p>
                    <strong><?php _e('Google Places API Update:', 'google-places-directory'); ?></strong>
                    <?php _e('This plugin now uses the new Google Places API. Results may look slightly different from previous versions.', 'google-places-directory'); ?>
                </p>
            </div>
            <?php
            // Set cookie to avoid showing the notice on every page load
            setcookie('gpd_api_notice_shown', '1', time() + DAY_IN_SECONDS, ADMIN_COOKIE_PATH);
        }
    }

    public function add_admin_pages() {
        add_submenu_page(
            'edit.php?post_type=business',
            __( 'Business Import', 'google-places-directory' ),
            __( 'Business Import', 'google-places-directory' ),
            'manage_options',
            'gpd-import',
            [ $this, 'render_import_page' ]
        );
    }

    public function render_import_page() {
        $radius_options   = [ 6, 15, 25, 50 ];
        $limit_options    = [ 5, 10, 15, 20 ];
        $selected_radius  = intval( $_GET['radius']  ?? 15 );
        $selected_limit   = intval( $_GET['limit']   ?? 10 );
        $query            = sanitize_text_field( $_GET['query']   ?? '' );
        $incoming_token   = sanitize_text_field( $_GET['pagetoken'] ?? '' );
        $prev_token       = sanitize_text_field( $_GET['prevtok']   ?? '' );
        $places           = [];
        $next_page_token  = '';
        $has_error        = false;
        $error_message    = '';

        // Fetch all imported place IDs to flag in the table
        $imported_posts = get_posts([
            'post_type'   => 'business',
            'numberposts' => -1,
            'fields'      => 'ids',
        ]);
        $imported_ids = [];

        foreach ( $imported_posts as $pid ) {
            $imported_ids[] = get_post_meta( $pid, '_gpd_place_id', true );
        }

        if ( $query ) {
            // Initialize pagination token
            $next_page_token = '';
            $places_result = GPD_Importer::instance()->import_places(
                $query,
                $selected_radius * 1000,
                $selected_limit,
                $next_page_token,
                $incoming_token
            );
            
            // Check for errors
            if (is_wp_error($places_result)) {
                $has_error = true;
                $error_message = $places_result->get_error_message();
                $places = [];
            } else {
                $places = $places_result;
                // Show newest first
                if (is_array($places)) {
                    $places = array_reverse($places, /* preserve_keys */ true);
                }
            }
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Business Import', 'google-places-directory' ); ?></h1>

            <?php if ($has_error): ?>
                <div class="notice notice-error">
                    <p><?php echo esc_html($error_message); ?></p>
                    <p><?php _e('If this is an API error, please check your API key settings and make sure Places API (New) is enabled.', 'google-places-directory'); ?></p>
                </div>
            <?php endif; ?>

            <form method="get" action="">
                <input type="hidden" name="post_type" value="business">
                <input type="hidden" name="page"      value="gpd-import">

                <div class="gpd-search-form">
                    <div class="gpd-search-row">
                        <label for="gpd-query"><?php esc_html_e( 'Search Query:', 'google-places-directory' ); ?></label>
                        <input type="text" id="gpd-query" name="query" value="<?php echo esc_attr( $query ); ?>" required 
                               placeholder="<?php esc_attr_e('Enter business name or type', 'google-places-directory'); ?>">
                    </div>

                    <div class="gpd-search-controls">
                        <div class="gpd-search-option">
                            <label for="gpd-radius"><?php esc_html_e( 'Radius (km):', 'google-places-directory' ); ?></label>
                            <select id="gpd-radius" name="radius">
                                <?php foreach ( $radius_options as $r ): ?>
                                    <option value="<?php echo esc_attr( $r ); ?>" <?php selected( $selected_radius, $r ); ?>>
                                        <?php echo esc_html( $r ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="gpd-search-option">
                            <label for="gpd-limit"><?php esc_html_e( 'Results:', 'google-places-directory' ); ?></label>
                            <select id="gpd-limit" name="limit">
                                <?php foreach ( $limit_options as $l ): ?>
                                    <option value="<?php echo esc_attr( $l ); ?>" <?php selected( $selected_limit, $l ); ?>>
                                        <?php echo esc_html( $l ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="gpd-search-action">
                        <?php submit_button( __( 'Search', 'google-places-directory' ), 'primary', 'submit', false ); ?>
                    </div>
                </div>
            </form>

            <?php if (!$has_error && $query): ?>
                <p class="gpd-search-summary">
                    <?php
                    printf(
                        __('Showing results for "%s" within %d km', 'google-places-directory'),
                        esc_html($query),
                        $selected_radius
                    );
                    ?>
                </p>
            <?php endif; ?>

            <?php if ( $query && !$has_error ) : ?>
                <div class="gpd-pagination">
                    <?php if ( $incoming_token ) : ?>
                        <a
                            class="button"
                            href="<?php echo esc_url( add_query_arg( [
                                'post_type'  => 'business',
                                'page'       => 'gpd-import',
                                'query'      => $query,
                                'radius'     => $selected_radius,
                                'limit'      => $selected_limit,
                                'pagetoken'  => $prev_token,
                            ], admin_url( 'edit.php' ) ) ); ?>"
                        ><?php esc_html_e( 'Prev Page', 'google-places-directory' ); ?></a>
                    <?php endif; ?>

                    <?php if ( $next_page_token ) : ?>
                        <a
                            class="button"
                            href="<?php echo esc_url( add_query_arg( [
                                'post_type'  => 'business',
                                'page'       => 'gpd-import',
                                'query'      => $query,
                                'radius'     => $selected_radius,
                                'limit'      => $selected_limit,
                                'prevtok'    => $incoming_token,
                                'pagetoken'  => $next_page_token,
                            ], admin_url( 'edit.php' ) ) ); ?>"
                        ><?php esc_html_e( 'Next Page', 'google-places-directory' ); ?></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $places ) ): ?>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <?php wp_nonce_field( 'gpd_import_action', 'gpd_import_nonce' ); ?>
                    <input type="hidden" name="action"    value="gpd_import">
                    <input type="hidden" name="query"     value="<?php echo esc_attr( $query ); ?>">
                    <input type="hidden" name="radius"    value="<?php echo esc_attr( $selected_radius ); ?>">
                    <input type="hidden" name="limit"     value="<?php echo esc_attr( $selected_limit ); ?>">
                    <input type="hidden" name="pagetoken" value="<?php echo esc_attr( $incoming_token ); ?>">
                    <input type="hidden" name="prevtok"   value="<?php echo esc_attr( $prev_token ); ?>">

                    <div class="gpd-bulk-actions">
                        <label>
                            <input type="checkbox" id="gpd-select-all" checked>
                            <?php esc_html_e('Select/Deselect All', 'google-places-directory'); ?>
                        </label>
                        
                        <?php submit_button( __( 'Import Selected', 'google-places-directory' ), 'primary', 'submit', false ); ?>
                    </div>

                    <table class="widefat fixed striped">
                        <thead>
                            <tr>
                                <th class="check-column"></th>
                                <th><?php esc_html_e( 'Name',    'google-places-directory' ); ?></th>
                                <th><?php esc_html_e( 'Address', 'google-places-directory' ); ?></th>
                                <th><?php esc_html_e( 'Type',    'google-places-directory' ); ?></th>
                                <th class="rating-column"><?php esc_html_e( 'Rating', 'google-places-directory' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ( $places as $index => $place ) :
                            $pid      = $place['place_id'] ?? '';
                            $imported = in_array( $pid, $imported_ids, true );
                            $address  = $place['formatted_address'] ?? ( isset( $place['address_components'] ) ? implode( ', ', wp_list_pluck( $place['address_components'], 'long_name' ) ) : '' );
                            $types    = isset( $place['types'] ) ? implode( ', ', $place['types'] ) : '';
                            $rating   = isset($place['rating']) ? floatval($place['rating']) : 0;
                        ?>
                            <tr class="<?php echo $imported ? 'gpd-imported' : ''; ?>">
                                <td>
                                    <?php if ( $imported ): ?>
                                        <span class="dashicons dashicons-yes-alt" title="<?php esc_attr_e( 'Already imported', 'google-places-directory' ); ?>"></span>
                                    <?php else: ?>
                                        <input type="checkbox" class="gpd-select-item" name="places[<?php echo $index; ?>]" value="1" checked>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html( $place['name'] ?? '—' ); ?></td>
                                <td><?php echo esc_html( $address ?: '—' ); ?></td>
                                <td><?php echo esc_html( $types ?: '—' ); ?></td>
                                <td class="rating-column">
                                    <?php if ($rating > 0): ?>
                                        <div class="gpd-star-rating" title="<?php echo esc_attr($rating); ?>">
                                            <?php echo esc_html(number_format($rating, 1)); ?>
                                            <span class="dashicons dashicons-star-filled"></span>
                                        </div>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php submit_button( __( 'Import Selected', 'google-places-directory' ) ); ?>
                </form>
                
                <script>
                jQuery(document).ready(function($) {
                    // Handle select all checkbox
                    $('#gpd-select-all').on('change', function() {
                        $('.gpd-select-item').prop('checked', $(this).prop('checked'));
                    });
                    
                    // Update select all when individual items change
                    $('.gpd-select-item').on('change', function() {
                        if (!$(this).prop('checked')) {
                            $('#gpd-select-all').prop('checked', false);
                        } else {
                            // Check if all items are checked
                            var allChecked = true;
                            $('.gpd-select-item').each(function() {
                                if (!$(this).prop('checked')) {
                                    allChecked = false;
                                    return false;
                                }
                            });
                            $('#gpd-select-all').prop('checked', allChecked);
                        }
                    });
                });
                </script>
            <?php elseif ($query && !$has_error): ?>
                <div class="gpd-no-results">
                    <p><?php _e('No businesses found matching your search criteria.', 'google-places-directory'); ?></p>
                    <p><?php _e('Try broadening your search or using different keywords.', 'google-places-directory'); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <style>
            .gpd-imported { opacity: 0.6; }
            .gpd-search-form {
                background: #fff;
                padding: 15px;
                border: 1px solid #ccd0d4;
                margin-bottom: 20px;
            }
            .gpd-search-controls {
                display: flex;
                gap: 20px;
                margin-top: 10px;
            }
            .gpd-search-row {
                margin-bottom: 10px;
            }
            .gpd-search-form label {
                display: inline-block;
                min-width: 80px;
            }
            #gpd-query {
                width: 50%;
                min-width: 300px;
            }
            .gpd-search-action {
                margin-top: 15px;
            }
            .gpd-pagination {
                margin: 15px 0;
                display: flex;
                gap: 10px;
            }
            .gpd-bulk-actions {
                margin: 15px 0;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            .rating-column {
                width: 80px;
            }
            .gpd-star-rating {
                display: flex;
                align-items: center;
                gap: 3px;
            }
            .gpd-star-rating .dashicons {
                color: #ffb900;
                font-size: 16px;
            }
            .gpd-search-summary {
                font-style: italic;
                margin-bottom: 15px;
            }
            .gpd-no-results {
                background: #fff;
                padding: 20px;
                border: 1px solid #ccd0d4;
                text-align: center;
            }
        </style>
        <?php
    }

    public function handle_import() {
        if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'gpd_import_action', 'gpd_import_nonce' ) ) {
            wp_die( __( 'Permission denied', 'google-places-directory' ) );
        }

        $query          = sanitize_text_field( $_POST['query'] );
        $radius         = intval( $_POST['radius'] );
        $limit          = intval( $_POST['limit'] );
        $incoming_token = sanitize_text_field( $_POST['pagetoken'] ?? '' );
        $prev_token     = sanitize_text_field( $_POST['prevtok'] ?? '' );

        // Initialize by-reference pagination token
        $next_page_token = '';

        $places_result = GPD_Importer::instance()->import_places(
            $query,
            $radius * 1000,
            $limit,
            $next_page_token,
            $incoming_token
        );
        
        // Handle potential errors
        if (is_wp_error($places_result)) {
            $redirect_url = add_query_arg([
                'post_type'  => 'business',
                'page'       => 'gpd-import',
                'query'      => $query,
                'radius'     => $radius,
                'limit'      => $limit,
                'error'      => urlencode($places_result->get_error_message()),
            ], admin_url( 'edit.php' ));
            
            wp_redirect($redirect_url);
            exit;
        }
        
        $places = $places_result;
        $selected  = array_keys( $_POST['places'] ?? [] );
        $to_import = [];
        foreach ( $selected as $i ) {
            if ( isset( $places[ $i ] ) ) {
                $to_import[] = $places[ $i ];
            }
        }

        $result = GPD_Importer::instance()->process_import( $to_import );
        $redirect_url = add_query_arg([
            'post_type'  => 'business',
            'page'       => 'gpd-import',
            'query'      => $query,
            'radius'     => $radius,
            'limit'      => $limit,
            'pagetoken'  => $incoming_token,
            'prevtok'    => $prev_token,
            'updated'    => $result['updated'],
            'created'    => $result['created'],
        ], admin_url( 'edit.php' ) );

        wp_redirect( $redirect_url );
        exit;
    }
}

// Add a little extra CSS for better styling
add_action('admin_head', function() {
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'business_page_gpd-import') {
        return;
    }
    ?>
    <style>
        .wp-list-table .check-column {
            width: 2.2em;
        }
    </style>
    <?php
});
