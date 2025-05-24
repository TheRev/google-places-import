# Google Places Directory Documentation

## Table of Contents
1. [Introduction](#introduction)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Usage](#usage)
5. [Shortcodes](#shortcodes)
6. [Custom Fields](#custom-fields)
7. [Developer API](#developer-api)
8. [Troubleshooting](#troubleshooting)
9. [FAQ](#faq)

## Introduction

Google Places Directory is a WordPress plugin that allows you to import business listings from Google Places API (New) and display them on your WordPress site. The plugin creates a custom post type called "Business" and taxonomies for "Destination" and "Region" to organize the imported businesses.

## Installation

### Requirements
- WordPress 5.5+
- PHP 7.2+
- A Google Cloud Platform account with Places API enabled
- A valid Google API key with Places API access

### Installation Steps
1. Download the plugin ZIP file
2. Go to WordPress Admin > Plugins > Add New
3. Click "Upload Plugin" and select the ZIP file
4. Click "Install Now"
5. Activate the plugin

## Configuration

### API Key Setup
1. Go to **Businesses > Settings** in the WordPress admin menu
2. Enter your Google Places API Key
3. Click "Save Settings"

To obtain a Google API key:
1. Go to the [Google Cloud Console](https://console.cloud.google.com)
2. Create a new project or select an existing one
3. Enable the Places API for your project
4. Create an API key with appropriate restrictions
5. Set up billing for your Google Cloud account

### Plugin Settings
- **Photo Import Limit**: Control how many photos to import per business
- **Additional settings available through hooks and filters**

## Usage

### Importing Businesses
1. Go to **Businesses > Import** in the WordPress admin menu
2. Enter search keywords and location
3. Review the search results
4. Select businesses to import
5. Click "Import Selected"

### Managing Businesses
- All imported businesses are available under **Businesses** in the WordPress admin
- Use the Destination and Region taxonomies to organize businesses
- Edit business details as you would regular WordPress posts

## Shortcodes

The plugin provides several shortcodes to display business listings on your site:

### Basic Business Listing
```
[gpd_businesses]
```

#### Parameters:
- `count`: Number of businesses to display (default: 10)
- `destination`: Filter by destination slug
- `region`: Filter by region slug
- `orderby`: Sort by field (title, date, rating)
- `order`: Sort direction (ASC or DESC)

### Business Map
```
[gpd_map]
```

#### Parameters:
- `width`: Map width (default: 100%)
- `height`: Map height (default: 400px)
- `zoom`: Initial zoom level (default: 12)
- `destination`: Filter by destination slug

### Business Photos
```
[gpd_photos]
```

#### Parameters:
- `business_id`: ID of the business to show photos for
- `count`: Number of photos to display (default: all)

## Custom Fields

The following custom fields are stored for each business:

| Field | Description |
|-------|-------------|
| `_gpd_place_id` | Google Place ID |
| `_gpd_display_name` | Business display name |
| `_gpd_address` | Formatted address |
| `_gpd_locality` | City/locality |
| `_gpd_latitude` | Geographic latitude |
| `_gpd_longitude` | Geographic longitude |
| `_gpd_types` | Business types/categories |
| `_gpd_rating` | Rating from Google Places |
| `_gpd_business_status` | Business status (OPERATIONAL, etc.) |
| `_gpd_maps_uri` | Google Maps URL |
| `_gpd_website` | Business website URL |
| `_gpd_phone_number` | Business phone number |
| `_gpd_api_version` | API version used for import |

## Developer API

### Hooks and Filters

#### Actions
- `gpd_before_business_import`: Fires before a business is imported
- `gpd_after_business_import`: Fires after a business is imported
- `gpd_import_error`: Fires when an import error occurs

#### Filters
- `gpd_business_post_args`: Filter the post arguments for business creation
- `gpd_business_meta_fields`: Filter the meta fields to be imported
- `gpd_parse_locality`: Filter how locality/city is extracted from address
- `gpd_get_photo_url`: Filter the photo URL before import

### Code Examples

```php
// Add a custom field to imported businesses
add_filter('gpd_business_meta_fields', function($meta_fields, $place_data) {
    $meta_fields['_my_custom_field'] = $place_data['someValue'] ?? '';
    return $meta_fields;
}, 10, 2);

// Do something after a business is imported
add_action('gpd_after_business_import', function($post_id, $place_data) {
    // Your code here
}, 10, 2);
```

## Troubleshooting

### Common Issues

#### API Key Issues
- Ensure your API key has Places API enabled
- Verify billing is set up in Google Cloud Console
- Check API key restrictions (if any) to ensure your server can use it

#### Import Issues
- Rate limiting: Google Places API has daily quota limits
- Missing data: Some businesses may have incomplete information
- Photos failing: Photo references expire quickly, retry the import

#### Display Issues
- Check theme compatibility
- Verify shortcode parameters
- Check for JavaScript conflicts

### Debug Mode

Add the following to your wp-config.php to enable debug logs:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## FAQ

**Q: How many businesses can I import at once?**
A: The plugin uses batch processing with a default batch size of 5 businesses to prevent API quota issues. This can be adjusted with a filter.

**Q: Does the plugin support Google Places API v1?**
A: Yes, the plugin has been updated to work with the latest Google Places API v1.

**Q: Can I customize the business display?**
A: Yes, you can customize the display using WordPress templates or CSS.

**Q: How are photos handled?**
A: Photos are downloaded from Google Places API and stored in your WordPress media library, associated with the business post.

**Q: Is the plugin compatible with page builders like Elementor or Divi?**
A: Yes, the plugin creates standard WordPress custom post types which are compatible with most page builders.

**Q: How can I show businesses on a map?**
A: Use the `[gpd_map]` shortcode to display businesses on an interactive map.

## Image Tools

The Image Tools section provides features to manage and fix business photos:

### Featured Image Fixer
This tool helps recover missing featured images for businesses:
- Automatically scans for businesses without featured images daily
- Multiple fallback mechanisms to find the correct image:
  1. Checks stored featured image ID
  2. Looks for attached photos
  3. Uses photo references from Google Places
- Batch processing capability for fixing multiple businesses
- Real-time progress tracking
- Automatic verification after fixing

Access the Image Tools at **Businesses > Image Tools** in the WordPress admin menu.

### Usage
1. Go to **Businesses > Image Tools**
2. Click "Fix Featured Images" to start the process
3. The tool will scan for businesses without featured images
4. Progress is shown in real-time
5. Results are logged for troubleshooting

### Automatic Daily Check
The plugin automatically:
- Checks for missing featured images once per day
- Processes a small batch (10 businesses) to maintain performance
- Only runs in the admin area to avoid front-end impact
- Stores the last run date to prevent duplicate processing

### Troubleshooting Image Tools

Common issues and solutions:

1. **Featured images not being fixed**
   - Verify media attachments exist for the business
   - Check WordPress media permissions
   - Look for errors in WordPress debug log
   - Make sure attachments are properly linked to businesses

2. **Progress bar stuck**
   - Try refreshing the page
   - Check browser console for JavaScript errors
   - Server timeout might be occurring for large batches

3. **Daily automatic fix not running**
   - Verify WordPress cron is working
   - Check server error logs
   - Make sure you're getting admin visits to trigger the check
   - Try manually running the fix tool

4. **Performance Issues**
   - Reduce batch size using the `gpd_featured_image_fixer_batch_size` filter
   - Schedule fixes during low-traffic periods
   - Consider disabling automatic daily checks if needed

### Related Hooks and Filters

For developers, several hooks and filters are available to customize the Image Tools behavior. See the Developer Guide section for details.
