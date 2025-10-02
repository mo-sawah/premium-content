<?php
/**
 * Template: Pricing Page - Airtable Style
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

<div class="airtable-pricing-wrapper">
    <!-- Hero Section -->
    <div class="airtable-pricing-hero">
        <h1>Choose the right plan for you</h1>
        <p>Get unlimited access to premium content with flexible pricing</p>
    </div>

    <?php if ($has_subscription && $current_subscription): 
        $current_plan = Premium_Content_Subscription_Manager::get_plan($current_subscription->plan_id);
    ?>
        <div class="airtable-current-subscription">
            <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
            </svg>
            <div>
                <strong>You're subscribed to <?php echo esc_html($current_plan->name); ?></strong>
                <a href="<?php echo esc_url(get_permalink(get_option('premium_content_page_account'))); ?>">Manage subscription</a>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($plans)): ?>
        <div class="airtable-no-plans">
            <p>No subscription plans are currently available.</p>
        </div>
    <?php else: ?>
        <!-- Plans Grid -->
        <div class="airtable-plans-grid">
            <?php 
            $plan_count = count($plans);
            foreach ($plans as $index => $plan): 
                $features = json_decode($plan->features, true);
                $is_popular = ($index === 1 && $plan_count >= 3); // Middle plan is popular
                $is_current = $has_subscription && $current_subscription && $current_subscription->plan_id == $plan->id;
                
                // Calculate yearly discount if applicable
                $yearly_discount = get_post_meta($plan->id, '_yearly_discount_percentage', true);
                $show_monthly_price = false;
                if ($plan->interval === 'yearly' && $yearly_discount) {
                    $monthly_equivalent = $plan->price / 12;
                    $show_monthly_price = true;
                }
            ?>
            <div class="airtable-plan-card <?php echo $is_popular ? 'popular' : ''; ?> <?php echo $is_current ? 'current' : ''; ?>">
                <?php if ($is_popular): ?>
                    <div class="popular-badge">Most popular</div>
                <?php endif; ?>
                
                <div class="plan-header">
                    <h2><?php echo esc_html($plan->name); ?></h2>
                    <p class="plan-subtitle"><?php echo esc_html($plan->description); ?></p>
                </div>

                <div class="plan-price">
                    <?php if ($plan->price == 0): ?>
                        <div class="price-display">
                            <span class="price-amount">Free</span>
                        </div>
                    <?php else: ?>
                        <div class="price-display">
                            <span class="price-currency">$</span>
                            <span class="price-amount"><?php echo number_format($plan->price, 0); ?></span>
                            <span class="price-period">
                                per <?php 
                                if ($plan->interval === 'yearly') echo 'seat/year';
                                elseif ($plan->interval === 'monthly') echo 'seat/month';
                                else echo 'seat';
                                ?>
                            </span>
                        </div>
                        <?php if ($plan->interval === 'yearly'): ?>
                            <p class="price-note">billed annually</p>
                        <?php elseif ($plan->interval === 'monthly'): ?>
                            <p class="price-note">billed monthly</p>
                        <?php else: ?>
                            <p class="price-note">one-time payment</p>
                        <?php endif; ?>
                        
                        <?php if ($show_monthly_price): ?>
                            <p class="price-note">$<?php echo number_format($plan->price / 12, 0); ?> billed monthly</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="plan-action">
                    <?php if ($is_current): ?>
                        <button class="plan-button current" disabled>Current plan</button>
                    <?php elseif ($user_id): ?>
                        <?php if ($plan->price == 0): ?>
                            <a href="<?php echo esc_url(home_url()); ?>" class="plan-button secondary">
                                Continue with Free
                            </a>
                        <?php else: ?>
                            <a href="<?php echo esc_url(add_query_arg('plan', $plan->id, get_permalink(get_option('premium_content_page_checkout')))); ?>" 
                               class="plan-button primary">
                                <?php echo $has_subscription ? 'Switch plan' : 'Try for free'; ?>
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="<?php echo esc_url(wp_login_url(add_query_arg('plan', $plan->id, get_permalink(get_option('premium_content_page_checkout'))))); ?>" 
                           class="plan-button primary">
                            <?php echo $plan->price == 0 ? 'Sign up' : 'Try for free'; ?>
                        </a>
                    <?php endif; ?>
                    
                    <?php if (!$is_current && $plan->price > 0): ?>
                        <p class="signup-note">or <a href="<?php echo esc_url(wp_registration_url()); ?>">sign up now</a></p>
                    <?php endif; ?>
                </div>

                <?php if ($features && is_array($features)): ?>
                    <div class="plan-features">
                        <p class="features-title"><?php echo $plan->price == 0 ? 'Free includes:' : 'Everything in ' . ($index > 0 ? $plans[$index-1]->name : 'Free') . ', plus:'; ?></p>
                        <ul>
                            <?php foreach ($features as $feature): ?>
                                <li>
                                    <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                                    </svg>
                                    <?php echo esc_html($feature); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>