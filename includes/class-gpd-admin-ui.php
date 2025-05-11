<?php
/**
 * class-gpd-admin-ui.php
 *
 * Manages the admin UI: search form, results table, import actions,
 * pagination (Prev/Next), and flags already‑imported businesses.
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

        // Add Destination column to Business list
        add_filter( 'manage_business_posts_columns', [ $this, 'add_destination_column' ] );
        add_action( 'manage_business_posts_custom_column', [ $this, 'render_destination_column' ], 10, 2 );
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
        add_submenu_page(
            'edit.php?post_type=business',
            __( 'Settings', 'google-places-directory' ),
            __( 'Settings', 'google-places-directory' ),
            'manage_options',
            'gpd-settings',
            [ GPD_Settings::instance(), 'render_settings_page' ]
        );
    }

    public function add_destination_column( $columns ) {
        $columns['destination'] = __( 'Destination', 'google-places-directory' );
        return $columns;
    }

    public function render_destination_column( $column, $post_id ) {
        if ( 'destination' === $column ) {
            $terms = get_the_terms( $post_id, 'destination' );
            if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                echo esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) );
            } else {
                echo '&mdash;';
            }
        }
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
            $places = GPD_Importer::instance()->import_places(
                $query,
                $selected_radius * 1000,
                $selected_limit,
                $next_page_token,
                $incoming_token
            );
            // Show newest first
            if ( is_array( $places ) ) {
                // $places = ( $places );
                $places = array_reverse($places, /* preserve_keys */ true);
            }
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Business Import', 'google-places-directory' ); ?></h1>

            <form method="get" action="">
                <input type="hidden" name="post_type" value="business">
                <input type="hidden" name="page"      value="gpd-import">

                <label for="gpd-query"><?php esc_html_e( 'Search Query:', 'google-places-directory' ); ?></label>
                <input type="text" id="gpd-query" name="query" value="<?php echo esc_attr( $query ); ?>" required>

                <label for="gpd-radius"><?php esc_html_e( 'Radius (km):', 'google-places-directory' ); ?></label>
                <select id="gpd-radius" name="radius">
                    <?php foreach ( $radius_options as $r ): ?>
                        <option value="<?php echo esc_attr( $r ); ?>" <?php selected( $selected_radius, $r ); ?>>
                            <?php echo esc_html( $r ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="gpd-limit"><?php esc_html_e( 'Results:', 'google-places-directory' ); ?></label>
                <select id="gpd-limit" name="limit">
                    <?php foreach ( $limit_options as $l ): ?>
                        <option value="<?php echo esc_attr( $l ); ?>" <?php selected( $selected_limit, $l ); ?>>
                            <?php echo esc_html( $l ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php submit_button( __( 'Search', 'google-places-directory' ), 'primary', 'submit', false ); ?>
            </form>

            <?php if ( $query ) : ?>
                <div style="margin:1em 0">
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

                    <table class="widefat fixed striped">
                        <thead>
                            <tr>
                                <th></th>
                                <th><?php esc_html_e( 'Name',    'google-places-directory' ); ?></th>
                                <th><?php esc_html_e( 'Address', 'google-places-directory' ); ?></th>
                                <th><?php esc_html_e( 'Type',    'google-places-directory' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ( $places as $index => $place ) :
                            $pid      = $place['place_id'] ?? '';
                            $imported = in_array( $pid, $imported_ids, true );
                            $address  = $place['formatted_address'] ?? ( isset( $place['address_components'] ) ? implode( ', ', wp_list_pluck( $place['address_components'], 'long_name' ) ) : '' );
                            $types    = isset( $place['types'] ) ? implode( ', ', $place['types'] ) : '';
                        ?>
                            <tr class="<?php echo $imported ? 'gpd-imported' : ''; ?>">
                                <td>
                                    <?php if ( $imported ): ?>
                                        <span class="dashicons dashicons-yes-alt" title="<?php esc_attr_e( 'Already imported', 'google-places-directory' ); ?>"></span>
                                    <?php else: ?>
                                        <input type="checkbox" name="places[<?php echo $index; ?>]" value="1" checked>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html( $place['name'] ?? '—' ); ?></td>
                                <td><?php echo esc_html( $address ?: '—' ); ?></td>
                                <td><?php echo esc_html( $types ?: '—' ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php submit_button( __( 'Import Selected', 'google-places-directory' ) ); ?>
                </form>
            <?php endif; ?>
        </div>

        <style>
            .gpd-imported { opacity: 0.6; }
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

        // Initialize by-reference pagination token
        $next_page_token = '';

        $places = GPD_Importer::instance()->import_places(
            $query,
            $radius * 1000,
            $limit,
            $next_page_token,
            $incoming_token
        );

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
            'next_token' => $next_page_token,
            'updated'    => $result['updated'],
            'created'    => $result['created'],
        ], admin_url( 'edit.php' ) );

        wp_redirect( $redirect_url );
        exit;
    }
}
