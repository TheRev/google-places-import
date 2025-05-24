<?php
/**
 * class-gpd-api-monitor.php
 *
 * Handles API quota monitoring, rate limiting, and usage statistics
 * for the Google Places API calls.
 * 
 * Enhanced in version 2.6.0 to implement per-minute rate limiting and better
 * caching to improve compliance with Google API usage policies.
 * 
 * @since 2.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class GPD_API_Monitor {
    private static $instance = null;
    
    // Default limits - these can be adjusted via filters
    private $daily_limit = 100000; // Default daily limit for Places API
    private $minute_limit = 60; // Default per-minute limit for Places API
    
    // Transient keys
    const DAILY_COUNT_TRANSIENT = 'gpd_api_daily_count';
    const MINUTE_COUNT_TRANSIENT = 'gpd_api_minute_count';
    const MINUTE_TIMESTAMP_TRANSIENT = 'gpd_api_minute_timestamps';
    
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->init_hooks();
            self::$instance->setup_limits();
        }
        return self::$instance;
    }
    
    /**
     * Set up the API limits, allowing them to be filtered
     */
    private function setup_limits() {
        $this->daily_limit = apply_filters('gpd_api_daily_limit', $this->daily_limit);
        $this->minute_limit = apply_filters('gpd_api_minute_limit', $this->minute_limit);
    }

    private function init_hooks() {
        add_action('admin_init', array($this, 'maybe_reset_daily_count'));
        add_action('gpd_before_api_request', array($this, 'track_api_request'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        add_action('admin_notices', array($this, 'display_quota_warnings'));
        add_filter('gpd_can_make_api_request', array($this, 'check_rate_limit'), 10, 2);
        
        // Add debug tools for admins
        add_action('admin_bar_menu', array($this, 'add_toolbar_items'), 100);
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
     * Check per-minute rate limits using a rolling window approach
     * This is more accurate than a fixed minute window
     * 
     * @return boolean True if under the limit, false if at or over limit
     */
    public function check_minute_rate_limit() {
        $timestamps = get_transient(self::MINUTE_TIMESTAMP_TRANSIENT);
        
        if (!is_array($timestamps)) {
            $timestamps = array();
        }
        
        // Current time
        $now = time();
        
        // Filter timestamps to only include those from the last 60 seconds
        $recent_timestamps = array_filter($timestamps, function($timestamp) use ($now) {
            return ($now - $timestamp) < 60;
        });
        
        // Count how many requests in the last minute
        $count = count($recent_timestamps);
        
        // Store the per-minute count for stats
        set_transient(self::MINUTE_COUNT_TRANSIENT, $count, 120);
        
        // Check if we're over the limit
        if ($count >= $this->minute_limit) {
            $this->log_rate_limit_hit('minute');
            return false;
        }
        
        return true;
    }
    
    /**
     * Add the current timestamp to the rolling window
     */
    private function update_minute_timestamps() {
        $timestamps = get_transient(self::MINUTE_TIMESTAMP_TRANSIENT);
        
        if (!is_array($timestamps)) {
            $timestamps = array();
        }
        
        // Add current timestamp
        $timestamps[] = time();
        
        // Keep only timestamps from the last 2 minutes (buffer for calculations)
        $now = time();
        $timestamps = array_filter($timestamps, function($timestamp) use ($now) {
            return ($now - $timestamp) < 120;
        });
        
        // Store for 2 minutes
        set_transient(self::MINUTE_TIMESTAMP_TRANSIENT, $timestamps, 120);
    }
    
    /**
     * Log a rate limit hit
     */
    private function log_rate_limit_hit($type = 'daily') {
        $message = '';
        
        switch ($type) {
            case 'daily':
                $message = sprintf(
                    'Google Places API daily rate limit reached (%d requests/day)', 
                    $this->daily_limit
                );
                break;
                
            case 'minute':
                $message = sprintf(
                    'Google Places API per-minute rate limit reached (%d requests/minute)', 
                    $this->minute_limit
                );
                break;
                
            default:
                $message = 'Google Places API rate limit reached';
        }
        
        // Log to WordPress error log
        error_log($message);
        
        // Store the message to display in admin notices
        update_option('gpd_rate_limit_message', array(
            'message' => $message,
            'time' => time(),
            'type' => $type
        ));
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
    
    /**
     * Add debug information to the admin toolbar
     */
    public function add_toolbar_items($admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $daily_count = $this->get_daily_request_count();
        $minute_count = get_transient(self::MINUTE_COUNT_TRANSIENT) ?: 0;
        
        $daily_percent = min(100, round(($daily_count / $this->daily_limit) * 100));
        $minute_percent = min(100, round(($minute_count / $this->minute_limit) * 100));
        
        // Set color based on usage
        $daily_color = $daily_percent > 80 ? '#ff4500' : ($daily_percent > 50 ? '#ffa500' : '#00aa00');
        $minute_color = $minute_percent > 80 ? '#ff4500' : ($minute_percent > 50 ? '#ffa500' : '#00aa00');
        
        $admin_bar->add_menu(array(
            'id'    => 'gpd-api-monitor',
            'title' => 'API: ' . $daily_count . '/' . $this->daily_limit,
            'href'  => admin_url('edit.php?post_type=business&page=gpd-settings'),
            'meta'  => array(
                'title' => 'Google Places API Usage',
            ),
        ));
        
        $admin_bar->add_menu(array(
            'id'    => 'gpd-api-daily',
            'parent' => 'gpd-api-monitor',
            'title' => sprintf('Daily: %d/%d (%d%%)', $daily_count, $this->daily_limit, $daily_percent),
            'meta'  => array(
                'html'  => '<span style="color: ' . $daily_color . '">●</span>',
            ),
        ));
        
        $admin_bar->add_menu(array(
            'id'    => 'gpd-api-minute',
            'parent' => 'gpd-api-monitor',
            'title' => sprintf('Per-Minute: %d/%d (%d%%)', $minute_count, $this->minute_limit, $minute_percent),
            'meta'  => array(
                'html'  => '<span style="color: ' . $minute_color . '">●</span>',
            ),
        ));
        
        $admin_bar->add_menu(array(
            'id'    => 'gpd-api-reset',
            'parent' => 'gpd-api-monitor',
            'title' => 'Reset Counters',
            'href'  => wp_nonce_url(admin_url('admin-ajax.php?action=gpd_reset_api_counters'), 'gpd_reset_api_counters'),
        ));
    }
}
