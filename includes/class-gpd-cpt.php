<?php
/**
 * class-gpd-cpt.php
 *
 * Defines the Business CPT and Region/Destination taxonomies.
 * Registers custom meta fields for the Business CPT.
 * Adds taxonomy filters to the Business CPT admin list.
 * 
 * Updated for Google Places API (New) in May 2025
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GPD_CPT {
    /**
     * Post type arguments
     * @var array
     */
    protected $post_type_args = [];
    public static function instance() {
        static $instance = null;
        if ( ! $instance ) {
            $instance = new self();
        }
        return $instance;
    }
    
    public function __construct() {
        // Initialize labels before registering post types
        $this->init_labels();
        
        // Register post types early in init
        add_action('init', array($this, 'register_post_types_and_taxonomies'), 0);
        
        // Register meta fields after post type is registered
        add_action('init', array($this, 'register_custom_meta'), 1);
        
        // Add hooks for HTML handling, taxonomy filters, and admin columns
        add_filter('content_save_pre', array($this, 'allow_html_for_business_cpt'));
        add_action('restrict_manage_posts', array($this, 'add_taxonomy_filters'));
        add_filter('manage_business_posts_columns', array($this, 'add_business_columns'));
        add_action('manage_business_posts_custom_column', array($this, 'render_business_column'), 10, 2);
        
        // REST API hooks
        add_filter('rest_prepare_business', array($this, 'add_meta_to_rest_api'), 10, 3);
        add_action('rest_api_init', array($this, 'register_meta_fields'));
    }

    public function init_labels() {
        // Initialize labels at init hook to ensure translations are available
        $this->post_type_args = array(
            'labels' => array(
                'name'               => __('Businesses', 'google-places-directory'),
                'singular_name'      => __('Business', 'google-places-directory'),
                'menu_name'          => __('Businesses', 'google-places-directory'),
                'name_admin_bar'     => __('Business', 'google-places-directory'),
                'add_new'           => __('Add New', 'google-places-directory'),
                'add_new_item'      => __('Add New Business', 'google-places-directory'),
                'new_item'          => __('New Business', 'google-places-directory'),
                'edit_item'         => __('Edit Business', 'google-places-directory'),
                'view_item'         => __('View Business', 'google-places-directory'),
                'all_items'         => __('All Businesses', 'google-places-directory'),
                'search_items'      => __('Search Businesses', 'google-places-directory'),
                'not_found'         => __('No businesses found.', 'google-places-directory'),
                'not_found_in_trash'=> __('No businesses found in Trash.', 'google-places-directory')
            ),
            'public'       => true,
            'has_archive'  => true,
            'show_in_rest' => true,
            'rest_base'    => 'businesses',
            'menu_icon'    => 'dashicons-store',
            'supports'     => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'rewrite'      => array('slug' => 'business')
        );
    }

    public function register_post_types_and_taxonomies() {
        // Register the post type
        register_post_type('business', $this->post_type_args);

        // Register taxonomies
        $this->register_taxonomies();
    }

    public function register_taxonomies() {
        // --- Destinations taxonomy ---
        register_taxonomy( 'destination', 'business', [
            'labels'            => [
                'name'          => __( 'Destinations', 'google-places-directory' ),
                'singular_name' => __( 'Destination',  'google-places-directory' ),
                'search_items'  => __( 'Search Destinations', 'google-places-directory' ),
                'all_items'     => __( 'All Destinations', 'google-places-directory' ),
                'edit_item'     => __( 'Edit Destination', 'google-places-directory' ),
                'update_item'   => __( 'Update Destination', 'google-places-directory' ),
                'add_new_item'  => __( 'Add New Destination', 'google-places-directory' ),
                'new_item_name' => __( 'New Destination Name', 'google-places-directory' ),
                'menu_name'     => __( 'Destinations', 'google-places-directory' ),
            ],
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'rewrite'           => [ 'slug' => 'destination' ],
            'show_in_rest'      => true, // Make taxonomy available in REST API
            'show_in_quick_edit' => false,
        ] );

        // --- Regions taxonomy ---
        register_taxonomy( 'region', 'business', [
            'labels'            => [
                'name'          => __( 'Regions', 'google-places-directory' ),
                'singular_name' => __( 'Region',  'google-places-directory' ),
                'search_items'  => __( 'Search Regions', 'google-places-directory' ),
                'all_items'     => __( 'All Regions', 'google-places-directory' ),
                'edit_item'     => __( 'Edit Region', 'google-places-directory' ),
                'update_item'   => __( 'Update Region', 'google-places-directory' ),
                'add_new_item'  => __( 'Add New Region', 'google-places-directory' ),
                'new_item_name' => __( 'New Region Name', 'google-places-directory' ),
                'menu_name'     => __( 'Regions', 'google-places-directory' ),
            ],
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'rewrite'           => [ 'slug' => 'region' ],
            'show_in_rest'      => true, // Make taxonomy available in REST API
            'show_in_quick_edit' => false,
        ] );
    }

    /**
     * Add custom columns to the business post type admin list
     */
    public function add_business_columns($columns) {
        $new_columns = array();
        
        // Insert columns after title but before date
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['rating'] = __('Rating', 'google-places-directory');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Render content for the custom columns
     */
    public function render_business_column($column, $post_id) {
        switch ($column) {
            case 'rating':
                $rating = get_post_meta($post_id, '_gpd_rating', true);
                if (!empty($rating)) {
                    echo '<div class="gpd-star-rating">';
                    echo esc_html(number_format(floatval($rating), 1));
                    echo ' <span class="dashicons dashicons-star-filled" style="color:#ffb900;"></span>';
                    echo '</div>';
                } else {
                    echo '—';
                }
                break;
        }
    }

    /**
     * Allow HTML tags, specifically <span> with class attributes, for the "Businesses" CPT.
     *
     * @param string $content The content being saved.
     * @return string The sanitized or unfiltered content.
     */
    public function allow_html_for_business_cpt( $content ) {
        // Check if the current post is of type 'business' being saved from the editor
        if ( get_post_type() === 'business' && ( isset( $_POST['action'] ) && $_POST['action'] === 'editpost' ) ) {
             global $post; // Ensure $post is available
             if ( $post && $post->post_type === 'business') {
                remove_filter( 'content_save_pre', 'wp_filter_post_kses' );
                remove_filter( 'content_filtered_save_pre', 'wp_filter_post_kses' );
            }
        }
        return $content;
    }

    /**
     * Add taxonomy filters to the Business CPT admin list.
     * Hooked to restrict_manage_posts.
     */
    public function add_taxonomy_filters() {
        global $typenow;

        if ( 'business' !== $typenow ) {
            return;
        }

        $taxonomies = [ 'destination', 'region' ];

        foreach ( $taxonomies as $taxonomy_slug ) {
            $taxonomy_obj = get_taxonomy( $taxonomy_slug );
            if ( ! $taxonomy_obj ) {
                continue;
            }

            $terms = get_terms( [
                'taxonomy'   => $taxonomy_slug,
                'hide_empty' => false,
            ] );

            if ( empty( $terms ) || is_wp_error( $terms ) ) {
                continue;
            }

            $current_term_slug = isset( $_GET[ $taxonomy_slug ] ) ? sanitize_text_field( wp_unslash( $_GET[ $taxonomy_slug ] ) ) : '';
            ?>
            <select name="<?php echo esc_attr( $taxonomy_slug ); ?>" id="<?php echo esc_attr( $taxonomy_slug . '_filter' ); ?>" class="postform">
                <option value=""><?php printf( esc_html__( 'All %s', 'google-places-directory' ), esc_html( $taxonomy_obj->labels->name ) ); ?></option>
                <?php foreach ( $terms as $term ) : ?>
                    <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $current_term_slug, $term->slug ); ?>>
                        <?php echo esc_html( $term->name ); ?> (<?php echo esc_html( $term->count ); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php
        }
        
        // Add rating filter
        $current_rating = isset( $_GET['min_rating'] ) ? floatval( $_GET['min_rating'] ) : 0;
        ?>
        <select name="min_rating" id="min_rating_filter">
            <option value=""><?php esc_html_e( 'Any Rating', 'google-places-directory' ); ?></option>
            <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                <option value="<?php echo esc_attr( $i ); ?>" <?php selected( $current_rating, $i ); ?>>
                    <?php printf( esc_html__( '%d+ Stars', 'google-places-directory' ), $i ); ?>
                </option>
            <?php endfor; ?>
        </select>
        <?php
    }

    public function register_custom_meta() {
        $meta_fields_to_register = [
            '_gpd_place_id'        => [
                'type'              => 'string',
                'description'       => __('Google Place ID.', 'google-places-directory'),
                'sanitize_callback' => 'sanitize_text_field',
            ],
            '_gpd_display_name'    => [
                'type'              => 'string',
                'description'       => __('Display name from Google Places.', 'google-places-directory'),
                'sanitize_callback' => 'sanitize_text_field',
            ],
            '_gpd_address'         => [
                'type'              => 'string',
                'description'       => __('Formatted address.', 'google-places-directory'),
                'sanitize_callback' => 'sanitize_textarea_field', // Good for potentially multi-line addresses
            ],
            '_gpd_locality'        => [
                'type'              => 'string',
                'description'       => __('Locality (city).', 'google-places-directory'),
                'sanitize_callback' => 'sanitize_text_field',
            ],
            '_gpd_latitude'        => [
                'type'              => 'number',
                'description'       => __('Latitude.', 'google-places-directory'),
            ],
            '_gpd_longitude'       => [
                'type'              => 'number',
                'description'       => __('Longitude.', 'google-places-directory'),
            ],
            '_gpd_types'           => [
                'type'              => 'array',
                'description'       => __('Place types from Google.', 'google-places-directory'),
                 'show_in_rest'      => [ // Explicit schema for array items
                    'schema' => [
                        'type'  => 'array',
                        'items' => [ 'type' => 'string' ],
                    ],
                ],
                'sanitize_callback' => [ $this, 'sanitize_array_of_strings' ],
            ],
            '_gpd_rating'          => [
                'type'              => 'number',
                'description'       => __('Rating from Google Places.', 'google-places-directory'),
            ],
            '_gpd_business_status' => [
                'type'              => 'string',
                'description'       => __('Business status from Google Places.', 'google-places-directory'),
                'sanitize_callback' => 'sanitize_text_field',
            ],
            '_gpd_maps_uri'        => [
                'type'              => 'string',
                'description'       => __('Google Maps URI for the place.', 'google-places-directory'),
                'sanitize_callback' => 'esc_url_raw',
            ],
            '_gpd_website'         => [ // New field
                'type'              => 'string',
                'description'       => __('Business website.', 'google-places-directory'),
                'sanitize_callback' => 'esc_url_raw',
            ],
            '_gpd_phone_number'    => [ // New field
                'type'              => 'string',
                'description'       => __('Business phone number.', 'google-places-directory'),
                'sanitize_callback' => 'sanitize_text_field',
            ],
            '_gpd_api_version'     => [ // New field to track API version
                'type'              => 'string',
                'description'       => __('Google Places API version used for import.', 'google-places-directory'),
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ];

        foreach ($meta_fields_to_register as $meta_key => $args) {
            $default_args = [
                'single'       => true,
                'show_in_rest' => true, // Default to true, can be overridden by specific schema like for _gpd_types
                'auth_callback' => function() { // Ensures user can edit posts to modify this meta via REST
                    return current_user_can( 'edit_posts' );
                }
            ];
            
            // Merge specific args from $meta_fields_to_register with defaults.
            // $args will override $default_args if keys conflict (e.g. show_in_rest for _gpd_types).
            $merged_args = array_merge($default_args, $args);

            register_post_meta( 'business', $meta_key, $merged_args );
        }
    }

    /**
     * Sanitize callback for an array of strings
     */    public function sanitize_array_of_strings($value) {
        if (!is_array($value)) {
            return [];
        }
        return array_map('sanitize_text_field', $value);
    }
    
    /**
     * Add meta fields to REST API response
     *
     * @param WP_REST_Response $response Current response object
     * @param WP_Post $post Current post object
     * @param WP_REST_Request $request Current request object
     * @return WP_REST_Response
     */
    public function add_meta_to_rest_api($response, $post, $request) {
        // Add meta fields to API response
        $meta_fields = [
            '_gpd_place_id',
            '_gpd_display_name',
            '_gpd_address',
            '_gpd_locality',
            '_gpd_latitude',
            '_gpd_longitude',
            '_gpd_types',
            '_gpd_rating',
            '_gpd_business_status',
            '_gpd_maps_uri',
            '_gpd_website',
            '_gpd_phone_number',
            '_gpd_api_version'
        ];
        
        foreach ($meta_fields as $field) {
            $response->data[$field] = get_post_meta($post->ID, $field, true);
        }
        
        return $response;
    }
      /**
     * Register meta fields for REST API
     */
    public function register_meta_fields() {
        // Register meta fields specifically for REST API
        register_rest_field('business', 'business_meta', [
            'get_callback' => function($post) {
                $meta_fields = [
                    '_gpd_place_id',
                    '_gpd_display_name',
                    '_gpd_address',
                    '_gpd_locality',
                    '_gpd_latitude',
                    '_gpd_longitude',
                    '_gpd_types',
                    '_gpd_rating',
                    '_gpd_business_status',
                    '_gpd_maps_uri',
                    '_gpd_website',
                    '_gpd_phone_number'
                ];
                
                $meta = [];
                foreach ($meta_fields as $field) {
                    $meta[str_replace('_gpd_', '', $field)] = get_post_meta($post['id'], $field, true);
                }
                
                return $meta;
            },
            'schema' => [
                'description' => __('Business meta data from Google Places', 'google-places-directory'),
                'type' => 'object'
            ]
        ]);
    }
}

/**
 * Add filter to the posts query for business ratings
 */
add_filter('parse_query', function($query) {
    global $pagenow;
    
    // Only run in admin on the post list page for business type
    if (!is_admin() || $pagenow !== 'edit.php' || $query->get('post_type') !== 'business') {
        return;
    }
    
    // If we have a min_rating filter
    if (isset($_GET['min_rating']) && !empty($_GET['min_rating'])) {
        $min_rating = floatval($_GET['min_rating']);
        
        // Add meta query
        $meta_query = $query->get('meta_query');
        if (!is_array($meta_query)) {
            $meta_query = [];
        }
        
        $meta_query[] = [
            'key' => '_gpd_rating',
            'value' => $min_rating,
            'compare' => '>=',
            'type' => 'NUMERIC'
        ];
        
        $query->set('meta_query', $meta_query);
    }
    
    return $query;
});

/**
 * Add styling for admin columns
 */
add_action('admin_head', function() {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'business') {
        return;
    }
    ?>
    <style>
        .gpd-star-rating {
            display: flex;
            align-items: center;
        }
    </style>
    <?php
});
