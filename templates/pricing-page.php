<?php
/**
 * Template: Pricing Page - Professional Design
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$plans = Premium_Content_Subscription_Manager::get_plans('active');
$user_id = get_current_user_id();
$has_subscription = Premium_Content_Subscription_Manager::user_has_active_subscription($user_id);
$current_subscription = $has_subscription ? Premium_Content_Subscription_Manager::get_user_subscription($user_id) : null;

// Separate plans by billing interval
$monthly_plans = array();
$yearly_plans = array();
$lifetime_plans = array();

foreach ($plans as $plan) {
    if ($plan->interval === 'monthly') {
        $monthly_plans[] = $plan;
    } elseif ($plan->interval === 'yearly') {
        $yearly_plans[] = $plan;
    } else {
        $lifetime_plans[] = $plan;
    }
}

// Calculate savings percentage
$yearly_discount = 0;
if (!empty($yearly_plans) && !empty($monthly_plans)) {
    // Find matching plans
    foreach ($yearly_plans as $yearly_plan) {
        $yearly_discount = get_post_meta($yearly_plan->id, '_yearly_discount_percentage', true);
        if ($yearly_discount) break;
    }
}
?>

<div class="pro-pricing-wrapper">
    <!-- Header -->
    <div class="pro-header">
        <h1>Choose the perfect plan for you</h1>
        <p>Get started with our flexible pricing options designed to scale with your needs</p>
    </div>

    <!-- Benefits Section -->
    <div class="pro-benefits">
        <div class="pro-benefit-item">
            <div class="benefit-icon">🚀</div>
            <h3>Instant Access</h3>
            <p>Get immediate access to all premium content upon subscription</p>
        </div>
        <div class="pro-benefit-item">
            <div class="benefit-icon">🔒</div>
            <h3>Secure Payment</h3>
            <p>Your data is protected with enterprise-grade security</p>
        </div>
        <div class="pro-benefit-item">
            <div class="benefit-icon">💬</div>
            <h3>24/7 Support</h3>
            <p>Our team is always here to help you succeed</p>
        </div>
    </div>

    <?php if ($has_subscription && $current_subscription): 
        $current_plan = Premium_Content_Subscription_Manager::get_plan($current_subscription->plan_id);
    ?>
        <div class="pro-current-subscription">
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
        <div class="pro-no-plans">
            <p>No subscription plans are currently available.</p>
        </div>
    <?php else: ?>
        <!-- Billing Toggle -->
        <?php
        // Count plans by type to decide whether to show toggle
        $has_monthly = false;
        $has_yearly = false;

        foreach ($plans as $plan) {
            if ($plan->interval === 'monthly') $has_monthly = true;
            if ($plan->interval === 'yearly') $has_yearly = true;
        }
        ?>

        <!-- Billing Toggle - Only show if we have BOTH monthly and yearly plans -->
        <?php if ($has_monthly && $has_yearly): ?>
        <div class="pro-billing-toggle">
            <span class="toggle-label" id="pro-monthly-label">Monthly</span>
            <div class="pro-toggle-switch" id="pro-billing-toggle">
                <div class="pro-toggle-slider"></div>
            </div>
            <span class="toggle-label pro-active" id="pro-yearly-label">Yearly</span>
            <?php if ($yearly_discount): ?>
                <span class="pro-savings-badge">Save <?php echo intval($yearly_discount); ?>%</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Pricing Cards -->
        <div class="pro-pricing-cards">
            <?php 
            $all_plans = array_merge($monthly_plans, $yearly_plans, $lifetime_plans);
            $plan_count = count($all_plans);
            
            foreach ($all_plans as $index => $plan): 
                $features = json_decode($plan->features, true);
                $is_popular = ($index === 1 && $plan_count >= 3); // Middle plan
                $is_current = $has_subscription && $current_subscription && $current_subscription->plan_id == $plan->id;
                $is_free = ($plan->price == 0);
                $is_monthly = ($plan->interval === 'monthly');
                $is_yearly = ($plan->interval === 'yearly');
                $is_lifetime = ($plan->interval === 'lifetime');
                
                // Calculate monthly price for yearly plans
                $monthly_price = $is_yearly ? round($plan->price / 12, 2) : $plan->price;
            ?>
            <div class="pro-pricing-card <?php echo $is_popular ? 'pro-featured' : ''; ?> <?php echo $is_current ? 'pro-current' : ''; ?>" 
                data-interval="<?php echo esc_attr($plan->interval); ?>"
                <?php if ($has_monthly && $has_yearly): ?>
                    style="<?php echo ($is_yearly) ? '' : 'display: none;'; ?>"
                <?php endif; ?>>
                
                <?php if ($is_popular): ?>
                    <div class="pro-popular-badge">Most popular</div>
                <?php endif; ?>
                
                <div class="pro-card-content <?php echo $is_popular ? 'has-badge' : ''; ?>">
                    <div class="pro-plan-name"><?php echo esc_html($plan->name); ?></div>
                    <div class="pro-plan-description"><?php echo esc_html($plan->description); ?></div>
                    
                    <div class="pro-price-container">
                        <?php if ($is_free): ?>
                            <div class="pro-price">
                                <span class="pro-price-amount">Free</span>
                            </div>
                        <?php else: ?>
                            <div class="pro-price">
                                <span class="pro-price-currency">$</span>
                                <span class="pro-price-amount"><?php echo number_format($plan->price, 0); ?></span>
                            </div>
                            <div class="pro-price-period">per seat/<?php echo esc_html($plan->interval === 'lifetime' ? 'lifetime' : $plan->interval); ?></div>
                            <?php if ($is_yearly): ?>
                                <div class="pro-price-billing-note">$<?php echo number_format($monthly_price, 0); ?> billed monthly</div>
                            <?php elseif ($is_monthly): ?>
                                <div class="pro-price-billing-note">billed monthly</div>
                            <?php else: ?>
                                <div class="pro-price-billing-note">one-time payment</div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <?php if ($is_current): ?>
                        <button class="pro-cta-button pro-current-btn" disabled>Current Plan</button>
                    <?php elseif ($user_id): ?>
                        <?php if ($is_free): ?>
                            <a href="<?php echo esc_url(home_url()); ?>" class="pro-cta-button">
                                Continue with Free
                            </a>
                        <?php else: ?>
                            <a href="<?php echo esc_url(add_query_arg('plan', $plan->id, get_permalink(get_option('premium_content_page_checkout')))); ?>" 
                               class="pro-cta-button <?php echo $is_popular ? 'pro-primary' : ''; ?>">
                                <?php echo $has_subscription ? 'Switch Plan' : 'Try for free'; ?>
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="<?php echo esc_url(wp_login_url(add_query_arg('plan', $plan->id, get_permalink(get_option('premium_content_page_checkout'))))); ?>" 
                           class="pro-cta-button <?php echo $is_popular ? 'pro-primary' : ''; ?>">
                            <?php echo $is_free ? 'Sign up' : 'Try for free'; ?>
                        </a>
                    <?php endif; ?>

                    <?php if (!$is_current && !$is_free): ?>
                        <div class="pro-signup-link">
                            or <a href="<?php echo esc_url(wp_registration_url()); ?>">sign up now</a>
                        </div>
                    <?php endif; ?>

                    <?php if ($features && is_array($features)): ?>
                        <div class="pro-features-header">
                            <?php echo $is_free ? 'Free includes:' : 'Includes:'; ?>
                        </div>
                        <ul class="pro-features-list">
                            <?php foreach ($features as $feature): ?>
                                <li class="pro-feature-item"><?php echo esc_html($feature); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- FAQ Section -->
        <div class="pro-faq-section">
            <h2>Frequently Asked Questions</h2>
            
            <div class="pro-faq-item">
                <button class="pro-faq-question">
                    Can I change my plan later?
                </button>
                <div class="pro-faq-answer">
                    Yes! You can upgrade or downgrade your plan at any time from your account settings. When you upgrade, you'll be charged the prorated difference. When you downgrade, the change will take effect at the end of your current billing period.
                </div>
            </div>

            <div class="pro-faq-item">
                <button class="pro-faq-question">
                    What payment methods do you accept?
                </button>
                <div class="pro-faq-answer">
                    We accept all major credit cards through Stripe and PayPal payments. All transactions are secure and encrypted with industry-standard SSL technology.
                </div>
            </div>

            <div class="pro-faq-item">
                <button class="pro-faq-question">
                    Is there a free trial available?
                </button>
                <div class="pro-faq-answer">
                    Yes! All paid plans come with instant access. You can explore all the features and cancel anytime. No hidden fees or long-term commitments required.
                </div>
            </div>

            <div class="pro-faq-item">
                <button class="pro-faq-question">
                    What happens if I cancel?
                </button>
                <div class="pro-faq-answer">
                    You can cancel anytime from your account settings. You'll continue to have access to premium content until the end of your current billing period. No cancellation fees.
                </div>
            </div>

            <div class="pro-faq-item">
                <button class="pro-faq-question">
                    Do you offer refunds?
                </button>
                <div class="pro-faq-answer">
                    Yes, we offer a 30-day money-back guarantee for all paid plans. If you're not satisfied within the first 30 days, contact our support team for a full refund.
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Billing toggle functionality
    var toggle = $('#pro-billing-toggle');
    var monthlyLabel = $('#pro-monthly-label');
    var yearlyLabel = $('#pro-yearly-label');
    var isYearly = true;

    // Start with yearly selected
    toggle.addClass('pro-active');
    $('.pro-pricing-card[data-interval="yearly"]').show();
    $('.pro-pricing-card[data-interval="monthly"]').hide();
    $('.pro-pricing-card[data-interval="lifetime"]').show(); // Always show lifetime

    toggle.on('click', function() {
        isYearly = !isYearly;
        toggle.toggleClass('pro-active');
        
        if (isYearly) {
            monthlyLabel.removeClass('pro-active');
            yearlyLabel.addClass('pro-active');
            $('.pro-pricing-card[data-interval="monthly"]').fadeOut(200, function() {
                $('.pro-pricing-card[data-interval="yearly"]').fadeIn(200);
            });
        } else {
            yearlyLabel.removeClass('pro-active');
            monthlyLabel.addClass('pro-active');
            $('.pro-pricing-card[data-interval="yearly"]').fadeOut(200, function() {
                $('.pro-pricing-card[data-interval="monthly"]').fadeIn(200);
            });
        }
        
        // Always show lifetime
        $('.pro-pricing-card[data-interval="lifetime"]').show();
    });

    // FAQ accordion functionality
    $('.pro-faq-question').on('click', function() {
        var answer = $(this).next('.pro-faq-answer');
        var isActive = $(this).hasClass('pro-active');

        // Close all other FAQs
        $('.pro-faq-question').removeClass('pro-active');
        $('.pro-faq-answer').removeClass('pro-active');

        // Toggle current FAQ
        if (!isActive) {
            $(this).addClass('pro-active');
            answer.addClass('pro-active');
        }
    });
});
</script>