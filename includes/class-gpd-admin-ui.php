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

class GPD_Admin_UI {
    private static $instance = null;
    const PROGRESS_TRANSIENT_PREFIX = 'gpd_import_progress_';

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
        add_action( 'wp_ajax_gpd_check_import_progress', [ $this, 'ajax_check_import_progress' ] );
        
        // Add column to business post type admin list
        add_filter('manage_business_posts_columns', array($this, 'add_photo_count_column'));
        add_action('manage_business_posts_custom_column', array($this, 'render_photo_count_column'), 10, 2);
        
        // Make the column sortable
        add_filter('manage_edit-business_sortable_columns', array($this, 'make_photo_count_sortable'));
        add_action('pre_get_posts', array($this, 'sort_by_photo_count'));
        
        // Add filter for photo status
        add_action('restrict_manage_posts', array($this, 'add_photo_filter'), 10, 2);
        add_filter('parse_query', array($this, 'filter_by_photo_status'));
        
        // Add admin styles
        add_action('admin_head', array($this, 'add_admin_styles'));
        
        // Enqueue admin JS
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function ajax_check_import_progress() {
        check_ajax_referer('gpd_import_action', 'nonce');
        
        $batch_id = sanitize_text_field($_POST['batch_id'] ?? '');
        if (!$batch_id) {
            wp_send_json_error('Invalid batch ID');
        }
        
        $progress = GPD_Importer::instance()->get_batch_progress($batch_id);
        if (!$progress) {
            wp_send_json_error('Batch not found');
        }
        
        wp_send_json_success($progress);
    }

    /**
     * Add a photos column to the business post type admin list
     *
     * @param array $columns The existing columns
     * @return array Modified columns
     */
    public function add_photo_count_column($columns) {
        $new_columns = array();
        
        // Insert the photos column before the date column
        foreach ($columns as $key => $label) {
            if ($key === 'date') {
                $new_columns['photos'] = __('Photos', 'google-places-directory');
            }
            $new_columns[$key] = $label;
        }
        
        // If there's no date column, just add it at the end
        if (!isset($columns['date'])) {
            $new_columns['photos'] = __('Photos', 'google-places-directory');
        }
        
        return $new_columns;
    }

    /**
     * Render the photo count column content
     *
     * @param string $column_name The column name
     * @param int $post_id The post ID
     */
    public function render_photo_count_column($column_name, $post_id) {
        if ($column_name !== 'photos') {
            return;
        }
        
        $photo_refs = get_post_meta($post_id, '_gpd_photo_references', true);
        $count = is_array($photo_refs) ? count($photo_refs) : 0;
        
        if ($count === 0) {
            echo '<span class="gpd-no-photos">' . esc_html__('0', 'google-places-directory') . '</span>';
            echo ' <a href="' . esc_url(admin_url('post.php?post=' . $post_id . '&action=edit')) . '#gpd-photos' . '" class="gpd-add-photos-link">' . 
                esc_html__('Add Photos', 'google-places-directory') . '</a>';
        } else {
            $featured = has_post_thumbnail($post_id) ? '<span class="dashicons dashicons-star-filled" title="' . esc_attr__('Has Featured Image', 'google-places-directory') . '"></span>' : '';
            
            echo '<div class="gpd-photos-count">';
            echo '<span class="gpd-count">' . esc_html($count) . '</span> ' . $featured;
            
            // Add a thumbnail preview of the first photo if we have a featured image
            if (has_post_thumbnail($post_id)) {
                $thumb_id = get_post_thumbnail_id($post_id);
                $thumb_url = wp_get_attachment_image_src($thumb_id, 'thumbnail');
                if ($thumb_url) {
                    echo '<div class="gpd-photo-preview-container">';
                    echo '<img class="gpd-photo-preview" src="' . esc_url($thumb_url[0]) . '" alt="' . esc_attr__('Photo Preview', 'google-places-directory') . '">';
                    echo '</div>';
                }
            }
            
            echo '</div>';
        }
    }

    /**
     * Make the photo count column sortable
     *
     * @param array $columns The sortable columns
     * @return array Modified sortable columns
     */
    public function make_photo_count_sortable($columns) {
        $columns['photos'] = 'photos';
        return $columns;
    }

