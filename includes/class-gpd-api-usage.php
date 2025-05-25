<?php
/**
 * Class GPD_API_Usage
 * 
 * Tracks and manages Google Places API usage to help prevent exceeding quotas
 */

if (!defined('ABSPATH')) {
    exit;
}

class GPD_API_Usage {
    private static $instance = null;
    
    // Usage tracking
    private $daily_requests = array(
        'text_search' => 0,
        'place_details' => 0,
        'photos' => 0
    );

    // Default thresholds (can be overridden in settings)
    private $default_thresholds = array(
        'daily_cost' => 50, // Alert when daily cost exceeds $50
        'request_limit' => 1000 // Alert when total daily requests exceed 1000
    );

    // Usage history - stores last 30 days
    private $usage_history = array();

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->init_hooks();
        $this->load_daily_counts();
    }

    private function init_hooks() {
        // Reset counts at midnight
        add_action('init', array($this, 'maybe_reset_daily_counts'));
        
        // Track API requests
        add_action('gpd_before_places_api_request', array($this, 'track_places_request'));
        add_action('gpd_before_photo_api_request', array($this, 'track_photo_request'));
        
        // Add admin bar menu
        add_action('admin_bar_menu', array($this, 'add_usage_menu'), 100);

        // Add settings section
        add_action('gpd_after_api_settings', array($this, 'add_usage_settings'));
        
        // Schedule reports and cleanup
        if (!wp_next_scheduled('gpd_daily_usage_report')) {
            wp_schedule_event(strtotime('tomorrow 00:01'), 'daily', 'gpd_daily_usage_report');
        }
        if (!wp_next_scheduled('gpd_weekly_usage_report')) {
            wp_schedule_event(strtotime('next monday 00:01'), 'weekly', 'gpd_weekly_usage_report');
        }
        
        add_action('gpd_daily_usage_report', array($this, 'send_daily_report'));
        add_action('gpd_weekly_usage_report', array($this, 'send_weekly_report'));
    }

    private function load_daily_counts() {
        $counts = get_option('gpd_daily_api_usage', array());
        if (!empty($counts) && isset($counts['date']) && $counts['date'] === date('Y-m-d')) {
            $this->daily_requests = $counts;
        }
    }

    public function maybe_reset_daily_counts() {
        $counts = get_option('gpd_daily_api_usage', array());
        if (empty($counts) || !isset($counts['date']) || $counts['date'] !== date('Y-m-d')) {
            $this->daily_requests = array(
                'date' => date('Y-m-d'),
                'text_search' => 0,
                'place_details' => 0,
                'photos' => 0
            );
            update_option('gpd_daily_api_usage', $this->daily_requests);
        }
    }

    private function check_thresholds() {
        $total_requests = array_sum($this->daily_requests);
        $total_cost = $this->calculate_total_cost();
        $thresholds = $this->get_thresholds();
        $alerts = array();

        if ($total_requests >= $thresholds['request_limit']) {
            $alerts[] = sprintf(
                __('API requests (%d) have exceeded the daily limit (%d)', 'google-places-directory'),
                $total_requests,
                $thresholds['request_limit']
            );
        }

        if ($total_cost >= $thresholds['daily_cost']) {
            $alerts[] = sprintf(
                __('API costs ($%.2f) have exceeded the daily budget ($%.2f)', 'google-places-directory'),
                $total_cost,
                $thresholds['daily_cost']
            );
        }

        if (!empty($alerts) && $this->should_send_alert()) {
            $this->send_alert_email($alerts);
        }
    }

    private function should_send_alert() {
        $last_alert = get_option('gpd_last_alert_time', 0);
        $alert_interval = 4 * HOUR_IN_SECONDS; // Don't send alerts more than once every 4 hours
        return (time() - $last_alert) >= $alert_interval;
    }

    private function send_alert_email($alerts) {
        $email_enabled = get_option('gpd_enable_email_alerts', false);
        if (!$email_enabled) {
            return;
        }

        $to = get_option('gpd_alert_email', get_option('admin_email'));
        $subject = __('Google Places Directory - API Usage Alert', 'google-places-directory');
        
        $message = __('The following API usage thresholds have been exceeded:', 'google-places-directory') . "\n\n";
        $message .= implode("\n", $alerts) . "\n\n";
        $message .= sprintf(
            __('View detailed usage statistics: %s', 'google-places-directory'),
            admin_url('edit.php?post_type=business&page=gpd-settings')
        );

        wp_mail($to, $subject, $message);
        update_option('gpd_last_alert_time', time());
    }

    private function send_daily_report() {
        if (!get_option('gpd_enable_daily_report', false)) {
            return;
        }

        $to = get_option('gpd_report_email', get_option('admin_email'));
        $subject = __('Google Places Directory - Daily API Usage Report', 'google-places-directory');
        $message = $this->generate_usage_report('daily');
        
        wp_mail($to, $subject, $message);
    }

    private function send_weekly_report() {
        if (!get_option('gpd_enable_weekly_report', false)) {
            return;
        }

        $to = get_option('gpd_report_email', get_option('admin_email'));
        $subject = __('Google Places Directory - Weekly API Usage Report', 'google-places-directory');
        $message = $this->generate_usage_report('weekly');
        
        wp_mail($to, $subject, $message);
    }

    private function generate_usage_report($type = 'daily') {
        $usage = $this->get_daily_usage();
        $total_cost = $this->calculate_total_cost();
        
        $message = sprintf(
            __('%s API Usage Report for %s', 'google-places-directory'),
            ucfirst($type),
            get_bloginfo('name')
        ) . "\n\n";
        
        $message .= "----------------------------------------\n";
        $message .= sprintf(__('Text Search Requests: %d', 'google-places-directory'), $usage['text_search']) . "\n";
        $message .= sprintf(__('Place Details Requests: %d', 'google-places-directory'), $usage['place_details']) . "\n";
        $message .= sprintf(__('Photo Requests: %d', 'google-places-directory'), $usage['photos']) . "\n";
        $message .= sprintf(__('Total Cost: $%.2f', 'google-places-directory'), $total_cost) . "\n";
        $message .= "----------------------------------------\n\n";
        
        // Add trend data for weekly reports
        if ($type === 'weekly') {
            $history = $this->get_usage_history(7);
            $message .= __('7-Day Usage Trend:', 'google-places-directory') . "\n";
            foreach ($history as $date => $data) {
                $message .= sprintf(
                    "%s: %d requests, $%.2f\n",
                    $date,
                    $data['total_requests'],
                    $data['total_cost']
                );
            }
        }
        
        return $message;
    }

    public function add_usage_menu($wp_admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Calculate costs
        $text_search_cost = ($this->daily_requests['text_search'] / 1000) * 5;
        $place_details_cost = ($this->daily_requests['place_details'] / 1000) * 4;
        $photos_cost = ($this->daily_requests['photos'] / 1000) * 7;
        $total_cost = $text_search_cost + $place_details_cost + $photos_cost;

        $wp_admin_bar->add_node(array(
            'id' => 'gpd-api-usage',
            'title' => sprintf(
                __('Places API: $%.2f today', 'google-places-directory'),
                $total_cost
            ),
            'href' => admin_url('edit.php?post_type=business&page=gpd-settings')
        ));

        $wp_admin_bar->add_node(array(
            'parent' => 'gpd-api-usage',
            'id' => 'gpd-search-usage',
            'title' => sprintf(
                __('Text Search: %d ($%.2f)', 'google-places-directory'),
                $this->daily_requests['text_search'],
                $text_search_cost
            )
        ));

        $wp_admin_bar->add_node(array(
            'parent' => 'gpd-api-usage',
            'id' => 'gpd-details-usage',
            'title' => sprintf(
                __('Place Details: %d ($%.2f)', 'google-places-directory'),
                $this->daily_requests['place_details'],
                $place_details_cost
            )
        ));

        $wp_admin_bar->add_node(array(
            'parent' => 'gpd-api-usage',
            'id' => 'gpd-photos-usage',
            'title' => sprintf(
                __('Photos: %d ($%.2f)', 'google-places-directory'),
                $this->daily_requests['photos'],
                $photos_cost
            )
        ));
    }

    public function add_usage_settings() {
        ?>
        <h3><?php esc_html_e('API Usage Alerts & Reports', 'google-places-directory'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Email Alerts', 'google-places-directory'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="gpd_enable_email_alerts" value="1" 
                            <?php checked(get_option('gpd_enable_email_alerts', false)); ?>
                        >
                        <?php esc_html_e('Enable email alerts for usage thresholds', 'google-places-directory'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Usage Reports', 'google-places-directory'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="gpd_enable_daily_report" value="1" 
                            <?php checked(get_option('gpd_enable_daily_report', false)); ?>
                        >
                        <?php esc_html_e('Send daily usage reports', 'google-places-directory'); ?>
                    </label>
                    <br>
                    <label>
                        <input type="checkbox" name="gpd_enable_weekly_report" value="1" 
                            <?php checked(get_option('gpd_enable_weekly_report', false)); ?>
                        >
                        <?php esc_html_e('Send weekly usage reports', 'google-places-directory'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Alert Email', 'google-places-directory'); ?></th>
                <td>
                    <input type="email" name="gpd_alert_email" class="regular-text" 
                        value="<?php echo esc_attr(get_option('gpd_alert_email', get_option('admin_email'))); ?>">
                    <p class="description">
                        <?php esc_html_e('Email address for alerts and reports. Defaults to admin email if empty.', 'google-places-directory'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Daily Cost Threshold', 'google-places-directory'); ?></th>
                <td>
                    <input type="number" name="gpd_daily_cost_threshold" class="small-text" min="0" step="0.01" 
                        value="<?php echo esc_attr(get_option('gpd_daily_cost_threshold', 50)); ?>">
                    <p class="description">
                        <?php esc_html_e('Send alert when daily API costs exceed this amount (in USD)', 'google-places-directory'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Daily Request Limit', 'google-places-directory'); ?></th>
                <td>
                    <input type="number" name="gpd_daily_request_limit" class="small-text" min="0" 
                        value="<?php echo esc_attr(get_option('gpd_daily_request_limit', 1000)); ?>">
                    <p class="description">
                        <?php esc_html_e('Send alert when daily API requests exceed this number', 'google-places-directory'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }

    private function calculate_total_cost() {
        $text_search_cost = ($this->daily_requests['text_search'] / 1000) * 5;
        $place_details_cost = ($this->daily_requests['place_details'] / 1000) * 4;
        $photos_cost = ($this->daily_requests['photos'] / 1000) * 7;
        return $text_search_cost + $place_details_cost + $photos_cost;
    }

    /**
     * Get API usage history for the last 30 days
     * 
     * @return array Usage history data
     */
    public function get_usage_history() {
        return $this->usage_history;
    }

    /**
     * Get the current day's API usage counts
     * 
     * @return array Daily request counts by type
     */
    public function get_daily_counts() {
        return $this->daily_requests;
    }    /**
     * Get the current usage thresholds
     * 
     * @return array Threshold settings
     */
    public function get_thresholds() {
        return array_merge($this->default_thresholds, array(
            'daily_cost' => get_option('gpd_daily_cost_threshold', $this->default_thresholds['daily_cost']),
            'request_limit' => get_option('gpd_daily_request_limit', $this->default_thresholds['request_limit'])
        ));
    }
}
