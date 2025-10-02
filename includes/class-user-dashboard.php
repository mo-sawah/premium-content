<?php
/**
 * Handles user dashboard and shortcodes
 */
class Premium_Content_User_Dashboard {
    
    public function __construct() {
        add_shortcode('premium_account_dashboard', array($this, 'render_dashboard'));
        add_shortcode('premium_pricing_table', array($this, 'render_pricing_table'));
        add_shortcode('premium_checkout', array($this, 'render_checkout'));
        add_shortcode('premium_thank_you', array($this, 'render_thank_you'));
        add_shortcode('premium_login_register', array($this, 'render_login_register'));
    }
    
    /**
     * Render account dashboard by loading template
     */
    public function render_dashboard() {
        ob_start();
        include PREMIUM_CONTENT_PATH . 'templates/dashboard-page.php';
        return ob_get_clean();
    }
    
    /**
     * Render pricing table by loading template
     */
    public function render_pricing_table() {
        ob_start();
        include PREMIUM_CONTENT_PATH . 'templates/pricing-page.php';
        return ob_get_clean();
    }
    
    /**
     * Render checkout by loading template
     */
    public function render_checkout() {
        ob_start();
        include PREMIUM_CONTENT_PATH . 'templates/checkout-page.php';
        return ob_get_clean();
    }
    
    /**
     * Render login/register by loading template
     */
    public function render_login_register() {
        ob_start();
        include PREMIUM_CONTENT_PATH . 'templates/login-register.php';
        return ob_get_clean();
    }
    
    /**
     * Render thank you page
     */
    public function render_thank_you() {
        if (!is_user_logged_in()) {
            return '<div class="premium-alert premium-alert-warning">
                <p>Please <a href="' . wp_login_url(get_permalink()) . '">log in</a> to view this page.</p>
            </div>';
        }

        // Check if there's a successful payment
        $session_id = isset($_GET['session_id']) ? sanitize_text_field($_GET['session_id']) : '';
        $order_id = isset($_GET['order_id']) ? sanitize_text_field($_GET['order_id']) : '';
        
        ob_start();
        ?>
        <div class="premium-thank-you-wrapper">
            <div class="premium-thank-you-content">
                <div class="thank-you-icon">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="64" height="64">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                </div>
                
                <h1>Thank You for Subscribing!</h1>
                <p class="thank-you-message">Your subscription has been activated successfully.</p>
                
                <div class="thank-you-details">
                    <p>You now have full access to all premium content on our site.</p>
                    <?php if ($session_id || $order_id): ?>
                        <p class="transaction-id">
                            Transaction ID: <code><?php echo esc_html($session_id ? $session_id : $order_id); ?></code>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="thank-you-actions">
                    <a href="<?php echo esc_url(home_url()); ?>" class="btn btn-primary">
                        <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                            <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                        </svg>
                        Browse Content
                    </a>
                    <a href="<?php echo esc_url(get_permalink(get_option('premium_content_page_account'))); ?>" class="btn btn-secondary">
                        <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                        View My Account
                    </a>
                </div>

                <div class="thank-you-footer">
                    <p>A confirmation email has been sent to your email address.</p>
                    <p>Need help? <a href="<?php echo esc_url(home_url('/contact')); ?>">Contact Support</a></p>
                </div>
            </div>
        </div>

        <style>
        .premium-thank-you-wrapper {
            max-width: 600px;
            margin: 60px auto;
            padding: 20px;
        }

        .premium-thank-you-content {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 48px 32px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .thank-you-icon {
            width: 96px;
            height: 96px;
            margin: 0 auto 24px;
            background: linear-gradient(135deg, #00a32a, #008a20);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .premium-thank-you-content h1 {
            margin: 0 0 16px 0;
            font-size: 32px;
            color: #2c3e50;
        }

        .thank-you-message {
            font-size: 18px;
            color: #6b7280;
            margin-bottom: 32px;
        }

        .thank-you-details {
            background: #f9fafb;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 32px;
        }

        .thank-you-details p {
            margin: 8px 0;
            color: #374151;
        }

        .transaction-id {
            font-size: 14px;
            color: #6b7280;
        }

        .transaction-id code {
            background: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-family: monospace;
            color: #667eea;
        }

        .thank-you-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-bottom: 32px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            background: #667eea;
            color: white;
        }

        .thank-you-footer {
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
        }

        .thank-you-footer p {
            margin: 8px 0;
            color: #6b7280;
            font-size: 14px;
        }

        .thank-you-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .premium-thank-you-wrapper {
                margin: 40px 20px;
            }
            
            .premium-thank-you-content {
                padding: 32px 24px;
            }
            
            .thank-you-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
        </style>
        <?php
        return ob_get_clean();
    }
}