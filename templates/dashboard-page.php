<?php
/**
 * Template: User Dashboard - Modern Design
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    ?>
    <div class="pcp-dashboard-wrapper">
        <div class="pcp-auth-required">
            <svg viewBox="0 0 24 24" fill="currentColor" width="48" height="48">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
            </svg>
            <h2>Please Log In</h2>
            <p>You need to be logged in to view your account dashboard</p>
            <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="pcp-btn primary">
                Sign In
            </a>
        </div>
    </div>
    <?php
    return;
}

$user_id = get_current_user_id();
$current_user = wp_get_current_user();
$subscription = Premium_Content_Subscription_Manager::get_user_subscription($user_id);
$access_mode = premium_content_get_option('access_mode', 'free');

$view_count = 0;
$view_limit = 0;
$remaining_views = 0;
if ($access_mode === 'metered') {
    $view_count = Premium_Content_Metered_Paywall::get_view_count();
    $view_limit = intval(premium_content_get_option('metered_limit', 3));
    $remaining_views = max(0, $view_limit - $view_count);
}
?>

<div class="pcp-dashboard-wrapper">
    <!-- Header -->
    <div class="dashboard-header">
        <div class="header-content">
            <div class="header-greeting">
                <h1>Welcome back, <?php echo esc_html($current_user->display_name); ?></h1>
                <p>Manage your subscription and account settings</p>
            </div>
        </div>
        <div class="header-actions">
            <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="logout-btn">
                <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
                    <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.11 0-2 .89-2 2v14c0 1.1.89 2 2 2h8v-2H4V5z"/>
                </svg>
                Log Out
            </a>
        </div>
    </div>

    <div class="dashboard-grid">
        <!-- Subscription Card -->
        <div class="dashboard-card subscription-card">
            <div class="card-icon">
                <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                    <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
                </svg>
            </div>
            <div class="card-header">
                <h3>Subscription</h3>
            </div>
            <div class="card-body">
                <?php if ($subscription): 
                    $plan = Premium_Content_Subscription_Manager::get_plan($subscription->plan_id);
                    $status_class = $subscription->status === 'active' ? 'success' : 'warning';
                ?>
                    <div class="subscription-status">
                        <div class="plan-info">
                            <div class="plan-name"><?php echo esc_html($plan->name); ?></div>
                            <span class="status-badge <?php echo $status_class; ?>">
                                <?php echo ucfirst($subscription->status); ?>
                            </span>
                        </div>
                        <div class="plan-price">$<?php echo number_format($plan->price, 2); ?>/<?php echo $plan->interval; ?></div>
                    </div>
                    
                    <div class="subscription-details">
                        <div class="detail-item">
                            <div class="detail-label">Started</div>
                            <div class="detail-value"><?php echo date('M j, Y', strtotime($subscription->started_at)); ?></div>
                        </div>
                        <?php if ($subscription->expires_at): ?>
                        <div class="detail-item">
                            <div class="detail-label"><?php echo $subscription->status === 'cancelled' ? 'Expires' : 'Renews'; ?></div>
                            <div class="detail-value"><?php echo date('M j, Y', strtotime($subscription->expires_at)); ?></div>
                        </div>
                        <?php endif; ?>
                        <div class="detail-item">
                            <div class="detail-label">Payment</div>
                            <div class="detail-value"><?php echo ucfirst($subscription->payment_method); ?></div>
                        </div>
                    </div>

                    <?php if ($subscription->status === 'active' && $subscription->status !== 'cancelled'): ?>
                    <div class="card-actions">
                        <button id="cancel-subscription-btn" class="pcp-btn secondary small" data-subscription="<?php echo esc_attr($subscription->id); ?>">
                            Cancel Subscription
                        </button>
                        <a href="<?php echo esc_url(get_permalink(get_option('premium_content_page_pricing'))); ?>" class="pcp-btn outline small">
                            Change Plan
                        </a>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-subscription">
                        <svg viewBox="0 0 24 24" fill="currentColor" width="40" height="40">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                        </svg>
                        <h4>No Active Subscription</h4>
                        <p>Subscribe to unlock unlimited access to all premium content</p>
                        <a href="<?php echo esc_url(get_permalink(get_option('premium_content_page_pricing'))); ?>" class="pcp-btn primary small">
                            View Plans
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Account Info Card -->
        <div class="dashboard-card account-card">
            <div class="card-icon">
                <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
            </div>
            <div class="card-header">
                <h3>Account Information</h3>
            </div>
            <div class="card-body">
                <div class="account-details">
                    <div class="detail-item">
                        <div class="detail-label">Name</div>
                        <div class="detail-value"><?php echo esc_html($current_user->display_name); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Email</div>
                        <div class="detail-value"><?php echo esc_html($current_user->user_email); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Username</div>
                        <div class="detail-value"><?php echo esc_html($current_user->user_login); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Member Since</div>
                        <div class="detail-value"><?php echo date('M j, Y', strtotime($current_user->user_registered)); ?></div>
                    </div>
                </div>
                <div class="card-actions">
                    <a href="<?php echo esc_url(admin_url('profile.php')); ?>" class="pcp-btn outline small">
                        Edit Profile
                    </a>
                </div>
            </div>
        </div>

        <?php if ($access_mode === 'metered' && !$subscription): ?>
        <!-- Usage Card -->
        <div class="dashboard-card usage-card">
            <div class="card-icon">
                <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
                </svg>
            </div>
            <div class="card-header">
                <h3>Article Usage</h3>
            </div>
            <div class="card-body">
                <div class="usage-stats">
                    <div class="usage-number"><?php echo $view_count; ?> <span>/ <?php echo $view_limit; ?></span></div>
                    <div class="usage-label">Articles read this month</div>
                </div>
                <div class="usage-bar">
                    <div class="usage-fill" style="width: <?php echo min(100, ($view_limit > 0) ? ($view_count / $view_limit) * 100 : 0); ?>%"></div>
                </div>
                <?php if ($remaining_views > 0): ?>
                    <p class="usage-message">
                        You have <strong><?php echo $remaining_views; ?> free article<?php echo $remaining_views !== 1 ? 's' : ''; ?></strong> remaining
                    </p>
                <?php else: ?>
                    <p class="usage-message error">
                        You've reached your free article limit
                    </p>
                    <a href="<?php echo esc_url(get_permalink(get_option('premium_content_page_pricing'))); ?>" class="pcp-btn primary small">
                        Upgrade Now
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#cancel-subscription-btn').on('click', function() {
        if (!confirm('Are you sure you want to cancel your subscription? You will continue to have access until the end of your billing period.')) {
            return;
        }

        var $btn = $(this);
        var originalText = $btn.text();
        $btn.prop('disabled', true).text('Cancelling...');

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'premium_cancel_subscription',
                nonce: '<?php echo wp_create_nonce('premium_content_admin'); ?>',
                subscription_id: $(this).data('subscription')
            },
            success: function(response) {
                if (response.success) {
                    alert('Your subscription has been cancelled. You will continue to have access until the end of your billing period.');
                    location.reload();
                } else {
                    alert('Error: ' + (response.data || 'Failed to cancel subscription'));
                    $btn.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                alert('Network error. Please try again.');
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });
});
</script>

<style>
.pcp-dashboard-wrapper {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px 80px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Header */
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
    padding-bottom: 24px;
    border-bottom: 2px solid #e5e7eb;
}

