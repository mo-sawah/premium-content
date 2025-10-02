<?php
/**
 * Template: Checkout Page - Fixed Layout Issues
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check if user is logged in
if (!is_user_logged_in()) {
    ?>
    <div class="pcp-checkout-wrapper">
        <div class="pcp-auth-required">
            <svg viewBox="0 0 24 24" fill="currentColor" width="48" height="48">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
            </svg>
            <h2>Sign in to continue</h2>
            <p>You need to be logged in to complete your purchase</p>
            <div class="auth-actions">
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="pcp-btn primary">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
                        <path d="M11 7L9.6 8.4l2.6 2.6H2v2h10.2l-2.6 2.6L11 17l5-5-5-5zm9 12h-8v2h8c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-8v2h8v14z"/>
                    </svg>
                    Sign In
                </a>
                <a href="<?php echo esc_url(wp_registration_url()); ?>" class="pcp-btn secondary">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
                        <path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                    Create Account
                </a>
            </div>
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
    <div class="pcp-checkout-wrapper">
        <div class="pcp-error-state">
            <svg viewBox="0 0 24 24" fill="currentColor" width="48" height="48">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
            </svg>
            <h2>Plan Not Available</h2>
            <p>The selected subscription plan is not currently available</p>
            <a href="<?php echo esc_url(get_permalink(get_option('premium_content_page_pricing'))); ?>" class="pcp-btn primary">
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
    <div class="pcp-checkout-wrapper">
        <div class="pcp-error-state">
            <svg viewBox="0 0 24 24" fill="currentColor" width="48" height="48">
                <path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/>
            </svg>
            <h2>Payment Unavailable</h2>
            <p>Payment processing is currently unavailable. Please contact support.</p>
        </div>
    </div>
    <?php
    return;
}

$features = json_decode($plan->features, true);
$current_user = wp_get_current_user();
?>

<div class="pcp-checkout-wrapper">
    <!-- Progress Steps -->
    <div class="checkout-progress">
        <div class="progress-step completed">
            <div class="step-number">1</div>
            <div class="step-label">Plan</div>
        </div>
        <div class="progress-line"></div>
        <div class="progress-step active">
            <div class="step-number">2</div>
            <div class="step-label">Payment</div>
        </div>
        <div class="progress-line"></div>
        <div class="progress-step">
            <div class="step-number">3</div>
            <div class="step-label">Confirm</div>
        </div>
    </div>

    <div class="checkout-container">
        <!-- Order Summary Sidebar -->
        <div class="checkout-sidebar">
            <div class="summary-card">
                <h3>Order Summary</h3>
                
                <div class="summary-plan">
                    <div class="plan-badge <?php echo $plan->interval; ?>">
                        <?php echo ucfirst($plan->interval); ?>
                    </div>
                    <h4><?php echo esc_html($plan->name); ?></h4>
                    <?php if ($plan->description): ?>
                        <p class="plan-desc"><?php echo esc_html($plan->description); ?></p>
                    <?php endif; ?>
                </div>

                <?php if ($features && is_array($features)): ?>
                    <div class="summary-features">
                        <div class="features-title">Included features:</div>
                        <ul>
                            <?php foreach (array_slice($features, 0, 5) as $feature): ?>
                                <li>
                                    <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <?php echo esc_html($feature); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="summary-pricing">
                    <div class="price-row">
                        <span>Subtotal</span>
                        <span class="amount">$<?php echo number_format($plan->price, 2); ?></span>
                    </div>
                    <div class="price-row total">
                        <span>Total</span>
                        <span class="amount">$<?php echo number_format($plan->price, 2); ?></span>
                    </div>
                    <div class="billing-cycle">
                        <?php if ($plan->interval === 'lifetime'): ?>
                            One-time payment • Lifetime access
                        <?php else: ?>
                            Billed <?php echo esc_html($plan->interval); ?> • Cancel anytime
                        <?php endif; ?>
                    </div>
                </div>

                <div class="money-back">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                        <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
                    </svg>
                    30-day money-back guarantee
                </div>
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="checkout-main">
            <div class="checkout-header">
                <h2>Complete Your Purchase</h2>
                <div class="user-info">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                    </svg>
                    <?php echo esc_html($current_user->user_email); ?>
                </div>
            </div>

            <div class="payment-methods">
                <h3>Select payment method</h3>
                
                <?php if ($stripe_enabled): ?>
                <div class="payment-option" id="stripe-option" data-plan="<?php echo esc_attr($plan_id); ?>">
                    <div class="payment-radio">
                        <input type="radio" name="payment_method" value="stripe" id="stripe-radio" checked>
                        <label for="stripe-radio"></label>
                    </div>
                    <div class="payment-content">
                        <div class="payment-header">
                            <div class="payment-logo stripe-logo">
                                <svg viewBox="0 0 60 25" fill="currentColor">
                                    <path d="M59.64 14.28h-8.06c.19 1.93 1.6 2.55 3.2 2.55 1.64 0 2.96-.37 4.05-.95v3.32a8.33 8.33 0 0 1-4.56 1.1c-4.01 0-6.83-2.5-6.83-7.48 0-4.19 2.39-7.52 6.3-7.52 3.92 0 5.96 3.28 5.96 7.5 0 .4-.04 1.26-.06 1.48zm-5.92-5.62c-1.03 0-2.17.73-2.17 2.58h4.25c0-1.85-1.07-2.58-2.08-2.58zM40.95 20.3c-1.44 0-2.32-.6-2.9-1.04l-.02 4.63-4.12.87V5.57h3.76l.08 1.02a4.7 4.7 0 0 1 3.23-1.29c2.9 0 5.62 2.6 5.62 7.4 0 5.23-2.7 7.6-5.65 7.6zM40 8.95c-.95 0-1.54.34-1.97.81l.02 6.12c.4.44.98.78 1.95.78 1.52 0 2.54-1.65 2.54-3.87 0-2.15-1.04-3.84-2.54-3.84zM28.24 5.57h4.13v14.44h-4.13V5.57zm0-4.7L32.37 0v3.36l-4.13.88V.88zm-4.32 9.35v9.79H19.8V5.57h3.7l.12 1.22c1-1.77 3.07-1.41 3.62-1.22v3.79c-.52-.17-2.29-.43-3.32.86zm-8.55 4.72c0 2.43 2.6 1.68 3.12 1.46v3.36c-.55.3-1.54.54-2.89.54a4.15 4.15 0 0 1-4.27-4.24l.01-13.17 4.02-.86v3.54h3.14V9.1h-3.13v5.85zm-4.91.7c0 2.97-2.31 4.66-5.73 4.66a11.2 11.2 0 0 1-4.46-.93v-3.93c1.38.75 3.1 1.31 4.46 1.31.92 0 1.53-.24 1.53-1C6.26 13.77 0 14.51 0 9.95 0 7.04 2.28 5.3 5.62 5.3c1.36 0 2.72.2 4.09.75v3.88a9.23 9.23 0 0 0-4.1-1.06c-.86 0-1.44.25-1.44.9 0 1.85 6.29.97 6.29 5.88z"/>
                                </svg>
                            </div>
                            <div class="payment-info">
                                <div class="payment-name">Credit / Debit Card</div>
                                <div class="payment-desc">Secure payment with Stripe</div>
                            </div>
                        </div>
                        <div class="payment-icons">
                            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='32' height='20' viewBox='0 0 32 20'%3E%3Crect fill='%231434CB' width='32' height='20' rx='2'/%3E%3Cpath fill='%23fff' d='M11.085 9.374c0-1.956 1.485-3.252 3.61-3.252 1.05 0 1.92.252 2.52.588v2.016c-.63-.42-1.35-.672-2.31-.672-1.26 0-2.1.63-2.1 1.68 0 .924.63 1.512 2.1 1.512.96 0 1.68-.252 2.31-.672v2.016c-.6.336-1.47.588-2.52.588-2.125 0-3.61-1.296-3.61-3.804zm8.54-3.948l-.21 1.26c.735-.084 1.26.084 1.26.756v4.956h1.89V7.482c0-1.512-.84-2.1-2.94-1.056zm4.935 0l-.21 1.26c.735-.084 1.26.084 1.26.756v4.956h1.89V7.482c0-1.512-.84-2.1-2.94-1.056z'/%3E%3C/svg%3E" alt="Visa">
                            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='32' height='20' viewBox='0 0 32 20'%3E%3Crect fill='%23EB001B' width='32' height='20' rx='2'/%3E%3Ccircle fill='%23FF5F00' cx='16' cy='10' r='6'/%3E%3Ccircle fill='%23F79E1B' cx='22' cy='10' r='6'/%3E%3C/svg%3E" alt="Mastercard">
                            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='32' height='20' viewBox='0 0 32 20'%3E%3Crect fill='%23016FD0' width='32' height='20' rx='2'/%3E%3Cpath fill='%23fff' d='M9 14h1.5l1.2-5.5h-1.5L9 14zm6-5.5l-1.4 3.7-.6-3.2c-.1-.4-.4-.5-.7-.5H9.8l0 .2c.6.1 1.2.3 1.7.6l1.4 5.2h1.6l2.4-5.5h-1.6zm3.8 0h-1.4l-1 5.5h1.4l1-5.5zm2.2 3.5c0-.8.5-1.3 1.3-1.3.4 0 .7.1.9.2l.2-1.1c-.3-.1-.7-.2-1.2-.2-1.3 0-2.2.7-2.2 1.8 0 .8.7 1.2 1.2 1.5.5.3.7.5.7.8 0 .5-.6.7-1.1.7-.5 0-1-.1-1.4-.3l-.2 1.1c.4.2 1 .3 1.6.3 1.4 0 2.4-.7 2.4-1.8 0-1.4-1.9-1.5-1.9-2.2z'/%3E%3C/svg%3E" alt="Amex">
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($paypal_enabled): ?>
                <div class="payment-option" id="paypal-option" data-plan="<?php echo esc_attr($plan_id); ?>">
                    <div class="payment-radio">
                        <input type="radio" name="payment_method" value="paypal" id="paypal-radio" <?php echo !$stripe_enabled ? 'checked' : ''; ?>>
                        <label for="paypal-radio"></label>
                    </div>
                    <div class="payment-content">
                        <div class="payment-header">
                            <div class="payment-logo paypal-logo">
                                <!-- Correct PayPal Logo -->
                                <svg viewBox="0 0 124 33" fill="none">
                                    <path d="M46.211 6.749h-6.839a.95.95 0 0 0-.939.802l-2.766 17.537a.57.57 0 0 0 .564.658h3.265a.95.95 0 0 0 .939-.803l.746-4.73a.95.95 0 0 1 .938-.803h2.165c4.505 0 7.105-2.18 7.784-6.5.306-1.89.013-3.375-.872-4.415-.972-1.142-2.696-1.746-4.985-1.746zM47.059 13.165c-.374 2.454-2.249 2.454-4.062 2.454h-1.032l.724-4.583a.57.57 0 0 1 .563-.481h.473c1.235 0 2.4 0 3.002.704.359.42.469 1.044.332 1.906zM66.965 13.126h-3.279a.57.57 0 0 0-.563.481l-.145.916-.229-.332c-.709-1.029-2.29-1.373-3.868-1.373-3.619 0-6.71 2.741-7.312 6.586-.313 1.918.132 3.752 1.22 5.031.998 1.176 2.426 1.666 4.125 1.666 2.916 0 4.533-1.875 4.533-1.875l-.146.91a.57.57 0 0 0 .562.66h2.95a.95.95 0 0 0 .939-.803l1.77-11.209a.568.568 0 0 0-.557-.658zm-4.565 6.374c-.316 1.871-1.801 3.127-3.695 3.127-.951 0-1.711-.305-2.199-.883-.484-.574-.668-1.391-.514-2.301.295-1.855 1.805-3.152 3.67-3.152.93 0 1.686.309 2.184.892.499.589.697 1.411.554 2.317zM84.096 13.126h-3.291a.954.954 0 0 0-.787.417l-4.539 6.686-1.924-6.425a.953.953 0 0 0-.912-.678h-3.234a.57.57 0 0 0-.541.754l3.625 10.638-3.408 4.811a.57.57 0 0 0 .465.9h3.287a.949.949 0 0 0 .781-.408l10.946-15.8a.57.57 0 0 0-.468-.895z" fill="%2300457C"/><path d="M94.992 6.749h-6.84a.95.95 0 0 0-.938.802l-2.766 17.537a.57.57 0 0 0 .562.658h3.51a.665.665 0 0 0 .656-.562l.785-4.971a.95.95 0 0 1 .938-.803h2.164c4.506 0 7.105-2.18 7.785-6.5.307-1.89.012-3.375-.873-4.415-.971-1.142-2.694-1.746-4.983-1.746zm.789 6.416c-.373 2.454-2.248 2.454-4.062 2.454h-1.031l.725-4.583a.568.568 0 0 1 .562-.481h.473c1.234 0 2.4 0 3.002.704.359.42.468 1.044.331 1.906zM115.484 13.126h-3.28a.57.57 0 0 0-.562.481l-.145.916-.23-.332c-.709-1.029-2.289-1.373-3.867-1.373-3.619 0-6.709 2.741-7.311 6.586-.312 1.918.131 3.752 1.219 5.031 1 1.176 2.426 1.666 4.125 1.666 2.916 0 4.533-1.875 4.533-1.875l-.146.91a.57.57 0 0 0 .564.66h2.949a.95.95 0 0 0 .938-.803l1.771-11.209a.57.57 0 0 0-.558-.658zm-4.565 6.374c-.314 1.871-1.801 3.127-3.695 3.127-.949 0-1.711-.305-2.199-.883-.484-.574-.666-1.391-.514-2.301.297-1.855 1.805-3.152 3.67-3.152.93 0 1.686.309 2.184.892.501.589.699 1.411.554 2.317zM119.295 7.23l-2.807 17.858a.569.569 0 0 0 .562.658h2.822c.469 0 .867-.34.939-.803l2.768-17.536a.57.57 0 0 0-.562-.659h-3.16a.571.571 0 0 0-.562.482z" fill="%230079C1"/><path d="M7.266 29.154l.523-3.322-1.165-.027H1.061L4.927 1.292a.316.316 0 0 1 .314-.268h9.38c3.114 0 5.263.648 6.385 1.927.526.6.861 1.227 1.023 1.917.17.724.173 1.589.007 2.644l-.012.077v.676l.526.298a3.69 3.69 0 0 1 1.065.812c.45.513.741 1.165.864 1.938.127.795.085 1.741-.123 2.812-.24 1.232-.628 2.305-1.152 3.183a6.547 6.547 0 0 1-1.825 2c-.696.494-1.523.869-2.458 1.109-.906.236-1.939.355-3.072.355h-.73c-.522 0-1.029.188-1.427.525a2.21 2.21 0 0 0-.744 1.328l-.055.299-.924 5.855-.042.215c-.011.068-.03.102-.058.125a.155.155 0 0 1-.096.035H7.266z" fill="%23009CDE"/><path d="M23.048 7.667c-.028.179-.06.362-.096.55-1.237 6.351-5.469 8.545-10.874 8.545H9.326c-.661 0-1.218.48-1.321 1.132L6.596 26.83l-.399 2.533a.704.704 0 0 0 .695.814h4.881c.578 0 1.069-.42 1.16-.99l.048-.248.919-5.832.059-.32c.09-.572.582-.992 1.16-.992h.73c4.729 0 8.431-1.92 9.513-7.476.452-2.321.218-4.259-.978-5.622a4.667 4.667 0 0 0-1.336-1.03z" fill="%23012169"/><path d="M21.754 7.151a9.757 9.757 0 0 0-1.203-.267 15.284 15.284 0 0 0-2.426-.177h-7.352a1.172 1.172 0 0 0-1.159.992L8.05 17.605l-.045.289a1.336 1.336 0 0 1 1.321-1.132h2.752c5.405 0 9.637-2.195 10.874-8.545.037-.188.068-.371.096-.55a6.594 6.594 0 0 0-1.017-.429 9.045 9.045 0 0 0-.277-.087z" fill="%23003087"/><path d="M9.614 7.699a1.169 1.169 0 0 1 1.159-.991h7.352c.871 0 1.684.057 2.426.177a9.757 9.757 0 0 1 1.481.353c.365.121.704.264 1.017.429.368-2.347-.003-3.945-1.272-5.392C20.378.682 17.853 0 14.622 0h-9.38c-.66 0-1.223.48-1.325 1.133L.01 25.898a.806.806 0 0 0 .795.932h5.791l1.454-9.225 1.564-9.906z" fill="%23001C64"/></svg>
                            </div>
                            <div class="payment-info">
                                <div class="payment-name">PayPal</div>
                                <div class="payment-desc">Pay securely with your PayPal account</div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div id="payment-error" class="payment-alert error" style="display: none;">
                <svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <strong>Payment Error</strong>
                    <p id="payment-error-message"></p>
                </div>
            </div>

            <button type="button" id="proceed-payment" class="pcp-btn primary large">
                <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                    <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
                </svg>
                Proceed to Payment
            </button>

            <div class="checkout-footer">
                <div class="security-badges">
                    <div class="security-item">
                        <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                            <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                        </svg>
                        SSL Encrypted
                    </div>
                    <div class="security-item">
                        <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                            <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
                        </svg>
                        PCI Compliant
                    </div>
                    <div class="security-item">
                        <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                        </svg>
                        30-Day Guarantee
                    </div>
                </div>
                <p class="terms-text">
                    By completing your purchase, you agree to our 
                    <a href="<?php echo esc_url(home_url('/terms')); ?>">Terms of Service</a> and 
                    <a href="<?php echo esc_url(home_url('/privacy')); ?>">Privacy Policy</a>
                </p>
            </div>
        </div>
    </div>
</div>

<style>
.pcp-checkout-wrapper {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px 80px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Auth Required / Error States */
.pcp-auth-required,
.pcp-error-state {
    max-width: 500px;
    margin: 60px auto;
    text-align: center;
    background: white;
    padding: 48px 40px;
    border-radius: 16px;
    border: 2px solid #e5e7eb;
}

