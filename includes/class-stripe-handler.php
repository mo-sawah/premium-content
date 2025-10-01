<?php
/**
 * class-stripe-handler.php
 * Stripe payment processing (Phase 2 implementation)
 */
class Premium_Content_Stripe_Handler {
    public function __construct() {
        // Stripe integration hooks will be added in Phase 2
    }
}

/**
 * class-paypal-handler.php  
 * PayPal payment processing (Phase 2 implementation)
 */
class Premium_Content_PayPal_Handler {
    public function __construct() {
        // PayPal integration hooks will be added in Phase 2
    }
}

/**
 * class-user-dashboard.php
 * User account dashboard (Phase 2 implementation)
 */
class Premium_Content_User_Dashboard {
    public function __construct() {
        add_shortcode('premium_account_dashboard', array($this, 'render_dashboard'));
        add_shortcode('premium_pricing_table', array($this, 'render_pricing_table'));
        add_shortcode('premium_checkout', array($this, 'render_checkout'));
        add_shortcode('premium_thank_you', array($this, 'render_thank_you'));
    }
    
    public function render_dashboard() {
        if (!is_user_logged_in()) {
            return '<p>Please <a href="' . wp_login_url(get_permalink()) . '">login</a> to view your account.</p>';
        }
        
        ob_start();
        ?>
        <div class="premium-user-dashboard">
            <h2>My Account</h2>
            <p>Account dashboard coming soon in Phase 2...</p>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function render_pricing_table() {
        $plans = Premium_Content_Subscription_Manager::get_plans('active');
        
        ob_start();
        ?>
        <div class="premium-pricing-wrapper">
            <div class="premium-pricing-header">
                <h2>Choose Your Plan</h2>
                <p>Get unlimited access to all premium content</p>
            </div>
            <div class="premium-pricing-table">
                <?php foreach ($plans as $plan): 
                    $features = json_decode($plan->features, true);
                ?>
                <div class="premium-pricing-plan">
                    <div class="plan-name"><?php echo esc_html($plan->name); ?></div>
                    <div class="plan-price">
                        <span class="currency">$</span>
                        <span class="amount"><?php echo number_format($plan->price, 0); ?></span>
                        <span class="period">/<?php echo esc_html($plan->interval); ?></span>
                    </div>
                    <?php if ($plan->description): ?>
                    <p class="plan-description"><?php echo esc_html($plan->description); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($features): ?>
                    <ul class="plan-features">
                        <?php foreach ($features as $feature): ?>
                        <li><?php echo esc_html($feature); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                    
                    <a href="<?php echo esc_url(add_query_arg('plan', $plan->id, get_permalink(get_option('premium_content_page_checkout')))); ?>" class="plan-button">
                        Choose Plan
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function render_checkout() {
        return '<div class="premium-checkout"><p>Checkout system coming in Phase 2...</p></div>';
    }
    
    public function render_thank_you() {
        return '<div class="premium-thank-you"><h2>Thank You!</h2><p>Your subscription is being processed...</p></div>';
    }
}

/**
 * class-page-generator.php
 * Handles dynamic page generation and templates (Phase 2 implementation)
 */
class Premium_Content_Page_Generator {
    public function __construct() {
        // Page template hooks will be added in Phase 2
    }
}