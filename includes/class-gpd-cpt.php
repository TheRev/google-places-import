<?php
/**
 * class-gpd-cpt.php
 *
 * Defines the Business CPT and Region/Destination taxonomies.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GPD_CPT {
    public static function instance() {
        static $instance = null;
        if ( ! $instance ) {
            $instance = new self();
            add_action( 'init', [ $instance, 'register_post_type_and_taxonomies' ], 0 );
        }
        return $instance;
    }

    public function register_post_type_and_taxonomies() {
        // --- Business CPT ---
        $labels = [
            'name'               => __( 'Businesses', 'google-places-directory' ),
            'singular_name'      => __( 'Business',   'google-places-directory' ),
            'menu_name'          => __( 'Businesses', 'google-places-directory' ),
            'name_admin_bar'     => __( 'Business',   'google-places-directory' ),
        ];

        $args = [
            'labels'        => $labels,
            'public'        => true,
            'show_ui'       => true,
            'show_in_menu'  => true,
            'menu_icon'     => 'dashicons-store',
            'supports'      => [ 'title', 'editor', 'custom-fields' ],
            'taxonomies'    => [ 'region', 'destination' ],  // ← ensure this line is present
            'has_archive'   => false,
            'rewrite'       => [ 'slug' => 'business' ],
        ];
        register_post_type( 'business', $args );

        // --- Destinations taxonomy ---
        register_taxonomy( 'destination', 'business', [
            'labels'            => [
                'name'          => __( 'Destinations', 'google-places-directory' ),
                'singular_name' => __( 'Destination',  'google-places-directory' ),
            ],
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'rewrite'           => [ 'slug' => 'destination' ],
        ] );

        // --- Regions taxonomy ---
        register_taxonomy( 'region', 'business', [
            'labels'            => [
                'name'          => __( 'Regions', 'google-places-directory' ),
                'singular_name' => __( 'Region',  'google-places-directory' ),
            ],
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'rewrite'           => [ 'slug' => 'region' ],
        ] );
    }
}