.pcp-auth-required svg,
.pcp-error-state svg {
    color: #6b7280;
    margin-bottom: 20px;
}

.pcp-auth-required h2,
.pcp-error-state h2 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #111827;
    margin: 0 0 12px 0;
}

.pcp-auth-required p,
.pcp-error-state p {
    color: #6b7280;
    font-size: 1rem;
    margin: 0 0 32px 0;
}

.auth-actions {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

/* Progress Steps */
.checkout-progress {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 48px;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
}

.progress-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #f3f4f6;
    color: #9ca3af;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.125rem;
    transition: all 0.3s;
}

.progress-step.completed .step-number,
.progress-step.active .step-number {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: white;
}

.step-label {
    font-size: 0.875rem;
    color: #6b7280;
    font-weight: 500;
}

.progress-step.active .step-label {
    color: #2563eb;
    font-weight: 600;
}

.progress-line {
    height: 2px;
    width: 80px;
    background: #e5e7eb;
    margin: 0 16px;
    margin-bottom: 32px;
}

/* Checkout Container */
.checkout-container {
    display: grid;
    grid-template-columns: 380px 1fr;
    gap: 40px;
    align-items: start;
}

/* Sidebar */
.checkout-sidebar {
    position: sticky;
    top: 20px;
}

.summary-card {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 28px;
}

