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
    }
    
    /**
     * Render account dashboard
     */
    public function render_dashboard() {
        if (!is_user_logged_in()) {
            return '<p>Please <a href="' . wp_login_url(get_permalink()) . '">login</a> to view your account.</p>';
        }
        
        $user_id = get_current_user_id();
        $subscription = Premium_Content_Subscription_Manager::get_user_subscription($user_id);
        
        ob_start();
        ?>
        <div class="premium-user-dashboard">
            <h2>My Account</h2>
            
            <?php if ($subscription): 
                $plan = Premium_Content_Subscription_Manager::get_plan($subscription->plan_id);
            ?>
                <div class="premium-subscription-card">
                    <h3>Active Subscription</h3>
                    <p><strong>Plan:</strong> <?php echo esc_html($plan->name); ?></p>
                    <p><strong>Status:</strong> <?php echo ucfirst($subscription->status); ?></p>
                    <p><strong>Started:</strong> <?php echo date('M j, Y', strtotime($subscription->started_at)); ?></p>
                    <?php if ($subscription->expires_at): ?>
                    <p><strong>Expires:</strong> <?php echo date('M j, Y', strtotime($subscription->expires_at)); ?></p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p>You don't have an active subscription.</p>
                <a href="<?php echo get_permalink(get_option('premium_content_page_pricing')); ?>" class="button">View Plans</a>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render pricing table
     */
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
    
    /**
     * Render checkout page
     */
    public function render_checkout() {
        if (!is_user_logged_in()) {
            return '<p>Please <a href="' . wp_login_url(get_permalink()) . '">login</a> to continue.</p>';
        }
        
        $plan_id = isset($_GET['plan']) ? intval($_GET['plan']) : 0;
        $plan = Premium_Content_Subscription_Manager::get_plan($plan_id);
        
        if (!$plan) {
            return '<p>Invalid plan selected. <a href="' . get_permalink(get_option('premium_content_page_pricing')) . '">View plans</a></p>';
        }
        
        $stripe_enabled = premium_content_get_option('stripe_enabled', '0');
        $paypal_enabled = premium_content_get_option('paypal_enabled', '0');
        
        ob_start();
        ?>
        <div class="premium-checkout">
            <h2>Checkout</h2>
            <div class="checkout-plan-summary">
                <h3><?php echo esc_html($plan->name); ?></h3>
                <p class="checkout-price">$<?php echo number_format($plan->price, 2); ?> / <?php echo esc_html($plan->interval); ?></p>
            </div>
            
            <div class="checkout-payment-methods">
                <h3>Select Payment Method</h3>
                
                <?php if ($stripe_enabled === '1'): ?>
                <button id="stripe-checkout-btn" class="payment-button stripe-button" data-plan="<?php echo esc_attr($plan_id); ?>">
                    Pay with Stripe
                </button>
                <?php endif; ?>
                
                <?php if ($paypal_enabled === '1'): ?>
                <button id="paypal-checkout-btn" class="payment-button paypal-button" data-plan="<?php echo esc_attr($plan_id); ?>">
                    Pay with PayPal
                </button>
                <?php endif; ?>
                
                <?php if ($stripe_enabled !== '1' && $paypal_enabled !== '1'): ?>
                <p>No payment methods available. Please contact support.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#stripe-checkout-btn').on('click', function() {
                var $btn = $(this);
                $btn.prop('disabled', true).text('Processing...');
                
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'premium_create_stripe_checkout',
                    nonce: '<?php echo wp_create_nonce('premium_checkout'); ?>',
                    plan_id: $(this).data('plan')
                }, function(response) {
                    if (response.success && response.data.url) {
                        window.location.href = response.data.url;
                    } else {
                        alert('Error: ' + (response.data || 'Unknown error'));
                        $btn.prop('disabled', false).text('Pay with Stripe');
                    }
                });
            });
            
            $('#paypal-checkout-btn').on('click', function() {
                var $btn = $(this);
                $btn.prop('disabled', true).text('Processing...');
                
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'premium_create_paypal_order',
                    nonce: '<?php echo wp_create_nonce('premium_checkout'); ?>',
                    plan_id: $(this).data('plan')
                }, function(response) {
                    if (response.success && response.data.approve_url) {
                        window.location.href = response.data.approve_url;
                    } else {
                        alert('Error: ' + (response.data || 'Unknown error'));
                        $btn.prop('disabled', false).text('Pay with PayPal');
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render thank you page
     */
    public function render_thank_you() {
        return '<div class="premium-thank-you">
            <h2>Thank You!</h2>
            <p>Your subscription has been activated. You now have full access to all premium content.</p>
            <a href="' . home_url() . '" class="button">Start Reading</a>
        </div>';
    }
}