    /**
     * Handle sorting by photo count
     *
     * @param WP_Query $query The WordPress query
     */
    public function sort_by_photo_count($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        
        $orderby = $query->get('orderby');
        
        if ($orderby === 'photos') {
            $query->set('meta_key', '_gpd_photo_references');
            $query->set('orderby', 'meta_value');
            
            // For proper sorting, we want businesses without photos at the bottom when sorting ascending
            // and at the top when sorting descending
            $order = strtoupper($query->get('order'));
            if ($order === 'ASC') {
                $query->set('meta_query', array(
                    'relation' => 'OR',
                    array(
                        'key' => '_gpd_photo_references',
                        'compare' => 'EXISTS',
                    ),
                    array(
                        'key' => '_gpd_photo_references',
                        'compare' => 'NOT EXISTS',
                    ),
                ));
            } else {
                $query->set('meta_query', array(
                    'relation' => 'OR',
                    array(
                        'key' => '_gpd_photo_references',
                        'compare' => 'NOT EXISTS',
                    ),
                    array(
                        'key' => '_gpd_photo_references',
                        'compare' => 'EXISTS',
                    ),
                ));
            }
        }
    }

    /**
     * Add a dropdown filter for photo status
     *
     * @param string $post_type The current post type
     * @param string $which The position of the filters (top or bottom)
     */
    public function add_photo_filter($post_type, $which) {
        if ($post_type !== 'business' || $which !== 'top') {
            return;
        }
        
        $photo_status = isset($_GET['photo_status']) ? $_GET['photo_status'] : '';
        ?>
        <select name="photo_status" id="filter-by-photo-status">
            <option value=""><?php _e('All Photos', 'google-places-directory'); ?></option>
            <option value="with" <?php selected($photo_status, 'with'); ?>><?php _e('With Photos', 'google-places-directory'); ?></option>
            <option value="without" <?php selected($photo_status, 'without'); ?>><?php _e('Without Photos', 'google-places-directory'); ?></option>
            <option value="no-featured" <?php selected($photo_status, 'no-featured'); ?>><?php _e('Missing Featured Image', 'google-places-directory'); ?></option>
        </select>
        <?php
    }

    /**
     * Filter businesses by photo status
     *
     * @param WP_Query $query The WordPress query
     */
    public function filter_by_photo_status($query) {
        global $pagenow;
        
        if (!is_admin() || $pagenow !== 'edit.php' || 
            !isset($_GET['post_type']) || $_GET['post_type'] !== 'business' || 
            !isset($_GET['photo_status']) || empty($_GET['photo_status'])) {
            return;
        }
        
        $photo_status = $_GET['photo_status'];
        $meta_query = $query->get('meta_query');
        if (!is_array($meta_query)) {
            $meta_query = array();
        }
        
        switch ($photo_status) {
            case 'with':
                $meta_query[] = array(
                    'key' => '_gpd_photo_references',
                    'compare' => 'EXISTS',
                );
                break;
                
            case 'without':
                $meta_query[] = array(
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
                );
                break;
                
            case 'no-featured':
                $meta_query[] = array(
                    'key' => '_thumbnail_id',
                    'compare' => 'NOT EXISTS',
                );
                break;
        }
        
        if (!empty($meta_query)) {
            $query->set('meta_query', $meta_query);
        }
    }

