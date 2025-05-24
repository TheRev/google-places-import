# Google Places Directory

[![WordPress Compatible](https://img.shields.io/badge/WordPress-5.5%2B-blue.svg)](https://wordpress.org/)
[![PHP Version](https://img.shields.io/badge/PHP-7.2%2B-purple.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)
[![Version](https://img.shields.io/badge/Version-2.6.0-orange.svg)](https://github.com/TheRev/google-places-import)

**Google Places Directory** is a powerful WordPress plugin designed to help you search, review, and bulk import business listings from Google Places API v1 into your own custom post type. It is ideal for creating a directory of businesses (e.g., dive shops, restaurants, hotels) on your WordPress site, with taxonomy support for destinations and regions.

## Features

- **Search Google Places**: Use the WordPress admin to search for businesses using keywords and location.
- **Smart Bulk Import**: Efficiently import multiple businesses with batch processing and progress tracking.
- **Enhanced Error Handling**: Detailed error reporting and automatic retry for transient failures.
- **Rate Limit Protection**: Smart batching and delays to prevent API quota issues.
- **Taxonomy Support**: Automatically organizes imported businesses by destination (city/locality) and region.
- **Duplicate Detection**: Flags already-imported businesses to prevent duplicates.
- **Optimized Photo Import**: Parallel photo processing with configurable batch sizes.
- **Real-time Progress**: Live progress tracking for both business and photo imports.
- **Background Processing**: Long-running imports handled in the background.
- **Customizable Radius & Limit**: Control the search area and number of results per request.
- **Modern Admin UI**: Responsive interface with live updates and error reporting.
- **Safe & Secure**: Uses WordPress security best practices.

## Requirements

- WordPress 5.5+
- PHP 7.2+
- A Google Places API key (with Places API enabled)

## Installation

1. Upload the plugin files to the `/wp-content/plugins/google-places-import` directory, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Businesses > Settings** in the WordPress admin menu.
4. Enter your Google Places API key.

## Usage

1. In the WordPress admin, go to **Businesses > Business Import**.
2. Use the search form to find businesses in a specific location by keyword.
3. Review the results. Already-imported businesses are flagged.
4. Select businesses to import, or use the bulk import option.
5. Imported businesses are added as custom post type entries, organized by destination and region.

## Custom Post Type & Taxonomies

- **CPT:** `business`
- **Taxonomies:** `destination` (e.g., city/locality), `region` (e.g., country/area)

## Developer Notes

- The plugin is modular, with code for CPT/taxonomy registration, admin UI, and import logic separated in `includes/`.
- Taxonomies are registered with `'show_admin_column' => true` for easy management in the admin.
- The plugin uses AJAX and REST API for bulk imports and search.

## Documentation

Full documentation is available in the `docs` folder:

- [User Guide](docs/DOCUMENTATION.md): Complete user documentation
- [Developer API](docs/developer.php): Documentation for developers extending the plugin
- [Privacy Policy](docs/PRIVACY.md): Information about data handling and GDPR compliance

## API Rate Limiting

The plugin includes sophisticated rate limiting to prevent exceeding Google's API quotas:

- **Daily quota monitoring**: Tracks daily API usage against your Google Places API quota
- **Per-minute rate limiting**: Implements a rolling 60-second window for limiting request frequency
- **Automatic request throttling**: Spreads requests over time to prevent hitting rate limits
- **Admin monitoring tools**: Real-time API usage stats in the WordPress admin toolbar
- **Smart caching**: Reduces API calls by caching results efficiently

## Contributing

Pull requests and GitHub issues are welcome! Please open an issue for bugs or feature requests.

1. Fork the repository
2. Create your feature branch: `git checkout -b my-new-feature`
3. Commit your changes: `git commit -am 'Add some feature'`
4. Push to the branch: `git push origin my-new-feature`
5. Submit a pull request

## License

This plugin is released under the MIT License.

## Credits

Developed by [TheRev](https://github.com/TheRev).

---

**Disclaimer:** This plugin is not affiliated with or endorsed by Google. Use of the Google Places API is subject to [Google's terms of service](https://developers.google.com/maps/terms).
