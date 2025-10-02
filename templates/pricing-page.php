<?php
/**
 * Template: Pricing Page - Fixed Division by Zero Error
 */

if (!defined('ABSPATH')) {
    exit;
}

$plans = Premium_Content_Subscription_Manager::get_plans('active');
$user_id = get_current_user_id();
$has_subscription = Premium_Content_Subscription_Manager::user_has_active_subscription($user_id);
$current_subscription = $has_subscription ? Premium_Content_Subscription_Manager::get_user_subscription($user_id) : null;

// Organize plans by interval
$plans_by_interval = [
    'monthly' => [],
    'yearly' => [],
    'lifetime' => []
];

foreach ($plans as $plan) {
    if (isset($plans_by_interval[$plan->interval])) {
        $plans_by_interval[$plan->interval][] = $plan;
    }
}

// Determine if we should show the toggle
$has_monthly = !empty($plans_by_interval['monthly']);
$has_yearly = !empty($plans_by_interval['yearly']);
$show_toggle = $has_monthly && $has_yearly;

// Calculate savings - FIX DIVISION BY ZERO
$yearly_savings = 0;
if ($has_monthly && $has_yearly) {
    $monthly_plan = $plans_by_interval['monthly'][0];
    $yearly_plan = $plans_by_interval['yearly'][0];
    $monthly_annual = $monthly_plan->price * 12;
    
    // Only calculate if monthly_annual is greater than 0
    if ($monthly_annual > 0 && $yearly_plan->price < $monthly_annual) {
        $yearly_savings = round((($monthly_annual - $yearly_plan->price) / $monthly_annual) * 100);
    }
}
?>

