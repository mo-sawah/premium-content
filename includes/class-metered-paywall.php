<?php
/**
 * Handles metered paywall, email gate, and content access control
 */
class Premium_Content_Metered_Paywall {

    public function __construct() {
        // Hook into content filter
        add_filter('the_content', array($this, 'filter_content'), 999);
        
        // AJAX handlers
        add_action('wp_ajax_premium_track_view', array($this, 'ajax_track_view'));
        add_action('wp_ajax_nopriv_premium_track_view', array($this, 'ajax_track_view'));
        add_action('wp_ajax_premium_social_unlock', array($this, 'ajax_social_unlock'));
        add_action('wp_ajax_nopriv_premium_social_unlock', array($this, 'ajax_social_unlock'));
        
        // Enqueue counter banner
        add_action('wp_footer', array($this, 'render_counter_banner'));
    }

    /**
     * Filter content based on access rules
     */
    public function filter_content($content) {
        if (!is_singular() || !in_the_loop() || !is_main_query()) {
            return $content;
        }

        $post_id = get_the_ID();
        
        // Check if content should be locked
        if (!$this->should_show_paywall($post_id)) {
            return $content;
        }

        // Get access mode
        $access_mode = $this->get_post_access_mode($post_id);
        
        // Render appropriate paywall
        switch ($access_mode) {
            case 'email_gate':
                return $this->render_email_gate($content, $post_id);
            
            case 'metered':
                return $this->render_metered_paywall($content, $post_id);
            
            case 'premium':
                return $this->render_premium_paywall($content, $post_id);
            
            default:
                return $content;
        }
    }

    /**
     * Check if paywall should be shown
     */
    public static function should_show_paywall($post_id) {
        // Exclude admins if setting enabled
        if (premium_content_get_option('exclude_admins', '1') === '1' && current_user_can('manage_options')) {
            return false;
        }

        // Check user subscription
        if (premium_content_user_has_subscription()) {
            return false;
        }

        // Get post access level
        $post_access_level = get_post_meta($post_id, '_premium_access_level', true);
        
        // If post is set to free, don't show paywall
        if ($post_access_level === 'free') {
            return false;
        }

        // If post has specific access level, use it
        if ($post_access_level && $post_access_level !== 'auto') {
            return true;
        }

        // Use global access mode
        $global_mode = premium_content_get_option('access_mode', 'free');
        
        if ($global_mode === 'free') {
            return false;
        }

        return true;
    }

    /**
     * Get effective access mode for post
     */
    private function get_post_access_mode($post_id) {
        $post_access_level = get_post_meta($post_id, '_premium_access_level', true);
        
        if ($post_access_level && $post_access_level !== 'auto') {
            return $post_access_level;
        }

        return premium_content_get_option('access_mode', 'free');
    }

