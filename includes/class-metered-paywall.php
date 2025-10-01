<?php
/**
 * Handles metered paywall logic, view tracking, and content restriction
 */
class Premium_Content_Metered_Paywall {

    public function __construct() {
        add_filter('the_content', array($this, 'filter_content'), 20);
        add_action('wp_ajax_premium_track_view', array($this, 'ajax_track_view'));
        add_action('wp_ajax_nopriv_premium_track_view', array($this, 'ajax_track_view'));
        add_action('wp_footer', array($this, 'render_counter_banner'));
        
        // Schedule daily cleanup
        if (!wp_next_scheduled('premium_content_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'premium_content_daily_cleanup');
        }
        add_action('premium_content_daily_cleanup', array($this, 'cleanup_old_views'));
    }

    /**
     * Generate unique user identifier
     */
    public static function get_user_identifier() {
        if (is_user_logged_in()) {
            return 'user_' . get_current_user_id();
        }
        
        // Check for existing cookie
        if (isset($_COOKIE['premium_visitor_id'])) {
            return $_COOKIE['premium_visitor_id'];
        }
        
        // Generate new identifier
        $identifier = 'visitor_' . wp_generate_password(32, false);
        
        // Set cookie for 30 days
        setcookie('premium_visitor_id', $identifier, time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
        
        return $identifier;
    }

    /**
     * Track article view
     */
    public function ajax_track_view() {
        check_ajax_referer('premium_content_paywall', 'nonce');
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if (!$post_id) {
            wp_send_json_error('Invalid post ID');
        }
        
        $tracked = $this->track_view($post_id);
        
        if ($tracked) {
            $view_count = self::get_view_count();
            $limit = intval(premium_content_get_option('metered_limit', 3));
            $remaining = max(0, $limit - $view_count);
            
            wp_send_json_success(array(
                'view_count' => $view_count,
                'remaining' => $remaining,
                'limit_reached' => $view_count >= $limit
            ));
        } else {
            wp_send_json_success(array(
                'already_tracked' => true
            ));
        }
    }

    /**
     * Track view in database
     */
    private function track_view($post_id) {
        global $wpdb;
        
        $identifier = self::get_user_identifier();
        $user_id = is_user_logged_in() ? get_current_user_id() : null;
        $view_month = date('Y-m');
        
        $table = $wpdb->prefix . 'premium_article_views';
        
        // Check if already viewed this month
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table 
            WHERE user_identifier = %s 
            AND post_id = %d 
            AND view_month = %s",
            $identifier,
            $post_id,
            $view_month
        ));
        
        if ($existing) {
            return false; // Already tracked
        }
        
        // Insert new view
        $inserted = $wpdb->insert(
            $table,
            array(
                'user_identifier' => $identifier,
                'user_id' => $user_id,
                'post_id' => $post_id,
                'ip_address' => self::get_client_ip(),
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'], 0, 255),
                'view_month' => $view_month
            ),
            array('%s', '%d', '%d', '%s', '%s', '%s')
        );
        
        return $inserted !== false;
    }

