<?php
/**
 * Template: User Dashboard/Account Page
 * Displays user subscription info and account management
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if user is logged in
if (!is_user_logged_in()) {
    ?>
    <div class="premium-dashboard-wrapper">
        <div class="premium-alert premium-alert-warning">
            <strong>Please Log In</strong>
            <p>You need to be logged in to view your account.</p>
            <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="premium-button premium-button-primary">
                Log In
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

// Get view count if metered mode
$view_count = 0;
$view_limit = 0;
$remaining_views = 0;
if ($access_mode === 'metered') {
    $view_count = Premium_Content_Metered_Paywall::get_view_count();
    $view_limit = intval(premium_content_get_option('metered_limit', 3));
    $remaining_views = max(0, $view_limit - $view_count);
}
?>

<div class="premium-dashboard-wrapper">
    <div class="dashboard-header">
        <div class="header-content">
            <h1>My Account</h1>
            <p>Welcome back, <?php echo esc_html($current_user->display_name); ?>!</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="logout-link">
                <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                    <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.11 0-2 .89-2 2v14c0 1.1.89 2 2 2h8v-2H4V5z"/>
                </svg>
                Log Out
            </a>
        </div>
    </div>

    <div class="dashboard-grid">
        <!-- Subscription Card -->
        <div class="dashboard-card subscription-card">
            <div class="card-header">
                <h2>
                    <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                        <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
                    </svg>
                    Subscription Status
                </h2>
            </div>
            <div class="card-body">
                <?php if ($subscription): 
                    $plan = Premium_Content_Subscription_Manager::get_plan($subscription->plan_id);
                    $status_class = $subscription->status === 'active' ? 'status-active' : 'status-inactive';
                ?>
                    <div class="subscription-info">
                        <div class="subscription-plan">
                            <div class="plan-name-display"><?php echo esc_html($plan->name); ?></div>
                            <span class="subscription-status <?php echo esc_attr($status_class); ?>">
                                <?php echo ucfirst($subscription->status); ?>
                            </span>
                        </div>
                        
                        <div class="subscription-details">
                            <div class="detail-row">
                                <span class="detail-label">Plan:</span>
                                <span class="detail-value"><?php echo esc_html($plan->name); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Price:</span>
                                <span class="detail-value">$<?php echo number_format($plan->price, 2); ?>/<?php echo esc_html($plan->interval); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Started:</span>
                                <span class="detail-value"><?php echo date('M j, Y', strtotime($subscription->started_at)); ?></span>
                            </div>
                            <?php if ($subscription->expires_at): ?>
                            <div class="detail-row">
                                <span class="detail-label">
                                    <?php echo $subscription->status === 'cancelled' ? 'Access until:' : 'Renews:'; ?>
                                </span>
                                <span class="detail-value"><?php echo date('M j, Y', strtotime($subscription->expires_at)); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="detail-row">
                                <span class="detail-label">Payment Method:</span>
                                <span class="detail-value"><?php echo ucfirst($subscription->payment_method); ?></span>
                            </div>
                        </div>

                        <?php if ($subscription->status === 'active' && $subscription->status !== 'cancelled'): ?>
                        <div class="subscription-actions">
                            <button id="cancel-subscription-btn" class="btn btn-secondary" data-subscription="<?php echo esc_attr($subscription->id); ?>">
                                Cancel Subscription
                            </button>
                            <a href="<?php echo esc_url(get_permalink(get_option('premium_content_page_pricing'))); ?>" class="btn btn-outline">
                                Change Plan
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="no-subscription">
                        <svg viewBox="0 0 24 24" fill="currentColor" width="48" height="48">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                        </svg>
                        <h3>No Active Subscription</h3>
                        <p>Subscribe to get unlimited access to all premium content.</p>
                        <a href="<?php echo esc_url(get_permalink(get_option('premium_content_page_pricing'))); ?>" class="btn btn-primary">
                            View Plans
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Account Info Card -->
        <div class="dashboard-card account-card">
            <div class="card-header">
                <h2>
                    <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                    Account Information
                </h2>
            </div>
            <div class="card-body">
                <div class="account-details">
                    <div class="detail-row">
                        <span class="detail-label">Name:</span>
                        <span class="detail-value"><?php echo esc_html($current_user->display_name); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value"><?php echo esc_html($current_user->user_email); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Username:</span>
                        <span class="detail-value"><?php echo esc_html($current_user->user_login); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Member Since:</span>
                        <span class="detail-value"><?php echo date('M j, Y', strtotime($current_user->user_registered)); ?></span>
                    </div>
                </div>
                <div class="account-actions">
                    <a href="<?php echo esc_url(admin_url('profile.php')); ?>" class="btn btn-outline">
                        Edit Profile
                    </a>
                </div>
            </div>
        </div>

        <?php if ($access_mode === 'metered' && !$subscription): ?>
        <!-- Usage Stats Card (Metered Mode Only) -->
        <div class="dashboard-card usage-card">
            <div class="card-header">
                <h2>
                    <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
                    </svg>
                    Article Usage
                </h2>
            </div>
            <div class="card-body">
                <div class="usage-progress">
                    <div class="usage-stats">
                        <span class="usage-count"><?php echo $view_count; ?> of <?php echo $view_limit; ?></span>
                        <span class="usage-label">articles read this month</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo ($view_limit > 0) ? min(100, ($view_count / $view_limit) * 100) : 0; ?>%"></div>
                    </div>
                    <p class="usage-remaining">
                        <?php if ($remaining_views > 0): ?>
                            You have <strong><?php echo $remaining_views; ?> free article<?php echo $remaining_views !== 1 ? 's' : ''; ?></strong> remaining.
                        <?php else: ?>
                            <span style="color: #d63638;">You've reached your free article limit.</span>
                        <?php endif; ?>
                    </p>
                </div>
                <?php if ($remaining_views === 0): ?>
                <div class="upgrade-prompt">
                    <p>Subscribe for unlimited access to all content!</p>
                    <a href="<?php echo esc_url(get_permalink(get_option('premium_content_page_pricing'))); ?>" class="btn btn-primary">
                        View Plans
                    </a>
                </div>
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
.premium-dashboard-wrapper {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
    padding-bottom: 24px;
    border-bottom: 2px solid #e5e7eb;
}

.dashboard-header h1 {
    margin: 0 0 8px 0;
    font-size: 32px;
    color: #2c3e50;
}

.dashboard-header p {
    margin: 0;
    color: #6b7280;
    font-size: 16px;
}

.logout-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    color: #6b7280;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
}

.logout-link:hover {
    border-color: #d63638;
    color: #d63638;
}

.dashboard-grid {
    display: grid;
    gap: 24px;
}

.dashboard-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.card-header {
    padding: 20px 24px;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}

.card-header h2 {
    margin: 0;
    font-size: 18px;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 10px;
}

.card-body {
    padding: 24px;
}

.subscription-plan {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.plan-name-display {
    font-size: 24px;
    font-weight: 700;
    color: #2c3e50;
}

.subscription-status {
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-active {
    background: #dcfce7;
    color: #166534;
}

.status-inactive {
    background: #fee2e2;
    color: #991b1b;
}

.subscription-details {
    background: #f9fafb;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 24px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #e5e7eb;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    color: #6b7280;
    font-weight: 500;
}

.detail-value {
    color: #2c3e50;
    font-weight: 600;
}

.subscription-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.no-subscription {
    text-align: center;
    padding: 40px 20px;
}

.no-subscription svg {
    color: #9ca3af;
    margin-bottom: 16px;
}

.no-subscription h3 {
    margin: 0 0 12px 0;
    color: #2c3e50;
}

.no-subscription p {
    margin: 0 0 24px 0;
    color: #6b7280;
}

.account-details {
    margin-bottom: 24px;
}

.account-actions {
    display: flex;
    gap: 12px;
}

.usage-progress {
    text-align: center;
}

.usage-stats {
    display: flex;
    flex-direction: column;
    margin-bottom: 16px;
}

.usage-count {
    font-size: 32px;
    font-weight: 700;
    color: #2c3e50;
}

.usage-label {
    font-size: 14px;
    color: #6b7280;
}

.progress-bar {
    height: 12px;
    background: #e5e7eb;
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 16px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    transition: width 0.3s ease;
}

.usage-remaining {
    color: #374151;
    margin-bottom: 20px;
}

.upgrade-prompt {
    background: #fef3c7;
    border: 1px solid #fcd34d;
    border-radius: 8px;
    padding: 20px;
    margin-top: 20px;
}

.upgrade-prompt p {
    margin: 0 0 16px 0;
    color: #92400e;
    font-weight: 600;
}

.btn {
    display: inline-block;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    text-align: center;
    transition: all 0.2s;
    border: 2px solid transparent;
    cursor: pointer;
    font-size: 14px;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-secondary {
    background: white;
    color: #d63638;
    border-color: #d63638;
}

.btn-secondary:hover {
    background: #d63638;
    color: white;
}

.btn-outline {
    background: white;
    color: #667eea;
    border-color: #667eea;
}

.btn-outline:hover {
    background: #667eea;
    color: white;
}

@media (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }

    .subscription-actions,
    .account-actions {
        flex-direction: column;
    }

    .btn {
        width: 100%;
    }
}
</style>