<?php
/**
 * Template: Checkout Page
 * Handles payment gateway selection and processing
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if user is logged in
if (!is_user_logged_in()) {
    ?>
    <div class="premium-checkout-wrapper">
        <div class="premium-alert premium-alert-warning">
            <strong>Please log in to continue</strong>
            <p>You need to be logged in to purchase a subscription.</p>
            <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="premium-button premium-button-primary">
                Log In
            </a>
            <a href="<?php echo esc_url(wp_registration_url()); ?>" class="premium-button premium-button-secondary">
                Create Account
            </a>
        </div>
    </div>
    <?php
    return;
}

// Get plan from URL
$plan_id = isset($_GET['plan']) ? intval($_GET['plan']) : 0;
$plan = Premium_Content_Subscription_Manager::get_plan($plan_id);

if (!$plan || $plan->status !== 'active') {
    ?>
    <div class="premium-checkout-wrapper">
        <div class="premium-alert premium-alert-error">
            <strong>Invalid Plan</strong>
            <p>The selected plan is not available.</p>
            <a href="<?php echo esc_url(get_permalink(get_option('premium_content_page_pricing'))); ?>" class="premium-button premium-button-primary">
                View Available Plans
            </a>
        </div>
    </div>
    <?php
    return;
}

// Check payment gateways
$stripe_enabled = premium_content_get_option('stripe_enabled', '0') === '1';
$paypal_enabled = premium_content_get_option('paypal_enabled', '0') === '1';

if (!$stripe_enabled && !$paypal_enabled) {
    ?>
    <div class="premium-checkout-wrapper">
        <div class="premium-alert premium-alert-error">
            <strong>Payment Not Available</strong>
            <p>No payment methods are currently configured. Please contact support.</p>
        </div>
    </div>
    <?php
    return;
}

$features = json_decode($plan->features, true);
$current_user = wp_get_current_user();
?>

<div class="premium-checkout-wrapper">
    <div class="premium-checkout-container">
        <!-- Order Summary -->
        <div class="checkout-summary">
            <h2>Order Summary</h2>
            
            <div class="summary-plan">
                <div class="summary-plan-header">
                    <h3><?php echo esc_html($plan->name); ?></h3>
                    <span class="plan-interval"><?php echo ucfirst(esc_html($plan->interval)); ?></span>
                </div>
                
                <?php if ($plan->description): ?>
                    <p class="summary-plan-description"><?php echo esc_html($plan->description); ?></p>
                <?php endif; ?>

                <?php if ($features && is_array($features)): ?>
                    <ul class="summary-features">
                        <?php foreach (array_slice($features, 0, 4) as $feature): ?>
                            <li><?php echo esc_html($feature); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="summary-pricing">
                <div class="pricing-row">
                    <span>Subtotal</span>
                    <span class="price">$<?php echo number_format($plan->price, 2); ?></span>
                </div>
                <div class="pricing-row pricing-total">
                    <span><strong>Total</strong></span>
                    <span class="price"><strong>$<?php echo number_format($plan->price, 2); ?></strong></span>
                </div>
                <p class="billing-note">
                    <?php if ($plan->interval === 'lifetime'): ?>
                        One-time payment
                    <?php else: ?>
                        Billed <?php echo esc_html($plan->interval); ?>. Cancel anytime.
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="checkout-payment">
            <h2>Select Payment Method</h2>
            
            <div class="payment-info">
                <p><strong>Account:</strong> <?php echo esc_html($current_user->user_email); ?></p>
            </div>

            <div class="payment-methods">
                <?php if ($stripe_enabled): ?>
                    <button id="stripe-checkout-btn" class="payment-method-button stripe-button" data-plan="<?php echo esc_attr($plan_id); ?>">
                        <div class="payment-method-content">
                            <div class="payment-icon">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M13.976 9.15c-2.172-.806-3.356-1.426-3.356-2.409 0-.831.683-1.305 1.901-1.305 2.227 0 4.515.858 6.09 1.631l.89-5.494C18.252.975 15.697 0 12.165 0 9.667 0 7.589.654 6.104 1.872 4.56 3.147 3.757 4.992 3.757 7.218c0 4.039 2.467 5.76 6.476 7.219 2.585.92 3.445 1.571 3.445 2.712 0 .81-.699 1.488-2.301 1.488-2.519 0-5.355-1.021-7.17-2.062l-.955 5.521c1.062.52 3.674 1.488 6.939 1.488 2.64 0 4.842-.627 6.328-1.813 1.573-1.259 2.405-3.146 2.405-5.458 0-3.509-2.406-5.448-6.948-7.143z"/>
                                </svg>
                            </div>
                            <div class="payment-method-text">
                                <div class="payment-method-name">Credit Card</div>
                                <div class="payment-method-desc">Secure payment via Stripe</div>
                            </div>
                        </div>
                        <span class="payment-arrow">→</span>
                    </button>
                <?php endif; ?>

                <?php if ($paypal_enabled): ?>
                    <button id="paypal-checkout-btn" class="payment-method-button paypal-button" data-plan="<?php echo esc_attr($plan_id); ?>">
                        <div class="payment-method-content">
                            <div class="payment-icon paypal-icon">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944.901C5.026.382 5.474 0 5.998 0h7.46c2.57 0 4.578.543 5.69 1.81 1.01 1.15 1.304 2.42 1.012 4.287-.023.143-.047.288-.077.437-.983 5.05-4.349 6.797-8.647 6.797h-2.19c-.524 0-.968.382-1.05.9l-1.12 7.106zm14.146-14.42a3.35 3.35 0 0 0-.607-.541c-.013.076-.026.175-.041.254-.93 4.778-4.005 7.201-9.138 7.201h-2.19a.563.563 0 0 0-.556.479l-1.187 7.527h-.506l-.24 1.516a.56.56 0 0 0 .554.647h3.882c.46 0 .85-.334.922-.788.06-.26.76-4.852.816-5.09a.932.932 0 0 1 .923-.788h.58c3.76 0 6.705-1.528 7.565-5.946.36-1.847.174-3.388-.777-4.471z"/>
                                </svg>
                            </div>
                            <div class="payment-method-text">
                                <div class="payment-method-name">PayPal</div>
                                <div class="payment-method-desc">Pay with your PayPal account</div>
                            </div>
                        </div>
                        <span class="payment-arrow">→</span>
                    </button>
                <?php endif; ?>
            </div>

            <div id="payment-error" class="premium-alert premium-alert-error" style="display: none; margin-top: 20px;">
                <strong>Payment Error</strong>
                <p id="payment-error-message"></p>
            </div>

            <div class="checkout-footer">
                <p class="secure-payment">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                        <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/>
                    </svg>
                    Secure 256-bit SSL encrypted payment
                </p>
                <p class="terms-note">
                    By completing this purchase, you agree to our 
                    <a href="<?php echo esc_url(home_url('/terms')); ?>">Terms of Service</a> and 
                    <a href="<?php echo esc_url(home_url('/privacy')); ?>">Privacy Policy</a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var processing = false;

    function showError(message) {
        $('#payment-error-message').text(message);
        $('#payment-error').slideDown();
        setTimeout(function() {
            $('#payment-error').slideUp();
        }, 5000);
    }

    function disableButtons() {
        $('.payment-method-button').prop('disabled', true).addClass('processing');
    }

    function enableButtons() {
        $('.payment-method-button').prop('disabled', false).removeClass('processing');
    }

    $('#stripe-checkout-btn').on('click', function() {
        if (processing) return;
        processing = true;
        
        var $btn = $(this);
        var originalHtml = $btn.html();
        $btn.html('<span class="spinner"></span> Processing...');
        disableButtons();
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'premium_create_stripe_checkout',
                nonce: '<?php echo wp_create_nonce('premium_checkout'); ?>',
                plan_id: $(this).data('plan')
            },
            success: function(response) {
                if (response.success && response.data.url) {
                    window.location.href = response.data.url;
                } else {
                    showError(response.data || 'Failed to initialize payment. Please try again.');
                    $btn.html(originalHtml);
                    enableButtons();
                    processing = false;
                }
            },
            error: function() {
                showError('Network error. Please check your connection and try again.');
                $btn.html(originalHtml);
                enableButtons();
                processing = false;
            }
        });
    });
    
    $('#paypal-checkout-btn').on('click', function() {
        if (processing) return;
        processing = true;
        
        var $btn = $(this);
        var originalHtml = $btn.html();
        $btn.html('<span class="spinner"></span> Processing...');
        disableButtons();
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'premium_create_paypal_order',
                nonce: '<?php echo wp_create_nonce('premium_checkout'); ?>',
                plan_id: $(this).data('plan')
            },
            success: function(response) {
                if (response.success && response.data.approve_url) {
                    window.location.href = response.data.approve_url;
                } else {
                    showError(response.data || 'Failed to initialize PayPal. Please try again.');
                    $btn.html(originalHtml);
                    enableButtons();
                    processing = false;
                }
            },
            error: function() {
                showError('Network error. Please check your connection and try again.');
                $btn.html(originalHtml);
                enableButtons();
                processing = false;
            }
        });
    });
});
</script>

<style>
.premium-checkout-wrapper {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
}

.premium-checkout-container {
    display: grid;
    grid-template-columns: 1fr 1.5fr;
    gap: 40px;
}

.checkout-summary {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 32px;
    height: fit-content;
    position: sticky;
    top: 20px;
}

.checkout-summary h2 {
    margin: 0 0 24px 0;
    font-size: 20px;
    color: #2c3e50;
}

.summary-plan {
    margin-bottom: 24px;
    padding-bottom: 24px;
    border-bottom: 1px solid #e5e7eb;
}

.summary-plan-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.summary-plan-header h3 {
    margin: 0;
    font-size: 18px;
    color: #2c3e50;
}

.plan-interval {
    background: #667eea;
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.summary-plan-description {
    color: #6b7280;
    font-size: 14px;
    margin: 8px 0 16px 0;
}

.summary-features {
    list-style: none;
    padding: 0;
    margin: 0;
}

.summary-features li {
    padding: 8px 0;
    padding-left: 24px;
    position: relative;
    font-size: 14px;
    color: #374151;
}

.summary-features li:before {
    content: "✓";
    position: absolute;
    left: 0;
    color: #00a32a;
    font-weight: 700;
}

.summary-pricing {
    padding-top: 16px;
}

.pricing-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    font-size: 15px;
    color: #374151;
}

.pricing-total {
    border-top: 2px solid #e5e7eb;
    margin-top: 8px;
    padding-top: 16px;
    font-size: 18px;
}

.price {
    color: #2c3e50;
}

.billing-note {
    margin: 16px 0 0 0;
    font-size: 13px;
    color: #6b7280;
    text-align: center;
    font-style: italic;
}

.checkout-payment {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 32px;
}

.checkout-payment h2 {
    margin: 0 0 24px 0;
    font-size: 20px;
    color: #2c3e50;
}

.payment-info {
    margin-bottom: 24px;
    padding: 16px;
    background: #f9fafb;
    border-radius: 8px;
}

.payment-info p {
    margin: 0;
    font-size: 14px;
    color: #374151;
}

.payment-methods {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.payment-method-button {
    width: 100%;
    padding: 20px;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-family: inherit;
}

.payment-method-button:hover:not(:disabled) {
    border-color: #667eea;
    background: #f8f9ff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
}

.payment-method-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.payment-method-content {
    display: flex;
    align-items: center;
    gap: 16px;
}

.payment-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.payment-icon svg {
    width: 24px;
    height: 24px;
}

.payment-icon.paypal-icon {
    background: #0070ba;
}

.payment-method-text {
    text-align: left;
}

.payment-method-name {
    font-size: 16px;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 4px;
}

.payment-method-desc {
    font-size: 13px;
    color: #6b7280;
}

.payment-arrow {
    font-size: 24px;
    color: #667eea;
}

.checkout-footer {
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid #e5e7eb;
    text-align: center;
}

.secure-payment {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: #00a32a;
    font-size: 14px;
    font-weight: 600;
    margin: 0 0 12px 0;
}

.terms-note {
    font-size: 12px;
    color: #6b7280;
    margin: 0;
}

.terms-note a {
    color: #667eea;
    text-decoration: none;
}

.spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(102, 126, 234, 0.3);
    border-radius: 50%;
    border-top-color: #667eea;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

@media (max-width: 768px) {
    .premium-checkout-container {
        grid-template-columns: 1fr;
    }
    
    .checkout-summary {
        position: static;
    }
}
</style>