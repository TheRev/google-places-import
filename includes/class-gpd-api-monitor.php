<?php
/**
 * class-gpd-api-monitor.php
 *
 * Handles API quota monitoring, rate limiting, and usage statistics
 * for the Google Places API calls.
 * 
 * @since 2.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class GPD_API_Monitor {
    private static $instance = null;
    private $daily_limit = 100000; // Default daily limit for Places API
    
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->init_hooks();
        }
        return self::$instance;
    }

    private function init_hooks() {
        add_action('admin_init', array($this, 'maybe_reset_daily_count'));
        add_action('gpd_before_api_request', array($this, 'track_api_request'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        add_action('admin_notices', array($this, 'display_quota_warnings'));
        add_filter('gpd_can_make_api_request', array($this, 'check_rate_limit'), 10, 2);
    }

    /**
     * Track an API request
     */
    public function track_api_request($request_type = 'search') {
        $today = date('Y-m-d');
        $counts = get_option('gpd_api_daily_counts', array());
        
        if (!isset($counts[$today])) {
            $counts[$today] = array(
                'search' => 0,
                'details' => 0,
                'photos' => 0,
                'total' => 0
            );
        }
        
        $counts[$today][$request_type]++;
        $counts[$today]['total']++;
        
        update_option('gpd_api_daily_counts', $counts);
    }

    /**
     * Reset daily count if needed
     */
    public function maybe_reset_daily_count() {
        $last_reset = get_option('gpd_api_last_reset');
        $today = date('Y-m-d');
        
        if ($last_reset !== $today) {
            update_option('gpd_api_last_reset', $today);
            delete_option('gpd_api_daily_counts');
        }
    }

    /**
     * Check if we can make an API request based on rate limits
     */
    public function check_rate_limit($can_request, $request_type) {
        $today = date('Y-m-d');
        $counts = get_option('gpd_api_daily_counts', array());
        
        if (!isset($counts[$today])) {
            return true;
        }

        // Check if we're approaching the daily limit
        if ($counts[$today]['total'] >= $this->daily_limit) {
            return false;
        }

        // Basic rate limiting - max 10 requests per second
        $last_request = get_option('gpd_last_api_request');
        $now = microtime(true);
        
        if ($last_request && ($now - $last_request) < 0.1) {
            usleep(100000); // Sleep for 100ms
        }
        
        update_option('gpd_last_api_request', $now);
        
        return true;
    }

    /**
     * Add dashboard widget for API usage
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'gpd_api_usage',
            __('Google Places API Usage', 'google-places-directory'),
            array($this, 'render_dashboard_widget')
        );
    }

    /**
     * Render the dashboard widget
     */
    public function render_dashboard_widget() {
        $today = date('Y-m-d');
        $counts = get_option('gpd_api_daily_counts', array());
        $today_counts = isset($counts[$today]) ? $counts[$today] : array(
            'search' => 0,
            'details' => 0,
            'photos' => 0,
            'total' => 0
        );
        
        ?>
        <div class="gpd-api-usage-stats">
            <p>
                <strong><?php _e('Today\'s API Usage:', 'google-places-directory'); ?></strong>
            </p>
            <ul>
                <li><?php printf(__('Search Requests: %d', 'google-places-directory'), $today_counts['search']); ?></li>
                <li><?php printf(__('Place Details: %d', 'google-places-directory'), $today_counts['details']); ?></li>
                <li><?php printf(__('Photo Requests: %d', 'google-places-directory'), $today_counts['photos']); ?></li>
                <li><strong><?php printf(__('Total Requests: %d/%d', 'google-places-directory'), $today_counts['total'], $this->daily_limit); ?></strong></li>
            </ul>
            <div class="gpd-usage-bar">
                <div class="gpd-usage-progress" style="width: <?php echo min(100, ($today_counts['total'] / $this->daily_limit) * 100); ?>%;"></div>
            </div>
            <p class="description">
                <?php _e('API quota resets daily at midnight UTC', 'google-places-directory'); ?>
            </p>
        </div>
        <style>
            .gpd-usage-bar {
                height: 20px;
                background: #f0f0f0;
                border-radius: 3px;
                margin: 10px 0;
                overflow: hidden;
            }
            .gpd-usage-progress {
                height: 100%;
                background: #0073aa;
                transition: width 0.3s ease;
            }
            .gpd-api-usage-stats ul {
                margin: 0.5em 0 1em 1.5em;
                list-style: disc;
            }
        </style>
        <?php
    }

    /**
     * Display quota warning notices
     */
    public function display_quota_warnings() {
        $today = date('Y-m-d');
        $counts = get_option('gpd_api_daily_counts', array());
        
        if (!isset($counts[$today])) {
            return;
        }

        $usage_percent = ($counts[$today]['total'] / $this->daily_limit) * 100;

        if ($usage_percent >= 90) {
            echo '<div class="notice notice-error"><p>';
            echo sprintf(
                __('Warning: Google Places API quota is at %d%% (%d/%d requests). The quota will reset at midnight UTC.', 'google-places-directory'),
                $usage_percent,
                $counts[$today]['total'],
                $this->daily_limit
            );
            echo '</p></div>';
        } elseif ($usage_percent >= 75) {
            echo '<div class="notice notice-warning"><p>';
            echo sprintf(
                __('Notice: Google Places API quota is at %d%% (%d/%d requests).', 'google-places-directory'),
                $usage_percent,
                $counts[$today]['total'],
                $this->daily_limit
            );
            echo '</p></div>';
        }
    }
}