.summary-card h3 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #111827;
    margin: 0 0 24px 0;
}

.summary-plan {
    padding-bottom: 20px;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 20px;
}

.plan-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    margin-bottom: 12px;
}

.plan-badge.monthly {
    background: #dbeafe;
    color: #1e40af;
}

.plan-badge.yearly {
    background: #dcfce7;
    color: #166534;
}

.plan-badge.lifetime {
    background: #fae8ff;
    color: #86198f;
}

.summary-plan h4 {
    font-size: 1.375rem;
    font-weight: 700;
    color: #111827;
    margin: 0 0 8px 0;
}

.plan-desc {
    color: #6b7280;
    font-size: 0.875rem;
    margin: 0;
}

.summary-features {
    padding-bottom: 20px;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 20px;
}

.features-title {
    font-size: 0.813rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.summary-features ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.summary-features li {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 8px 0;
    color: #374151;
    font-size: 0.875rem;
}

.summary-features li svg {
    color: #10b981;
    flex-shrink: 0;
    margin-top: 2px;
}

.summary-pricing {
    padding-bottom: 20px;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 20px;
}

.price-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    font-size: 0.938rem;
    color: #374151;
}

.price-row.total {
    border-top: 2px solid #e5e7eb;
    margin-top: 8px;
    padding-top: 16px;
    font-size: 1.125rem;
    font-weight: 700;
    color: #111827;
}