    /**
     * Get view count for current period
     */
    public static function get_view_count($identifier = null) {
        global $wpdb;
        
        if (!$identifier) {
            $identifier = self::get_user_identifier();
        }
        
        $period = premium_content_get_option('metered_period', 'monthly');
        $view_month = date('Y-m');
        
        $table = $wpdb->prefix . 'premium_article_views';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT post_id) FROM $table 
            WHERE user_identifier = %s 
            AND view_month = %s",
            $identifier,
            $view_month
        ));
        
        return intval($count);
    }

    /**
     * Check if paywall should be displayed
     */
    public static function should_show_paywall($post_id = null) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        // Only work on singular posts/pages, not homepage or archives
        if (!$post_id || !is_singular()) {
            return false;
        }
        
        // Check allowed post types
        $allowed_post_types = premium_content_get_option('allowed_post_types', array('post'));
        if (!is_array($allowed_post_types)) {
            $allowed_post_types = array('post');
        }
        
        $post_type = get_post_type($post_id);
        if (!in_array($post_type, $allowed_post_types)) {
            return false;
        }
        
        // Check allowed categories (only for posts)
        if ($post_type === 'post') {
            $allowed_categories = premium_content_get_option('allowed_categories', array());
            if (!empty($allowed_categories) && is_array($allowed_categories)) {
                $post_categories = wp_get_post_categories($post_id);
                $has_allowed_category = !empty(array_intersect($post_categories, $allowed_categories));
                
                if (!$has_allowed_category) {
                    return false;
                }
            }
        }
        
        // Check if user is admin and admins are excluded
        if (premium_content_get_option('exclude_admins', '1') === '1' && current_user_can('manage_options')) {
            return false;
        }
        
        // Check if user has active subscription
        if (premium_content_user_has_subscription()) {
            return false;
        }
        
        $access_mode = premium_content_get_option('access_mode', 'free');
        
        // Free mode - no paywall
        if ($access_mode === 'free') {
            return false;
        }
        
        // Check individual post settings (highest priority)
        $post_setting = get_post_meta($post_id, '_premium_access_level', true);
        
        if ($post_setting === 'free') {
            return false;
        }
        
        if ($post_setting === 'premium') {
            return true;
        }
        
        if ($post_setting === 'email_gate') {
            // Check if user has email gate access
            return !self::has_email_gate_access();
        }
        
        // Email Gate mode - check cookie
        if ($access_mode === 'email_gate') {
            return !self::has_email_gate_access();
        }
        
        // Premium mode - show paywall for all
        if ($access_mode === 'premium') {
            return true;
        }
        
        // Metered mode - check view count
        if ($access_mode === 'metered') {
            // Check if post is excluded from count
            $excluded = get_post_meta($post_id, '_premium_excluded_from_count', true);
            if ($excluded === '1') {
                return false;
            }
            
            $view_count = self::get_view_count();
            $limit = intval(premium_content_get_option('metered_limit', 3));
            
            return $view_count >= $limit;
        }
        
        return false;
    }
    
    /**
     * Check if user has email gate access (30-day cookie)
     */
    public static function has_email_gate_access() {
        return isset($_COOKIE['premium_email_gate_access']) && $_COOKIE['premium_email_gate_access'] === 'granted';
    }

    /**
     * Filter post content to add paywall
     */
    public function filter_content($content) {
        if (!is_singular() || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        $post_id = get_the_ID();
        
        if (!$this->should_show_paywall($post_id)) {
            return $content;
        }
        
        // Truncate content
        $truncate_length = 500;
        $truncated = substr(strip_tags($content), 0, $truncate_length);
        
        // Get paywall HTML
        $paywall_html = $this->get_paywall_html($post_id);
        
        return '<div class="premium-content-wrapper">
            <div class="premium-truncated-content">' . wpautop($truncated) . '...</div>
            ' . $paywall_html . '
            <div class="premium-full-content" style="display:none;">' . $content . '</div>
        </div>';
    }

    /**
     * Get paywall HTML based on mode
     */
    private function get_paywall_html($post_id) {
        $access_mode = premium_content_get_option('access_mode', 'free');
        
        $title = premium_content_get_option('paywall_title', 'Subscribe to Continue Reading');
        $description = premium_content_get_option('paywall_description', 'Get unlimited access to all premium content');
        
        $pricing_url = get_permalink(get_option('premium_content_page_pricing'));
        
        ob_start();
        ?>
        <div class="premium-paywall-gate" id="premium-paywall">
            <div class="premium-paywall-content">
                <div class="premium-paywall-icon">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/>
                    </svg>
                </div>
                <h2 class="premium-paywall-title"><?php echo esc_html($title); ?></h2>
                <p class="premium-paywall-description"><?php echo esc_html($description); ?></p>
                
                <?php if ($access_mode === 'metered'): 
                    $view_count = self::get_view_count();
                    $limit = intval(premium_content_get_option('metered_limit', 3));
                ?>
                    <div class="premium-limit-info">
                        <p>You've read <strong><?php echo $view_count; ?> of <?php echo $limit; ?></strong> free articles this month.</p>
                    </div>
                <?php endif; ?>
                
                <div class="premium-paywall-actions">
                    <a href="<?php echo esc_url($pricing_url); ?>" class="premium-button premium-button-primary">
                        View Pricing Plans
                    </a>
                    <?php if (is_user_logged_in()): ?>
                        <a href="<?php echo esc_url(get_permalink(get_option('premium_content_page_account'))); ?>" class="premium-button premium-button-secondary">
                            My Account
                        </a>
                    <?php else: ?>
                        <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="premium-button premium-button-secondary">
                            Sign In
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render floating counter banner
     */
    public function render_counter_banner() {
        if (!is_singular() || !in_the_loop() || premium_content_user_has_subscription()) {
            return;
        }
        
        $access_mode = premium_content_get_option('access_mode', 'free');
        
        if ($access_mode !== 'metered') {
            return;
        }
        
        $show_counter = premium_content_get_option('metered_show_counter', '1');
        if ($show_counter !== '1') {
            return;
        }
        
        $view_count = self::get_view_count();
        $limit = intval(premium_content_get_option('metered_limit', 3));
        $remaining = max(0, $limit - $view_count);
        
        if ($remaining === 0) {
            return; // Don't show if limit reached
        }
        
        $counter_text = premium_content_get_option('counter_text', 'You have {remaining} free articles remaining');
        $counter_text = str_replace('{remaining}', $remaining, $counter_text);
        
        $position = premium_content_get_option('metered_counter_position', 'top');
        
        $warning_class = $remaining === 1 ? 'premium-counter-warning' : '';
        
        ?>
        <div class="premium-counter-banner premium-counter-<?php echo esc_attr($position); ?> <?php echo $warning_class; ?>" id="premium-counter-banner">
            <div class="premium-counter-content">
                <span class="premium-counter-text"><?php echo esc_html($counter_text); ?></span>
                <a href="<?php echo esc_url(get_permalink(get_option('premium_content_page_pricing'))); ?>" class="premium-counter-cta">
                    Subscribe Now
                </a>
                <button class="premium-counter-close" onclick="this.parentElement.parentElement.style.display='none'">×</button>
            </div>
        </div>
        <?php
    }

    /**
     * Get client IP address
     */
    private static function get_client_ip() {
        $ip_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER)) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }

    /**
     * Cleanup old view records (keep only last 12 months)
     */
    public function cleanup_old_views() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'premium_article_views';
        $cutoff_date = date('Y-m', strtotime('-12 months'));
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table WHERE view_month < %s",
            $cutoff_date
        ));
    }

    /**
     * Reset view count for user (admin function)
     */
    public static function reset_user_views($identifier) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'premium_article_views';
        $view_month = date('Y-m');
        
        return $wpdb->delete(
            $table,
            array(
                'user_identifier' => $identifier,
                'view_month' => $view_month
            ),
            array('%s', '%s')
        );
    }

    /**
     * Get view statistics for admin
     */
    public static function get_view_statistics() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'premium_article_views';
        $view_month = date('Y-m');
        
        $stats = array(
            'total_views' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE view_month = %s",
                $view_month
            )),
            'unique_visitors' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT user_identifier) FROM $table WHERE view_month = %s",
                $view_month
            )),
            'registered_users' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT user_id) FROM $table WHERE view_month = %s AND user_id IS NOT NULL",
                $view_month
            )),
            'most_viewed_posts' => $wpdb->get_results($wpdb->prepare(
                "SELECT post_id, COUNT(*) as view_count 
                FROM $table 
                WHERE view_month = %s 
                GROUP BY post_id 
                ORDER BY view_count DESC 
                LIMIT 10",
                $view_month
            ))
        );
        
        return $stats;
    }
}