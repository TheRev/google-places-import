<?php
/**
 * class-gpd-featured-image-fixer.php
 *
 * A utility class to fix missing featured images on businesses
 * 
 * @since 2.6.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class GPD_Featured_Image_Fixer {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->init_hooks();
        }
        return self::$instance;
    }

    private function init_hooks() {
        // Run the fixer on init but with a very low priority
        add_action('init', array($this, 'maybe_fix_featured_images'), 999);
        
        // Also run after a business is updated
        add_action('save_post_business', array($this, 'check_business_featured_image'), 20, 3);
        
        // Add admin tools
        add_action('admin_menu', array($this, 'add_tools_page'), 99);
        
        // Handle ajax request to fix all featured images
        add_action('wp_ajax_gpd_fix_all_featured_images', array($this, 'ajax_fix_all_featured_images'));
    }
    
    /**
     * Maybe fix featured images - only runs once per day
     */
    public function maybe_fix_featured_images() {
        // Only run once per day
        $last_run = get_option('gpd_featured_image_fixer_last_run');
        $today = date('Y-m-d');
        
        if ($last_run === $today) {
            return;
        }
        
        // Only run for admin users to avoid performance impact on front-end
        if (!is_admin()) {
            return;
        }
        
        // Store the run date
        update_option('gpd_featured_image_fixer_last_run', $today);
        
        // Get a small batch of businesses without featured images
        $businesses_to_fix = $this->get_businesses_without_featured_images(10);
        
        if (!empty($businesses_to_fix)) {
            foreach ($businesses_to_fix as $business) {
                $this->fix_business_featured_image($business->ID);
            }
        }
    }
    
    /**
     * Check and fix featured image when a business is saved
     */
    public function check_business_featured_image($post_id, $post, $update) {
        // Skip if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Skip if this is a revision
        if (wp_is_post_revision($post_id)) {
            return;
        }
        
        // Check if the business has a featured image
        $has_thumbnail = has_post_thumbnail($post_id);
        
        if (!$has_thumbnail) {
            // Try to fix it
            $this->fix_business_featured_image($post_id);
        }
    }
    
    /**
     * Fix featured image for a specific business
     */
    public function fix_business_featured_image($post_id) {
        // Skip if it already has a featured image
        if (has_post_thumbnail($post_id)) {
            return true;
        }
        
        // First try our stored featured image ID
        $stored_featured_id = get_post_meta($post_id, '_gpd_featured_photo_id', true);
        
        if ($stored_featured_id && get_post($stored_featured_id)) {
            $result = set_post_thumbnail($post_id, $stored_featured_id);
            if ($result) {
                error_log('GPD Featured Image Fixer: Fixed featured image for business ' . $post_id . ' using stored ID ' . $stored_featured_id);
                return true;
            }
        }
        
        // Next, try to find any attached photos
        $attachments = get_posts(array(
            'post_type' => 'attachment',
            'posts_per_page' => 1,
            'post_parent' => $post_id,
            'orderby' => 'ID',
            'order' => 'ASC'
        ));
        
        if (!empty($attachments)) {
            $result = set_post_thumbnail($post_id, $attachments[0]->ID);
            if ($result) {
                error_log('GPD Featured Image Fixer: Fixed featured image for business ' . $post_id . ' using first attachment ' . $attachments[0]->ID);
                update_post_meta($post_id, '_gpd_featured_photo_id', $attachments[0]->ID);
                return true;
            }
        }
        
        // Try using a photo reference if we have one
        $photo_refs = get_post_meta($post_id, '_gpd_photo_references', true);
        
        if (!empty($photo_refs) && is_array($photo_refs)) {
            $first_ref = $photo_refs[0];
            
            $existing_photo = get_posts(array(
                'post_type' => 'attachment',
                'posts_per_page' => 1,
                'meta_key' => '_gpd_photo_reference',
                'meta_value' => $first_ref,
                'fields' => 'ids'
            ));
            
            if (!empty($existing_photo)) {
                $result = set_post_thumbnail($post_id, $existing_photo[0]);
                if ($result) {
                    error_log('GPD Featured Image Fixer: Fixed featured image for business ' . $post_id . ' using photo reference');
                    update_post_meta($post_id, '_gpd_featured_photo_id', $existing_photo[0]);
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get businesses without featured images
     */
    private function get_businesses_without_featured_images($limit = 50) {
        // This is a resource-intensive query, so we need to be careful
        global $wpdb;
        
        $query = $wpdb->prepare("
            SELECT p.ID 
            FROM $wpdb->posts p
            LEFT JOIN $wpdb->postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_thumbnail_id'
            WHERE p.post_type = 'business'
            AND p.post_status = 'publish'
            AND pm.meta_value IS NULL
            LIMIT %d
        ", $limit);
        
        $business_ids = $wpdb->get_col($query);
        
        if (empty($business_ids)) {
            return array();
        }
        
        return get_posts(array(
            'post_type' => 'business',
            'posts_per_page' => $limit,
            'post__in' => $business_ids
        ));
    }
    
    /**
     * Add tools page
     */
    public function add_tools_page() {
        add_submenu_page(
            'edit.php?post_type=business',
            __('Image Tools', 'google-places-directory'),
            __('Image Tools', 'google-places-directory'),
            'manage_options',
            'gpd-image-tools',
            array($this, 'render_tools_page')
        );
    }
    
    /**
     * Render tools page
     */
    public function render_tools_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Google Places Directory Image Tools', 'google-places-directory'); ?></h1>
            
            <div class="card">
                <h2><?php _e('Featured Image Fixer', 'google-places-directory'); ?></h2>
                <p><?php _e('This tool will check all businesses without a featured image and attempt to set one from their attached photos.', 'google-places-directory'); ?></p>
                
                <button id="gpd-fix-featured-images" class="button button-primary"><?php _e('Fix Featured Images', 'google-places-directory'); ?></button>
                
                <div id="gpd-fix-progress" style="margin-top: 15px; display: none;">
                    <div class="gpd-progress-bar">
                        <div class="gpd-progress-complete" style="width: 0%"></div>
                    </div>
                    <div class="gpd-progress-status">
                        <span id="gpd-fixed-count">0</span> <?php _e('fixed so far...', 'google-places-directory'); ?>
                    </div>
                </div>
            </div>
            
            <style>
                .gpd-progress-bar {
                    width: 100%;
                    height: 20px;
                    background-color: #f0f0f0;
                    border-radius: 3px;
                    margin-bottom: 10px;
                    overflow: hidden;
                }
                .gpd-progress-complete {
                    height: 100%;
                    background-color: #0073aa;
                    transition: width 0.3s ease;
                }
            </style>
            
            <script>
            jQuery(document).ready(function($) {
                $('#gpd-fix-featured-images').on('click', function() {
                    var $button = $(this);
                    var $progress = $('#gpd-fix-progress');
                    var fixedCount = 0;
                    
                    $button.prop('disabled', true);
                    $progress.show();
                    
                    // Function to process a batch
                    function processBatch() {
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'gpd_fix_all_featured_images',
                                nonce: '<?php echo wp_create_nonce('gpd_fix_featured_images'); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    fixedCount += response.data.fixed_count;
                                    $('#gpd-fixed-count').text(fixedCount);
                                    
                                    // If there are more to fix, process the next batch
                                    if (response.data.more_remaining) {
                                        processBatch();
                                    } else {
                                        $button.prop('disabled', false).text('<?php _e('Completed!', 'google-places-directory'); ?>');
                                    }
                                } else {
                                    $button.prop('disabled', false);
                                    alert('Error: ' + response.data.message);
                                }
                            },
                            error: function() {
                                $button.prop('disabled', false);
                                alert('<?php _e('An error occurred while processing.', 'google-places-directory'); ?>');
                            }
                        });
                    }
                    
                    // Start processing
                    processBatch();
                });
            });
            </script>
        </div>
        <?php
    }
    
    /**
     * Ajax handler to fix all featured images
     */
    public function ajax_fix_all_featured_images() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'google-places-directory')));
        }
        
        // Check nonce
        if (!check_ajax_referer('gpd_fix_featured_images', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'google-places-directory')));
        }
        
        // Get businesses without featured images (limit to 25 per batch)
        $businesses = $this->get_businesses_without_featured_images(25);
        
        $fixed_count = 0;
        
        // Process each business
        foreach ($businesses as $business) {
            $result = $this->fix_business_featured_image($business->ID);
            if ($result) {
                $fixed_count++;
            }
        }
        
        // Check if there are more to process
        $more_remaining = count($businesses) >= 25;
        
        wp_send_json_success(array(
            'fixed_count' => $fixed_count,
            'more_remaining' => $more_remaining
        ));
    }
}
