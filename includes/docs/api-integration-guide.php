<?php
/**
 * Google Places Directory API Integration Guide
 * This file contains documentation for developers integrating with the Google Places API
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
    </div>    <div id="api-overview" class="gpd-docs-section">
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
        <p><?php _e('As of May 2025, Google Places Directory plugin uses the latest version of the Google Places API (v1), which offers better performance, more data fields, and improved reliability compared to previous versions. Google has been transitioning from the legacy Places API to this new version, and our plugin is fully compatible with the new API structure.', 'google-places-directory'); ?></p>
        
        <h4><?php esc_html_e('What You Can Do With The API', 'google-places-directory'); ?></h4>
        <p><?php _e('With the Google Places Directory plugin utilizing the Places API, you can:', 'google-places-directory'); ?></p>
        <ul>
            <li><?php _e('<strong>Import Business Data:</strong> Automatically import comprehensive business information directly from Google, including names, addresses, phone numbers, websites, business hours, and more.', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Display Maps:</strong> Show imported businesses on interactive maps with custom markers and info windows.', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Create Search Directories:</strong> Allow visitors to search and filter businesses by location, category, or custom attributes.', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Import Photos:</strong> Automatically import and display high-quality photos of businesses from Google.', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Display Business Details:</strong> Show comprehensive business information including hours, ratings, price levels, and more.', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Keep Data Updated:</strong> Schedule automatic updates to keep business information current with Google\'s database.', 'google-places-directory'); ?></li>
        </ul>

        <h4><?php esc_html_e('API Endpoints Used By This Plugin', 'google-places-directory'); ?></h4>
        <table class="gpd-docs-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('API Endpoint', 'google-places-directory'); ?></th>
                    <th><?php esc_html_e('Used For', 'google-places-directory'); ?></th>
                    <th><?php esc_html_e('Pricing Impact', 'google-places-directory'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>places/textsearch</code></td>
                    <td><?php esc_html_e('Finding businesses by name, type, or keyword', 'google-places-directory'); ?></td>
                    <td><?php esc_html_e('$5 per 1000 requests', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>places/details</code></td>
                    <td><?php esc_html_e('Getting detailed information about a specific business', 'google-places-directory'); ?></td>
                    <td><?php esc_html_e('$17 per 1000 requests', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>places/photos</code></td>
                    <td><?php esc_html_e('Retrieving business photos', 'google-places-directory'); ?></td>
                    <td><?php esc_html_e('$7 per 1000 requests', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>geocode</code></td>
                    <td><?php esc_html_e('Converting addresses to coordinates', 'google-places-directory'); ?></td>
                    <td><?php esc_html_e('$5 per 1000 requests', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><code>maps/embed</code></td>
                    <td><?php esc_html_e('Displaying interactive maps', 'google-places-directory'); ?></td>
                    <td><?php esc_html_e('Dynamic pricing - see Google Cloud Console', 'google-places-directory'); ?></td>
                </tr>
            </tbody>
        </table>
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
                        <ul>                            <li><?php _e('<strong>Places API</strong> - Required for business data', 'google-places-directory'); ?></li>
                            <li><?php _e('<strong>Geocoding API</strong> - Required for location search', 'google-places-directory'); ?></li>
                            <li><?php _e('<strong>Places API (New)</strong> - Latest version with enhanced features', 'google-places-directory'); ?></li>
                            <li><?php _e('Geocoding API (optional for address search)', 'google-places-directory'); ?></li>
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
            <li>
                <strong><?php _e('Create API Key', 'google-places-directory'); ?></strong>
                <ul>
                    <li><?php _e('Go to "APIs & Services" → "Credentials"', 'google-places-directory'); ?></li>
                    <li><?php _e('Click "Create Credentials" → "API Key"', 'google-places-directory'); ?></li>
                    <li><?php _e('Your new API key will be displayed', 'google-places-directory'); ?></li>
                </ul>
            </li>            <li>
                <strong><?php _e('Restrict Your API Key (Highly Recommended)', 'google-places-directory'); ?></strong>
                <ul>
                    <li><?php _e('Click "Edit" on your newly created API key', 'google-places-directory'); ?></li>
                    <li><?php _e('Under "Application restrictions", select "HTTP referrers"', 'google-places-directory'); ?></li>
                    <li><?php _e('Add your website domain(s) with wildcards as needed (e.g., <code>*.yourdomain.com/*</code>)', 'google-places-directory'); ?></li>
                    <li><?php _e('Under "API restrictions", select "Restrict key"', 'google-places-directory'); ?></li>
                    <li><?php _e('Select the APIs you enabled previously', 'google-places-directory'); ?></li>
                    <li><?php _e('Click "Save"', 'google-places-directory'); ?></li>
                </ul>
            </li>
            <li>
                <strong><?php _e('API Key Security Best Practices', 'google-places-directory'); ?></strong>
                <ul>
                    <li><?php _e('<strong>Never Share Your API Key:</strong> Treat your API key like a password. Never share it in public repositories, forums, or support tickets.', 'google-places-directory'); ?></li>
                    <li><?php _e('<strong>Set Usage Quotas:</strong> In Google Cloud Console, set daily quotas to prevent unexpected charges from excessive usage or potential API key abuse.', 'google-places-directory'); ?></li>
                    <li><?php _e('<strong>Monitor API Usage:</strong> Regularly check your Google Cloud Console dashboard to monitor API usage and detect any unusual activity.', 'google-places-directory'); ?></li>
                    <li><?php _e('<strong>Rotate API Keys:</strong> If you suspect your key has been compromised, create a new API key, update it in your WordPress settings, and then delete the old one.', 'google-places-directory'); ?></li>
                    <li><?php _e('<strong>Use Environment Variables:</strong> For advanced users, consider storing your API key in an environment variable rather than directly in the database.', 'google-places-directory'); ?></li>
                    <li><?php _e('<strong>Regular Audits:</strong> Periodically audit your API keys in Google Cloud Console and remove any that are no longer in use.', 'google-places-directory'); ?></li>
                </ul>
            </li>
        </ol>
        
        <div class="gpd-notice gpd-notice-warning">
            <p><?php _e('<strong>Security Warning:</strong> Unrestricted API keys can be used by anyone, potentially resulting in unexpected charges to your Google Cloud billing account. Always apply appropriate restrictions.', 'google-places-directory'); ?></p>
        </div>
    </div>

    <div class="gpd-docs-section">
        <h3><?php esc_html_e('Understanding API Usage & Billing', 'google-places-directory'); ?></h3>
        
        <p><?php _e('Google Places API operates on a pay-as-you-go billing model. Understanding the cost structure helps you optimize your implementation.', 'google-places-directory'); ?></p>
        
        <h4><?php esc_html_e('Price Structure (as of May 2025)', 'google-places-directory'); ?></h4>
        <table class="gpd-docs-table">
            <thead>
                <tr>
                    <th><?php _e('API Request Type', 'google-places-directory'); ?></th>
                    <th><?php _e('Cost', 'google-places-directory'); ?></th>
                    <th><?php _e('Free Monthly Quota', 'google-places-directory'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php _e('Basic Data (Place Search)', 'google-places-directory'); ?></td>
                    <td><?php _e('$5.00 per 1,000 requests', 'google-places-directory'); ?></td>
                    <td><?php _e('1,000 requests', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><?php _e('Detailed Data (Place Details)', 'google-places-directory'); ?></td>
                    <td><?php _e('$5.00 per 1,000 requests', 'google-places-directory'); ?></td>
                    <td><?php _e('1,000 requests', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><?php _e('Contact Data', 'google-places-directory'); ?></td>
                    <td><?php _e('$5.00 per 1,000 requests', 'google-places-directory'); ?></td>
                    <td><?php _e('1,000 requests', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><?php _e('Atmosphere Data', 'google-places-directory'); ?></td>
                    <td><?php _e('$7.00 per 1,000 requests', 'google-places-directory'); ?></td>
                    <td><?php _e('1,000 requests', 'google-places-directory'); ?></td>
                </tr>
                <tr>
                    <td><?php _e('Photos', 'google-places-directory'); ?></td>
                    <td><?php _e('$7.00 per 1,000 requests', 'google-places-directory'); ?></td>
                    <td><?php _e('1,000 requests', 'google-places-directory'); ?></td>
                </tr>
            </tbody>
        </table>
        
        <h4><?php esc_html_e('Usage Optimization Strategies', 'google-places-directory'); ?></h4>
        <ol>
            <li><?php _e('<strong>Cache API Responses:</strong> Store API responses locally to reduce repeated calls for the same data.', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Batch Requests:</strong> Import multiple businesses in a single session rather than one at a time.', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Limit Photo Imports:</strong> Each photo requires a separate API call. Limit the number of photos per business.', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Use Pre-fetching:</strong> Import all required data during the import process rather than making on-demand API calls during front-end rendering.', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Set Usage Alerts:</strong> Configure billing alerts in Google Cloud Console to notify you when costs reach certain thresholds.', 'google-places-directory'); ?></li>
        </ol>
        
        <div class="gpd-notice gpd-notice-info">
            <p><?php _e('<strong>Note:</strong> This plugin includes built-in API usage tracking and alerts to help you monitor your usage. Configure these in the Settings page.', 'google-places-directory'); ?></p>
        </div>
    </div>

    <div class="gpd-docs-section">
        <h3><?php esc_html_e('API Usage Tracking in Google Places Directory', 'google-places-directory'); ?></h3>
        
        <p><?php _e('The plugin includes robust API usage tracking capabilities to help you monitor and manage your API consumption.', 'google-places-directory'); ?></p>
        
        <h4><?php esc_html_e('Tracked Metrics', 'google-places-directory'); ?></h4>
        <ul>
            <li><?php _e('<strong>Text Search Requests:</strong> API calls for searching businesses by name, location, etc.', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Place Details Requests:</strong> API calls for retrieving detailed information about a specific place', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Photo Requests:</strong> API calls for retrieving photos', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Daily Totals:</strong> Aggregate daily usage across all request types', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Usage History:</strong> 30-day historical record of API usage', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Estimated Costs:</strong> Calculated based on current Google pricing', 'google-places-directory'); ?></li>
        </ul>
        
        <h4><?php esc_html_e('Alert Configuration', 'google-places-directory'); ?></h4>
        <p><?php _e('Configure usage alerts in the Settings page:', 'google-places-directory'); ?></p>
        <ul>
            <li><?php _e('<strong>Cost Threshold:</strong> Set a daily cost limit to trigger alerts', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Request Threshold:</strong> Set a daily request limit to trigger alerts', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Alert Email:</strong> Designate who receives alert notifications', 'google-places-directory'); ?></li>
            <li><?php _e('<strong>Alert Frequency:</strong> Control how often alerts are sent', 'google-places-directory'); ?></li>
        </ul>
        
        <h4><?php esc_html_e('Usage Dashboard', 'google-places-directory'); ?></h4>
        <p><?php _e('The plugin provides a visual dashboard of your API usage in the Settings page, including:', 'google-places-directory'); ?></p>
        <ul>
            <li><?php _e('Daily usage graph', 'google-places-directory'); ?></li>
            <li><?php _e('Request type breakdown', 'google-places-directory'); ?></li>
            <li><?php _e('Cost estimates', 'google-places-directory'); ?></li>
            <li><?php _e('Threshold indicators', 'google-places-directory'); ?></li>
        </ul>
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