    /**
     * Add admin styles for the photo count column
     */
    public function add_admin_styles() {
        $screen = get_current_screen();
        if (!$screen) return;
        
        // Add styles for the business listing screen
        if ($screen->id === 'edit-business') {
            ?>
            <style>
                .column-photos {
                    width: 80px;
                    text-align: center;
                }
                
                .gpd-no-photos {
                    color: #a00;
                    font-weight: bold;
                }
                
                .gpd-add-photos-link {
                    display: block;
                    font-size: 11px;
                    margin-top: 4px;
                }
                
                .gpd-photos-count {
                    position: relative;
                }
                
                .gpd-count {
                    font-weight: bold;
                    color: #0073aa;
                }
                
                .gpd-photo-preview-container {
                    display: none;
                    position: absolute;
                    z-index: 10;
                    left: 50%;
                    transform: translateX(-50%);
                    top: 20px;
                    padding: 5px;
                    background: #fff;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                    border-radius: 5px;
                }
                
                .gpd-photos-count:hover .gpd-photo-preview-container {
                    display: block;
                }
                
                .gpd-photo-preview {
                    max-width: 100px;
                    height: auto;
                    border-radius: 3px;
                }
                
                .dashicons-star-filled {
                    color: #f1c40f;
                    margin-left: 3px;
                    font-size: 16px;
                    line-height: 1.5;
                    width: 16px;
                    height: 16px;
                }
            </style>
            <?php
        }
        
        // Import page styles
        if ($screen->id === 'business_page_gpd-import') {
            ?>
            <style>
                .wp-list-table .check-column {
                    width: 2.2em;
                }
            </style>
            <?php
        }
    }

    /**
     * Display notices about imported businesses
     */
    public function display_import_notices() {
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'business_page_gpd-import') {
            return;
        }

        // Set API notice cookie before any output
        $show_api_notice = !isset($_COOKIE['gpd_api_notice_shown']);
        if ($show_api_notice) {
            setcookie('gpd_api_notice_shown', '1', [
                'expires' => time() + DAY_IN_SECONDS,
                'path' => parse_url(admin_url(), PHP_URL_PATH),
                'secure' => is_ssl(),
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        }

        // Display success message for imported businesses
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

                // Add info about photo import if enabled
                $photo_limit = (int)get_option('gpd_photo_limit', 3);
                if ($photo_limit > 0) {
                    $message .= ' ' . sprintf(
                        _n(
                            'Up to %d photo imported per business.',
                            'Up to %d photos imported per business.',
                            $photo_limit,
                            'google-places-directory'
                        ),
                        $photo_limit
                    );
                }

                echo '<div class="notice notice-success is-dismissible"><p>' . 
                    esc_html($message) . '</p></div>';
            }
        }

        // Show API errors
        if (!empty($_GET['error'])) {
            $error_message = urldecode($_GET['error']);
            echo '<div class="notice notice-error"><p>' . 
                esc_html($error_message) . '</p>';
            ?>
            <p><?php _e('API Error Troubleshooting:', 'google-places-directory'); ?></p>
            <ol>
                <li><?php _e('Ensure Places API v1 is enabled for your project', 'google-places-directory'); ?></li>
                <li><?php _e('Verify that "Place Details - Advanced" or "Higher Data Freshness" SKU is enabled', 'google-places-directory'); ?></li>
                <li><?php _e('Check that your API key has permissions to access Places API v1', 'google-places-directory'); ?></li>
                <li><?php _e('Make sure your billing account is properly configured', 'google-places-directory'); ?></li>
            </ol>
            </div>
            <?php
        }

        // Show API migration notice only once per session
        $show_api_notice = !isset($_COOKIE['gpd_api_notice_shown']);
        if ($show_api_notice) {
            // Set cookie to avoid showing the notice on every page load
            setcookie('gpd_api_notice_shown', '1', time() + DAY_IN_SECONDS, ADMIN_COOKIE_PATH);
        }
        
