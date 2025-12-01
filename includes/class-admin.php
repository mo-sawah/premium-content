<?php
/**
 * Handles admin interface and settings
 */
class Premium_Content_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // AJAX handlers
        add_action('wp_ajax_premium_test_stripe', array($this, 'ajax_test_stripe'));
        add_action('wp_ajax_premium_test_paypal', array($this, 'ajax_test_paypal'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'Premium Content',
            'Premium Content',
            'manage_options',
            'premium-content',
            array($this, 'render_dashboard'),
            'dashicons-lock',
            30
        );

        add_submenu_page(
            'premium-content',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'premium-content',
            array($this, 'render_dashboard')
        );

        add_submenu_page(
            'premium-content',
            'Access Control',
            'Access Control',
            'manage_options',
            'premium-content-access',
            array($this, 'render_access_settings')
        );

        add_submenu_page(
            'premium-content',
            'Plans',
            'Plans',
            'manage_options',
            'premium-content-plans',
            array($this, 'render_plans')
        );

        add_submenu_page(
            'premium-content',
            'Subscribers',
            'Subscribers',
            'manage_options',
            'premium-content-subscribers',
            array($this, 'render_subscribers')
        );

        add_submenu_page(
            'premium-content',
            'Payment Settings',
            'Payment Settings',
            'manage_options',
            'premium-content-payments',
            array($this, 'render_payment_settings')
        );

        add_submenu_page(
            'premium-content',
            'Settings',
            'Settings',
            'manage_options',
            'premium-content-settings',
            array($this, 'render_general_settings')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('premium_content_access', 'premium_content_access_mode');
        register_setting('premium_content_access', 'premium_content_metered_limit');
        register_setting('premium_content_access', 'premium_content_metered_period');
        register_setting('premium_content_access', 'premium_content_metered_show_counter');
        register_setting('premium_content_access', 'premium_content_metered_counter_position');
        register_setting('premium_content_access', 'premium_content_cf7_form_id');
        
        // Email Gate Settings
        register_setting('premium_content_access', 'premium_content_email_gate_title');
        register_setting('premium_content_access', 'premium_content_email_gate_description');
        register_setting('premium_content_access', 'premium_content_email_gate_social_enabled');
        register_setting('premium_content_access', 'premium_content_social_facebook_url');
        register_setting('premium_content_access', 'premium_content_social_twitter_url');
        register_setting('premium_content_access', 'premium_content_social_instagram_url');
        register_setting('premium_content_access', 'premium_content_social_linkedin_url');
        register_setting('premium_content_access', 'premium_content_social_unlock_delay');
        
        // Payment Settings
        register_setting('premium_content_payments', 'premium_content_stripe_enabled');
        register_setting('premium_content_payments', 'premium_content_stripe_test_mode');
        register_setting('premium_content_payments', 'premium_content_stripe_test_publishable_key');
        register_setting('premium_content_payments', 'premium_content_stripe_test_secret_key');
        register_setting('premium_content_payments', 'premium_content_stripe_live_publishable_key');
        register_setting('premium_content_payments', 'premium_content_stripe_live_secret_key');
        
        register_setting('premium_content_payments', 'premium_content_paypal_enabled');
        register_setting('premium_content_payments', 'premium_content_paypal_test_mode');
        register_setting('premium_content_payments', 'premium_content_paypal_client_id');
        register_setting('premium_content_payments', 'premium_content_paypal_client_secret');
        
        // Text Settings
        register_setting('premium_content_settings', 'premium_content_paywall_title');
        register_setting('premium_content_settings', 'premium_content_paywall_description');
        register_setting('premium_content_settings', 'premium_content_counter_text');
        register_setting('premium_content_settings', 'premium_content_limit_reached_text');
        register_setting('premium_content_settings', 'premium_content_exclude_admins');
    }

    /**
     * Render dashboard
     */
    public function render_dashboard() {
        $stats = Premium_Content_Subscription_Manager::get_statistics();
        $email_stats = Premium_Content_CF7_Handler::get_email_statistics();
        
        ?>
        <div class="wrap premium-admin-wrap">
            <h1 class="premium-page-title">
                <span class="dashicons dashicons-lock"></span>
                Premium Content Dashboard
            </h1>

            <div class="premium-dashboard-grid">
                <div class="premium-stat-card">
                    <div class="premium-stat-icon premium-stat-subscribers">
                        <span class="dashicons dashicons-groups"></span>
                    </div>
                    <div class="premium-stat-content">
                        <div class="premium-stat-number"><?php echo esc_html($stats['total_active']); ?></div>
                        <div class="premium-stat-label">Active Subscribers</div>
                    </div>
                </div>

                <div class="premium-stat-card">
                    <div class="premium-stat-icon premium-stat-revenue">
                        <span class="dashicons dashicons-money-alt"></span>
                    </div>
                    <div class="premium-stat-content">
                        <div class="premium-stat-number">$<?php echo number_format($stats['total_revenue'], 2); ?></div>
                        <div class="premium-stat-label">Total Revenue</div>
                    </div>
                </div>

                <div class="premium-stat-card">
                    <div class="premium-stat-icon premium-stat-emails">
                        <span class="dashicons dashicons-email"></span>
                    </div>
                    <div class="premium-stat-content">
                        <div class="premium-stat-number"><?php echo esc_html($email_stats['total_emails']); ?></div>
                        <div class="premium-stat-label">Email Subscribers</div>
                    </div>
                </div>

                <div class="premium-stat-card">
                    <div class="premium-stat-icon premium-stat-views">
                        <span class="dashicons dashicons-visibility"></span>
                    </div>
                    <div class="premium-stat-content">
                        <div class="premium-stat-number"><?php echo esc_html($email_stats['this_month']); ?></div>
                        <div class="premium-stat-label">This Month</div>
                    </div>
                </div>
            </div>

            <div class="premium-dashboard-section">
                <div class="premium-section-header">
                    <h2>Quick Actions</h2>
                </div>
                <div class="premium-quick-actions">
                    <a href="<?php echo admin_url('admin.php?page=premium-content-access'); ?>" class="premium-action-button">
                        <span class="dashicons dashicons-admin-settings"></span>
                        Access Control
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=premium-content-plans'); ?>" class="premium-action-button">
                        <span class="dashicons dashicons-cart"></span>
                        Manage Plans
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=premium-content-payments'); ?>" class="premium-action-button">
                        <span class="dashicons dashicons-money"></span>
                        Payment Settings
                    </a>
                    <a href="<?php echo admin_url('post-new.php?post_type=post'); ?>" class="premium-action-button">
                        <span class="dashicons dashicons-edit"></span>
                        Create Content
                    </a>
                </div>
            </div>

            <div class="premium-dashboard-section">
                <div class="premium-section-header">
                    <h2>Current Configuration</h2>
                </div>
                <div class="premium-status-grid">
                    <div class="premium-status-item">
                        <span class="status-label">Access Mode:</span>
                        <span class="status-badge status-<?php echo esc_attr(premium_content_get_option('access_mode', 'free')); ?>">
                            <?php echo esc_html(ucfirst(str_replace('_', ' ', premium_content_get_option('access_mode', 'free')))); ?>
                        </span>
                    </div>
                    <div class="premium-status-item">
                        <span class="status-label">Stripe:</span>
                        <span class="status-value">
                            <?php echo premium_content_get_option('stripe_enabled', '0') === '1' ? '✓ Enabled' : '✗ Disabled'; ?>
                        </span>
                    </div>
                    <div class="premium-status-item">
                        <span class="status-label">PayPal:</span>
                        <span class="status-value">
                            <?php echo premium_content_get_option('paypal_enabled', '0') === '1' ? '✓ Enabled' : '✗ Disabled'; ?>
                        </span>
                    </div>
                    <div class="premium-status-item">
                        <span class="status-label">CF7 Form:</span>
                        <span class="status-value">
                            <?php echo premium_content_get_option('cf7_form_id', '') ? '✓ Configured' : '✗ Not Set'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render access control settings
     */
    public function render_access_settings() {
        if (isset($_POST['submit']) && check_admin_referer('premium_content_access')) {
            // Save settings
            update_option('premium_content_access_mode', sanitize_text_field($_POST['access_mode']));
            update_option('premium_content_metered_limit', intval($_POST['metered_limit']));
            update_option('premium_content_metered_period', sanitize_text_field($_POST['metered_period']));
            update_option('premium_content_metered_show_counter', isset($_POST['metered_show_counter']) ? '1' : '0');
            update_option('premium_content_metered_counter_position', sanitize_text_field($_POST['metered_counter_position']));
            update_option('premium_content_cf7_form_id', sanitize_text_field($_POST['cf7_form_id']));
            
            // Email Gate Settings
            update_option('premium_content_email_gate_title', sanitize_text_field($_POST['email_gate_title']));
            update_option('premium_content_email_gate_description', sanitize_textarea_field($_POST['email_gate_description']));
            update_option('premium_content_email_gate_social_enabled', isset($_POST['email_gate_social_enabled']) ? '1' : '0');
            update_option('premium_content_social_facebook_url', esc_url_raw($_POST['social_facebook_url']));
            update_option('premium_content_social_twitter_url', esc_url_raw($_POST['social_twitter_url']));
            update_option('premium_content_social_instagram_url', esc_url_raw($_POST['social_instagram_url']));
            update_option('premium_content_social_linkedin_url', esc_url_raw($_POST['social_linkedin_url']));
            update_option('premium_content_social_unlock_delay', intval($_POST['social_unlock_delay']));
            
            echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
        }

        $access_mode = premium_content_get_option('access_mode', 'free');
        $cf7_forms = Premium_Content_CF7_Handler::get_forms_dropdown();
        ?>
        <div class="wrap premium-admin-wrap">
            <h1 class="premium-page-title">
                <span class="dashicons dashicons-admin-settings"></span>
                Access Control Settings
            </h1>

            <form method="post" class="premium-settings-form">
                <?php wp_nonce_field('premium_content_access'); ?>

                <div class="premium-card">
                    <div class="premium-card-header">
                        <h2>Content Access Mode</h2>
                        <p>Choose how users can access your premium content</p>
                    </div>
                    <div class="premium-card-body">
                        <div class="premium-mode-selector">
                            <label class="premium-mode-option <?php echo $access_mode === 'free' ? 'active' : ''; ?>">
                                <input type="radio" name="access_mode" value="free" <?php checked($access_mode, 'free'); ?>>
                                <div class="mode-content">
                                    <div class="mode-icon">🔓</div>
                                    <h3 class="mode-title">Free Access</h3>
                                    <p class="mode-description">All content is freely accessible</p>
                                </div>
                            </label>

                            <label class="premium-mode-option <?php echo $access_mode === 'metered' ? 'active' : ''; ?>">
                                <input type="radio" name="access_mode" value="metered" <?php checked($access_mode, 'metered'); ?>>
                                <div class="mode-content">
                                    <div class="mode-icon">📊</div>
                                    <h3 class="mode-title">Metered Paywall</h3>
                                    <p class="mode-description">Limited free articles per period</p>
                                </div>
                            </label>

                            <label class="premium-mode-option <?php echo $access_mode === 'email_gate' ? 'active' : ''; ?>">
                                <input type="radio" name="access_mode" value="email_gate" <?php checked($access_mode, 'email_gate'); ?>>
                                <div class="mode-content">
                                    <div class="mode-icon">📧</div>
                                    <h3 class="mode-title">Email Gate</h3>
                                    <p class="mode-description">Email required for 30-day access</p>
                                </div>
                            </label>

                            <label class="premium-mode-option <?php echo $access_mode === 'premium' ? 'active' : ''; ?>">
                                <input type="radio" name="access_mode" value="premium" <?php checked($access_mode, 'premium'); ?>>
                                <div class="mode-content">
                                    <div class="mode-icon">🔒</div>
                                    <h3 class="mode-title">Premium Only</h3>
                                    <p class="mode-description">Subscription required</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Metered Settings -->
                <div class="premium-card metered-settings" style="display: <?php echo $access_mode === 'metered' ? 'block' : 'none'; ?>;">
                    <div class="premium-card-header">
                        <h2>Metered Paywall Settings</h2>
                    </div>
                    <div class="premium-card-body">
                        <div class="premium-form-row">
                            <div class="premium-form-group">
                                <label class="premium-label">Article Limit</label>
                                <input type="number" name="metered_limit" class="premium-input" 
                                       value="<?php echo esc_attr(premium_content_get_option('metered_limit', 3)); ?>" 
                                       min="1" max="100">
                                <p class="premium-description">Number of free articles per period</p>
                            </div>

                            <div class="premium-form-group">
                                <label class="premium-label">Period</label>
                                <select name="metered_period" class="premium-select">
                                    <option value="daily" <?php selected(premium_content_get_option('metered_period'), 'daily'); ?>>Daily</option>
                                    <option value="weekly" <?php selected(premium_content_get_option('metered_period'), 'weekly'); ?>>Weekly</option>
                                    <option value="monthly" <?php selected(premium_content_get_option('metered_period'), 'monthly'); ?>>Monthly</option>
                                </select>
                            </div>
                        </div>

                        <div class="premium-form-group">
                            <label class="premium-checkbox-label">
                                <input type="checkbox" name="metered_show_counter" value="1" 
                                       <?php checked(premium_content_get_option('metered_show_counter', '1'), '1'); ?>>
                                Show article counter banner
                            </label>
                        </div>

                        <div class="premium-form-group counter-position" style="display: <?php echo premium_content_get_option('metered_show_counter', '1') === '1' ? 'block' : 'none'; ?>;">
                            <label class="premium-label">Counter Position</label>
                            <select name="metered_counter_position" class="premium-select">
                                <option value="top" <?php selected(premium_content_get_option('metered_counter_position'), 'top'); ?>>Top</option>
                                <option value="bottom" <?php selected(premium_content_get_option('metered_counter_position'), 'bottom'); ?>>Bottom</option>
                                <option value="floating" <?php selected(premium_content_get_option('metered_counter_position'), 'floating'); ?>>Floating</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Email Gate Settings -->
                <div class="premium-card email-gate-settings" style="display: <?php echo $access_mode === 'email_gate' ? 'block' : 'none'; ?>;">
                    <div class="premium-card-header">
                        <h2>Email Gate Settings</h2>
                        <p>Configure email collection and social media unlock options</p>
                    </div>
                    <div class="premium-card-body">
                        <div class="premium-form-group">
                            <label class="premium-label">Gate Title</label>
                            <input type="text" name="email_gate_title" class="premium-input" 
                                   value="<?php echo esc_attr(premium_content_get_option('email_gate_title', 'Unlock This Content')); ?>" 
                                   placeholder="Unlock This Content">
                        </div>

                        <div class="premium-form-group">
                            <label class="premium-label">Gate Description</label>
                            <textarea name="email_gate_description" class="premium-textarea" rows="3"
                                      placeholder="Get instant access to this article and all premium content for 30 days."><?php echo esc_textarea(premium_content_get_option('email_gate_description', 'Get instant access to this article and all premium content for 30 days.')); ?></textarea>
                        </div>

                        <div class="premium-form-group">
                            <label class="premium-label">Contact Form 7</label>
                            <select name="cf7_form_id" class="premium-select">
                                <option value="">Select Form</option>
                                <?php foreach ($cf7_forms as $form_id => $form_title): ?>
                                    <option value="<?php echo esc_attr($form_id); ?>" 
                                            <?php selected(premium_content_get_option('cf7_form_id'), $form_id); ?>>
                                        <?php echo esc_html($form_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($cf7_forms)): ?>
                                <p class="premium-description" style="color: #d63638;">
                                    Contact Form 7 is not installed or no forms exist. 
                                    <button type="button" id="premium-create-cf7-form" class="button">Create Form</button>
                                </p>
                            <?php endif; ?>
                        </div>

                        <hr style="margin: 30px 0; border: none; border-top: 1px solid #e5e7eb;">

                        <div class="premium-form-group">
                            <label class="premium-checkbox-label">
                                <input type="checkbox" name="email_gate_social_enabled" value="1" id="social-toggle"
                                       <?php checked(premium_content_get_option('email_gate_social_enabled', '0'), '1'); ?>>
                                <strong>Enable Social Media Unlock Option</strong>
                            </label>
                            <p class="premium-description">Allow users to unlock content by following on social media OR providing email (not both required)</p>
                        </div>

                        <div id="social-media-settings" style="display: <?php echo premium_content_get_option('email_gate_social_enabled', '0') === '1' ? 'block' : 'none'; ?>; padding: 20px; background: #f9fafb; border-radius: 8px; margin-top: 20px;">
                            <h3 style="margin-top: 0;">Social Media Links</h3>
                            <p style="color: #6b7280; font-size: 14px;">Add your social media URLs. Only filled URLs will be shown as unlock options.</p>

                            <div class="premium-form-group">
                                <label class="premium-label">
                                    <svg viewBox="0 0 24 24" fill="#1877f2" style="width: 20px; height: 20px; vertical-align: middle; margin-right: 5px;">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                    Facebook Page URL
                                </label>
                                <input type="url" name="social_facebook_url" class="premium-input" 
                                       value="<?php echo esc_url(premium_content_get_option('social_facebook_url', '')); ?>" 
                                       placeholder="https://facebook.com/yourpage">
                            </div>

                            <div class="premium-form-group">
                                <label class="premium-label">
                                    <svg viewBox="0 0 24 24" fill="#1da1f2" style="width: 20px; height: 20px; vertical-align: middle; margin-right: 5px;">
                                        <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                                    </svg>
                                    Twitter Profile URL
                                </label>
                                <input type="url" name="social_twitter_url" class="premium-input" 
                                       value="<?php echo esc_url(premium_content_get_option('social_twitter_url', '')); ?>" 
                                       placeholder="https://twitter.com/yourprofile">
                            </div>

                            <div class="premium-form-group">
                                <label class="premium-label">
                                    <svg viewBox="0 0 24 24" fill="url(#instagram-gradient)" style="width: 20px; height: 20px; vertical-align: middle; margin-right: 5px;">
                                        <defs>
                                            <linearGradient id="instagram-gradient" x1="0%" y1="100%" x2="100%" y2="0%">
                                                <stop offset="0%" style="stop-color:#f09433;stop-opacity:1" />
                                                <stop offset="25%" style="stop-color:#e6683c;stop-opacity:1" />
                                                <stop offset="50%" style="stop-color:#dc2743;stop-opacity:1" />
                                                <stop offset="75%" style="stop-color:#cc2366;stop-opacity:1" />
                                                <stop offset="100%" style="stop-color:#bc1888;stop-opacity:1" />
                                            </linearGradient>
                                        </defs>
                                        <path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/>
                                    </svg>
                                    Instagram Profile URL
                                </label>
                                <input type="url" name="social_instagram_url" class="premium-input" 
                                       value="<?php echo esc_url(premium_content_get_option('social_instagram_url', '')); ?>" 
                                       placeholder="https://instagram.com/yourprofile">
                            </div>

                            <div class="premium-form-group">
                                <label class="premium-label">
                                    <svg viewBox="0 0 24 24" fill="#0077b5" style="width: 20px; height: 20px; vertical-align: middle; margin-right: 5px;">
                                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                    </svg>
                                    LinkedIn Page URL
                                </label>
                                <input type="url" name="social_linkedin_url" class="premium-input" 
                                       value="<?php echo esc_url(premium_content_get_option('social_linkedin_url', '')); ?>" 
                                       placeholder="https://linkedin.com/company/yourcompany">
                            </div>

                            <div class="premium-form-group">
                                <label class="premium-label">Unlock Delay (seconds)</label>
                                <input type="number" name="social_unlock_delay" class="premium-input" 
                                       value="<?php echo esc_attr(premium_content_get_option('social_unlock_delay', 4)); ?>" 
                                       min="3" max="10" step="1">
                                <p class="premium-description">Delay before unlocking content after social media click (3-10 seconds)</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="premium-form-actions">
                    <button type="submit" name="submit" class="button button-primary button-hero">Save Settings</button>
                </div>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Toggle social settings visibility
            $('#social-toggle').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#social-media-settings').slideDown(300);
                } else {
                    $('#social-media-settings').slideUp(300);
                }
            });

            // Toggle email gate settings visibility
            $('input[name="access_mode"]').on('change', function() {
                $('.email-gate-settings, .metered-settings').hide();
                if ($(this).val() === 'email_gate') {
                    $('.email-gate-settings').slideDown(300);
                } else if ($(this).val() === 'metered') {
                    $('.metered-settings').slideDown(300);
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Render plans page
     */
    public function render_plans() {
        $plans = Premium_Content_Subscription_Manager::get_plans();
        ?>
        <div class="wrap premium-admin-wrap">
            <h1 class="premium-page-title">
                <span class="dashicons dashicons-cart"></span>
                Subscription Plans
            </h1>

            <div class="premium-plans-grid">
                <?php foreach ($plans as $plan): 
                    $features = json_decode($plan->features, true);
                ?>
                    <div class="premium-plan-card">
                        <div class="plan-header">
                            <h3><?php echo esc_html($plan->name); ?></h3>
                            <div class="plan-price">
                                <span class="currency">$</span>
                                <span class="amount"><?php echo number_format($plan->price, 0); ?></span>
                                <?php if ($plan->interval !== 'lifetime'): ?>
                                    <span class="period">/<?php echo esc_html($plan->interval); ?></span>
                                <?php else: ?>
                                    <span class="period">one-time</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="plan-body">
                            <p class="plan-description"><?php echo esc_html($plan->description); ?></p>
                            <?php if ($features): ?>
                                <ul class="plan-features">
                                    <?php foreach ($features as $feature): ?>
                                        <li><?php echo esc_html($feature); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                        <div class="plan-footer">
                            <span class="plan-status status-<?php echo esc_attr($plan->status); ?>">
                                <?php echo esc_html(ucfirst($plan->status)); ?>
                            </span>
                            <div class="plan-actions">
                                <a href="#" class="button button-small">Edit</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render subscribers page
     */
    public function render_subscribers() {
        global $wpdb;
        
        $subscriptions_table = $wpdb->prefix . 'premium_subscriptions';
        $plans_table = $wpdb->prefix . 'premium_plans';
        
        // Get all subscriptions with plan info
        $subscriptions = $wpdb->get_results("
            SELECT s.*, p.name as plan_name, u.user_email, u.display_name
            FROM $subscriptions_table s
            LEFT JOIN $plans_table p ON s.plan_id = p.id
            LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
            ORDER BY s.created_at DESC
            LIMIT 50
        ");
        
        ?>
        <div class="wrap premium-admin-wrap">
            <h1 class="premium-page-title">
                <span class="dashicons dashicons-groups"></span>
                Subscribers
            </h1>

            <div class="premium-card">
                <div class="premium-card-header">
                    <h2>Active Subscriptions</h2>
                    <p>Manage your subscribers and their subscriptions</p>
                </div>
                <div class="premium-card-body">
                    <?php if (empty($subscriptions)): ?>
                        <p style="text-align: center; padding: 40px 20px; color: #6b7280;">
                            No subscriptions yet. Subscribers will appear here once they purchase a plan.
                        </p>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Subscriber</th>
                                    <th>Email</th>
                                    <th>Plan</th>
                                    <th>Status</th>
                                    <th>Started</th>
                                    <th>Expires</th>
                                    <th>Method</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subscriptions as $sub): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html($sub->display_name ?: 'User #' . $sub->user_id); ?></strong>
                                        </td>
                                        <td><?php echo esc_html($sub->user_email); ?></td>
                                        <td><?php echo esc_html($sub->plan_name); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo esc_attr($sub->status); ?>">
                                                <?php echo esc_html(ucfirst($sub->status)); ?>
                                            </span>
                                        </td>
                                        <td><?php echo esc_html(date('M d, Y', strtotime($sub->started_at))); ?></td>
                                        <td>
                                            <?php 
                                            if ($sub->expires_at) {
                                                echo esc_html(date('M d, Y', strtotime($sub->expires_at)));
                                            } else {
                                                echo 'Lifetime';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo esc_html(ucfirst($sub->payment_method)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <style>
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-badge.status-active {
            background: #dcfce7;
            color: #166534;
        }
        .status-badge.status-cancelled {
            background: #fef3c7;
            color: #92400e;
        }
        .status-badge.status-expired {
            background: #fee2e2;
            color: #991b1b;
        }
        </style>
        <?php
    }

    /**
     * Render payment settings
     */
    public function render_payment_settings() {
        if (isset($_POST['submit']) && check_admin_referer('premium_content_payments')) {
            // Save Stripe settings
            update_option('premium_content_stripe_enabled', isset($_POST['stripe_enabled']) ? '1' : '0');
            update_option('premium_content_stripe_test_mode', isset($_POST['stripe_test_mode']) ? '1' : '0');
            update_option('premium_content_stripe_test_publishable_key', sanitize_text_field($_POST['stripe_test_publishable_key']));
            update_option('premium_content_stripe_test_secret_key', sanitize_text_field($_POST['stripe_test_secret_key']));
            update_option('premium_content_stripe_live_publishable_key', sanitize_text_field($_POST['stripe_live_publishable_key']));
            update_option('premium_content_stripe_live_secret_key', sanitize_text_field($_POST['stripe_live_secret_key']));
            
            // Save PayPal settings
            update_option('premium_content_paypal_enabled', isset($_POST['paypal_enabled']) ? '1' : '0');
            update_option('premium_content_paypal_test_mode', isset($_POST['paypal_test_mode']) ? '1' : '0');
            update_option('premium_content_paypal_client_id', sanitize_text_field($_POST['paypal_client_id']));
            update_option('premium_content_paypal_client_secret', sanitize_text_field($_POST['paypal_client_secret']));
            
            echo '<div class="notice notice-success"><p>Payment settings saved!</p></div>';
        }
        ?>
        <div class="wrap premium-admin-wrap">
            <h1 class="premium-page-title">
                <span class="dashicons dashicons-money"></span>
                Payment Settings
            </h1>

            <form method="post" class="premium-settings-form">
                <?php wp_nonce_field('premium_content_payments'); ?>

                <!-- Stripe Settings -->
                <div class="premium-card">
                    <div class="premium-card-header">
                        <h2>Stripe Settings</h2>
                        <p>Configure Stripe payment gateway</p>
                    </div>
                    <div class="premium-card-body">
                        <div class="premium-form-group">
                            <label class="premium-checkbox-label">
                                <input type="checkbox" name="stripe_enabled" value="1" 
                                       <?php checked(premium_content_get_option('stripe_enabled', '0'), '1'); ?>>
                                Enable Stripe
                            </label>
                        </div>

                        <div class="premium-form-group">
                            <label class="premium-checkbox-label">
                                <input type="checkbox" name="stripe_test_mode" value="1" 
                                       <?php checked(premium_content_get_option('stripe_test_mode', '1'), '1'); ?>>
                                Test Mode
                            </label>
                        </div>

                        <hr style="margin: 20px 0;">

                        <h3>Test Keys</h3>
                        <div class="premium-form-group">
                            <label class="premium-label">Test Publishable Key</label>
                            <input type="text" name="stripe_test_publishable_key" class="premium-input" 
                                   value="<?php echo esc_attr(premium_content_get_option('stripe_test_publishable_key', '')); ?>" 
                                   placeholder="pk_test_...">
                        </div>

                        <div class="premium-form-group">
                            <label class="premium-label">Test Secret Key</label>
                            <input type="password" name="stripe_test_secret_key" class="premium-input" 
                                   value="<?php echo esc_attr(premium_content_get_option('stripe_test_secret_key', '')); ?>" 
                                   placeholder="sk_test_...">
                        </div>

                        <h3>Live Keys</h3>
                        <div class="premium-form-group">
                            <label class="premium-label">Live Publishable Key</label>
                            <input type="text" name="stripe_live_publishable_key" class="premium-input" 
                                   value="<?php echo esc_attr(premium_content_get_option('stripe_live_publishable_key', '')); ?>" 
                                   placeholder="pk_live_...">
                        </div>

                        <div class="premium-form-group">
                            <label class="premium-label">Live Secret Key</label>
                            <input type="password" name="stripe_live_secret_key" class="premium-input" 
                                   value="<?php echo esc_attr(premium_content_get_option('stripe_live_secret_key', '')); ?>" 
                                   placeholder="sk_live_...">
                        </div>

                        <button type="button" id="test-stripe-connection" class="button">Test Connection</button>
                    </div>
                </div>

                <!-- PayPal Settings -->
                <div class="premium-card">
                    <div class="premium-card-header">
                        <h2>PayPal Settings</h2>
                        <p>Configure PayPal payment gateway</p>
                    </div>
                    <div class="premium-card-body">
                        <div class="premium-form-group">
                            <label class="premium-checkbox-label">
                                <input type="checkbox" name="paypal_enabled" value="1" 
                                       <?php checked(premium_content_get_option('paypal_enabled', '0'), '1'); ?>>
                                Enable PayPal
                            </label>
                        </div>

                        <div class="premium-form-group">
                            <label class="premium-checkbox-label">
                                <input type="checkbox" name="paypal_test_mode" value="1" 
                                       <?php checked(premium_content_get_option('paypal_test_mode', '1'), '1'); ?>>
                                Sandbox Mode
                            </label>
                        </div>

                        <div class="premium-form-group">
                            <label class="premium-label">Client ID</label>
                            <input type="text" name="paypal_client_id" class="premium-input" 
                                   value="<?php echo esc_attr(premium_content_get_option('paypal_client_id', '')); ?>">
                        </div>

                        <div class="premium-form-group">
                            <label class="premium-label">Client Secret</label>
                            <input type="password" name="paypal_client_secret" class="premium-input" 
                                   value="<?php echo esc_attr(premium_content_get_option('paypal_client_secret', '')); ?>">
                        </div>

                        <button type="button" id="test-paypal-connection" class="button">Test Connection</button>
                    </div>
                </div>

                <div class="premium-form-actions">
                    <button type="submit" name="submit" class="button button-primary button-hero">Save Settings</button>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Render general settings
     */
    public function render_general_settings() {
        if (isset($_POST['submit']) && check_admin_referer('premium_content_settings')) {
            // Save text settings
            update_option('premium_content_paywall_title', sanitize_text_field($_POST['paywall_title']));
            update_option('premium_content_paywall_description', sanitize_textarea_field($_POST['paywall_description']));
            update_option('premium_content_counter_text', sanitize_text_field($_POST['counter_text']));
            update_option('premium_content_limit_reached_text', sanitize_textarea_field($_POST['limit_reached_text']));
            update_option('premium_content_exclude_admins', isset($_POST['exclude_admins']) ? '1' : '0');
            update_option('premium_content_debug_mode', isset($_POST['debug_mode']) ? '1' : '0');
            
            echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
        }
        ?>
        <div class="wrap premium-admin-wrap">
            <h1 class="premium-page-title">
                <span class="dashicons dashicons-admin-generic"></span>
                General Settings
            </h1>

            <form method="post" class="premium-settings-form">
                <?php wp_nonce_field('premium_content_settings'); ?>

                <div class="premium-card">
                    <div class="premium-card-header">
                        <h2>Paywall Text Settings</h2>
                        <p>Customize the text shown in your paywalls</p>
                    </div>
                    <div class="premium-card-body">
                        <div class="premium-form-group">
                            <label class="premium-label">Paywall Title</label>
                            <input type="text" name="paywall_title" class="premium-input" 
                                   value="<?php echo esc_attr(premium_content_get_option('paywall_title', 'Subscribe to Continue Reading')); ?>">
                            <p class="premium-description">Main title shown in premium paywall</p>
                        </div>

                        <div class="premium-form-group">
                            <label class="premium-label">Paywall Description</label>
                            <textarea name="paywall_description" class="premium-textarea" rows="3"><?php echo esc_textarea(premium_content_get_option('paywall_description', 'Get unlimited access to all premium content')); ?></textarea>
                            <p class="premium-description">Description shown in premium paywall</p>
                        </div>

                        <div class="premium-form-group">
                            <label class="premium-label">Counter Text</label>
                            <input type="text" name="counter_text" class="premium-input" 
                                   value="<?php echo esc_attr(premium_content_get_option('counter_text', 'You have {remaining} free articles remaining')); ?>">
                            <p class="premium-description">Use {remaining} as placeholder for article count</p>
                        </div>

                        <div class="premium-form-group">
                            <label class="premium-label">Limit Reached Text</label>
                            <textarea name="limit_reached_text" class="premium-textarea" rows="2"><?php echo esc_textarea(premium_content_get_option('limit_reached_text', "You've reached your free article limit for this month")); ?></textarea>
                            <p class="premium-description">Message shown when metered limit is reached</p>
                        </div>
                    </div>
                </div>

                <div class="premium-card">
                    <div class="premium-card-header">
                        <h2>Advanced Options</h2>
                    </div>
                    <div class="premium-card-body">
                        <div class="premium-form-group">
                            <label class="premium-checkbox-label">
                                <input type="checkbox" name="exclude_admins" value="1" 
                                       <?php checked(premium_content_get_option('exclude_admins', '1'), '1'); ?>>
                                Exclude administrators from paywall
                            </label>
                            <p class="premium-description">Admins will always have full access to content</p>
                        </div>

                        <div class="premium-form-group">
                            <label class="premium-checkbox-label">
                                <input type="checkbox" name="debug_mode" value="1" 
                                       <?php checked(premium_content_get_option('debug_mode', '0'), '1'); ?>>
                                Enable debug mode
                            </label>
                            <p class="premium-description">Log detailed information for troubleshooting</p>
                        </div>
                    </div>
                </div>

                <div class="premium-form-actions">
                    <button type="submit" name="submit" class="button button-primary button-hero">Save Settings</button>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Test Stripe connection
     */
    public function ajax_test_stripe() {
        check_ajax_referer('premium_content_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $result = Premium_Content_Stripe_Handler::test_connection();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Test PayPal connection
     */
    public function ajax_test_paypal() {
        check_ajax_referer('premium_content_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $result = Premium_Content_PayPal_Handler::test_connection();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
}