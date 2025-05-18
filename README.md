# Google Places Import

**Google Places Import** is a WordPress plugin designed to help you search, review, and bulk import business listings from Google Places into your own custom post type. It is ideal for creating a directory of businesses (e.g., dive shops, restaurants, hotels) on your WordPress site, with taxonomy support for destinations and regions.

## Features

- **Search Google Places**: Use the WordPress admin to search for businesses using keywords and location.
- **Bulk Import**: Quickly import multiple businesses and their details into your custom post type (`business`).
- **Taxonomy Support**: Automatically organizes imported businesses by destination (city/locality) and region.
- **Duplicate Detection**: Flags already-imported businesses to prevent duplicates.
- **Customizable Radius & Limit**: Control the search area and number of results per request.
- **Admin UI**: Simple, modern admin user interface for searching and importing.
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

## Contributing

Pull requests and GitHub issues are welcome! Please open an issue for bugs or feature requests.

## License

This plugin is released under the MIT License.

## Credits

Developed by [TheRev](https://github.com/TheRev).

---

**Disclaimer:** This plugin is not affiliated with or endorsed by Google. Use of the Google Places API is subject to [Google's terms of service](https://developers.google.com/maps/terms).
