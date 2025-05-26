<?php
/**
 * TEMPORARY PERMALINK FIX
 * 
 * Instructions:
 * 1. Upload this file to your WordPress root directory
 * 2. Visit: yoursite.com/fix-permalinks.php in your browser
 * 3. Delete this file after running it once
 * 
 * This will flush the rewrite rules and fix the "Page Not Found" errors for business pages.
 */

// Include WordPress
require_once('wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('You must be an administrator to run this script.');
}

echo "<h1>Google Places Directory - Permalink Fix</h1>";

// Register the business post type
register_post_type('business', array(
    'public' => true,
    'has_archive' => false,
    'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
    'show_in_rest' => true,
    'rewrite' => array('slug' => 'business'),
));

// Flush rewrite rules
flush_rewrite_rules();

echo "<p style='color: green; font-weight: bold;'>✅ Rewrite rules have been flushed!</p>";
echo "<p>Your business page URLs should now work correctly.</p>";
echo "<p><strong>Important:</strong> Please delete this file (fix-permalinks.php) from your server now.</p>";
echo "<p><a href='" . admin_url() . "'>Go to WordPress Admin</a></p>";
?>
