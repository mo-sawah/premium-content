<?php
/**
 * Handles all admin functionality and settings pages
 */
class Premium_Content_Admin {

    /**
     * Render Payment Gateways Page
     */
    public function render_payments_page() {
        if (isset($_POST['premium_save_payments']) && check_admin_referer('premium_payment_settings')) {
            $this->save_payment_settings();
            echo '<div class="notice notice-success"><p>Payment settings saved!</p></div>';
        }

        // Stripe settings
        $stripe_enabled = premium_content_get_option('stripe_enabled', '0');
        $stripe_test_mode = premium_content_get_option('stripe_test_mode', '1');
        $stripe_test_pk = premium_content_get_option('stripe_test_publishable_key', '');
        $stripe_test_sk = premium_content_get_option('stripe_test_secret_key', '');
        $stripe_test_webhook = premium_content_get_option('stripe_test_webhook_secret', '');
        $stripe_live_pk = premium_content_get_option('stripe_live_publishable_key', '');
        $stripe_live_sk = premium_content_get_option('stripe_live_secret_key', '');
        $stripe_live_webhook = premium_content_get_option('stripe_live_webhook_secret', '');

        // PayPal settings
        $paypal_enabled = premium_content_get_option('paypal_enabled', '0');
        $paypal_test_mode = premium_content_get_option('paypal_test_mode', '1');
        $paypal_client_id = premium_content_get_option('paypal_client_id', '');
        $paypal_client_secret = premium_content_get_option('paypal_client_secret', '');

        ?>
        <div class="wrap premium-admin-wrap">
            <h1 class="premium-page-title">
                <span class="dashicons dashicons-cart"></span>
                Payment Gateways
            </h1>

            <form method="post" class="premium-settings-form">
                <?php wp_nonce_field('premium_payment_settings'); ?>

                <!-- Stripe Settings -->
                <div class="premium-card">
                    <div class="premium-card-header">
                        <h2>Stripe Configuration</h2>
                        <label class="premium-toggle">
                            <input type="checkbox" name="stripe_enabled" value="1" <?php checked($stripe_enabled, '1'); ?>>
                            <span>Enable Stripe</span>
                        </label>
                    </div>
                    <div class="premium-card-body" style="<?php echo $stripe_enabled !== '1' ? 'opacity: 0.5;' : ''; ?>">
                        <div class="premium-form-group">
                            <label class="premium-checkbox-label">
                                <input type="checkbox" name="stripe_test_mode" value="1" <?php checked($stripe_test_mode, '1'); ?>>
                                <span>Test Mode</span>
                            </label>
                            <p class="premium-description">Use test API keys for development</p>
                        </div>

                        <h3 style="margin: 20px 0 15px 0; border-bottom: 1px solid #ddd; padding-bottom: 10px;">Test API Keys</h3>
                        <div class="premium-form-group">
                            <label class="premium-label">Test Publishable Key</label>
                            <input type="text" name="stripe_test_publishable_key" value="<?php echo esc_attr($stripe_test_pk); ?>" class="premium-input" placeholder="pk_test_...">
                        </div>
                        <div class="premium-form-group">
                            <label class="premium-label">Test Secret Key</label>
                            <input type="password" name="stripe_test_secret_key" value="<?php echo esc_attr($stripe_test_sk); ?>" class="premium-input" placeholder="sk_test_...">
                        </div>
                        <div class="premium-form-group">
                            <label class="premium-label">Test Webhook Secret</label>
                            <input type="password" name="stripe_test_webhook_secret" value="<?php echo esc_attr($stripe_test_webhook); ?>" class="premium-input" placeholder="whsec_...">
                            <p class="premium-description">
                                Webhook URL: <code><?php echo admin_url('admin-ajax.php?action=premium_stripe_webhook'); ?></code>
                            </p>
                        </div>

                        <h3 style="margin: 20px 0 15px 0; border-bottom: 1px solid #ddd; padding-bottom: 10px;">Live API Keys</h3>
                        <div class="premium-form-group">
                            <label class="premium-label">Live Publishable Key</label>
                            <input type="text" name="stripe_live_publishable_key" value="<?php echo esc_attr($stripe_live_pk); ?>" class="premium-input" placeholder="pk_live_...">
                        </div>
                        <div class="premium-form-group">
                            <label class="premium-label">Live Secret Key</label>
                            <input type="password" name="stripe_live_secret_key" value="<?php echo esc_attr($stripe_live_sk); ?>" class="premium-input" placeholder="sk_live_...">
                        </div>
                        <div class="premium-form-group">
                            <label class="premium-label">Live Webhook Secret</label>
                            <input type="password" name="stripe_live_webhook_secret" value="<?php echo esc_attr($stripe_live_webhook); ?>" class="premium-input" placeholder="whsec_...">
                        </div>

                        <div class="premium-form-group">
                            <button type="button" id="test-stripe" class="button">Test Connection</button>
                            <span id="stripe-test-result"></span>
                        </div>

                        <div class="premium-alert premium-alert-info">
                            <strong>Setup Instructions:</strong>
                            <ol style="margin: 10px 0 0 20px;">
                                <li>Go to <a href="https://dashboard.stripe.com/apikeys" target="_blank">Stripe Dashboard</a></li>
                                <li>Copy your API keys</li>
                                <li>Create a webhook endpoint with URL above</li>
                                <li>Select events: checkout.session.completed, customer.subscription.*</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- PayPal Settings -->
                <div class="premium-card">
                    <div class="premium-card-header">
                        <h2>PayPal Configuration</h2>
                        <label class="premium-toggle">
                            <input type="checkbox" name="paypal_enabled" value="1" <?php checked($paypal_enabled, '1'); ?>>
                            <span>Enable PayPal</span>
                        </label>
                    </div>
                    <div class="premium-card-body" style="<?php echo $paypal_enabled !== '1' ? 'opacity: 0.5;' : ''; ?>">
                        <div class="premium-form-group">
                            <label class="premium-checkbox-label">
                                <input type="checkbox" name="paypal_test_mode" value="1" <?php checked($paypal_test_mode, '1'); ?>>
                                <span>Sandbox Mode</span>
                            </label>
                            <p class="premium-description">Use sandbox for testing</p>
                        </div>

                        <div class="premium-form-group">
                            <label class="premium-label">Client ID</label>
                            <input type="text" name="paypal_client_id" value="<?php echo esc_attr($paypal_client_id); ?>" class="premium-input">
                            <p class="premium-description">Works for both sandbox and live</p>
                        </div>

                        <div class="premium-form-group">
                            <label class="premium-label">Client Secret</label>
                            <input type="password" name="paypal_client_secret" value="<?php echo esc_attr($paypal_client_secret); ?>" class="premium-input">
                        </div>

                        <div class="premium-form-group">
                            <button type="button" id="test-paypal" class="button">Test Connection</button>
                            <span id="paypal-test-result"></span>
                        </div>

                        <div class="premium-alert premium-alert-info">
                            <strong>Setup Instructions:</strong>
                            <ol style="margin: 10px 0 0 20px;">
                                <li>Go to <a href="https://developer.paypal.com/dashboard/" target="_blank">PayPal Developer</a></li>
                                <li>Create an app (sandbox or live)</li>
                                <li>Copy Client ID and Secret</li>
                                <li>Configure webhook URL: <code><?php echo admin_url('admin-ajax.php?action=premium_paypal_webhook'); ?></code></li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="premium-form-actions">
                    <button type="submit" name="premium_save_payments" class="button button-primary button-large">Save Payment Settings</button>
                </div>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#test-stripe').on('click', function() {
                var $btn = $(this);
                var $result = $('#stripe-test-result');
                $btn.prop('disabled', true).text('Testing...');
                $result.html('');

                $.post(ajaxurl, {
                    action: 'premium_test_stripe',
                    nonce: '<?php echo wp_create_nonce('premium_content_admin'); ?>'
                }, function(response) {
                    $btn.prop('disabled', false).text('Test Connection');
                    if (response.success) {
                        $result.html('<span style="color: #00a32a; font-weight: bold;">✓ ' + response.data.message + '</span>');
                    } else {
                        $result.html('<span style="color: #d63638; font-weight: bold;">✗ ' + response.data + '</span>');
                    }
                });
            });

            $('#test-paypal').on('click', function() {
                var $btn = $(this);
                var $result = $('#paypal-test-result');
                $btn.prop('disabled', true).text('Testing...');
                $result.html('');

                $.post(ajaxurl, {
                    action: 'premium_test_paypal',
                    nonce: '<?php echo wp_create_nonce('premium_content_admin'); ?>'
                }, function(response) {
                    $btn.prop('disabled', false).text('Test Connection');
                    if (response.success) {
                        $result.html('<span style="color: #00a32a; font-weight: bold;">✓ ' + response.data.message + '</span>');
                    } else {
                        $result.html('<span style="color: #d63638; font-weight: bold;">✗ ' + response.data + '</span>');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Save payment settings
     */
    private function save_payment_settings() {
        // Stripe
        premium_content_update_option('stripe_enabled', isset($_POST['stripe_enabled']) ? '1' : '0');
        premium_content_update_option('stripe_test_mode', isset($_POST['stripe_test_mode']) ? '1' : '0');
        premium_content_update_option('stripe_test_publishable_key', sanitize_text_field($_POST['stripe_test_publishable_key']));
        premium_content_update_option('stripe_test_secret_key', sanitize_text_field($_POST['stripe_test_secret_key']));
        premium_content_update_option('stripe_test_webhook_secret', sanitize_text_field($_POST['stripe_test_webhook_secret']));
        premium_content_update_option('stripe_live_publishable_key', sanitize_text_field($_POST['stripe_live_publishable_key']));
        premium_content_update_option('stripe_live_secret_key', sanitize_text_field($_POST['stripe_live_secret_key']));
        premium_content_update_option('stripe_live_webhook_secret', sanitize_text_field($_POST['stripe_live_webhook_secret']));

        // PayPal
        premium_content_update_option('paypal_enabled', isset($_POST['paypal_enabled']) ? '1' : '0');
        premium_content_update_option('paypal_test_mode', isset($_POST['paypal_test_mode']) ? '1' : '0');
        premium_content_update_option('paypal_client_id', sanitize_text_field($_POST['paypal_client_id']));
        premium_content_update_option('paypal_client_secret', sanitize_text_field($_POST['paypal_client_secret']));
    }

    /**
     * AJAX: Test Stripe connection
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
            wp_send_json_error($result['message']);
        }
    }

    /**
     * AJAX: Test PayPal connection
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
            wp_send_json_error($result['message']);
        }
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_notices', array($this, 'admin_notices'));

        // In Premium_Content_Admin __construct(), add:
        add_action('wp_ajax_premium_test_stripe', array($this, 'ajax_test_stripe'));
        add_action('wp_ajax_premium_test_paypal', array($this, 'ajax_test_paypal'));
    }

    /**
     * Add admin menu and submenus
     */
    public function add_admin_menu() {
        add_menu_page(
            'Premium Content',
            'Premium Content',
            'manage_options',
            'premium-content',
            array($this, 'render_dashboard_page'),
            'dashicons-lock',
            30
        );

        add_submenu_page('premium-content', 'Dashboard', 'Dashboard', 'manage_options', 'premium-content', array($this, 'render_dashboard_page'));
        add_submenu_page('premium-content', 'Access Control', 'Access Control', 'manage_options', 'premium-content-access', array($this, 'render_access_control_page'));
        add_submenu_page('premium-content', 'Subscription Plans', 'Plans', 'manage_options', 'premium-content-plans', array($this, 'render_plans_page'));
        add_submenu_page('premium-content', 'Form Settings', 'Form Settings', 'manage_options', 'premium-content-form', array($this, 'render_form_page'));
        add_submenu_page('premium-content', 'Subscribers', 'Subscribers', 'manage_options', 'premium-content-subscribers', array($this, 'render_subscribers_page'));
        add_submenu_page('premium-content', 'Email Collection', 'Emails', 'manage_options', 'premium-content-emails', array($this, 'render_emails_page'));
        add_submenu_page('premium-content', 'Settings', 'Settings', 'manage_options', 'premium-content-settings', array($this, 'render_settings_page'));
        add_submenu_page('premium-content', 'Payment Gateways', 'Payments', 'manage_options', 'premium-content-payments', array($this, 'render_payments_page'));
    }

    /**
     * Display admin notices
     */
    public function admin_notices() {
        if (!Premium_Content_CF7_Handler::is_cf7_active() && get_current_screen()->parent_base === 'premium-content') {
            echo '<div class="notice notice-warning"><p><strong>Premium Content:</strong> Contact Form 7 plugin is not installed or activated. <a href="' . admin_url('plugin-install.php?s=contact+form+7&tab=search&type=term') . '">Install Now</a></p></div>';
        }
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('premium_content_options', 'premium_content_access_mode');
        register_setting('premium_content_options', 'premium_content_metered_limit');
    }

    /**
     * Render Dashboard Page
     */
    public function render_dashboard_page() {
        $subscription_stats = Premium_Content_Subscription_Manager::get_statistics();
        $view_stats = Premium_Content_Metered_Paywall::get_view_statistics();
        $email_stats = Premium_Content_CF7_Handler::get_email_statistics();
        $access_mode = premium_content_get_option('access_mode', 'free');
        
        $mode_labels = array('free' => 'Free', 'email_gate' => 'Email Gate', 'metered' => 'Metered', 'premium' => 'Premium');
        ?>
        <div class="wrap premium-admin-wrap">
            <h1 class="premium-page-title">
                <span class="dashicons dashicons-lock"></span>
                Premium Content Dashboard
            </h1>

            <div class="premium-dashboard-grid">
                <div class="premium-stat-card">
                    <div class="premium-stat-icon premium-stat-subscribers"><span class="dashicons dashicons-groups"></span></div>
                    <div class="premium-stat-content">
                        <div class="premium-stat-number"><?php echo number_format($subscription_stats['total_active']); ?></div>
                        <div class="premium-stat-label">Active Subscribers</div>
                    </div>
                </div>
                <div class="premium-stat-card">
                    <div class="premium-stat-icon premium-stat-revenue"><span class="dashicons dashicons-chart-line"></span></div>
                    <div class="premium-stat-content">
                        <div class="premium-stat-number">$<?php echo number_format($subscription_stats['total_revenue'], 2); ?></div>
                        <div class="premium-stat-label">Total Revenue</div>
                    </div>
                </div>
                <div class="premium-stat-card">
                    <div class="premium-stat-icon premium-stat-views"><span class="dashicons dashicons-visibility"></span></div>
                    <div class="premium-stat-content">
                        <div class="premium-stat-number"><?php echo number_format($view_stats['total_views']); ?></div>
                        <div class="premium-stat-label">Article Views (This Month)</div>
                    </div>
                </div>
                <div class="premium-stat-card">
                    <div class="premium-stat-icon premium-stat-emails"><span class="dashicons dashicons-email"></span></div>
                    <div class="premium-stat-content">
                        <div class="premium-stat-number"><?php echo number_format($email_stats['total_emails']); ?></div>
                        <div class="premium-stat-label">Collected Emails</div>
                    </div>
                </div>
            </div>

            <div class="premium-dashboard-section">
                <div class="premium-section-header"><h2>Current Configuration</h2></div>
                <div class="premium-status-grid">
                    <div class="premium-status-item">
                        <span class="status-label">Access Mode:</span>
                        <span class="status-value status-badge status-<?php echo esc_attr($access_mode); ?>">
                            <?php echo isset($mode_labels[$access_mode]) ? $mode_labels[$access_mode] : ucfirst($access_mode); ?>
                        </span>
                    </div>
                    <?php if ($access_mode === 'metered'): ?>
                    <div class="premium-status-item">
                        <span class="status-label">Article Limit:</span>
                        <span class="status-value"><?php echo premium_content_get_option('metered_limit', 3); ?> articles/month</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="premium-dashboard-section">
                <div class="premium-section-header"><h2>Quick Actions</h2></div>
                <div class="premium-quick-actions">
                    <a href="<?php echo admin_url('admin.php?page=premium-content-access'); ?>" class="premium-action-button">
                        <span class="dashicons dashicons-admin-settings"></span>Configure Access Control
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=premium-content-plans'); ?>" class="premium-action-button">
                        <span class="dashicons dashicons-cart"></span>Manage Plans
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=premium-content-subscribers'); ?>" class="premium-action-button">
                        <span class="dashicons dashicons-groups"></span>View Subscribers
                    </a>
                    <a href="<?php echo get_permalink(get_option('premium_content_page_pricing')); ?>" class="premium-action-button" target="_blank">
                        <span class="dashicons dashicons-external"></span>Preview Pricing Page
                    </a>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render Access Control Page
     */
    public function render_access_control_page() {
        if (isset($_POST['premium_save_access']) && check_admin_referer('premium_access_settings')) {
            $this->save_access_settings();
            echo '<div class="notice notice-success"><p>Access settings saved successfully!</p></div>';
        }

        $access_mode = premium_content_get_option('access_mode', 'free');
        $metered_limit = premium_content_get_option('metered_limit', 3);
        $metered_period = premium_content_get_option('metered_period', 'monthly');
        $metered_show_counter = premium_content_get_option('metered_show_counter', '1');
        $metered_counter_position = premium_content_get_option('metered_counter_position', 'top');
        $exclude_admins = premium_content_get_option('exclude_admins', '1');
        $allowed_post_types = premium_content_get_option('allowed_post_types', array('post'));
        $allowed_categories = premium_content_get_option('allowed_categories', array());
        
        if (!is_array($allowed_post_types)) {
            $allowed_post_types = array('post');
        }
        ?>
        <div class="wrap premium-admin-wrap">
            <h1 class="premium-page-title">
                <span class="dashicons dashicons-admin-settings"></span>
                Access Control Settings
            </h1>

            <form method="post" action="" class="premium-settings-form">
                <?php wp_nonce_field('premium_access_settings'); ?>

                <div class="premium-card">
                    <div class="premium-card-header">
                        <h2>Content Access Mode</h2>
                        <p>Choose how visitors access your premium content</p>
                    </div>
                    <div class="premium-card-body">
                        <div class="premium-mode-selector">
                            <label class="premium-mode-option <?php echo $access_mode === 'free' ? 'active' : ''; ?>">
                                <input type="radio" name="access_mode" value="free" <?php checked($access_mode, 'free'); ?>>
                                <div class="mode-content">
                                    <div class="mode-icon">🔓</div>
                                    <div class="mode-title">Free Access</div>
                                    <div class="mode-description">All content is freely accessible</div>
                                </div>
                            </label>
                            <label class="premium-mode-option <?php echo $access_mode === 'email_gate' ? 'active' : ''; ?>">
                                <input type="radio" name="access_mode" value="email_gate" <?php checked($access_mode, 'email_gate'); ?>>
                                <div class="mode-content">
                                    <div class="mode-icon">✉️</div>
                                    <div class="mode-title">Email Gate</div>
                                    <div class="mode-description">Require email, 30-day access</div>
                                </div>
                            </label>
                            <label class="premium-mode-option <?php echo $access_mode === 'metered' ? 'active' : ''; ?>">
                                <input type="radio" name="access_mode" value="metered" <?php checked($access_mode, 'metered'); ?>>
                                <div class="mode-content">
                                    <div class="mode-icon">📊</div>
                                    <div class="mode-title">Metered Paywall</div>
                                    <div class="mode-description">Limited free articles</div>
                                </div>
                            </label>
                            <label class="premium-mode-option <?php echo $access_mode === 'premium' ? 'active' : ''; ?>">
                                <input type="radio" name="access_mode" value="premium" <?php checked($access_mode, 'premium'); ?>>
                                <div class="mode-content">
                                    <div class="mode-icon">🔒</div>
                                    <div class="mode-title">Full Premium</div>
                                    <div class="mode-description">Requires subscription</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Email Gate Settings -->
                <div class="premium-card email-gate-settings" style="<?php echo $access_mode !== 'email_gate' ? 'display:none;' : ''; ?>">
                    <div class="premium-card-header"><h2>Email Gate Configuration</h2></div>
                    <div class="premium-card-body">
                        <div class="premium-alert premium-alert-info">
                            <strong>How It Works:</strong> Visitor submits email → Gets 30-day cookie → Access to all email-gated content
                        </div>
                        <h3 style="margin: 30px 0 15px 0; border-bottom: 1px solid #ddd; padding-bottom: 10px;">Content Targeting</h3>
                        <?php $this->render_targeting_fields($allowed_post_types, $allowed_categories); ?>
                    </div>
                </div>

                <!-- Metered Settings -->
                <div class="premium-card metered-settings" style="<?php echo $access_mode !== 'metered' ? 'display:none;' : ''; ?>">
                    <div class="premium-card-header"><h2>Metered Paywall Configuration</h2></div>
                    <div class="premium-card-body">
                        <div class="premium-form-row">
                            <div class="premium-form-group">
                                <label class="premium-label">Free Article Limit</label>
                                <input type="number" name="metered_limit" value="<?php echo esc_attr($metered_limit); ?>" min="1" max="100" class="premium-input">
                            </div>
                            <div class="premium-form-group">
                                <label class="premium-label">Reset Period</label>
                                <select name="metered_period" class="premium-select">
                                    <option value="monthly" <?php selected($metered_period, 'monthly'); ?>>Monthly</option>
                                    <option value="weekly" <?php selected($metered_period, 'weekly'); ?>>Weekly</option>
                                    <option value="daily" <?php selected($metered_period, 'daily'); ?>>Daily</option>
                                </select>
                            </div>
                        </div>
                        <div class="premium-form-group">
                            <label class="premium-checkbox-label">
                                <input type="checkbox" name="metered_show_counter" value="1" <?php checked($metered_show_counter, '1'); ?>>
                                <span>Show article counter banner</span>
                            </label>
                        </div>
                        <div class="premium-form-group counter-position" style="<?php echo $metered_show_counter !== '1' ? 'display:none;' : ''; ?>">
                            <label class="premium-label">Counter Position</label>
                            <select name="metered_counter_position" class="premium-select">
                                <option value="top" <?php selected($metered_counter_position, 'top'); ?>>Top</option>
                                <option value="bottom" <?php selected($metered_counter_position, 'bottom'); ?>>Bottom</option>
                                <option value="floating" <?php selected($metered_counter_position, 'floating'); ?>>Floating</option>
                            </select>
                        </div>
                        <h3 style="margin: 30px 0 15px 0; border-bottom: 1px solid #ddd; padding-bottom: 10px;">Content Targeting</h3>
                        <?php $this->render_targeting_fields($allowed_post_types, $allowed_categories); ?>
                    </div>
                </div>

                <!-- Premium Settings -->
                <div class="premium-card premium-settings" style="<?php echo $access_mode !== 'premium' ? 'display:none;' : ''; ?>">
                    <div class="premium-card-header"><h2>Full Premium Configuration</h2></div>
                    <div class="premium-card-body">
                        <div class="premium-alert premium-alert-info">
                            <strong>Full Premium:</strong> All content requires active subscription, no free access
                        </div>
                        <h3 style="margin: 30px 0 15px 0; border-bottom: 1px solid #ddd; padding-bottom: 10px;">Content Targeting</h3>
                        <?php $this->render_targeting_fields($allowed_post_types, $allowed_categories); ?>
                    </div>
                </div>

                <div class="premium-card">
                    <div class="premium-card-header"><h2>Additional Options</h2></div>
                    <div class="premium-card-body">
                        <div class="premium-form-group">
                            <label class="premium-checkbox-label">
                                <input type="checkbox" name="exclude_admins" value="1" <?php checked($exclude_admins, '1'); ?>>
                                <span>Exclude administrators from paywall</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="premium-form-actions">
                    <button type="submit" name="premium_save_access" class="button button-primary button-large">Save Settings</button>
                </div>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('input[name="access_mode"]').on('change', function() {
                $('.email-gate-settings, .metered-settings, .premium-settings').hide();
                var mode = $(this).val();
                if (mode === 'email_gate') $('.email-gate-settings').slideDown();
                else if (mode === 'metered') $('.metered-settings').slideDown();
                else if (mode === 'premium') $('.premium-settings').slideDown();
                $('.premium-mode-option').removeClass('active');
                $(this).closest('.premium-mode-option').addClass('active');
            });
            $('input[name="metered_show_counter"]').on('change', function() {
                $('.counter-position').toggle($(this).is(':checked'));
            });
        });
        </script>
        <?php
    }

    private function render_targeting_fields($allowed_post_types, $allowed_categories) {
        ?>
        <div class="premium-form-group">
            <label class="premium-label">Allowed Post Types</label>
            <?php
            $post_types = get_post_types(array('public' => true), 'objects');
            foreach ($post_types as $post_type):
                if (in_array($post_type->name, array('attachment', 'wp_block'))) continue;
            ?>
                <label class="premium-checkbox-label" style="display: block; margin-bottom: 8px;">
                    <input type="checkbox" name="allowed_post_types[]" value="<?php echo esc_attr($post_type->name); ?>" <?php checked(in_array($post_type->name, $allowed_post_types)); ?>>
                    <span><?php echo esc_html($post_type->label); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <div class="premium-form-group">
            <label class="premium-label">Allowed Categories (Posts Only)</label>
            <?php $categories = get_categories(array('hide_empty' => false));
            if (!empty($categories)): ?>
                <select name="allowed_categories[]" class="premium-select" multiple size="10" style="height: auto;">
                    <option value="">-- All Categories --</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo esc_attr($category->term_id); ?>" <?php echo in_array($category->term_id, $allowed_categories) ? 'selected' : ''; ?>>
                            <?php echo esc_html($category->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="premium-description">Hold Ctrl/Cmd to select multiple. Empty = all categories</p>
            <?php else: ?>
                <p>No categories found.</p>
            <?php endif; ?>
        </div>
        <?php
    }

    private function save_access_settings() {
        premium_content_update_option('access_mode', sanitize_text_field($_POST['access_mode']));
        premium_content_update_option('metered_limit', intval($_POST['metered_limit']));
        premium_content_update_option('metered_period', sanitize_text_field($_POST['metered_period']));
        premium_content_update_option('metered_show_counter', isset($_POST['metered_show_counter']) ? '1' : '0');
        premium_content_update_option('metered_counter_position', sanitize_text_field($_POST['metered_counter_position']));
        premium_content_update_option('exclude_admins', isset($_POST['exclude_admins']) ? '1' : '0');
        
        $allowed_post_types = isset($_POST['allowed_post_types']) && is_array($_POST['allowed_post_types']) ? array_map('sanitize_text_field', $_POST['allowed_post_types']) : array('post');
        premium_content_update_option('allowed_post_types', $allowed_post_types);
        
        $allowed_categories = isset($_POST['allowed_categories']) && is_array($_POST['allowed_categories']) ? array_map('intval', $_POST['allowed_categories']) : array();
        premium_content_update_option('allowed_categories', $allowed_categories);
    }

    public function render_plans_page() {
        if (isset($_POST['premium_save_plan']) && check_admin_referer('premium_plan_action')) {
            $this->save_plan();
            echo '<div class="notice notice-success"><p>Plan saved!</p></div>';
        }
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['plan_id']) && check_admin_referer('delete_plan_' . $_GET['plan_id'])) {
            Premium_Content_Subscription_Manager::delete_plan($_GET['plan_id']);
            echo '<div class="notice notice-success"><p>Plan deleted!</p></div>';
        }

        $plans = Premium_Content_Subscription_Manager::get_plans();
        $editing_plan = null;
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['plan_id'])) {
            $editing_plan = Premium_Content_Subscription_Manager::get_plan(intval($_GET['plan_id']));
        }
        ?>
        <div class="wrap premium-admin-wrap">
            <h1 class="premium-page-title">
                <span class="dashicons dashicons-cart"></span>Subscription Plans
                <?php if (!$editing_plan && !isset($_GET['action'])): ?>
                <a href="?page=premium-content-plans&action=new" class="page-title-action">Add New</a>
                <?php endif; ?>
            </h1>

            <?php if (isset($_GET['action']) && ($_GET['action'] === 'new' || $_GET['action'] === 'edit')): ?>
                <form method="post" class="premium-settings-form">
                    <?php wp_nonce_field('premium_plan_action'); ?>
                    <input type="hidden" name="plan_id" value="<?php echo $editing_plan ? esc_attr($editing_plan->id) : ''; ?>">
                    <div class="premium-card">
                        <div class="premium-card-header"><h2><?php echo $editing_plan ? 'Edit Plan' : 'Create Plan'; ?></h2></div>
                        <div class="premium-card-body">
                            <div class="premium-form-row">
                                <div class="premium-form-group">
                                    <label class="premium-label">Plan Name *</label>
                                    <input type="text" name="plan_name" value="<?php echo $editing_plan ? esc_attr($editing_plan->name) : ''; ?>" class="premium-input" required>
                                </div>
                                <div class="premium-form-group">
                                    <label class="premium-label">Price *</label>
                                    <div class="premium-input-group">
                                        <span class="input-prefix">$</span>
                                        <input type="number" name="plan_price" value="<?php echo $editing_plan ? esc_attr($editing_plan->price) : ''; ?>" step="0.01" min="0" class="premium-input" required>
                                    </div>
                                </div>
                            </div>
                            <div class="premium-form-row">
                                <div class="premium-form-group">
                                    <label class="premium-label">Billing Interval *</label>
                                    <select name="plan_interval" class="premium-select" required>
                                        <option value="monthly" <?php echo ($editing_plan && $editing_plan->interval === 'monthly') ? 'selected' : ''; ?>>Monthly</option>
                                        <option value="yearly" <?php echo ($editing_plan && $editing_plan->interval === 'yearly') ? 'selected' : ''; ?>>Yearly</option>
                                        <option value="lifetime" <?php echo ($editing_plan && $editing_plan->interval === 'lifetime') ? 'selected' : ''; ?>>Lifetime</option>
                                    </select>
                                </div>
                                <div class="premium-form-group">
                                    <label class="premium-label">Status</label>
                                    <select name="plan_status" class="premium-select">
                                        <option value="active" <?php echo ($editing_plan && $editing_plan->status === 'active') ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo ($editing_plan && $editing_plan->status === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="premium-form-group">
                                <label class="premium-label">Description</label>
                                <textarea name="plan_description" rows="3" class="premium-textarea"><?php echo $editing_plan ? esc_textarea($editing_plan->description) : ''; ?></textarea>
                            </div>
                            <div class="premium-form-group">
                                <label class="premium-label">Features (one per line)</label>
                                <textarea name="plan_features" rows="5" class="premium-textarea"><?php 
                                    if ($editing_plan && $editing_plan->features) {
                                        $features = json_decode($editing_plan->features, true);
                                        echo esc_textarea(implode("\n", $features));
                                    }
                                ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="premium-form-actions">
                        <button type="submit" name="premium_save_plan" class="button button-primary button-large"><?php echo $editing_plan ? 'Update' : 'Create'; ?></button>
                        <a href="?page=premium-content-plans" class="button button-large">Cancel</a>
                    </div>
                </form>
            <?php else: ?>
                <div class="premium-plans-grid">
                    <?php foreach ($plans as $plan): 
                        $features = json_decode($plan->features, true);
                    ?>
                    <div class="premium-plan-card">
                        <div class="plan-header">
                            <h3><?php echo esc_html($plan->name); ?></h3>
                            <div class="plan-price">
                                <span class="currency">$</span>
                                <span class="amount"><?php echo number_format($plan->price, 2); ?></span>
                                <span class="period">/<?php echo esc_html($plan->interval); ?></span>
                            </div>
                        </div>
                        <div class="plan-body">
                            <?php if ($plan->description): ?><p class="plan-description"><?php echo esc_html($plan->description); ?></p><?php endif; ?>
                            <?php if ($features): ?>
                            <ul class="plan-features">
                                <?php foreach ($features as $feature): ?><li><?php echo esc_html($feature); ?></li><?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </div>
                        <div class="plan-footer">
                            <span class="plan-status status-<?php echo esc_attr($plan->status); ?>"><?php echo ucfirst($plan->status); ?></span>
                            <div class="plan-actions">
                                <a href="?page=premium-content-plans&action=edit&plan_id=<?php echo $plan->id; ?>" class="button button-small">Edit</a>
                                <a href="?page=premium-content-plans&action=delete&plan_id=<?php echo $plan->id; ?>&_wpnonce=<?php echo wp_create_nonce('delete_plan_' . $plan->id); ?>" class="button button-small button-link-delete" onclick="return confirm('Delete this plan?')">Delete</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function save_plan() {
        $plan_id = isset($_POST['plan_id']) && !empty($_POST['plan_id']) ? intval($_POST['plan_id']) : null;
        $features_text = sanitize_textarea_field($_POST['plan_features']);
        $features_array = array_filter(array_map('trim', explode("\n", $features_text)));
        
        $plan_data = array(
            'name' => sanitize_text_field($_POST['plan_name']),
            'description' => sanitize_textarea_field($_POST['plan_description']),
            'price' => floatval($_POST['plan_price']),
            'interval' => sanitize_text_field($_POST['plan_interval']),
            'features' => $features_array,
            'status' => sanitize_text_field($_POST['plan_status'])
        );
        
        Premium_Content_Subscription_Manager::save_plan($plan_data, $plan_id);
    }

    public function render_form_page() {
        if (isset($_POST['premium_save_form']) && check_admin_referer('premium_form_settings')) {
            premium_content_update_option('cf7_form_id', intval($_POST['cf7_form_id']));
            echo '<div class="notice notice-success"><p>Form settings saved!</p></div>';
        }

        $cf7_form_id = premium_content_get_option('cf7_form_id', '');
        $cf7_forms = Premium_Content_CF7_Handler::get_forms_dropdown();
        $cf7_active = Premium_Content_CF7_Handler::is_cf7_active();
        ?>
        <div class="wrap premium-admin-wrap">
            <h1 class="premium-page-title"><span class="dashicons dashicons-feedback"></span>Form Settings</h1>

            <?php if (!$cf7_active): ?>
                <div class="premium-card">
                    <div class="premium-card-body">
                        <h3>Contact Form 7 Not Installed</h3>
                        <p>This plugin requires Contact Form 7 to be installed and activated.</p>
                        <a href="<?php echo admin_url('plugin-install.php?s=contact+form+7&tab=search&type=term'); ?>" class="button button-primary">Install Contact Form 7</a>
                    </div>
                </div>
            <?php else: ?>
                <form method="post" class="premium-settings-form">
                    <?php wp_nonce_field('premium_form_settings'); ?>
                    <div class="premium-card">
                        <div class="premium-card-header"><h2>Contact Form 7 Configuration</h2></div>
                        <div class="premium-card-body">
                            <?php if (empty($cf7_forms)): ?>
                                <p>No Contact Form 7 forms found.</p>
                                <button type="button" id="premium-create-cf7-form" class="button button-primary">Create Premium Form Automatically</button>
                            <?php else: ?>
                                <div class="premium-form-group">
                                    <label class="premium-label">Select Form</label>
                                    <select name="cf7_form_id" class="premium-select">
                                        <option value="">-- Select a form --</option>
                                        <?php foreach ($cf7_forms as $form_id => $form_title): ?>
                                            <option value="<?php echo esc_attr($form_id); ?>" <?php selected($cf7_form_id, $form_id); ?>><?php echo esc_html($form_title); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if ($cf7_form_id): ?>
                                        <p class="premium-description"><a href="<?php echo admin_url('admin.php?page=wpcf7&post=' . $cf7_form_id . '&action=edit'); ?>" target="_blank">Edit this form in Contact Form 7</a></p>
                                    <?php endif; ?>
                                </div>
                                <div class="premium-form-group">
                                    <button type="button" id="premium-create-cf7-form" class="button">Create New Premium Form</button>
                                </div>
                            <?php endif; ?>
                            <div class="premium-form-group">
                                <label class="premium-label">Form Template</label>
                                <p class="premium-description">Use this template if creating a form manually:</p>
                                <textarea readonly class="premium-textarea" rows="12"><?php echo esc_textarea(Premium_Content_CF7_Handler::get_form_template()); ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="premium-form-actions">
                        <button type="submit" name="premium_save_form" class="button button-primary button-large">Save Form Settings</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }

    public function render_subscribers_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'premium_subscriptions';
        $subscriptions = $wpdb->get_results("SELECT s.*, p.name as plan_name, u.user_email, u.display_name FROM $table s LEFT JOIN {$wpdb->prefix}premium_plans p ON s.plan_id = p.id LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID ORDER BY s.created_at DESC LIMIT 100");
        ?>
        <div class="wrap premium-admin-wrap">
            <h1 class="premium-page-title"><span class="dashicons dashicons-groups"></span>Subscribers</h1>
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr><th>User</th><th>Plan</th><th>Status</th><th>Started</th><th>Expires</th><th>Payment</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($subscriptions)): ?>
                        <tr><td colspan="6">No subscriptions yet.</td></tr>
                    <?php else: foreach ($subscriptions as $sub): ?>
                        <tr>
                            <td><strong><?php echo esc_html($sub->display_name); ?></strong><br><small><?php echo esc_html($sub->user_email); ?></small></td>
                            <td><?php echo esc_html($sub->plan_name); ?></td>
                            <td><span class="plan-status status-<?php echo esc_attr($sub->status); ?>"><?php echo ucfirst($sub->status); ?></span></td>
                            <td><?php echo date('M j, Y', strtotime($sub->started_at)); ?></td>
                            <td><?php echo $sub->expires_at ? date('M j, Y', strtotime($sub->expires_at)) : 'Never'; ?></td>
                            <td><?php echo ucfirst($sub->payment_method); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_emails_page() {
        global $wpdb;
        if (isset($_GET['action']) && $_GET['action'] === 'export') {
            Premium_Content_CF7_Handler::export_emails_csv();
            exit;
        }
        
        $table = $wpdb->prefix . 'premium_emails';
        $emails = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 100");
        $stats = Premium_Content_CF7_Handler::get_email_statistics();
        ?>
        <div class="wrap premium-admin-wrap">
            <h1 class="premium-page-title">
                <span class="dashicons dashicons-email"></span>Email Collection
                <a href="?page=premium-content-emails&action=export" class="page-title-action">Export CSV</a>
            </h1>
            <div class="premium-dashboard-grid" style="margin-bottom: 30px;">
                <div class="premium-stat-card">
                    <div class="premium-stat-icon premium-stat-emails"><span class="dashicons dashicons-email"></span></div>
                    <div class="premium-stat-content">
                        <div class="premium-stat-number"><?php echo number_format($stats['total_emails']); ?></div>
                        <div class="premium-stat-label">Total Emails</div>
                    </div>
                </div>
                <div class="premium-stat-card">
                    <div class="premium-stat-icon premium-stat-subscribers"><span class="dashicons dashicons-admin-users"></span></div>
                    <div class="premium-stat-content">
                        <div class="premium-stat-number"><?php echo number_format($stats['unique_emails']); ?></div>
                        <div class="premium-stat-label">Unique Emails</div>
                    </div>
                </div>
                <div class="premium-stat-card">
                    <div class="premium-stat-icon premium-stat-views"><span class="dashicons dashicons-calendar"></span></div>
                    <div class="premium-stat-content">
                        <div class="premium-stat-number"><?php echo number_format($stats['this_month']); ?></div>
                        <div class="premium-stat-label">This Month</div>
                    </div>
                </div>
            </div>
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr><th>Email Address</th><th>Post</th><th>Date Collected</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($emails)): ?>
                        <tr><td colspan="3">No emails collected yet.</td></tr>
                    <?php else: foreach ($emails as $email): ?>
                        <tr>
                            <td><strong><?php echo esc_html($email->email); ?></strong></td>
                            <td><a href="<?php echo get_permalink($email->post_id); ?>" target="_blank"><?php echo get_the_title($email->post_id) ?: 'Post #' . $email->post_id; ?></a></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($email->created_at)); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_settings_page() {
        if (isset($_POST['premium_save_settings']) && check_admin_referer('premium_general_settings')) {
            $this->save_general_settings();
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }

        $primary_color = premium_content_get_option('primary_color', '#667eea');
        $secondary_color = premium_content_get_option('secondary_color', '#764ba2');
        $paywall_title = premium_content_get_option('paywall_title', 'Subscribe to Continue Reading');
        $paywall_description = premium_content_get_option('paywall_description', 'Get unlimited access to all premium content');
        $counter_text = premium_content_get_option('counter_text', 'You have {remaining} free articles remaining');
        $debug_mode = premium_content_get_option('debug_mode', '0');
        ?>
        <div class="wrap premium-admin-wrap">
            <h1 class="premium-page-title"><span class="dashicons dashicons-admin-generic"></span>General Settings</h1>
            <form method="post" class="premium-settings-form">
                <?php wp_nonce_field('premium_general_settings'); ?>
                <div class="premium-card">
                    <div class="premium-card-header"><h2>Design & Styling</h2></div>
                    <div class="premium-card-body">
                        <div class="premium-form-row">
                            <div class="premium-form-group">
                                <label class="premium-label">Primary Color</label>
                                <input type="color" name="primary_color" value="<?php echo esc_attr($primary_color); ?>" class="premium-input">
                            </div>
                            <div class="premium-form-group">
                                <label class="premium-label">Secondary Color</label>
                                <input type="color" name="secondary_color" value="<?php echo esc_attr($secondary_color); ?>" class="premium-input">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="premium-card">
                    <div class="premium-card-header"><h2>Text & Messaging</h2></div>
                    <div class="premium-card-body">
                        <div class="premium-form-group">
                            <label class="premium-label">Paywall Title</label>
                            <input type="text" name="paywall_title" value="<?php echo esc_attr($paywall_title); ?>" class="premium-input">
                        </div>
                        <div class="premium-form-group">
                            <label class="premium-label">Paywall Description</label>
                            <textarea name="paywall_description" rows="3" class="premium-textarea"><?php echo esc_textarea($paywall_description); ?></textarea>
                        </div>
                        <div class="premium-form-group">
                            <label class="premium-label">Counter Banner Text</label>
                            <input type="text" name="counter_text" value="<?php echo esc_attr($counter_text); ?>" class="premium-input">
                            <p class="premium-description">Use {remaining} as placeholder</p>
                        </div>
                    </div>
                </div>
                <div class="premium-card">
                    <div class="premium-card-header"><h2>Advanced Options</h2></div>
                    <div class="premium-card-body">
                        <div class="premium-form-group">
                            <label class="premium-checkbox-label">
                                <input type="checkbox" name="debug_mode" value="1" <?php checked($debug_mode, '1'); ?>>
                                <span>Enable debug mode</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="premium-form-actions">
                    <button type="submit" name="premium_save_settings" class="button button-primary button-large">Save Settings</button>
                </div>
            </form>
        </div>
        <?php
    }

    private function save_general_settings() {
        premium_content_update_option('primary_color', sanitize_hex_color($_POST['primary_color']));
        premium_content_update_option('secondary_color', sanitize_hex_color($_POST['secondary_color']));
        premium_content_update_option('paywall_title', sanitize_text_field($_POST['paywall_title']));
        premium_content_update_option('paywall_description', sanitize_textarea_field($_POST['paywall_description']));
        premium_content_update_option('counter_text', sanitize_text_field($_POST['counter_text']));
        premium_content_update_option('debug_mode', isset($_POST['debug_mode']) ? '1' : '0');
    }
}