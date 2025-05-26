<?php
/**
 * Google Places Directory API Integration Guide
 * This file contains comprehensive documentation for developers integrating with the Google Places API
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="gpd-docs-wrapper api-guide">
    <h2><?php esc_html_e('Google Places API Integration Guide', 'google-places-directory'); ?></h2>
    <p><?php _e('This comprehensive guide provides detailed information about integrating with the Google Places API, understanding usage limits, optimizing your implementation, and troubleshooting common issues.', 'google-places-directory'); ?></p>

    <div class="gpd-docs-toc">
        <h4><?php esc_html_e('Quick Navigation', 'google-places-directory'); ?></h4>
        <ul>
            <li><a href="#api-overview"><?php esc_html_e('API Overview', 'google-places-directory'); ?></a></li>
            <li><a href="#setting-up"><?php esc_html_e('Setting Up Your API Project', 'google-places-directory'); ?></a></li>
            <li><a href="#api-key"><?php esc_html_e('Creating & Securing API Keys', 'google-places-directory'); ?></a></li>
            <li><a href="#usage-limits"><?php esc_html_e('Understanding Usage Limits', 'google-places-directory'); ?></a></li>
            <li><a href="#optimization"><?php esc_html_e('Optimizing API Usage', 'google-places-directory'); ?></a></li>
            <li><a href="#billing"><?php esc_html_e('Billing & Cost Management', 'google-places-directory'); ?></a></li>
            <li><a href="#troubleshooting"><?php esc_html_e('Troubleshooting', 'google-places-directory'); ?></a></li>
        </ul>
    </div>

    <div id="api-overview" class="gpd-docs-section">
        <h3><?php esc_html_e('Google Places API Overview', 'google-places-directory'); ?></h3>
        <p><?php _e('The Google Places API is a service that returns information about places using HTTP requests. Places are defined within this API as establishments, geographic locations, or prominent points of interest.', 'google-places-directory'); ?></p>
        
        <h4><?php esc_html_e('Key Features', 'google-places-directory'); ?></h4>
        <ul>
            <li><?php _e('<strong>Place Search:</strong> Find places based on a text query or nearby location. Supports searching by text, phone number, or location coordinates.', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Place Details:</strong> Get detailed information about a specific place including address, phone number, website, opening hours, reviews, and more.', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Place Photos:</strong> Access high-quality photos associated with places. Photos are available in various sizes and can be used in your website.', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Place Autocomplete:</strong> Automatically complete place names as users type, improving the search experience.', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Query Autocomplete:</strong> Provide query predictions based on search terms and location context.', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Geocoding:</strong> Convert addresses to geographic coordinates (and vice versa).', 'google-places-directory'); ?></li>
        </ul>

        <h4><?php esc_html_e('API Versions & Updates', 'google-places-directory'); ?></h4>
        <p><?php _e('As of May 2025, Google Places Directory plugin uses the latest version of the Google Places API. This guide reflects the current implementation requirements and best practices.', 'google-places-directory'); ?></p>
    </div>

    <div id="setting-up" class="gpd-docs-section">
        <h3><?php esc_html_e('Setting Up Your API Project', 'google-places-directory'); ?></h3>
        <p><?php _e('Follow these detailed steps to set up your Google Cloud project and enable the required APIs:', 'google-places-directory'); ?></p>
        
        <ol>
            <li>
                <strong><?php _e('Create a Google Cloud Project', 'google-places-directory'); ?></strong>
                <ul>
                    <li><?php _e('Go to the <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a>', 'google-places-directory'); ?></li>
                    <li><?php _e('Click on the project dropdown and select "New Project"', 'google-places-directory'); ?></li>
                    <li><?php _e('Enter a name for your project and click "Create"', 'google-places-directory'); ?></li>
                    <li><?php _e('Once created, make sure it is selected as your active project', 'google-places-directory'); ?></li>
                </ul>
            </li>
            <li>
                <strong><?php _e('Enable Required APIs', 'google-places-directory'); ?></strong>
                <ul>
                    <li><?php _e('Select your project in the Google Cloud Console', 'google-places-directory'); ?></li>
                    <li><?php _e('Navigate to "APIs & Services" → "Library"', 'google-places-directory'); ?></li>
                    <li><?php _e('Search for and enable the following APIs:', 'google-places-directory'); ?>
                        <ul>
                            <li><?php _e('<strong>Places API</strong> - Required for business data', 'google-places-directory'); ?></li>
                            <li><?php _e('<strong>Maps JavaScript API</strong> - Required for maps display', 'google-places-directory'); ?></li>
                            <li><?php _e('<strong>Geocoding API</strong> - Required for location search', 'google-places-directory'); ?></li>
                            <li><?php _e('<strong>Places API (New)</strong> - Latest version with enhanced features', 'google-places-directory'); ?></li>
                        </ul>
                    </li>
                </ul>
            </li>
            <li>
                <strong><?php _e('Set Up Billing', 'google-places-directory'); ?></strong>
                <ul>
                    <li><?php _e('Google requires billing to be enabled for Places API usage', 'google-places-directory'); ?></li>
                    <li><?php _e('Navigate to "Billing" in the left navigation', 'google-places-directory'); ?></li>
                    <li><?php _e('Click "Link a billing account" and follow the steps', 'google-places-directory'); ?></li>
                    <li><?php _e('New accounts typically receive a free credit allowance to start', 'google-places-directory'); ?></li>
                </ul>
            </li>
        </ol>
    </div>
        
    <div id="api-key" class="gpd-docs-section">
        <h3><?php esc_html_e('Creating & Securing Your API Keys', 'google-places-directory'); ?></h3>
        <p><?php _e('API keys are necessary to authenticate your requests to Google Places API. Follow these steps to create and properly secure your API keys:', 'google-places-directory'); ?></p>
        
        <h4><?php esc_html_e('Creating API Keys', 'google-places-directory'); ?></h4>
        <ol>
            <li><?php _e('In Google Cloud Console, navigate to "APIs & Services" → "Credentials"', 'google-places-directory'); ?></li>
            <li><?php _e('Click "Create Credentials" and select "API key"', 'google-places-directory'); ?></li>
            <li><?php _e('Your new API key will be displayed. Copy this key for use in the plugin settings.', 'google-places-directory'); ?></li>
        </ol>
        
        <h4><?php esc_html_e('Securing Your API Keys (Critical)', 'google-places-directory'); ?></h4>
        <p><?php _e('<strong>Important:</strong> Always restrict your API keys to prevent unauthorized use and potential billing charges.', 'google-places-directory'); ?></p>
        
        <ol>
            <li>
                <strong><?php _e('HTTP Referrer Restrictions', 'google-places-directory'); ?></strong>
                <ul>
                    <li><?php _e('After creating your key, click "Edit API key"', 'google-places-directory'); ?></li>
                    <li><?php _e('Under "Application restrictions", select "HTTP referrers"', 'google-places-directory'); ?></li>
                    <li><?php _e('Add your website domain in the format: <code>*.yourdomain.com/*</code>', 'google-places-directory'); ?></li>
                    <li><?php _e('Add your development domains too if needed (e.g., <code>*.localhost/*</code> for local development)', 'google-places-directory'); ?></li>
                </ul>
            </li>
            <li>
                <strong><?php _e('API Restrictions', 'google-places-directory'); ?></strong>
                <ul>
                    <li><?php _e('Under "API restrictions", select "Restrict key"', 'google-places-directory'); ?></li>
                    <li><?php _e('Select all the APIs you enabled (Places API, Maps JavaScript API, etc.)', 'google-places-directory'); ?></li>
                    <li><?php _e('Click "Save" to apply restrictions', 'google-places-directory'); ?></li>
                </ul>
            </li>
        </ol>
        
        <h4><?php esc_html_e('Using API Keys in the Google Places Directory Plugin', 'google-places-directory'); ?></h4>
        <ol>
            <li><?php _e('In your WordPress admin, go to Businesses → Settings', 'google-places-directory'); ?></li>
            <li><?php _e('In the "API Settings" section, enter your API key', 'google-places-directory'); ?></li>
            <li><?php _e('Click "Test Connection" to verify your API key works', 'google-places-directory'); ?></li>
            <li><?php _e('Save your settings', 'google-places-directory'); ?></li>
        </ol>
    </div>
    
    <div id="usage-limits" class="gpd-docs-section">
        <h3><?php esc_html_e('Understanding API Usage Limits', 'google-places-directory'); ?></h3>
        <p><?php _e('Google Places API has usage limits and pricing that you should understand to avoid unexpected charges:', 'google-places-directory'); ?></p>
        
        <h4><?php esc_html_e('Default Quotas', 'google-places-directory'); ?></h4>
        <ul>
            <li><?php _e('<strong>Places API:</strong> $200 USD of free usage per month (approximately 11,000 basic requests)', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Maps JavaScript API:</strong> $200 USD of free usage per month', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Geocoding API:</strong> $200 USD of free usage per month', 'google-places-directory'); ?></li>
        </ul>
        
        <h4><?php esc_html_e('Request Cost Breakdown', 'google-places-directory'); ?></h4>
        <table class="gpd-docs-table">
            <thead>
                <tr>
                    <th><?php _e('API Request Type', 'google-places-directory'); ?></th>
                    <th><?php _e('Cost per Request', 'google-places-directory'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php _e('Basic Places API Request (Search)', 'google-places-directory'); ?></td>
                    <td>$0.017 USD</td>
                </tr>
                <tr>
                    <td><?php _e('Places Details Request', 'google-places-directory'); ?></td>
                    <td>$0.017 USD</td>
                </tr>
                <tr>
                    <td><?php _e('Places Photo Request', 'google-places-directory'); ?></td>
                    <td>$0.007 USD</td>
                </tr>
                <tr>
                    <td><?php _e('Geocoding Request', 'google-places-directory'); ?></td>
                    <td>$0.005 USD</td>
                </tr>
            </tbody>
        </table>
        
        <p><?php _e('<strong>Note:</strong> Prices may have changed since this documentation was created. Always check the <a href="https://cloud.google.com/maps-platform/pricing" target="_blank">official Google pricing page</a> for the most up-to-date information.', 'google-places-directory'); ?></p>
    </div>
    
    <div id="optimization" class="gpd-docs-section">
        <h3><?php esc_html_e('Optimizing API Usage', 'google-places-directory'); ?></h3>
        <p><?php _e('To minimize costs and improve performance, consider these optimization strategies:', 'google-places-directory'); ?></p>
        
        <h4><?php esc_html_e('Caching Strategies', 'google-places-directory'); ?></h4>
        <ul>
            <li><?php _e('<strong>Local Database Storage:</strong> Google Places Directory automatically stores imported business data in your WordPress database, reducing the need for repeated API calls.', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Photo Caching:</strong> Place photos are downloaded and stored locally, reducing API usage and improving load times.', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Search Results Caching:</strong> Search results are cached to reduce repetitive API calls.', 'google-places-directory'); ?></li>
        </ul>
        
        <h4><?php esc_html_e('Request Batching', 'google-places-directory'); ?></h4>
        <p><?php _e('When importing multiple businesses, the plugin batches requests to minimize API calls. You can configure batch sizes in the import settings.', 'google-places-directory'); ?></p>
        
        <h4><?php esc_html_e('Scheduling Updates', 'google-places-directory'); ?></h4>
        <p><?php _e('Use the built-in scheduling feature to update business information during off-peak hours.', 'google-places-directory'); ?></p>
        
        <h4><?php esc_html_e('Monitoring and Alerts', 'google-places-directory'); ?></h4>
        <p><?php _e('Configure email alerts for API usage in Settings → API Usage to receive notifications when approaching your thresholds.', 'google-places-directory'); ?></p>
    </div>
    
    <div id="billing" class="gpd-docs-section">
        <h3><?php esc_html_e('Billing & Cost Management', 'google-places-directory'); ?></h3>
        
        <h4><?php esc_html_e('Setting Up Billing', 'google-places-directory'); ?></h4>
        <ol>
            <li><?php _e('In Google Cloud Console, navigate to "Billing"', 'google-places-directory'); ?></li>
            <li><?php _e('Link a billing account to your project', 'google-places-directory'); ?></li>
            <li><?php _e('Set up billing alerts to prevent unexpected charges', 'google-places-directory'); ?></li>
        </ol>
        
        <h4><?php esc_html_e('Budget Alerts', 'google-places-directory'); ?></h4>
        <ol>
            <li><?php _e('Go to "Billing" → "Budgets & alerts"', 'google-places-directory'); ?></li>
            <li><?php _e('Create a budget with threshold notifications', 'google-places-directory'); ?></li>
            <li><?php _e('Set alerts at 50%, 90%, and 100% of your budget', 'google-places-directory'); ?></li>
        </ol>
        
        <h4><?php esc_html_e('Quota Management', 'google-places-directory'); ?></h4>
        <p><?php _e('You can request higher quotas through the Google Cloud Console if needed. This is useful for high-traffic websites.', 'google-places-directory'); ?></p>
    </div>
    
    <div id="troubleshooting" class="gpd-docs-section">
        <h3><?php esc_html_e('Troubleshooting', 'google-places-directory'); ?></h3>
        
        <h4><?php esc_html_e('Common API Errors', 'google-places-directory'); ?></h4>
        <table class="gpd-docs-table">
            <thead>
                <tr>
                    <th><?php _e('Error', 'google-places-directory'); ?></th>
                    <th><?php _e('Common Causes', 'google-places-directory'); ?></th>
                    <th><?php _e('Solution', 'google-places-directory'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>INVALID_REQUEST</code></td>
                    <td><?php _e('Malformed request parameters', 'google-places-directory'); ?></td>
                    <td><?php _e('Check your API request structure', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>OVER_QUERY_LIMIT</code></td>
                    <td><?php _e('Exceeded daily quota or rate limit', 'google-places-directory'); ?></td>
                    <td><?php _e('Implement caching, upgrade billing, or wait until quota resets', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>REQUEST_DENIED</code></td>
                    <td><?php _e('Invalid API key or key not authorized', 'google-places-directory'); ?></td>
                    <td><?php _e('Check API key restrictions and enabled services', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>UNKNOWN_ERROR</code></td>
                    <td><?php _e('Server-side error on Google\'s end', 'google-places-directory'); ?></td>
                    <td><?php _e('Retry the request after a brief delay', 'google-places-directory'); ?></td>
                </tr>
            </tbody>
        </table>
        
        <h4><?php esc_html_e('Plugin-Specific Troubleshooting', 'google-places-directory'); ?></h4>
        <ul>
            <li><?php _e('<strong>Import Failures:</strong> Check the API key settings and ensure all required APIs are enabled', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Missing Photos:</strong> Verify that the Places API (Photos) is enabled and your API key has the correct permissions', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Search Not Working:</strong> Test API connectivity in the plugin settings and check for JavaScript errors in your browser console', 'google-places-directory'); ?></li>
        </ul>
        
        <h4><?php esc_html_e('Debug Mode', 'google-places-directory'); ?></h4>
        <p><?php _e('Enable debug mode in Settings → Advanced → Debug Mode to log API requests and responses for troubleshooting.', 'google-places-directory'); ?></p>
    </div>

    <div class="gpd-docs-section">
        <h3><?php esc_html_e('Advanced API Integration', 'google-places-directory'); ?></h3>
        
        <h4><?php esc_html_e('Custom API Integration', 'google-places-directory'); ?></h4>
        <p><?php _e('Developers can access the plugin\'s API wrapper directly for custom integrations:', 'google-places-directory'); ?></p>
        
        <pre><code>// Get the API client
$api_client = GPD_API_Client::instance();

// Search for businesses
$results = $api_client->search_places([
    'query' => 'coffee shops',
    'location' => 'Seattle, WA',
    'radius' => 5000,
    'type' => 'cafe'
]);

// Get details for a specific place
$place_details = $api_client->get_place_details('place_id_here');

// Get photos for a business
$photos = $api_client->get_place_photos('place_id_here', 5);</code></pre>
        
        <h4><?php esc_html_e('API Usage Hooks', 'google-places-directory'); ?></h4>
        <p><?php _e('The plugin provides several action and filter hooks for monitoring and controlling API usage:', 'google-places-directory'); ?></p>
        
        <table class="gpd-docs-table">
            <thead>
                <tr>
                    <th><?php _e('Hook', 'google-places-directory'); ?></th>
                    <th><?php _e('Type', 'google-places-directory'); ?></th>
                    <th><?php _e('Description', 'google-places-directory'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>gpd_before_places_api_request</code></td>
                    <td><?php _e('Action', 'google-places-directory'); ?></td>
                    <td><?php _e('Fires before any Places API request is made', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>gpd_after_places_api_request</code></td>
                    <td><?php _e('Action', 'google-places-directory'); ?></td>
                    <td><?php _e('Fires after any Places API request completes', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>gpd_api_request_params</code></td>
                    <td><?php _e('Filter', 'google-places-directory'); ?></td>
                    <td><?php _e('Modify request parameters before an API call', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>gpd_api_response</code></td>
                    <td><?php _e('Filter', 'google-places-directory'); ?></td>
                    <td><?php _e('Modify API response data', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>gpd_track_api_usage</code></td>
                    <td><?php _e('Filter', 'google-places-directory'); ?></td>
                    <td><?php _e('Control whether to track specific API calls', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>gpd_daily_usage_threshold_exceeded</code></td>
                    <td><?php _e('Action', 'google-places-directory'); ?></td>
                    <td><?php _e('Fires when daily usage exceeds threshold', 'google-places-directory'); ?></td>
                </tr>
            </tbody>
        </table>
        
        <h4><?php esc_html_e('Example: Custom Usage Tracking', 'google-places-directory'); ?></h4>
        <pre><code>// Track API usage with custom logging
add_action('gpd_after_places_api_request', 'my_custom_api_tracking', 10, 3);
function my_custom_api_tracking($request_type, $response, $params) {
    // Log to custom system
    my_custom_logger([
        'time' => current_time('mysql'),
        'request_type' => $request_type,
        'success' => !is_wp_error($response),
        'params' => $params
    ]);
    
    // Send notification if error occurred
    if (is_wp_error($response)) {
        wp_mail(
            get_option('admin_email'),
            'API Error Detected',
            'Error in ' . $request_type . ' request: ' . $response->get_error_message()
        );
    }
}</code></pre>
    </div>
</div>
