<?php
/**
 * Template: Pricing Page
 * Displays subscription plans with pricing
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

<div class="premium-pricing-wrapper">
    <div class="premium-pricing-header">
        <h1>Choose Your Plan</h1>
        <p>Get unlimited access to all premium content</p>
    </div>

    <?php if ($has_subscription && $current_subscription): 
        $current_plan = Premium_Content_Subscription_Manager::get_plan($current_subscription->plan_id);
    ?>
        <div class="premium-alert premium-alert-success premium-current-plan-notice">
            <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
            </svg>
            <div>
                <strong>You're currently subscribed to: <?php echo esc_html($current_plan->name); ?></strong>
                <p>You can switch to a different plan below or <a href="<?php echo esc_url(get_permalink(get_option('premium_content_page_account'))); ?>">manage your subscription</a></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($plans)): ?>
        <div class="premium-alert premium-alert-warning premium-no-plans">
            <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
            </svg>
            <div>
                <strong>No Plans Available</strong>
                <p>No subscription plans are currently available. Please check back later or contact support.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="premium-pricing-table">
            <?php 
            $plan_count = count($plans);
            foreach ($plans as $index => $plan): 
                $features = json_decode($plan->features, true);
                $is_popular = ($plan_count === 3 && $index === 1) || ($plan_count === 2 && $index === 1);
                $is_current = $has_subscription && $current_subscription && $current_subscription->plan_id == $plan->id;
            ?>
            <div class="premium-pricing-plan <?php echo $is_popular ? 'popular-plan' : ''; ?> <?php echo $is_current ? 'current-plan' : ''; ?>">
                <?php if ($is_popular): ?>
                    <div class="plan-badge plan-badge-popular">Most Popular</div>
                <?php endif; ?>
                
                <?php if ($is_current): ?>
                    <div class="plan-badge plan-badge-current">Current Plan</div>
                <?php endif; ?>
                
                <div class="plan-header-content">
                    <h2 class="plan-name"><?php echo esc_html($plan->name); ?></h2>
                    
                    <div class="plan-price">
                        <span class="currency">$</span>
                        <span class="amount"><?php echo number_format($plan->price, 0); ?></span>
                        <span class="period">/<?php echo esc_html($plan->interval); ?></span>
                    </div>

                    <?php if ($plan->description): ?>
                        <p class="plan-description"><?php echo esc_html($plan->description); ?></p>
                    <?php endif; ?>
                </div>

                <?php if ($features && is_array($features)): ?>
                    <ul class="plan-features">
                        <?php foreach ($features as $feature): ?>
                            <li>
                                <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                                </svg>
                                <?php echo esc_html($feature); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <div class="plan-cta">
                    <?php if ($is_current): ?>
                        <button class="plan-button plan-button-current" disabled>
                            <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                            Your Current Plan
                        </button>
                    <?php elseif ($user_id): ?>
                        <a href="<?php echo esc_url(add_query_arg('plan', $plan->id, get_permalink(get_option('premium_content_page_checkout')))); ?>" 
                           class="plan-button plan-button-primary">
                            <?php echo $has_subscription ? 'Switch to This Plan' : 'Get Started'; ?>
                            <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                                <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/>
                            </svg>
                        </a>
                    <?php else: ?>
                        <a href="<?php echo esc_url(wp_login_url(add_query_arg('plan', $plan->id, get_permalink(get_option('premium_content_page_checkout'))))); ?>" 
                           class="plan-button plan-button-primary">
                            Sign In to Subscribe
                            <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                                <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/>
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="premium-pricing-footer">
            <div class="pricing-footer-features">
                <div class="footer-feature">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                        <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
                    </svg>
                    <span>Secure Payment</span>
                </div>
                <div class="footer-feature">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                        <path d="M9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm2-7h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/>
                    </svg>
                    <span>Cancel Anytime</span>
                </div>
                <div class="footer-feature">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                        <path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 5H6v-2h8v2zm4-6H6V6h12v2z"/>
                    </svg>
                    <span>24/7 Support</span>
                </div>
            </div>
            
            <div class="pricing-footer-text">
                <p>All plans include full access to premium content with no hidden fees.</p>
                <p>Questions? <a href="<?php echo esc_url(home_url('/contact')); ?>">Contact our support team</a> or view our <a href="<?php echo esc_url(home_url('/faq')); ?>">FAQ</a></p>
            </div>
        </div>
    <?php endif; ?>
</div>