.price-row .amount {
    font-weight: 600;
}

.billing-cycle {
    margin-top: 12px;
    padding: 12px;
    background: white;
    border-radius: 8px;
    text-align: center;
    font-size: 0.813rem;
    color: #6b7280;
}

.money-back {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #10b981;
    font-size: 0.875rem;
    font-weight: 600;
    justify-content: center;
}

/* Main Content */
.checkout-main {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 36px;
    /* FIX: Prevent overflow */
    max-width: 100%;
    overflow: hidden;
}

.checkout-header {
    margin-bottom: 32px;
    padding-bottom: 24px;
    border-bottom: 1px solid #e5e7eb;
}

.checkout-header h2 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #111827;
    margin: 0 0 12px 0;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #6b7280;
    font-size: 0.938rem;
}

/* Payment Methods */
.payment-methods h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
    margin: 0 0 20px 0;
}

.payment-option {
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 16px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    gap: 16px;
}

.payment-option:hover {
    border-color: #cbd5e1;
    background: #f9fafb;
}

.payment-option:has(input:checked) {
    border-color: #2563eb;
    background: #eff6ff;
}

.payment-radio {
    flex-shrink: 0;
}

.payment-radio input[type="radio"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: #2563eb;
}

.payment-content {
    flex: 1;
    /* FIX: Prevent content overflow */
    min-width: 0;
}