<div class="pcp-pricing-wrapper">
    <!-- Hero Section -->
    <div class="pcp-hero">
        <h1>Choose Your Perfect Plan</h1>
        <p>Get started with flexible pricing designed to scale with your needs</p>
    </div>

    <!-- Trust Badges -->
    <div class="pcp-trust-badges">
        <div class="trust-badge">
            <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
            </svg>
            <span>Secure Payment</span>
        </div>
        <div class="trust-badge">
            <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
            </svg>
            <span>Cancel Anytime</span>
        </div>
        <div class="trust-badge">
            <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                <path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/>
            </svg>
            <span>Money-Back Guarantee</span>
        </div>
    </div>

    <?php if ($has_subscription && $current_subscription): 
        $current_plan = Premium_Content_Subscription_Manager::get_plan($current_subscription->plan_id);
    ?>
        <div class="pcp-current-subscription">
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
        <div class="pcp-no-plans">
            <svg viewBox="0 0 24 24" fill="currentColor" width="48" height="48">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
            </svg>
            <p>No subscription plans are currently available. Please check back soon!</p>
        </div>
    <?php else: ?>
        
        <?php if ($show_toggle): ?>
        <!-- Billing Toggle -->
        <div class="pcp-billing-toggle">
            <button class="toggle-option" data-interval="monthly" id="monthly-toggle">
                Monthly
            </button>
            <button class="toggle-option active" data-interval="yearly" id="yearly-toggle">
                Yearly
                <?php if ($yearly_savings > 0): ?>
                    <span class="savings-badge">Save <?php echo $yearly_savings; ?>%</span>
                <?php endif; ?>
            </button>
        </div>
        <?php endif; ?>

        <!-- Pricing Cards -->
        <div class="pcp-pricing-grid">
            <?php 
            $all_intervals = ['monthly', 'yearly', 'lifetime'];
            $plan_index = 0;
            $total_plans = count($plans);
            
            foreach ($all_intervals as $interval):
                if (empty($plans_by_interval[$interval])) continue;
                
                foreach ($plans_by_interval[$interval] as $plan):
                    $features = json_decode($plan->features, true);
                    if (!is_array($features)) $features = [];
                    
                    $is_current = $has_subscription && $current_subscription && $current_subscription->plan_id == $plan->id;
                    $is_popular = ($plan_index === 1 && $total_plans >= 3);
                    
                    // Format price with decimals
                    $display_price = number_format($plan->price, 2);
                    $price_period = ucfirst($plan->interval);
                    $price_note = '';
                    
                    if ($plan->interval === 'yearly') {
                        $monthly_equiv = $plan->price / 12;
                        $price_note = sprintf('$%s/mo', number_format($monthly_equiv, 2));
                    } elseif ($plan->interval === 'monthly') {
                        $price_note = 'Billed monthly';
                    } elseif ($plan->interval === 'lifetime') {
                        $price_note = 'One-time payment';
                    }
                    
                    $card_classes = ['pcp-card'];
                    if ($is_popular) $card_classes[] = 'popular';
                    if ($is_current) $card_classes[] = 'current';
                    $card_classes[] = 'interval-' . $plan->interval;
                    
                    $initial_display = '';
                    if ($show_toggle && $plan->interval === 'monthly') {
                        $initial_display = ' style="display: none;"';
                    }
                    
                    $plan_index++;
            ?>
                <div class="<?php echo esc_attr(implode(' ', $card_classes)); ?>"<?php echo $initial_display; ?>>
                    <?php if ($is_popular): ?>
                        <div class="popular-badge">
                            <svg viewBox="0 0 24 24" fill="currentColor" width="14" height="14">
                                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                            </svg>
                            Most Popular
                        </div>
                    <?php endif; ?>

                    <?php if ($is_current): ?>
                        <div class="current-badge">Current Plan</div>
                    <?php endif; ?>

                    <div class="pcp-card-header">
                        <h3 class="plan-name"><?php echo esc_html($plan->name); ?></h3>
                        <?php if ($plan->description): ?>
                            <p class="plan-description"><?php echo esc_html($plan->description); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="pcp-card-pricing">
                        <div class="price-display">
                            <span class="currency">$</span>
                            <span class="amount"><?php echo $display_price; ?></span>
                        </div>
                        <div class="price-period"><?php echo esc_html($price_period); ?></div>
                        <?php if ($price_note): ?>
                            <div class="price-note"><?php echo esc_html($price_note); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="pcp-card-cta">
                        <?php if ($is_current): ?>
                            <button class="pcp-btn disabled" disabled>Current Plan</button>
                        <?php elseif ($user_id): ?>
                            <a href="<?php echo esc_url(add_query_arg('plan', $plan->id, get_permalink(get_option('premium_content_page_checkout')))); ?>" 
                               class="pcp-btn <?php echo $is_popular ? 'primary' : 'secondary'; ?>">
                                <?php echo $has_subscription ? 'Switch Plan' : 'Get Started'; ?>
                            </a>
                        <?php else: ?>
                            <a href="<?php echo esc_url(wp_login_url(add_query_arg('plan', $plan->id, get_permalink(get_option('premium_content_page_checkout'))))); ?>" 
                               class="pcp-btn <?php echo $is_popular ? 'primary' : 'secondary'; ?>">
                                Get Started
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($features)): ?>
                        <div class="pcp-card-features">
                            <div class="features-header">What's included:</div>
                            <ul class="features-list">
                                <?php foreach ($features as $feature): ?>
                                    <li>
                                        <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <span><?php echo esc_html($feature); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            <?php 
                endforeach;
            endforeach; 
            ?>
        </div>

        <!-- FAQ Section -->
        <div class="pcp-faq">
            <h2>Frequently Asked Questions</h2>
            <div class="faq-grid">
                <div class="faq-item">
                    <div class="faq-question">
                        <h4>Can I change my plan later?</h4>
                        <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="faq-answer">
                        <p>Yes! You can upgrade or downgrade your plan at any time from your account dashboard.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <h4>What payment methods do you accept?</h4>
                        <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="faq-answer">
                        <p>We accept all major credit cards via Stripe, and PayPal. All transactions are secured with SSL encryption.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <h4>Can I cancel anytime?</h4>
                        <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="faq-answer">
                        <p>Absolutely! Cancel anytime from your dashboard. You'll keep access until the end of your billing period.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <h4>Do you offer refunds?</h4>
                        <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="faq-answer">
                        <p>Yes, we offer a 30-day money-back guarantee on all plans. Not satisfied? Get a full refund.</p>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>

