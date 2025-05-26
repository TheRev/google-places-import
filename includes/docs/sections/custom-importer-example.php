<?php
/**
 * Example of extending the Google Places Directory Importer
 * 
 * This example shows how to:
 * 1. Add a custom import source
 * 2. Process external data and convert it to the GPD format
 * 3. Integrate with the existing import functionality
 */

/**
 * Register a custom import source in the admin UI
 */
function my_gpd_register_custom_import_source($import_sources) {
    $import_sources['my_external_source'] = array(
        'name' => 'External Business Directory',
        'description' => 'Import businesses from your external directory API',
        'callback' => 'my_gpd_process_external_import',
        'fields' => array(
            'api_endpoint' => array(
                'label' => 'API Endpoint URL',
                'type' => 'text',
                'default' => 'https://example.com/api/businesses',
                'required' => true,
                'description' => 'Enter the full URL to your external API endpoint',
            ),
            'api_key' => array(
                'label' => 'API Key',
                'type' => 'password',
                'default' => '',
                'required' => true,
                'description' => 'Enter the API key for authentication',
            ),
            'import_limit' => array(
                'label' => 'Import Limit',
                'type' => 'number',
                'default' => 25,
                'min' => 1,
                'max' => 100,
                'description' => 'Maximum number of businesses to import',
            ),
        ),
    );
    
    return $import_sources;
}
add_filter('gpd_import_sources', 'my_gpd_register_custom_import_source');

/**
 * Process the import from the external source
 * 
 * @param array $options Import options from the form
 * @return array Results of the import process
 */
function my_gpd_process_external_import($options) {
    $results = array(
        'success' => 0,
        'failures' => 0,
        'messages' => array(),
        'imported_ids' => array(),
    );
    
    // Get options
    $api_endpoint = isset($options['api_endpoint']) ? esc_url_raw($options['api_endpoint']) : '';
    $api_key = isset($options['api_key']) ? sanitize_text_field($options['api_key']) : '';
    $import_limit = isset($options['import_limit']) ? intval($options['import_limit']) : 25;
    
    if (empty($api_endpoint) || empty($api_key)) {
        $results['messages'][] = 'Missing required API information.';
        return $results;
    }
    
    // Make request to external API
    $response = wp_remote_get(add_query_arg(array(
        'api_key' => $api_key,
        'limit' => $import_limit,
    ), $api_endpoint));
    
    // Check for errors
    if (is_wp_error($response)) {
        $results['messages'][] = 'API Error: ' . $response->get_error_message();
        return $results;
    }
    
    // Parse response
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        $results['messages'][] = 'Invalid JSON response from API.';
        return $results;
    }
    
    // Get GPD importer to use its functions
    $gpd_importer = class_exists('GPD_Importer') ? GPD_Importer::instance() : null;
    
    if (!$gpd_importer) {
        $results['messages'][] = 'GPD Importer not available.';
        return $results;
    }
    
    // Process each business
    $businesses = isset($data['businesses']) ? $data['businesses'] : array();
    
    foreach ($businesses as $business) {
        // Convert external data format to GPD format
        $gpd_business_data = my_gpd_convert_to_gpd_format($business);
        
        // Skip if required data is missing
        if (empty($gpd_business_data['title'])) {
            $results['failures']++;
            continue;
        }
        
        // Check if business already exists by external ID
        $existing_id = my_gpd_find_existing_business($business['id']);
        
        // Import/update the business
        $business_id = $gpd_importer->import_single_business($gpd_business_data, $existing_id);
        
        if ($business_id) {
            // Store the external ID for future reference
            update_post_meta($business_id, '_my_external_business_id', $business['id']);
            
            $results['success']++;
            $results['imported_ids'][] = $business_id;
        } else {
            $results['failures']++;
        }
    }
    
    $results['messages'][] = sprintf('Imported %d businesses successfully. %d failed.',
        $results['success'], $results['failures']);
        
    return $results;
}

/**
 * Convert external business data format to GPD format
 * 
 * @param array $external_business Business data from external API
 * @return array Formatted for GPD import
 */
function my_gpd_convert_to_gpd_format($external_business) {
    // Map external fields to GPD fields
    return array(
        'title' => isset($external_business['name']) ? $external_business['name'] : '',
        'content' => isset($external_business['description']) ? $external_business['description'] : '',
        'meta' => array(
            '_gpd_address' => isset($external_business['address']) ? $external_business['address'] : '',
            '_gpd_phone' => isset($external_business['phone']) ? $external_business['phone'] : '',
            '_gpd_website' => isset($external_business['website']) ? $external_business['website'] : '',
            '_gpd_email' => isset($external_business['email']) ? $external_business['email'] : '',
            '_gpd_rating' => isset($external_business['rating']) ? $external_business['rating'] : '',
            '_gpd_coordinates' => isset($external_business['latitude']) && isset($external_business['longitude']) ? 
                $external_business['latitude'] . ',' . $external_business['longitude'] : '',
            '_gpd_business_type' => isset($external_business['type']) ? $external_business['type'] : '',
            '_my_external_source' => 'external_directory',
            '_my_external_business_id' => isset($external_business['id']) ? $external_business['id'] : '',
        ),
        'taxonomies' => array(
            'business_category' => isset($external_business['categories']) ? $external_business['categories'] : array(),
            'business_region' => isset($external_business['regions']) ? $external_business['regions'] : array(),
        ),
    );
}

/**
 * Find if a business with the given external ID already exists
 * 
 * @param string $external_id External business ID
 * @return int|null WordPress post ID if found, null otherwise
 */
function my_gpd_find_existing_business($external_id) {
    if (empty($external_id)) {
        return null;
    }
    
    // Query for businesses with matching external ID
    $query = new WP_Query(array(
        'post_type' => 'business',
        'meta_query' => array(
            array(
                'key' => '_my_external_business_id',
                'value' => $external_id,
            ),
        ),
        'posts_per_page' => 1,
        'fields' => 'ids',
    ));
    
    if ($query->have_posts()) {
        return $query->posts[0];
    }
    
    return null;
}
