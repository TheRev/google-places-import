<?php
/**
 * class-gpd-background-process.php
 *
 * Handles background processing for photo imports and optimizations.
 * 
 * @since 2.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class GPD_Background_Process {
    private static $instance = null;
    private $batch_size = 10;
    private $processing = false;
    
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->init_hooks();
        }
        return self::$instance;
    }

    private function init_hooks() {
        add_action('init', array($this, 'init_background_processing'));
        add_action('wp_ajax_gpd_get_process_status', array($this, 'ajax_get_process_status'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('gpd_background_processing', array($this, 'process_batch'));
    }

    /**
     * Initialize background processing
     */
    public function init_background_processing() {
        if (!wp_next_scheduled('gpd_background_processing')) {
            wp_schedule_event(time(), '1min', 'gpd_background_processing');
        }
    }

    /**
     * Add a task to the queue
     */
    public function add_to_queue($task) {
        $queue = get_option('gpd_process_queue', array());
        $queue[] = $task;
        update_option('gpd_process_queue', $queue);
    }

    /**
     * Process a batch of items
     */
    public function process_batch() {
        if ($this->processing) {
            return;
        }

        $this->processing = true;
        
        $queue = get_option('gpd_process_queue', array());
        $processed = array();
        $count = 0;

        foreach ($queue as $key => $task) {
            if ($count >= $this->batch_size) {
                break;
            }

            $this->process_task($task);
            $processed[] = $key;
            $count++;
        }

        // Remove processed items
        foreach ($processed as $key) {
            unset($queue[$key]);
        }

        // Reset array keys
        $queue = array_values($queue);
        update_option('gpd_process_queue', $queue);
        
        $this->processing = false;
    }

    /**
     * Process a single task
     */
    private function process_task($task) {
        switch ($task['type']) {
            case 'photo_import':
                $this->process_photo_import($task);
                break;
            case 'photo_optimization':
                $this->process_photo_optimization($task);
                break;
        }
    }

    /**
     * Process photo import task
     */
    private function process_photo_import($task) {
        $place_id = $task['place_id'];
        $post_id = $task['post_id'];
        
        // Use the photo manager to handle the actual import
        GPD_Photo_Manager::instance()->refresh_photos($post_id, $place_id);
        
        // Add optimization task to queue if enabled
        if (get_option('gpd_optimize_photos', true)) {
            $this->add_to_queue(array(
                'type' => 'photo_optimization',
                'post_id' => $post_id
            ));
        }
    }

    /**
     * Process photo optimization task
     */
    private function process_photo_optimization($task) {
        $post_id = $task['post_id'];
        
        // Get all photos for this business
        $photos = get_post_meta($post_id, '_gpd_photo_references', true);
        if (!is_array($photos)) {
            return;
        }

        foreach ($photos as $photo_ref) {
            // Find the attachment
            $args = array(
                'post_type' => 'attachment',
                'posts_per_page' => 1,
                'meta_key' => '_gpd_photo_reference',
                'meta_value' => $photo_ref,
            );
            
            $query = new WP_Query($args);
            if ($query->have_posts()) {
                $attachment_id = $query->posts[0]->ID;
                $this->optimize_image($attachment_id);
            }
        }
    }

    /**
     * Optimize an image
     */
    private function optimize_image($attachment_id) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $file = get_attached_file($attachment_id);
        if (!$file) {
            return;
        }

        // Basic optimization - resize if too large
        list($width, $height) = getimagesize($file);
        $max_size = 1500; // Max width/height

        if ($width > $max_size || $height > $max_size) {
            $editor = wp_get_image_editor($file);
            if (!is_wp_error($editor)) {
                $editor->resize($max_size, $max_size, false);
                $editor->save($file);
                
                // Update attachment metadata
                $metadata = wp_generate_attachment_metadata($attachment_id, $file);
                wp_update_attachment_metadata($attachment_id, $metadata);
            }
        }
    }

    /**
     * Get current process status
     */
    public function ajax_get_process_status() {
        check_ajax_referer('gpd_background_process', 'nonce');
        
        $queue = get_option('gpd_process_queue', array());
        $total = get_option('gpd_process_total', 0);
        $remaining = count($queue);
        $processed = $total - $remaining;
        
        wp_send_json_success(array(
            'total' => $total,
            'processed' => $processed,
            'remaining' => $remaining,
            'percent' => $total > 0 ? ($processed / $total) * 100 : 100
        ));
    }

    /**
     * Enqueue required scripts
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'gpd-photo-management') === false) {
            return;
        }

        wp_enqueue_script(
            'gpd-background-process',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/background-process.js',
            array('jquery'),
            GPD_VERSION,
            true
        );

        wp_localize_script('gpd-background-process', 'gpdProcess', array(
            'nonce' => wp_create_nonce('gpd_background_process'),
            'ajaxurl' => admin_url('admin-ajax.php')
        ));
    }
}