.payment-header {
    display: flex;
    align-items: center;
    gap: 16px;
}

.payment-logo {
    width: 80px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: white;
    border: 1px solid #e5e7eb;
    flex-shrink: 0;
}

.stripe-logo svg {
    width: 60px;
    height: auto;
    color: #635bff;
}

/* FIX: PayPal logo sizing */
.paypal-logo svg {
    width: 100%;
    height: auto;
    max-width: 70px;
}

.payment-info {
    flex: 1;
    min-width: 0;
}

.payment-name {
    font-size: 1rem;
    font-weight: 600;
    color: #111827;
    margin-bottom: 2px;
}

.payment-desc {
    font-size: 0.875rem;
    color: #6b7280;
}

.payment-icons {
    display: flex;
    gap: 8px;
    margin-top: 12px;
}

.payment-icons img {
    height: 28px;
    border-radius: 4px;
}

/* Payment Alert */
.payment-alert {
    display: flex;
    gap: 12px;
    padding: 16px;
    border-radius: 8px;
    margin: 20px 0;
}

.payment-alert.error {
    background: #fef2f2;
    border: 1px solid #fca5a5;
}

.payment-alert svg {
    color: #dc2626;
    flex-shrink: 0;
}

.payment-alert strong {
    display: block;
    color: #991b1b;
    font-weight: 600;
    margin-bottom: 4px;
}

