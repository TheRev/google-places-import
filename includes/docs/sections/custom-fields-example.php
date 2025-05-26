<?php
/**
 * Example of extending Google Places Directory with custom fields
 * 
 * This example shows how to:
 * 1. Add custom fields to business posts
 * 2. Save data from Google Places API to these fields
 * 3. Display the custom fields in templates
 */

/**
 * Add custom meta boxes to the business post type
 */
function my_gpd_add_custom_meta_boxes() {
    add_meta_box(
        'my_gpd_custom_fields',
        'Custom Business Data',
        'my_gpd_custom_fields_callback',
        'business',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'my_gpd_add_custom_meta_boxes');

/**
 * Render custom meta box content
 */
function my_gpd_custom_fields_callback($post) {
    wp_nonce_field('my_gpd_custom_fields', 'my_gpd_custom_fields_nonce');
    
    // Get the current values
    $special_features = get_post_meta($post->ID, '_my_gpd_special_features', true);
    $manual_override = get_post_meta($post->ID, '_my_gpd_manual_override', true);
    
    ?>
    <p>
        <label for="my_gpd_special_features">Special Features:</label><br>
        <textarea id="my_gpd_special_features" name="my_gpd_special_features" rows="3" style="width: 100%;"><?php echo esc_textarea($special_features); ?></textarea>
        <span class="description">Enter special features for this business (one per line)</span>
    </p>
    <p>
        <input type="checkbox" id="my_gpd_manual_override" name="my_gpd_manual_override" value="1" <?php checked($manual_override, '1'); ?>>
        <label for="my_gpd_manual_override">Manual data override (prevent API updates from overwriting)</label>
    </p>
    <?php
}

/**
 * Save custom field data
 */
function my_gpd_save_custom_fields($post_id) {
    // Security checks
    if (!isset($_POST['my_gpd_custom_fields_nonce']) || 
        !wp_verify_nonce($_POST['my_gpd_custom_fields_nonce'], 'my_gpd_custom_fields')) {
        return;
    }
    
    // Don't save during autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Save special features
    if (isset($_POST['my_gpd_special_features'])) {
        update_post_meta($post_id, '_my_gpd_special_features', 
            sanitize_textarea_field($_POST['my_gpd_special_features']));
    }
    
    // Save manual override setting
    $manual_override = isset($_POST['my_gpd_manual_override']) ? '1' : '0';
    update_post_meta($post_id, '_my_gpd_manual_override', $manual_override);
}
add_action('save_post_business', 'my_gpd_save_custom_fields');

/**
 * Filter business data before it's imported from the API
 * This allows us to preserve manually entered data
 */
function my_gpd_filter_imported_data($business_data, $place_data, $business_id) {
    // Check if manual override is enabled for this business
    $manual_override = get_post_meta($business_id, '_my_gpd_manual_override', true);
    
    if ($manual_override === '1') {
        // Preserve existing post meta that shouldn't be overwritten
        $preserved_fields = array(
            '_gpd_price_level',
            '_gpd_business_type',
            '_my_gpd_special_features'
        );
        
        foreach ($preserved_fields as $field) {
            $existing_value = get_post_meta($business_id, $field, true);
            if (!empty($existing_value)) {
                // Remove the field from the data to be imported
                // This prevents the API data from overwriting our manual entries
                if (isset($business_data['meta'][$field])) {
                    unset($business_data['meta'][$field]);
                }
            }
        }
    }
    
    // Add additional data from the place_data that might not be included by default
    if (isset($place_data['primaryType'])) {
        $business_data['meta']['_my_gpd_primary_type'] = $place_data['primaryType'];
    }
    
    return $business_data;
}
add_filter('gpd_business_data_before_import', 'my_gpd_filter_imported_data', 10, 3);

/**
 * Add custom fields to the business info shortcode
 */
function my_gpd_add_custom_business_fields($fields) {
    $fields['special_features'] = array(
        'label' => 'Special Features',
        'meta_key' => '_my_gpd_special_features',
        'type' => 'list',
        'format_callback' => 'my_gpd_format_special_features'
    );
    
    return $fields;
}
add_filter('gpd_business_info_fields', 'my_gpd_add_custom_business_fields');

/**
 * Format special features as an HTML list
 */
function my_gpd_format_special_features($value) {
    if (empty($value)) {
        return '';
    }
    
    $features = explode("\n", $value);
    $output = '<ul class="gpd-special-features">';
    
    foreach ($features as $feature) {
        $feature = trim($feature);
        if (!empty($feature)) {
            $output .= '<li>' . esc_html($feature) . '</li>';
        }
    }
    
    $output .= '</ul>';
    return $output;
}

/**
 * Add our custom fields to the business export
 */
function my_gpd_add_export_fields($export_fields) {
    $export_fields['special_features'] = '_my_gpd_special_features';
    $export_fields['manual_override'] = '_my_gpd_manual_override';
    
    return $export_fields;
}
add_filter('gpd_business_export_fields', 'my_gpd_add_export_fields');
