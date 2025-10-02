<?php
/**
 * Template: Pricing Page - Premium Design
 * Inspired by NYT, Medium, and professional subscription services
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$plans = Premium_Content_Subscription_Manager::get_plans('active');
$user_id = get_current_user_id();
$has_subscription = Premium_Content_Subscription_Manager::user_has_active_subscription($user_id);
$current_subscription = $has_subscription ? Premium_Content_Subscription_Manager::get_user_subscription($user_id) : null;
?>

<div class="nyt-pricing-wrapper">
    <!-- Hero Section -->
    <div class="nyt-pricing-hero">
        <h1 class="nyt-hero-title">Subscribe to continue reading</h1>
        <p class="nyt-hero-subtitle">Get unlimited access to premium content</p>
        <div class="nyt-hero-benefits">
            <span class="benefit-item">
                <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                </svg>
                Cancel anytime
            </span>
            <span class="benefit-item">
                <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                </svg>
                Ad-free experience
            </span>
            <span class="benefit-item">
                <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                </svg>
                Premium support
            </span>
        </div>
    </div>

    <?php if ($has_subscription && $current_subscription): 
        $current_plan = Premium_Content_Subscription_Manager::get_plan($current_subscription->plan_id);
    ?>
        <div class="nyt-current-subscription">
            <div class="subscription-badge">
                <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
                Active Subscriber
            </div>
            <p>You're currently subscribed to <strong><?php echo esc_html($current_plan->name); ?></strong></p>
            <a href="<?php echo esc_url(get_permalink(get_option('premium_content_page_account'))); ?>" class="manage-link">Manage subscription →</a>
        </div>
    <?php endif; ?>

    <?php if (empty($plans)): ?>
        <div class="nyt-no-plans">
            <p>No subscription plans are currently available. Please check back later.</p>
        </div>
    <?php else: ?>
        <!-- Plan Toggle (Monthly/Yearly) -->
        <div class="nyt-plan-toggle">
            <button class="toggle-btn active" data-interval="monthly">Monthly</button>
            <button class="toggle-btn" data-interval="yearly">
                Yearly
                <span class="save-badge">Save 20%</span>
            </button>
        </div>

        <!-- Plans Grid -->
        <div class="nyt-plans-container">
            <?php 
            $plan_count = count($plans);
            $monthly_plans = array_filter($plans, function($p) { return $p->interval === 'monthly'; });
            $yearly_plans = array_filter($plans, function($p) { return $p->interval === 'yearly'; });
            $lifetime_plans = array_filter($plans, function($p) { return $p->interval === 'lifetime'; });
            
            // Show monthly plans by default
            foreach ($plans as $index => $plan): 
                $features = json_decode($plan->features, true);
                $is_recommended = ($plan->interval === 'yearly');
                $is_current = $has_subscription && $current_subscription && $current_subscription->plan_id == $plan->id;
                $is_lifetime = $plan->interval === 'lifetime';
            ?>
            <div class="nyt-plan-card <?php echo $is_recommended ? 'recommended' : ''; ?> <?php echo $is_current ? 'current-plan' : ''; ?> <?php echo $is_lifetime ? 'lifetime-plan' : ''; ?>" data-interval="<?php echo esc_attr($plan->interval); ?>">
                <?php if ($is_recommended): ?>
                    <div class="recommended-badge">Best Value</div>
                <?php endif; ?>
                
                <?php if ($is_current): ?>
                    <div class="current-badge">Current Plan</div>
                <?php endif; ?>

                <div class="plan-content">
                    <h3 class="plan-title"><?php echo esc_html($plan->name); ?></h3>
                    
                    <div class="plan-pricing">
                        <span class="price-amount">
                            <span class="currency">$</span><?php echo number_format($plan->price, 0); ?>
                        </span>
                        <span class="price-period">
                            <?php if ($plan->interval === 'yearly'): ?>
                                per year
                            <?php elseif ($plan->interval === 'monthly'): ?>
                                per month
                            <?php else: ?>
                                one-time
                            <?php endif; ?>
                        </span>
                    </div>

                    <?php if ($plan->interval === 'yearly'): ?>
                        <p class="price-detail">Billed annually at $<?php echo number_format($plan->price, 2); ?></p>
                    <?php elseif ($plan->interval === 'monthly'): ?>
                        <p class="price-detail">Billed monthly</p>
                    <?php else: ?>
                        <p class="price-detail">Lifetime access - pay once</p>
                    <?php endif; ?>

                    <?php if ($plan->description): ?>
                        <p class="plan-description"><?php echo esc_html($plan->description); ?></p>
                    <?php endif; ?>

                    <?php if ($features && is_array($features)): ?>
                        <ul class="plan-features">
                            <?php foreach ($features as $feature): ?>
                                <li>
                                    <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                                    </svg>
                                    <?php echo esc_html($feature); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <div class="plan-action">
                        <?php if ($is_current): ?>
                            <button class="nyt-button current" disabled>Your Current Plan</button>
                        <?php elseif ($user_id): ?>
                            <a href="<?php echo esc_url(add_query_arg('plan', $plan->id, get_permalink(get_option('premium_content_page_checkout')))); ?>" 
                               class="nyt-button primary">
                                <?php echo $has_subscription ? 'Switch Plan' : 'Continue'; ?>
                            </a>
                        <?php else: ?>
                            <a href="<?php echo esc_url(wp_login_url(add_query_arg('plan', $plan->id, get_permalink(get_option('premium_content_page_checkout'))))); ?>" 
                               class="nyt-button primary">
                                Continue
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Trust Indicators -->
        <div class="nyt-trust-section">
            <div class="trust-item">
                <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                    <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
                </svg>
                <div>
                    <strong>Secure Payment</strong>
                    <p>256-bit SSL encryption</p>
                </div>
            </div>
            <div class="trust-item">
                <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                    <path d="M9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm2-7h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/>
                </svg>
                <div>
                    <strong>Cancel Anytime</strong>
                    <p>No hidden fees or commitments</p>
                </div>
            </div>
            <div class="trust-item">
                <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                    <path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 5H6v-2h8v2zm4-6H6V6h12v2z"/>
                </svg>
                <div>
                    <strong>24/7 Support</strong>
                    <p>Always here to help</p>
                </div>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="nyt-faq-section">
            <h2>Frequently Asked Questions</h2>
            <div class="faq-grid">
                <div class="faq-item">
                    <h3>Can I cancel anytime?</h3>
                    <p>Yes. You can cancel your subscription at any time from your account settings. You'll continue to have access until the end of your billing period.</p>
                </div>
                <div class="faq-item">
                    <h3>What payment methods do you accept?</h3>
                    <p>We accept all major credit cards through Stripe and PayPal payments. All transactions are secure and encrypted.</p>
                </div>
                <div class="faq-item">
                    <h3>Can I switch plans later?</h3>
                    <p>Yes. You can upgrade or downgrade your plan at any time. Changes will be reflected in your next billing cycle.</p>
                </div>
                <div class="faq-item">
                    <h3>Do you offer refunds?</h3>
                    <p>We offer a 30-day money-back guarantee. If you're not satisfied, contact our support team for a full refund.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Plan toggle functionality
    $('.toggle-btn').on('click', function() {
        var interval = $(this).data('interval');
        
        $('.toggle-btn').removeClass('active');
        $(this).addClass('active');
        
        // Show/hide plans based on interval
        if (interval === 'yearly') {
            $('.nyt-plan-card[data-interval="monthly"]').fadeOut(200, function() {
                $('.nyt-plan-card[data-interval="yearly"]').fadeIn(200);
            });
        } else {
            $('.nyt-plan-card[data-interval="yearly"]').fadeOut(200, function() {
                $('.nyt-plan-card[data-interval="monthly"]').fadeIn(200);
            });
        }
        
        // Always show lifetime plans
        $('.nyt-plan-card[data-interval="lifetime"]').show();
    });
    
    // Initialize - show only monthly plans by default
    $('.nyt-plan-card[data-interval="yearly"]').hide();
});
</script>