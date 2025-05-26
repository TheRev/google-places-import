<?php
/**
 * Test script to verify the [gpd-business-map] shortcode fix
 * Run this file from a WordPress context to check:
 * 1. If the shortcode is registered
 * 2. If there are businesses with coordinates in the database
 * 3. If the shortcode renders properly
 */

// This should be run from a WordPress context (e.g., wp-cli or a WordPress page)

// Test 1: Check if shortcode is registered
echo "=== SHORTCODE REGISTRATION TEST ===\n";
if (shortcode_exists('gpd-business-map')) {
    echo "✓ [gpd-business-map] shortcode is registered\n";
} else {
    echo "✗ [gpd-business-map] shortcode is NOT registered\n";
}

// Test 2: Check for businesses with coordinates
echo "\n=== DATABASE TEST ===\n";
$args = array(
    'post_type' => 'business',
    'posts_per_page' => 5,
    'meta_query' => array(
        'relation' => 'AND',
        array(
            'key' => '_gpd_latitude',
            'compare' => 'EXISTS',
        ),
        array(
            'key' => '_gpd_longitude', 
            'compare' => 'EXISTS',
        ),
    ),
);

$query = new WP_Query($args);
if ($query->have_posts()) {
    echo "✓ Found {$query->found_posts} businesses with coordinates\n";
    while ($query->have_posts()) {
        $query->the_post();
        $lat = get_post_meta(get_the_ID(), '_gpd_latitude', true);
        $lng = get_post_meta(get_the_ID(), '_gpd_longitude', true);
        echo "  - " . get_the_title() . " (Lat: $lat, Lng: $lng)\n";
    }
    wp_reset_postdata();
} else {
    echo "✗ No businesses with coordinates found\n";
}

// Test 3: Try to render the shortcode
echo "\n=== SHORTCODE RENDERING TEST ===\n";
$shortcode_output = do_shortcode('[gpd-business-map height="300px" zoom="12"]');
if (!empty($shortcode_output) && !str_contains($shortcode_output, 'No businesses with location data found')) {
    echo "✓ Shortcode rendered successfully\n";
    echo "Output length: " . strlen($shortcode_output) . " characters\n";
    if (str_contains($shortcode_output, 'gpd-leaflet-map')) {
        echo "✓ Contains expected map container\n";
    }
    if (str_contains($shortcode_output, 'gpdInitMap')) {
        echo "✓ Contains map initialization script\n";
    }
} else {
    echo "✗ Shortcode failed to render or no data available\n";
    echo "Output: " . substr($shortcode_output, 0, 200) . "...\n";
}

// Test 4: Check required assets
echo "\n=== ASSETS TEST ===\n";
if (wp_script_is('gpd-leaflet-maps', 'registered')) {
    echo "✓ Leaflet maps script is registered\n";
} else {
    echo "✗ Leaflet maps script is NOT registered\n";
}

if (wp_style_is('gpd-leaflet-maps', 'registered')) {
    echo "✓ Leaflet maps style is registered\n";
} else {
    echo "✗ Leaflet maps style is NOT registered\n";
}

echo "\n=== TEST COMPLETE ===\n";