        if ($show_api_notice) {
            ?>
            <div class="notice notice-info is-dismissible">
                <p>
                    <strong><?php _e('Google Places API Update:', 'google-places-directory'); ?></strong>
                    <?php _e('This plugin now uses the new Google Places API v1. Results may look slightly different from previous versions.', 'google-places-directory'); ?>
                </p>
            </div>
            <?php
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
        $photo_limit      = (int) get_option('gpd_photo_limit', 3);

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
                    <p><?php _e('API Error Troubleshooting:', 'google-places-directory'); ?></p>
                    <ol>
                        <li><?php _e('Ensure Places API v1 is enabled for your project', 'google-places-directory'); ?></li>
                        <li><?php _e('Verify that "Place Details - Advanced" or "Higher Data Freshness" SKU is enabled', 'google-places-directory'); ?></li>
                        <li><?php _e('Check that your API key has permissions to access Places API v1', 'google-places-directory'); ?></li>
                        <li><?php _e('Make sure your billing account is properly configured', 'google-places-directory'); ?></li>
                    </ol>
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
                    $summary = sprintf(
                        __('Showing results for "%s" within %d km', 'google-places-directory'),
                        esc_html($query),
                        $selected_radius
                    );
                    
                    // Add photo import info if enabled
                    if ($photo_limit > 0) {
                        $summary .= ' · ' . sprintf(
                            _n(
                                'Will import %d photo per business', 
                                'Will import up to %d photos per business', 
                                $photo_limit,
                                'google-places-directory'
                            ),
                            $photo_limit
                        );
                    }
                    echo esc_html($summary);
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
                                <th><?php esc_html_e( 'Name', 'google-places-directory' ); ?></th>
                                <th><?php esc_html_e( 'Address', 'google-places-directory' ); ?></th>
                                <th><?php esc_html_e( 'Type', 'google-places-directory' ); ?></th>
                                <th class="rating-column"><?php esc_html_e( 'Rating', 'google-places-directory' ); ?></th>
                                <?php if ($photo_limit > 0): ?>
                                <th class="photo-column"><?php esc_html_e( 'Photos', 'google-places-directory' ); ?></th>
                                <?php endif; ?>
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
                                <?php if ($photo_limit > 0): ?>
                                <td class="photo-column">
                                    <span class="dashicons dashicons-camera" title="<?php esc_attr_e('Photos will be imported', 'google-places-directory'); ?>"></span>
                                </td>
                                <?php endif; ?>
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
            .photo-column {
                width: 60px;
                text-align: center;
            }
            .photo-column .dashicons {
                color: #0073aa;
                font-size: 18px;
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
    }    /**
     * Enqueue admin scripts and styles
     * 
     * @param string $hook The current admin page hook
     */    public function enqueue_admin_scripts($hook) {
        // Load admin styles on business post type pages
        if (strpos($hook, 'business') !== false || $hook === 'edit.php' || $hook === 'post.php' || $hook === 'post-new.php') {
            $admin_css_file = plugin_dir_path(__FILE__) . '../assets/css/admin-style.css';
            wp_enqueue_style(
                'gpd-admin',
                plugin_dir_url(__FILE__) . '../assets/css/admin-style.css',
                array(),
                file_exists($admin_css_file) ? filemtime($admin_css_file) : GPD_VERSION
            );
        }
        
        // Only load admin JS on our import page
        if ('business_page_gpd-import' === $hook) {
            $admin_js_file = plugin_dir_path(__FILE__) . '../assets/js/admin.js';
            wp_enqueue_script(
                'gpd-admin',
                plugin_dir_url(__FILE__) . '../assets/js/admin.js',
                array('jquery'),
                file_exists($admin_js_file) ? filemtime($admin_js_file) : GPD_VERSION,
                true
            );
            
            wp_localize_script('gpd-admin', 'gpdAdmin', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('gpd_import_action'),
                'i18n' => array(
                    'processing' => __('Processing', 'google-places-directory'),
                    'of' => __('of', 'google-places-directory'),
                    'businesses' => __('businesses...', 'google-places-directory')
                )
            ));
        }
    }

