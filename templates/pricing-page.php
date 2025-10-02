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
?>

<div class="premium-pricing-wrapper">
    <div class="premium-pricing-header">
        <h1>Choose Your Plan</h1>
        <p>Get unlimited access to all premium content</p>
    </div>

    <?php if ($has_subscription): ?>
        <div class="premium-alert premium-alert-success" style="max-width: 600px; margin: 0 auto 40px;">
            <strong>You have an active subscription!</strong> 
            <a href="<?php echo esc_url(get_permalink(get_option('premium_content_page_account'))); ?>">Manage your account</a>
        </div>
    <?php endif; ?>

    <?php if (empty($plans)): ?>
        <div class="premium-alert premium-alert-warning" style="max-width: 600px; margin: 0 auto;">
            <p>No plans are currently available. Please check back later.</p>
        </div>
    <?php else: ?>
        <div class="premium-pricing-table">
            <?php foreach ($plans as $index => $plan): 
                $features = json_decode($plan->features, true);
                $is_popular = $index === 1;
            ?>
            <div class="premium-pricing-plan <?php echo $is_popular ? 'popular-plan' : ''; ?>">
                <?php if ($is_popular): ?>
                    <div class="plan-badge">Most Popular</div>
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
                            <li><?php echo esc_html($feature); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <div class="plan-cta">
                    <?php if ($user_id): ?>
                        <a href="<?php echo esc_url(add_query_arg('plan', $plan->id, get_permalink(get_option('premium_content_page_checkout')))); ?>" 
                           class="plan-button">
                            <?php echo $has_subscription ? 'Switch Plan' : 'Choose ' . esc_html($plan->name); ?>
                        </a>
                    <?php else: ?>
                        <a href="<?php echo esc_url(wp_login_url(add_query_arg('plan', $plan->id, get_permalink(get_option('premium_content_page_checkout'))))); ?>" 
                           class="plan-button">
                            Sign In to Subscribe
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="premium-pricing-footer">
            <p>All plans include full access to premium content. Cancel anytime.</p>
            <p>Need help choosing? <a href="<?php echo esc_url(home_url('/contact')); ?>">Contact us</a></p>
        </div>
    <?php endif; ?>
</div>

<style>
.popular-plan {
    position: relative;
    border-color: #667eea !important;
    transform: scale(1.05);
}
.plan-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 6px 20px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
}
.premium-pricing-footer {
    text-align: center;
    margin-top: 48px;
    color: #6b7280;
}
</style>