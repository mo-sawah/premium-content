<?php
/**
 * Template: Pricing Page - Modern Professional Design
 * Version: 2.0 - Complete Redesign
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

// Calculate savings for display
$yearly_savings = 0;
if ($has_monthly && $has_yearly) {
    $monthly_plan = $plans_by_interval['monthly'][0];
    $yearly_plan = $plans_by_interval['yearly'][0];
    $monthly_annual = $monthly_plan->price * 12;
    $yearly_savings = round((($monthly_annual - $yearly_plan->price) / $monthly_annual) * 100);
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
            <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
            </svg>
            <span>Secure Payment</span>
        </div>
        <div class="trust-badge">
            <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
            </svg>
            <span>Cancel Anytime</span>
        </div>
        <div class="trust-badge">
            <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
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
        <div class="pcp-pricing-grid <?php echo count($plans) === 2 ? 'two-col' : ''; ?>">
            <?php 
            $all_intervals = ['monthly', 'yearly', 'lifetime'];
            $plan_index = 0;
            
            foreach ($all_intervals as $interval):
                if (empty($plans_by_interval[$interval])) continue;
                
                foreach ($plans_by_interval[$interval] as $plan):
                    $features = json_decode($plan->features, true);
                    if (!is_array($features)) $features = [];
                    
                    $is_current = $has_subscription && $current_subscription && $current_subscription->plan_id == $plan->id;
                    $is_popular = ($plan_index === 1 && count($plans) >= 3); // Middle plan
                    
                    // Calculate monthly equivalent for yearly plans
                    $display_price = $plan->price;
                    $price_period = ucfirst($plan->interval);
                    $price_note = '';
                    
                    if ($plan->interval === 'yearly') {
                        $monthly_equiv = round($plan->price / 12, 2);
                        $price_note = sprintf('$%s billed monthly', number_format($monthly_equiv, 2));
                    } elseif ($plan->interval === 'monthly') {
                        $price_note = 'Billed monthly';
                    } elseif ($plan->interval === 'lifetime') {
                        $price_note = 'One-time payment';
                    }
                    
                    $card_classes = ['pcp-card'];
                    if ($is_popular) $card_classes[] = 'popular';
                    if ($is_current) $card_classes[] = 'current';
                    
                    // Add interval class for toggle
                    $card_classes[] = 'interval-' . $plan->interval;
                    
                    // Initially hide monthly plans if toggle exists
                    $initial_display = '';
                    if ($show_toggle && $plan->interval === 'monthly') {
                        $initial_display = ' style="display: none;"';
                    }
                    
                    $plan_index++;
            ?>
                <div class="<?php echo esc_attr(implode(' ', $card_classes)); ?>"<?php echo $initial_display; ?>>
                    <?php if ($is_popular): ?>
                        <div class="popular-badge">
                            <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
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
                            <span class="amount"><?php echo number_format($display_price, 0); ?></span>
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
                                        <svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20">
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
                        <svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="faq-answer">
                        <p>Yes! You can upgrade or downgrade your plan at any time from your account dashboard. Changes take effect immediately with prorated billing adjustments.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <h4>What payment methods do you accept?</h4>
                        <svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="faq-answer">
                        <p>We accept all major credit cards (Visa, MasterCard, American Express) via Stripe, and PayPal. All transactions are secured with 256-bit SSL encryption.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <h4>Is there a free trial?</h4>
                        <svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="faq-answer">
                        <p>All plans come with instant access. You can cancel anytime within the first 30 days for a full refund, no questions asked.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <h4>Can I cancel anytime?</h4>
                        <svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="faq-answer">
                        <p>Absolutely! You can cancel your subscription at any time from your account dashboard. You'll continue to have access until the end of your current billing period.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <h4>Do you offer refunds?</h4>
                        <svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="faq-answer">
                        <p>Yes, we offer a 30-day money-back guarantee on all plans. If you're not completely satisfied, contact support for a full refund within 30 days of purchase.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <h4>How secure is my payment?</h4>
                        <svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="faq-answer">
                        <p>Your payment information is protected with enterprise-grade security. We never store your card details on our servers - all payments are processed securely through Stripe and PayPal.</p>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>

<style>
/* Modern Pricing Page Styles */
.pcp-pricing-wrapper {
    max-width: 1400px;
    margin: 0 auto;
    padding: 60px 20px 100px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

/* Hero Section */
.pcp-hero {
    text-align: center;
    margin-bottom: 40px;
}

.pcp-hero h1 {
    font-size: clamp(2rem, 5vw, 3.5rem);
    font-weight: 700;
    color: #111827;
    margin: 0 0 16px 0;
    letter-spacing: -0.02em;
}

.pcp-hero p {
    font-size: clamp(1.125rem, 2vw, 1.375rem);
    color: #6b7280;
    margin: 0;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

/* Trust Badges */
.pcp-trust-badges {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 32px;
    margin-bottom: 60px;
    padding: 24px;
}

.trust-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #4b5563;
    font-size: 0.9rem;
    font-weight: 500;
}

.trust-badge svg {
    color: #10b981;
}

/* Current Subscription */
.pcp-current-subscription {
    max-width: 800px;
    margin: 0 auto 40px;
    padding: 16px 24px;
    background: #f0fdf4;
    border: 1px solid #86efac;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.pcp-current-subscription svg {
    color: #16a34a;
    flex-shrink: 0;
}

.pcp-current-subscription strong {
    display: block;
    color: #166534;
    font-size: 15px;
    margin-bottom: 4px;
}

.pcp-current-subscription a {
    color: #16a34a;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
}

.pcp-current-subscription a:hover {
    text-decoration: underline;
}

/* No Plans Message */
.pcp-no-plans {
    text-align: center;
    padding: 60px 20px;
}

.pcp-no-plans svg {
    color: #9ca3af;
    margin-bottom: 16px;
}

.pcp-no-plans p {
    color: #6b7280;
    font-size: 1.125rem;
}

/* Billing Toggle */
.pcp-billing-toggle {
    display: flex;
    justify-content: center;
    gap: 0;
    margin-bottom: 50px;
    background: #f3f4f6;
    padding: 4px;
    border-radius: 12px;
    width: fit-content;
    margin-left: auto;
    margin-right: auto;
}

.toggle-option {
    padding: 12px 32px;
    background: transparent;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    display: flex;
    align-items: center;
    gap: 8px;
}

.toggle-option:hover {
    color: #111827;
}

.toggle-option.active {
    background: white;
    color: #2563eb;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.savings-badge {
    background: #dcfce7;
    color: #166534;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 700;
}

/* Pricing Grid */
.pcp-pricing-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 32px;
    margin-bottom: 80px;
    max-width: 1200px;
    margin-left: auto;
    margin-right: auto;
}