.payment-alert p {
    color: #991b1b;
    margin: 0;
    font-size: 0.875rem;
}

/* Buttons - FIX: Prevent overflow */
.pcp-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    text-decoration: none;
    border: 2px solid transparent;
    cursor: pointer;
    transition: all 0.2s;
    font-family: inherit;
    /* FIX: Ensure button stays within container */
    max-width: 100%;
    box-sizing: border-box;
}

.pcp-btn.primary {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: white;
}

.pcp-btn.primary:hover:not(:disabled) {
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

.pcp-btn.large {
    width: 100%;
    padding: 16px 32px;
    font-size: 1.125rem;
    margin-top: 24px;
}

.pcp-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.pcp-btn.processing {
    position: relative;
}

.pcp-btn.processing::after {
    content: '';
    position: absolute;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: white;
    animation: spin 0.6s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Footer */
.checkout-footer {
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid #e5e7eb;
}

.security-badges {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 24px;
    margin-bottom: 20px;
}

.security-item {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #10b981;
    font-size: 0.875rem;
    font-weight: 600;
}

.terms-text {
    text-align: center;
    font-size: 0.813rem;
    color: #6b7280;
    margin: 0;
}

.terms-text a {
    color: #2563eb;
    text-decoration: none;
}

.terms-text a:hover {
    text-decoration: underline;
}

/* Responsive */
@media (max-width: 1024px) {
    .checkout-container {
        grid-template-columns: 1fr;
    }
    
    .checkout-sidebar {
        position: static;
        order: 2;
    }
}

@media (max-width: 768px) {
    .pcp-checkout-wrapper {
        padding: 30px 16px 60px;
    }
    
    .checkout-progress {
        margin-bottom: 32px;
    }
    
    .progress-line {
        width: 50px;
        margin: 0 8px;
        margin-bottom: 32px;
    }
    
    .step-number {
        width: 36px;
        height: 36px;
        font-size: 1rem;
    }
    
    .step-label {
        font-size: 0.75rem;
    }
    
    .checkout-main {
        padding: 24px 20px;
    }
    
    .summary-card {
        padding: 20px;
    }
    
    .security-badges {
        flex-direction: column;
        gap: 12px;
        align-items: center;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    'use strict';
    
    var processing = false;

    function showError(message) {
        $('#payment-error-message').text(message);
        $('#payment-error').slideDown();
        setTimeout(function() {
            $('#payment-error').slideUp();
        }, 8000);
    }

    function disableButton($btn) {
        $btn.prop('disabled', true).addClass('processing');
    }

    function enableButton($btn) {
        $btn.prop('disabled', false).removeClass('processing');
    }

    $('.payment-option').on('click', function() {
        var $radio = $(this).find('input[type="radio"]');
        $radio.prop('checked', true);
        $('.payment-option').removeClass('selected');
        $(this).addClass('selected');
    });

    $('#proceed-payment').on('click', function() {
        if (processing) return;
        
        var selectedMethod = $('input[name="payment_method"]:checked').val();
        if (!selectedMethod) {
            showError('Please select a payment method');
            return;
        }

        processing = true;
        var $btn = $(this);
        disableButton($btn);

        var planId = $('#' + selectedMethod + '-option').data('plan');
        var action = selectedMethod === 'stripe' ? 'premium_create_stripe_checkout' : 'premium_create_paypal_order';

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: action,
                nonce: '<?php echo wp_create_nonce('premium_checkout'); ?>',
                plan_id: planId
            },
            success: function(response) {
                if (response.success) {
                    var redirectUrl = response.data.url || response.data.approve_url;
                    if (redirectUrl) {
                        window.location.href = redirectUrl;
                    } else {
                        showError('Payment processing error. Please try again.');
                        enableButton($btn);
                        processing = false;
                    }
                } else {
                    showError(response.data || 'Failed to process payment. Please try again.');
                    enableButton($btn);
                    processing = false;
                }
            },
            error: function() {
                showError('Network error. Please check your connection and try again.');
                enableButton($btn);
                processing = false;
            }
        });
    });
});
</script>