    /**
     * Render email gate paywall
     */
    private function render_email_gate($content, $post_id) {
        // Check if user has email gate access cookie
        if (isset($_COOKIE['premium_email_gate_access']) && $_COOKIE['premium_email_gate_access'] === 'granted') {
            return $content;
        }

        // Check if social unlock is active
        if (isset($_COOKIE['premium_social_unlock']) && $_COOKIE['premium_social_unlock'] === 'granted') {
            return $content;
        }

        // Truncate content
        $truncated = $this->truncate_content($content, 200);
        
        // Check if social media option is enabled
        $social_enabled = premium_content_get_option('email_gate_social_enabled', '0') === '1';
        
        ob_start();
        ?>
        <div class="premium-content-wrapper">
            <div class="premium-truncated-content">
                <?php echo $truncated; ?>
            </div>
            
            <div id="premium-content-gate" class="premium-paywall-gate">
                <div class="premium-paywall-icon">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 17a2 2 0 0 0 2-2 2 2 0 0 0-2-2 2 2 0 0 0-2 2 2 2 0 0 0 2 2m6-9a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V10a2 2 0 0 1 2-2h1V6a5 5 0 0 1 5-5 5 5 0 0 1 5 5v2h1m-6-5a3 3 0 0 0-3 3v2h6V6a3 3 0 0 0-3-3z"/>
                    </svg>
                </div>
                
                <h2 class="premium-paywall-title">
                    <?php echo esc_html(premium_content_get_option('email_gate_title', 'Unlock This Content')); ?>
                </h2>
                
                <p class="premium-paywall-description">
                    <?php echo esc_html(premium_content_get_option('email_gate_description', 'Get instant access to this article and all premium content for 30 days.')); ?>
                </p>

                <?php if ($social_enabled): ?>
                    <div class="premium-unlock-options">
                        <p class="unlock-choice-text">Choose one option to unlock:</p>
                        
                        <!-- Social Media Follow Option -->
                        <div class="premium-social-unlock">
                            <h3 class="unlock-option-title">
                                <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                </svg>
                                Follow us on Social Media
                            </h3>
                            <p class="unlock-option-description">Follow us and unlock instantly</p>
                            
                            <div class="social-buttons">
                                <?php
                                $facebook = premium_content_get_option('social_facebook_url', '');
                                $twitter = premium_content_get_option('social_twitter_url', '');
                                $instagram = premium_content_get_option('social_instagram_url', '');
                                $linkedin = premium_content_get_option('social_linkedin_url', '');
                                
                                if ($facebook): ?>
                                    <a href="<?php echo esc_url($facebook); ?>" 
                                       class="social-button social-facebook" 
                                       data-network="facebook"
                                       target="_blank" 
                                       rel="noopener noreferrer">
                                        <svg viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                        </svg>
                                        Facebook
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($twitter): ?>
                                    <a href="<?php echo esc_url($twitter); ?>" 
                                       class="social-button social-twitter" 
                                       data-network="twitter"
                                       target="_blank" 
                                       rel="noopener noreferrer">
                                        <svg viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                                        </svg>
                                        Twitter
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($instagram): ?>
                                    <a href="<?php echo esc_url($instagram); ?>" 
                                       class="social-button social-instagram" 
                                       data-network="instagram"
                                       target="_blank" 
                                       rel="noopener noreferrer">
                                        <svg viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/>
                                        </svg>
                                        Instagram
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($linkedin): ?>
                                    <a href="<?php echo esc_url($linkedin); ?>" 
                                       class="social-button social-linkedin" 
                                       data-network="linkedin"
                                       target="_blank" 
                                       rel="noopener noreferrer">
                                        <svg viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                        </svg>
                                        LinkedIn
                                    </a>
                                <?php endif; ?>
                            </div>
                            
                            <div id="social-unlock-status" class="unlock-status" style="display: none;">
                                <div class="unlock-loader"></div>
                                <p class="unlock-message">Verifying... Unlocking content...</p>
                            </div>
                        </div>

                        <div class="unlock-divider">
                            <span>OR</span>
                        </div>

                        <!-- Email Option -->
                        <div class="premium-email-unlock">
                            <h3 class="unlock-option-title">
                                <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                                </svg>
                                Unlock with Email
                            </h3>
                            <p class="unlock-option-description">Get 30 days of full access</p>
                            
                            <?php echo Premium_Content_CF7_Handler::render_form_in_gate($post_id); ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Email Only Mode -->
                    <div class="premium-email-gate-single">
                        <?php echo Premium_Content_CF7_Handler::render_form_in_gate($post_id); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="premium-full-content" style="display: none;">
                <?php echo $content; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render metered paywall
     */
    private function render_metered_paywall($content, $post_id) {
        // Check if post is excluded from count
        if (Premium_Content_Post_Meta::is_excluded_from_count($post_id)) {
            return $content;
        }

        $limit = intval(premium_content_get_option('metered_limit', 3));
        $view_count = $this->get_view_count();

        // Allow access if under limit
        if ($view_count < $limit) {
            return $content;
        }

        // Show paywall
        $truncated = $this->truncate_content($content, 200);
        
        ob_start();
        ?>
        <div class="premium-content-wrapper">
            <div class="premium-truncated-content">
                <?php echo $truncated; ?>
            </div>
            
            <div class="premium-paywall-gate">
                <div class="premium-paywall-icon">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 17a2 2 0 0 0 2-2 2 2 0 0 0-2-2 2 2 0 0 0-2 2 2 2 0 0 0 2 2m6-9a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V10a2 2 0 0 1 2-2h1V6a5 5 0 0 1 5-5 5 5 0 0 1 5 5v2h1m-6-5a3 3 0 0 0-3 3v2h6V6a3 3 0 0 0-3-3z"/>
                    </svg>
                </div>
                
                <h2 class="premium-paywall-title">
                    <?php echo esc_html(premium_content_get_option('paywall_title', 'Subscribe to Continue Reading')); ?>
                </h2>
                
                <p class="premium-paywall-description">
                    <?php echo esc_html(premium_content_get_option('limit_reached_text', "You've reached your free article limit for this month")); ?>
                </p>

                <div class="premium-paywall-actions">
                    <a href="<?php echo esc_url(get_permalink(get_option('premium_content_page_pricing'))); ?>" 
                       class="premium-button premium-button-primary">
                        View Plans
                    </a>
                    
                    <?php if (!is_user_logged_in()): ?>
                        <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" 
                           class="premium-button premium-button-secondary">
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
     * Render premium paywall
     */
    private function render_premium_paywall($content, $post_id) {
        $truncated = $this->truncate_content($content, 200);
        
        ob_start();
        ?>
        <div class="premium-content-wrapper">
            <div class="premium-truncated-content">
                <?php echo $truncated; ?>
            </div>
            
            <div class="premium-paywall-gate">
                <div class="premium-paywall-icon">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
                    </svg>
                </div>
                
                <h2 class="premium-paywall-title">
                    <?php echo esc_html(premium_content_get_option('paywall_title', 'Premium Content')); ?>
                </h2>
                
                <p class="premium-paywall-description">
                    <?php echo esc_html(premium_content_get_option('paywall_description', 'Subscribe to access this exclusive content')); ?>
                </p>

                <div class="premium-paywall-actions">
                    <a href="<?php echo esc_url(get_permalink(get_option('premium_content_page_pricing'))); ?>" 
                       class="premium-button premium-button-primary">
                        Subscribe Now
                    </a>
                    
                    <?php if (!is_user_logged_in()): ?>
                        <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" 
                           class="premium-button premium-button-secondary">
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
     * Truncate content
     */
    private function truncate_content($content, $word_count = 200) {
        $content = wp_strip_all_tags($content);
        $words = explode(' ', $content);
        
        if (count($words) > $word_count) {
            $words = array_slice($words, 0, $word_count);
            $content = implode(' ', $words) . '...';
        }
        
        return '<p>' . esc_html($content) . '</p>';
    }

    /**
     * Get user view count
     */
    public static function get_view_count($identifier = null) {
        if (!$identifier) {
            $identifier = self::get_user_identifier();
        }

        global $wpdb;
        $table = $wpdb->prefix . 'premium_article_views';
        $view_month = date('Y-m');

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT post_id) FROM $table 
            WHERE user_identifier = %s AND view_month = %s",
            $identifier,
            $view_month
        ));

        return intval($count);
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

        // Don't track if user has subscription
        if (premium_content_user_has_subscription()) {
            wp_send_json_success(array('has_subscription' => true));
        }

        $identifier = self::get_user_identifier();
        $this->record_view($post_id, $identifier);

        $limit = intval(premium_content_get_option('metered_limit', 3));
        $view_count = $this->get_view_count($identifier);
        $remaining = max(0, $limit - $view_count);

        wp_send_json_success(array(
            'remaining' => $remaining,
            'limit_reached' => $remaining === 0,
            'view_count' => $view_count
        ));
    }