.pcp-pricing-grid.two-col {
    max-width: 800px;
    grid-template-columns: repeat(2, 1fr);
}

/* Pricing Cards */
.pcp-card {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 16px;
    padding: 36px 32px;
    position: relative;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
}

.pcp-card:hover {
    border-color: #cbd5e1;
    transform: translateY(-4px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.pcp-card.popular {
    border-color: #2563eb;
    border-width: 2px;
    box-shadow: 0 12px 32px rgba(37, 99, 235, 0.15);
}

.pcp-card.popular:hover {
    border-color: #1d4ed8;
    box-shadow: 0 24px 48px rgba(37, 99, 235, 0.2);
}

.pcp-card.current {
    border-color: #10b981;
    opacity: 0.9;
}

/* Badges */
.popular-badge,
.current-badge {
    position: absolute;
    top: -1px;
    left: 0;
    right: 0;
    padding: 8px 16px;
    text-align: center;
    font-size: 0.813rem;
    font-weight: 700;
    border-radius: 14px 14px 0 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
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
    margin-top: 20px;
}

/* Card Header */
.pcp-card-header {
    margin-bottom: 24px;
}

.plan-name {
    font-size: 1.625rem;
    font-weight: 700;
    color: #111827;
    margin: 0 0 8px 0;
}

.plan-description {
    color: #6b7280;
    font-size: 0.938rem;
    line-height: 1.5;
    margin: 0;
}

/* Card Pricing */
.pcp-card-pricing {
    margin-bottom: 24px;
    padding-bottom: 24px;
    border-bottom: 1px solid #f3f4f6;
}

.price-display {
    display: flex;
    align-items: baseline;
    gap: 2px;
    line-height: 1;
    margin-bottom: 8px;
}

.currency {
    font-size: 2rem;
    font-weight: 700;
    color: #111827;
}

.amount {
    font-size: 4rem;
    font-weight: 700;
    color: #111827;
    letter-spacing: -0.02em;
}

.price-period {
    color: #6b7280;
    font-size: 1rem;
    font-weight: 500;
    margin-bottom: 4px;
}

.price-note {
    color: #9ca3af;
    font-size: 0.875rem;
}

/* Card CTA */
.pcp-card-cta {
    margin-bottom: 28px;
}

.pcp-btn {
    display: block;
    width: 100%;
    padding: 14px 24px;
    border-radius: 10px;
    font-size: 1rem;
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
    box-shadow: 0 8px 20px rgba(37, 99, 235, 0.4);
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

/* Card Features */
.pcp-card-features {
    flex-grow: 1;
}

.features-header {
    font-size: 0.875rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: 16px;
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
    gap: 12px;
    padding: 10px 0;
    color: #374151;
    font-size: 0.938rem;
    line-height: 1.5;
}

.features-list li svg {
    color: #10b981;
    flex-shrink: 0;
    margin-top: 2px;
}

/* FAQ Section */
.pcp-faq {
    max-width: 900px;
    margin: 0 auto;
    padding-top: 40px;
}

.pcp-faq h2 {
    text-align: center;
    font-size: 2.25rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: 48px;
}

.faq-grid {
    display: grid;
    gap: 16px;
}

.faq-item {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s ease;
}

.faq-item:hover {
    border-color: #cbd5e1;
}

.faq-question {
    width: 100%;
    padding: 20px 24px;
    background: transparent;
    border: none;
    text-align: left;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
}

.faq-question h4 {
    margin: 0;
    font-size: 1.063rem;
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
    max-height: 300px;
    padding: 0 24px 20px 24px;
}

.faq-answer p {
    margin: 0;
    color: #6b7280;
    font-size: 0.938rem;
    line-height: 1.6;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .pcp-pricing-grid {
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    }
    
    .pcp-pricing-grid.two-col {
        max-width: 100%;
    }
}

@media (max-width: 768px) {
    .pcp-pricing-wrapper {
        padding: 40px 16px 60px;
    }
    
    .pcp-hero h1 {
        font-size: 2rem;
    }
    
    .pcp-trust-badges {
        flex-direction: column;
        gap: 16px;
        align-items: center;
    }
    
    .pcp-pricing-grid,
    .pcp-pricing-grid.two-col {
        grid-template-columns: 1fr;
        gap: 24px;
    }
    
    .pcp-card {
        padding: 28px 24px;
    }
    
    .amount {
        font-size: 3rem;
    }
    
    .toggle-option {
        padding: 10px 20px;
        font-size: 0.938rem;
    }
}

@media (max-width: 480px) {
    .pcp-current-subscription {
        flex-direction: column;
        text-align: center;
    }
    
    .faq-question {
        padding: 16px 20px;
    }
    
    .faq-question h4 {
        font-size: 0.938rem;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    'use strict';
    
    // Billing Toggle Logic
    const $monthlyToggle = $('#monthly-toggle');
    const $yearlyToggle = $('#yearly-toggle');
    const $monthlyCards = $('.interval-monthly');
    const $yearlyCards = $('.interval-yearly');
    const $lifetimeCards = $('.interval-lifetime');
    
    // Only set up toggle if both monthly and yearly plans exist
    if ($monthlyToggle.length && $yearlyToggle.length) {
        
        $monthlyToggle.on('click', function() {
            if ($(this).hasClass('active')) return;
            
            // Update toggle state
            $monthlyToggle.addClass('active');
            $yearlyToggle.removeClass('active');
            
            // Show monthly, hide yearly, always show lifetime
            $monthlyCards.fadeIn(300);
            $yearlyCards.fadeOut(300);
            $lifetimeCards.fadeIn(300);
        });
        
        $yearlyToggle.on('click', function() {
            if ($(this).hasClass('active')) return;
            
            // Update toggle state
            $yearlyToggle.addClass('active');
            $monthlyToggle.removeClass('active');
            
            // Show yearly, hide monthly, always show lifetime
            $yearlyCards.fadeIn(300);
            $monthlyCards.fadeOut(300);
            $lifetimeCards.fadeIn(300);
        });
    }
    
    // FAQ Accordion
    $('.faq-question').on('click', function() {
        const $item = $(this).closest('.faq-item');
        const isActive = $item.hasClass('active');
        
        // Close all FAQs
        $('.faq-item').removeClass('active');
        
        // Open clicked FAQ if it wasn't active
        if (!isActive) {
            $item.addClass('active');
        }
    });
    
    // Smooth scroll for anchor links
    $('a[href^="#"]').on('click', function(e) {
        const target = $(this.getAttribute('href'));
        if (target.length) {
            e.preventDefault();
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 100
            }, 600);
        }
    });
});
</script>

<?php