.header-greeting h1 {
    font-size: 2rem;
    font-weight: 700;
    color: #111827;
    margin: 0 0 8px 0;
}

.header-greeting p {
    color: #6b7280;
    margin: 0;
    font-size: 1rem;
}

.logout-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    color: #6b7280;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s;
}

.logout-btn:hover {
    border-color: #dc2626;
    color: #dc2626;
}

/* Dashboard Grid */
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 24px;
}

/* Dashboard Cards */
.dashboard-card {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 28px;
    transition: all 0.3s;
}

.dashboard-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
}

.card-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
}

.card-header {
    margin-bottom: 20px;
}

.card-header h3 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #111827;
    margin: 0;
}

.card-body {
    flex: 1;
}

/* Subscription Card */
.subscription-status {
    padding: 20px;
    background: #f9fafb;
    border-radius: 8px;
    margin-bottom: 20px;
}

.plan-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.plan-name {
    font-size: 1.375rem;
    font-weight: 700;
    color: #111827;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
}

.status-badge.success {
    background: #dcfce7;
    color: #166534;
}

.status-badge.warning {
    background: #fef3c7;
    color: #92400e;
}

.plan-price {
    font-size: 1.125rem;
    font-weight: 600;
    color: #2563eb;
}

.subscription-details {
    display: grid;
    gap: 16px;
    margin-bottom: 20px;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    background: white;
    border-radius: 6px;
}