    /**
     * Record article view
     */
    private function record_view($post_id, $identifier) {
        global $wpdb;
        $table = $wpdb->prefix . 'premium_article_views';

        // Check if already viewed today
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table 
            WHERE user_identifier = %s 
            AND post_id = %d 
            AND DATE(viewed_at) = CURDATE()",
            $identifier,
            $post_id
        ));

        if ($exists) {
            return;
        }

        $wpdb->insert($table, array(
            'user_identifier' => $identifier,
            'user_id' => get_current_user_id() ?: null,
            'post_id' => $post_id,
            'ip_address' => self::get_user_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : '',
            'view_month' => date('Y-m')
        ));
    }

    /**
     * Handle social unlock AJAX
     */
    public function ajax_social_unlock() {
        check_ajax_referer('premium_content_paywall', 'nonce');

        $network = isset($_POST['network']) ? sanitize_text_field($_POST['network']) : '';
        
        if (!$network) {
            wp_send_json_error('Invalid network');
        }

        // Set 30-day cookie
        setcookie('premium_social_unlock', 'granted', time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);

        // Log the social unlock
        global $wpdb;
        $table = $wpdb->prefix . 'premium_emails';
        
        $wpdb->insert($table, array(
            'email' => 'social_unlock_' . $network . '_' . time(),
            'post_id' => isset($_POST['post_id']) ? intval($_POST['post_id']) : 0,
            'user_id' => get_current_user_id() ?: null
        ));

        wp_send_json_success(array(
            'message' => 'Access granted',
            'network' => $network
        ));
    }

    /**
     * Get user identifier
     */
    private static function get_user_identifier() {
        if (is_user_logged_in()) {
            return 'user_' . get_current_user_id();
        }

        // Use cookie-based identifier
        if (isset($_COOKIE['premium_visitor_id'])) {
            return sanitize_text_field($_COOKIE['premium_visitor_id']);
        }

        // Generate new identifier
        $identifier = 'visitor_' . wp_generate_password(32, false);
        setcookie('premium_visitor_id', $identifier, time() + (365 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
        
        return $identifier;
    }

    /**
     * Get user IP address
     */
    private static function get_user_ip() {
        $ip = '';
        
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return sanitize_text_field($ip);
    }

    /**
     * Render counter banner in footer
     */
    public function render_counter_banner() {
        if (!is_singular() || is_user_admin() || is_admin()) {
            return;
        }

        // Don't show if user has subscription
        if (premium_content_user_has_subscription()) {
            return;
        }

        // Only show in metered mode
        $access_mode = premium_content_get_option('access_mode', 'free');
        if ($access_mode !== 'metered') {
            return;
        }

        // Check if counter should be shown
        if (premium_content_get_option('metered_show_counter', '1') !== '1') {
            return;
        }

        $limit = intval(premium_content_get_option('metered_limit', 3));
        $view_count = $this->get_view_count();
        $remaining = max(0, $limit - $view_count);

        if ($remaining === 0) {
            return; // Don't show counter if limit reached (paywall shown instead)
        }

        $position = premium_content_get_option('metered_counter_position', 'top');
        $counter_text = premium_content_get_option('counter_text', 'You have {remaining} free articles remaining');
        $counter_text = str_replace('{remaining}', $remaining, $counter_text);
        
        $position_class = 'premium-counter-' . $position;
        ?>
        <div id="premium-counter-banner" class="premium-counter-banner <?php echo esc_attr($position_class); ?>">
            <div class="premium-counter-content">
                <span class="premium-counter-text"><?php echo esc_html($counter_text); ?></span>
                <a href="<?php echo esc_url(get_permalink(get_option('premium_content_page_pricing'))); ?>" 
                   class="premium-counter-cta">Subscribe Now</a>
                <button type="button" class="premium-counter-close" aria-label="Close">&times;</button>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.premium-counter-close').on('click', function() {
                $('#premium-counter-banner').fadeOut(300);
            });
        });
        </script>
        <?php
    }
}