<?php
/**
 * Uninstall file for Google Places Directory plugin.
 *
 * @package GooglePlacesDirectory
 */

// If not called by WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Security check - verify we're coming from WP core.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get options from database
$options = get_option( 'gpd_settings', array() );

// Check if we should delete all data when plugin is uninstalled
if ( isset( $options['delete_data_on_uninstall'] ) && 'yes' === $options['delete_data_on_uninstall'] ) {
    
    // Get all business posts
    $business_posts = get_posts(
        array(
            'post_type'      => 'business',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
        )
    );

    // Delete all business posts
    foreach ( $business_posts as $post_id ) {
        wp_delete_post( $post_id, true );
    }

    // Delete terms in our custom taxonomies
    $taxonomies = array( 'destination', 'region' );
    foreach ( $taxonomies as $taxonomy ) {
        $terms = get_terms(
            array(
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
                'fields'     => 'ids',
            )
        );

        if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
            foreach ( $terms as $term_id ) {
                wp_delete_term( $term_id, $taxonomy );
            }
        }
    }

    // Clean up options
    delete_option( 'gpd_settings' );
    delete_option( 'gpd_version' );
    delete_option( 'gpd_activation_time' );
    
    // Clean up transients
    delete_transient( 'gpd_api_request_count' );

    // Flush rewrite rules
    flush_rewrite_rules();
}