.detail-label {
    font-size: 0.875rem;
    color: #6b7280;
    font-weight: 500;
}

.detail-value {
    font-size: 0.938rem;
    color: #111827;
    font-weight: 600;
}

/* Account Details */
.account-details {
    display: grid;
    gap: 12px;
    margin-bottom: 20px;
}

/* No Subscription */
.no-subscription {
    text-align: center;
    padding: 40px 20px;
}

.no-subscription svg {
    color: #9ca3af;
    margin-bottom: 16px;
}

.no-subscription h4 {
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
    margin: 0 0 8px 0;
}

.no-subscription p {
    color: #6b7280;
    margin: 0 0 20px 0;
    font-size: 0.938rem;
}

/* Usage Card */
.usage-stats {
    text-align: center;
    margin-bottom: 20px;
}

.usage-number {
    font-size: 3rem;
    font-weight: 700;
    color: #111827;
    line-height: 1;
}

.usage-number span {
    font-size: 1.5rem;
    color: #6b7280;
}

.usage-label {
    font-size: 0.875rem;
    color: #6b7280;
    margin-top: 8px;
}

.usage-bar {
    height: 12px;
    background: #e5e7eb;
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 16px;
}

.usage-fill {
    height: 100%;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    transition: width 0.3s ease;
}

.usage-message {
    text-align: center;
    color: #374151;
    font-size: 0.938rem;
    margin: 0 0 20px 0;
}

.usage-message.error {
    color: #dc2626;
    font-weight: 600;
}

/* Card Actions */
.card-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

/* Buttons */
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
}

.pcp-btn.small {
    padding: 8px 16px;
    font-size: 0.875rem;
}

.pcp-btn.primary {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: white;
}

.pcp-btn.primary:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
}

.pcp-btn.secondary {
    background: white;
    color: #dc2626;
    border-color: #dc2626;
}

.pcp-btn.secondary:hover {
    background: #dc2626;
    color: white;
}

.pcp-btn.outline {
    background: white;
    color: #2563eb;
    border-color: #2563eb;
}

.pcp-btn.outline:hover {
    background: #2563eb;
    color: white;
}

.pcp-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Auth Required */
.pcp-auth-required {
    max-width: 500px;
    margin: 60px auto;
    text-align: center;
    background: white;
    padding: 48px 40px;
    border-radius: 16px;
    border: 2px solid #e5e7eb;
}

.pcp-auth-required svg {
    color: #6b7280;
    margin-bottom: 20px;
}

.pcp-auth-required h2 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #111827;
    margin: 0 0 12px 0;
}

.pcp-auth-required p {
    color: #6b7280;
    margin: 0 0 24px 0;
}

/* Responsive */
@media (max-width: 768px) {
    .pcp-dashboard-wrapper {
        padding: 30px 16px 60px;
    }
    
    .dashboard-header {
        flex-direction: column;
        gap: 16px;
        align-items: flex-start;
    }
    
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .card-actions {
        flex-direction: column;
    }
    
    .pcp-btn.small {
        width: 100%;
    }
}
</style>