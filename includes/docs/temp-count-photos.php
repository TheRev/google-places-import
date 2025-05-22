<?php
// This function should be copied into class-gpd-photo-manager.php
private function count_total_photos() {
    global $wpdb;
    
    // Get count of all valid photo attachments with references
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) 
        FROM {$wpdb->postmeta} pm
        JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key = %s 
        AND p.post_type = 'attachment'
        AND p.post_status = 'inherit'
        AND pm.meta_value != ''",
        '_gpd_photo_reference'
    ));
    
    return (int) $count;
}