<style>
/* Pricing Page Styles */
.pcp-pricing-wrapper {
    max-width: 1400px;
    margin: 0 auto;
    padding: 50px 20px 80px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.pcp-hero {
    text-align: center;
    margin-bottom: 32px;
}

.pcp-hero h1 {
    font-size: clamp(2rem, 4vw, 2.75rem);
    font-weight: 700;
    color: #111827;
    margin: 0 0 12px 0;
    letter-spacing: -0.02em;
}

.pcp-hero p {
    font-size: clamp(1rem, 2vw, 1.188rem);
    color: #6b7280;
    margin: 0;
}

.pcp-trust-badges {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 24px;
    margin-bottom: 40px;
    padding: 16px;
}

.trust-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #4b5563;
    font-size: 0.875rem;
    font-weight: 500;
}

.trust-badge svg {
    color: #10b981;
}

.pcp-current-subscription {
    max-width: 800px;
    margin: 0 auto 32px;
    padding: 12px 20px;
    background: #f0fdf4;
    border: 1px solid #86efac;
    border-radius: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.pcp-current-subscription svg {
    color: #16a34a;
    flex-shrink: 0;
}

.pcp-current-subscription strong {
    display: block;
    color: #166534;
    font-size: 14px;
    margin-bottom: 2px;
}

.pcp-current-subscription a {
    color: #16a34a;
    text-decoration: none;
    font-weight: 600;
    font-size: 13px;
}

.pcp-no-plans {
    text-align: center;
    padding: 50px 20px;
}

.pcp-no-plans svg {
    color: #9ca3af;
    margin-bottom: 12px;
}

.pcp-no-plans p {
    color: #6b7280;
    font-size: 1.063rem;
}

.pcp-billing-toggle {
    display: inline-flex;
    gap: 0;
    margin: 0 auto 40px;
    background: #f3f4f6;
    padding: 6px;
    border-radius: 12px;
    position: relative;
    left: 50%;
    transform: translateX(-50%);
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.toggle-option {
    padding: 12px 28px;
    background: transparent;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.25s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
}

.toggle-option:hover {
    color: #111827;
}

.toggle-option.active {
    background: white;
    color: #2563eb;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.savings-badge {
    background: #dcfce7;
    color: #166534;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 700;
}

.pcp-pricing-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 60px;
}

.pcp-card {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px 16px;
    position: relative;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
}

.pcp-card:hover {
    border-color: #cbd5e1;
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.1);
}

.pcp-card.popular {
    border-color: #2563eb;
    box-shadow: 0 8px 24px rgba(37, 99, 235, 0.12);
}

.pcp-card.popular:hover {
    box-shadow: 0 16px 40px rgba(37, 99, 235, 0.18);
}

.pcp-card.current {
    border-color: #10b981;
    opacity: 0.9;
}

.popular-badge,
.current-badge {
    position: absolute;
    top: -1px;
    left: 0;
    right: 0;
    padding: 6px 12px;
    text-align: center;
    font-size: 0.75rem;
    font-weight: 700;
    border-radius: 10px 10px 0 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
}

.popular-badge {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: white;
}

.current-badge {
    background: #10b981;
    color: white;
}

.pcp-card.popular .pcp-card-header,
.pcp-card.current .pcp-card-header {
    margin-top: 16px;
}

.pcp-card-header {
    margin-bottom: 16px;
}

.plan-name {
    font-size: 1.375rem;
    font-weight: 700;
    color: #111827;
    margin: 0 0 6px 0;
}

.plan-description {
    color: #6b7280;
    font-size: 0.875rem;
    line-height: 1.4;
    margin: 0;
}

.pcp-card-pricing {
    margin-bottom: 16px;
    padding-bottom: 16px;
    border-bottom: 1px solid #f3f4f6;
}

.price-display {
    display: flex;
    align-items: baseline;
    gap: 2px;
    line-height: 1;
    margin-bottom: 6px;
}

.currency {
    font-size: 1.5rem;
    font-weight: 700;
    color: #111827;
}

.amount {
    font-size: 3rem;
    font-weight: 700;
    color: #111827;
    letter-spacing: -0.02em;
}

