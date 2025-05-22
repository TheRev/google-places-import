<?php
/**
 * class-gpd-seo.php
 * 
 * Handles SEO functionality for Google Places Directory
 * Including schema.org markup, meta tags, and sitemap integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class GPD_SEO {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->init_hooks();
        }
        return self::$instance;
    }

    private function init_hooks() {
        // Meta tags
        add_action('wp_head', array($this, 'add_meta_tags'), 1);
        add_action('wp_head', array($this, 'add_business_schema'), 2);

        // Social meta tags
        add_filter('wpseo_opengraph_type', array($this, 'opengraph_type'), 10, 2);
        add_filter('wpseo_opengraph_title', array($this, 'opengraph_title'), 10, 2);
        add_filter('wpseo_opengraph_desc', array($this, 'opengraph_description'), 10, 2);
        add_filter('wpseo_opengraph_url', array($this, 'opengraph_url'), 10, 2);
        add_filter('wpseo_opengraph_image', array($this, 'opengraph_image'), 10, 2);

        // Twitter card meta tags
        add_filter('wpseo_twitter_title', array($this, 'twitter_title'), 10, 2);
        add_filter('wpseo_twitter_description', array($this, 'twitter_description'), 10, 2);
        add_filter('wpseo_twitter_image', array($this, 'twitter_image'), 10, 2);

        // XML sitemap integration
        add_filter('wpseo_sitemap_index', array($this, 'add_businesses_to_sitemap'));
        add_filter('wpseo_sitemap_businesses_content', array($this, 'build_businesses_sitemap'));
        
        // Add SEO fields to business post type
        add_action('add_meta_boxes', array($this, 'add_seo_metabox'));
        add_action('save_post', array($this, 'save_seo_metabox'), 10, 2);
    }

    /**
     * Add SEO meta tags to head
     */
    public function add_meta_tags() {
        if (!is_singular('business')) {
            return;
        }

        $post_id = get_the_ID();
        $rating = get_post_meta($post_id, '_gpd_rating', true);
        $title = get_the_title();
        $description = get_post_meta($post_id, '_gpd_seo_description', true);
        if (empty($description)) {
            $description = wp_trim_words(get_the_content(), 20);
        }

        ?>
        <meta name="description" content="<?php echo esc_attr($description); ?>">
        <?php if ($rating): ?>
        <meta name="rating" content="<?php echo esc_attr($rating); ?>">
        <?php endif; ?>
        <?php
    }

    /**
     * Add schema.org structured data for business
     */
    public function add_business_schema() {
        if (!is_singular('business')) {
            return;
        }

        $post_id = get_the_ID();
        $business_data = array(
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => get_the_title(),
            'description' => get_post_meta($post_id, '_gpd_seo_description', true),
            'address' => array(
                '@type' => 'PostalAddress',
                'streetAddress' => get_post_meta($post_id, '_gpd_address', true),
                'addressLocality' => get_post_meta($post_id, '_gpd_locality', true)
            ),
            'geo' => array(
                '@type' => 'GeoCoordinates',
                'latitude' => get_post_meta($post_id, '_gpd_latitude', true),
                'longitude' => get_post_meta($post_id, '_gpd_longitude', true)
            )
        );

        // Add rating if available
        $rating = get_post_meta($post_id, '_gpd_rating', true);
        if (!empty($rating)) {
            $business_data['aggregateRating'] = array(
                '@type' => 'AggregateRating',
                'ratingValue' => $rating,
                'bestRating' => '5',
                'worstRating' => '1'
            );
        }

        // Add business hours if available
        $hours = get_post_meta($post_id, '_gpd_hours', true);
        if (!empty($hours) && is_array($hours)) {
            $business_data['openingHoursSpecification'] = array();
            foreach ($hours as $day => $times) {
                $business_data['openingHoursSpecification'][] = array(
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => $day,
                    'opens' => $times['open'],
                    'closes' => $times['close']
                );
            }
        }

        // Add images
        $photos = get_attached_media('image', $post_id);
        if (!empty($photos)) {
            $business_data['image'] = array();
            foreach ($photos as $photo) {
                $business_data['image'][] = wp_get_attachment_url($photo->ID);
            }
        }

        // Add URL and phone if available
        $website = get_post_meta($post_id, '_gpd_website', true);
        if (!empty($website)) {
            $business_data['url'] = esc_url($website);
        }

        $phone = get_post_meta($post_id, '_gpd_phone_number', true);
        if (!empty($phone)) {
            $business_data['telephone'] = $phone;
        }

        // Print schema.org data
        ?>
        <script type="application/ld+json">
        <?php echo json_encode($business_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?>
        </script>
        <?php
    }

    /**
     * Set OpenGraph type for business pages
     */
    public function opengraph_type($type) {
        if (is_singular('business')) {
            return 'business.business';
        }
        return $type;
    }

    /**
     * Set OpenGraph title
     */
    public function opengraph_title($title) {
        if (is_singular('business')) {
            $post_id = get_the_ID();
            $og_title = get_post_meta($post_id, '_gpd_seo_title', true);
            if (!empty($og_title)) {
                return $og_title;
            }
        }
        return $title;
    }

    /**
     * Set OpenGraph description
     */
    public function opengraph_description($description) {
        if (is_singular('business')) {
            $post_id = get_the_ID();
            $og_desc = get_post_meta($post_id, '_gpd_seo_description', true);
            if (!empty($og_desc)) {
                return $og_desc;
            }
        }
        return $description;
    }

    /**
     * Set OpenGraph URL
     */
    public function opengraph_url($url) {
        if (is_singular('business')) {
            return get_permalink();
        }
        return $url;
    }

    /**
     * Set OpenGraph image
     */
    public function opengraph_image($image) {
        if (is_singular('business') && has_post_thumbnail()) {
            return get_the_post_thumbnail_url(null, 'large');
        }
        return $image;
    }

    /**
     * Set Twitter card title
     */
    public function twitter_title($title) {
        if (is_singular('business')) {
            $post_id = get_the_ID();
            $tw_title = get_post_meta($post_id, '_gpd_seo_title', true);
            if (!empty($tw_title)) {
                return $tw_title;
            }
        }
        return $title;
    }

    /**
     * Set Twitter card description
     */
    public function twitter_description($description) {
        if (is_singular('business')) {
            $post_id = get_the_ID();
            $tw_desc = get_post_meta($post_id, '_gpd_seo_description', true);
            if (!empty($tw_desc)) {
                return $tw_desc;
            }
        }
        return $description;
    }

    /**
     * Set Twitter card image
     */
    public function twitter_image($image) {
        if (is_singular('business') && has_post_thumbnail()) {
            return get_the_post_thumbnail_url(null, 'large');
        }
        return $image;
    }

    /**
     * Add businesses to XML sitemap
     */
    public function add_businesses_to_sitemap($sitemap) {
        $sitemap[] = array(
            'loc' => home_url('/sitemap-businesses.xml'),
            'lastmod' => $this->get_last_business_modified()
        );
        return $sitemap;
    }

    /**
     * Build businesses sitemap content
     */
    public function build_businesses_sitemap() {
        $output = '';
        $businesses = get_posts(array(
            'post_type' => 'business',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));

        foreach ($businesses as $business) {
            $last_mod = get_the_modified_date('c', $business);
            $images = get_attached_media('image', $business->ID);
            
            $output .= '<url>' . "\n";
            $output .= "\t" . '<loc>' . get_permalink($business) . '</loc>' . "\n";
            $output .= "\t" . '<lastmod>' . $last_mod . '</lastmod>' . "\n";
            $output .= "\t" . '<changefreq>weekly</changefreq>' . "\n";
            $output .= "\t" . '<priority>0.8</priority>' . "\n";

            // Add image entries if available
            if (!empty($images)) {
                foreach ($images as $image) {
                    $output .= "\t" . '<image:image>' . "\n";
                    $output .= "\t\t" . '<image:loc>' . wp_get_attachment_url($image->ID) . '</image:loc>' . "\n";
                    $output .= "\t\t" . '<image:title>' . esc_html(get_the_title($image->ID)) . '</image:title>' . "\n";
                    $output .= "\t" . '</image:image>' . "\n";
                }
            }

            $output .= '</url>' . "\n";
        }

        return $output;
    }

    /**
     * Get last modified date of any business
     */
    private function get_last_business_modified() {
        $latest = get_posts(array(
            'post_type' => 'business',
            'posts_per_page' => 1,
            'orderby' => 'modified',
            'order' => 'DESC'
        ));

        if (!empty($latest)) {
            return get_the_modified_date('c', $latest[0]);
        }

        return date('c');
    }

    /**
     * Add SEO metabox to business post type
     */
    public function add_seo_metabox() {
        add_meta_box(
            'gpd_seo_metabox',
            __('Business SEO Settings', 'google-places-directory'),
            array($this, 'render_seo_metabox'),
            'business',
            'normal',
            'high'
        );
    }

    /**
     * Render SEO metabox content
     */
    public function render_seo_metabox($post) {
        // Add nonce for security
        wp_nonce_field('gpd_seo_metabox', 'gpd_seo_nonce');

        // Get saved values
        $seo_title = get_post_meta($post->ID, '_gpd_seo_title', true);
        $seo_description = get_post_meta($post->ID, '_gpd_seo_description', true);
        $business_type = get_post_meta($post->ID, '_gpd_business_type', true);

        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="gpd_seo_title"><?php _e('SEO Title', 'google-places-directory'); ?></label>
                </th>
                <td>
                    <input type="text" id="gpd_seo_title" name="gpd_seo_title" value="<?php echo esc_attr($seo_title); ?>" class="large-text">
                    <p class="description"><?php _e('Custom title for search engines and social media. Leave blank to use post title.', 'google-places-directory'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="gpd_seo_description"><?php _e('SEO Description', 'google-places-directory'); ?></label>
                </th>
                <td>
                    <textarea id="gpd_seo_description" name="gpd_seo_description" rows="3" class="large-text"><?php echo esc_textarea($seo_description); ?></textarea>
                    <p class="description"><?php _e('Custom description for search engines and social media. Leave blank to use post excerpt.', 'google-places-directory'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="gpd_business_type"><?php _e('Business Type', 'google-places-directory'); ?></label>
                </th>
                <td>
                    <select id="gpd_business_type" name="gpd_business_type">
                        <option value=""><?php _e('-- Select Type --', 'google-places-directory'); ?></option>
                        <option value="LocalBusiness" <?php selected($business_type, 'LocalBusiness'); ?>><?php _e('Local Business', 'google-places-directory'); ?></option>
                        <option value="Restaurant" <?php selected($business_type, 'Restaurant'); ?>><?php _e('Restaurant', 'google-places-directory'); ?></option>
                        <option value="Hotel" <?php selected($business_type, 'Hotel'); ?>><?php _e('Hotel', 'google-places-directory'); ?></option>
                        <option value="Store" <?php selected($business_type, 'Store'); ?>><?php _e('Store', 'google-places-directory'); ?></option>
                        <option value="Service" <?php selected($business_type, 'Service'); ?>><?php _e('Service Business', 'google-places-directory'); ?></option>
                    </select>
                    <p class="description"><?php _e('Schema.org business type for better search engine understanding.', 'google-places-directory'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save SEO metabox data
     */
    public function save_seo_metabox($post_id, $post) {
        // Verify nonce
        if (!isset($_POST['gpd_seo_nonce']) || !wp_verify_nonce($_POST['gpd_seo_nonce'], 'gpd_seo_metabox')) {
            return;
        }

        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Don't save during autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Make sure we're saving a business post type
        if ($post->post_type !== 'business') {
            return;
        }

        // Save SEO fields
        update_post_meta($post_id, '_gpd_seo_title', sanitize_text_field($_POST['gpd_seo_title'] ?? ''));
        update_post_meta($post_id, '_gpd_seo_description', sanitize_textarea_field($_POST['gpd_seo_description'] ?? ''));
        update_post_meta($post_id, '_gpd_business_type', sanitize_text_field($_POST['gpd_business_type'] ?? ''));
    }
}