    /**
     * Handle import with progress tracking
     */
    public function handle_import() {
        if (!current_user_can('manage_options') || !check_admin_referer('gpd_import_action', 'gpd_import_nonce')) {
            wp_die(__('Permission denied', 'google-places-directory'));
        }

        $query = sanitize_text_field($_POST['query']);
        $radius = intval($_POST['radius']);
        $limit = intval($_POST['limit']);
        $incoming_token = sanitize_text_field($_POST['pagetoken'] ?? '');
        
        // Get places from API
        $next_page_token = '';
        $places_result = GPD_Importer::instance()->import_places(
            $query,
            $radius * 1000,
            $limit,
            $next_page_token, 
            $incoming_token
        );
        
        // Handle API errors
        if (is_wp_error($places_result)) {
            $redirect_url = add_query_arg([
                'post_type' => 'business',
                'page' => 'gpd-import',
                'query' => $query,
                'radius' => $radius,
                'limit' => $limit,
                'error' => urlencode($places_result->get_error_message())
            ], admin_url('edit.php'));
            
            wp_redirect($redirect_url);
            exit;
        }

        // Get selected places
        $selected = array_keys($_POST['places'] ?? []);
        $to_import = [];
        foreach ($selected as $i) {
            if (isset($places_result[$i])) {
                $to_import[] = $places_result[$i];
            }
        }

        // Process the selected places
        $result = GPD_Importer::instance()->process_import($to_import);

        // Redirect back with results
        $redirect_url = add_query_arg([
            'post_type' => 'business',
            'page' => 'gpd-import',
            'query' => $query,
            'radius' => $radius,
            'limit' => $limit,
            'created' => $result['created'],
            'updated' => $result['updated']
        ], admin_url('edit.php'));

        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Display import progress
     */
    private function display_import_progress($batch_id) {
        $status = GPD_Importer::instance()->get_batch_status($batch_id);
        
        if (!$status) {
            echo '<div class="notice notice-error"><p>' . 
                esc_html__('Import batch not found or expired.', 'google-places-directory') . 
                '</p></div>';
            return;
        }

        $percent = ($status['total'] > 0) 
            ? round(($status['processed'] / $status['total']) * 100) 
            : 0;

        ?>
        <div class="gpd-import-progress">
            <h2><?php esc_html_e('Import Progress', 'google-places-directory'); ?></h2>
            
            <div class="gpd-progress-bar">
                <div class="gpd-progress-complete" style="width: <?php echo esc_attr($percent); ?>%">
                    <?php echo esc_html($percent); ?>%
                </div>
            </div>
            
            <p class="gpd-progress-stats">
                <?php
                printf(
                    esc_html__('Processing %1$d of %2$d businesses...', 'google-places-directory'),
                    $status['processed'],
                    $status['total']
                );
                ?>
            </p>

            <?php if ($status['status'] === 'completed'): ?>
                <div class="notice notice-success">
                    <p>
                        <?php
                        printf(
                            esc_html__('Import completed: %1$d created, %2$d updated, %3$d failed', 'google-places-directory'),
                            $status['results']['created'],
                            $status['results']['updated'],
                            $status['results']['failed']
                        );
                        ?>
                    </p>
                    <?php if (!empty($status['results']['errors'])): ?>
                        <div class="gpd-import-errors">
                            <h4><?php esc_html_e('Import Errors:', 'google-places-directory'); ?></h4>
                            <ul>
                                <?php foreach ($status['results']['errors'] as $error): ?>
                                    <li>
                                        <?php
                                        printf(
                                            esc_html__('Place ID %1$s: %2$s', 'google-places-directory'),
                                            esc_html($error['place_id']),
                                            esc_html($error['error'])
                                        );
                                        ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p class="description">
                    <?php esc_html_e('The import is running in the background. You can leave this page and come back later.', 'google-places-directory'); ?>
                </p>
                <script>
                    jQuery(document).ready(function($) {
                        function updateProgress() {
                            $.ajax({
                                url: ajaxurl,
                                data: {
                                    action: 'gpd_check_import_progress',
                                    batch_id: '<?php echo esc_js($batch_id); ?>',
                                    nonce: '<?php echo wp_create_nonce('gpd_import_progress'); ?>'
                                },
                                success: function(response) {
                                    if (response.success && response.data) {
                                        if (response.data.status === 'completed') {
                                            window.location.reload();
                                        } else {
                                            $('.gpd-progress-complete').css('width', response.data.percent + '%')
                                                .text(response.data.percent + '%');
                                            $('.gpd-progress-stats').text(
                                                '<?php esc_html_e('Processing', 'google-places-directory'); ?> ' + 
                                                response.data.processed + ' <?php esc_html_e('of', 'google-places-directory'); ?> ' + 
                                                response.data.total + ' <?php esc_html_e('businesses...', 'google-places-directory'); ?>'
                                            );                                            setTimeout(updateProgress, 2000);
                                        }
                                    }
                                }
                            });
                        }
                        updateProgress();
                    });
                </script>
            <?php endif; ?>
        </div>
        <?php
    }
}