.price-period {
    color: #6b7280;
    font-size: 0.938rem;
    font-weight: 500;
    margin-bottom: 2px;
}

.price-note {
    color: #9ca3af;
    font-size: 0.813rem;
}

.pcp-card-cta {
    margin-bottom: 16px;
}

.pcp-btn {
    display: block;
    width: 100%;
    padding: 11px 20px;
    border-radius: 8px;
    font-size: 0.938rem;
    font-weight: 600;
    text-align: center;
    text-decoration: none;
    transition: all 0.2s ease;
    border: 2px solid transparent;
}

.pcp-btn.primary {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: white;
}

.pcp-btn.primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
}

.pcp-btn.secondary {
    background: white;
    color: #2563eb;
    border-color: #2563eb;
}

.pcp-btn.secondary:hover {
    background: #eff6ff;
}

.pcp-btn.disabled {
    background: #f3f4f6;
    color: #9ca3af;
    cursor: not-allowed;
}

.pcp-card-features {
    flex-grow: 1;
}

.features-header {
    font-size: 0.813rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.features-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.features-list li {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 1px 0;
    color: #374151;
    font-size: 0.875rem;
    line-height: 1.4;
}

.features-list li svg {
    color: #10b981;
    flex-shrink: 0;
    margin-top: 2px;
}

.pcp-faq {
    max-width: 900px;
    margin: 0 auto;
    padding-top: 40px;
}

.pcp-faq h2 {
    text-align: center;
    font-size: 2rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: 32px;
}

.faq-grid {
    display: grid;
    gap: 12px;
}

.faq-item {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    overflow: hidden;
    transition: all 0.2s ease;
}

.faq-item:hover {
    border-color: #cbd5e1;
}

.faq-question {
    width: 100%;
    padding: 16px 20px;
    background: transparent;
    border: none;
    text-align: left;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
}

.faq-question h4 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: #111827;
}

.faq-question svg {
    color: #6b7280;
    flex-shrink: 0;
    transition: transform 0.3s ease;
}

.faq-item.active .faq-question svg {
    transform: rotate(180deg);
}

.faq-answer {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.faq-item.active .faq-answer {
    max-height: 200px;
    padding: 0 20px 16px 20px;
}

.faq-answer p {
    margin: 0;
    color: #6b7280;
    font-size: 0.875rem;
    line-height: 1.5;
}

@media (max-width: 1200px) {
    .pcp-pricing-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .pcp-pricing-wrapper {
        padding: 40px 16px 60px;
    }
    
    .pcp-trust-badges {
        flex-direction: column;
        gap: 12px;
        align-items: center;
    }
    
    .pcp-pricing-grid {
        grid-template-columns: 1fr;
    }
    
    .toggle-option {
        padding: 10px 20px;
        font-size: 0.938rem;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    'use strict';
    
    const $monthlyToggle = $('#monthly-toggle');
    const $yearlyToggle = $('#yearly-toggle');
    const $monthlyCards = $('.interval-monthly');
    const $yearlyCards = $('.interval-yearly');
    const $lifetimeCards = $('.interval-lifetime');
    
    if ($monthlyToggle.length && $yearlyToggle.length) {
        $monthlyToggle.on('click', function() {
            if ($(this).hasClass('active')) return;
            
            $monthlyToggle.addClass('active');
            $yearlyToggle.removeClass('active');
            
            $monthlyCards.fadeIn(250);
            $yearlyCards.fadeOut(250);
            $lifetimeCards.fadeIn(250);
        });
        
        $yearlyToggle.on('click', function() {
            if ($(this).hasClass('active')) return;
            
            $yearlyToggle.addClass('active');
            $monthlyToggle.removeClass('active');
            
            $yearlyCards.fadeIn(250);
            $monthlyCards.fadeOut(250);
            $lifetimeCards.fadeIn(250);
        });
    }
    
    $('.faq-question').on('click', function() {
        const $item = $(this).closest('.faq-item');
        const isActive = $item.hasClass('active');
        
        $('.faq-item').removeClass('active');
        
        if (!isActive) {
            $item.addClass('active');
        }
    });
});
</script>