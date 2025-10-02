<?php
/**
 * Manages user subscriptions, plans, and access control
 */
class Premium_Content_Subscription_Manager {

    public function __construct() {
        // AJAX handlers
        add_action('wp_ajax_premium_cancel_subscription', array($this, 'ajax_cancel_subscription'));
        add_action('wp_ajax_premium_create_subscription', array($this, 'ajax_create_subscription'));
        
        // Subscription status checks
        add_action('init', array($this, 'check_subscription_expiry'));
    }

    /**
     * Verify AJAX request with proper nonce and capability checks
     */
    private static function verify_ajax_request($action = 'premium_content_admin', $capability = 'read') {
        // Check nonce
        if (!check_ajax_referer($action, 'nonce', false)) {
            wp_send_json_error('Invalid security token');
            exit;
        }
        
        // Check user capability
        if (!current_user_can($capability)) {
            wp_send_json_error('Insufficient permissions');
            exit;
        }
        
        return true;
    }

    /**
     * Check if user has active subscription
     */
    public static function user_has_active_subscription($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'premium_subscriptions';
        
        $subscription = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table 
            WHERE user_id = %d 
            AND status = 'active' 
            AND (expires_at IS NULL OR expires_at > NOW())
            ORDER BY id DESC 
            LIMIT 1",
            $user_id
        ));
        
        return !empty($subscription);
    }

    /**
     * Get user's active subscription
     */
    public static function get_user_subscription($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return null;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'premium_subscriptions';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table 
            WHERE user_id = %d 
            AND status = 'active' 
            ORDER BY id DESC 
            LIMIT 1",
            $user_id
        ));
    }

    /**
     * Create new subscription
     */
    public static function create_subscription($user_id, $plan_id, $payment_method, $transaction_data = array()) {
        global $wpdb;
        
        $plan = self::get_plan($plan_id);
        if (!$plan) {
            return false;
        }
        
        // Calculate expiry date
        $expires_at = null;
        if ($plan->interval !== 'lifetime') {
            $interval_map = array(
                'monthly' => '+1 month',
                'yearly' => '+1 year',
                'weekly' => '+1 week',
                'daily' => '+1 day'
            );
            
            $interval_string = isset($interval_map[$plan->interval]) ? $interval_map[$plan->interval] : '+1 month';
            $expires_at = date('Y-m-d H:i:s', strtotime($interval_string));
        }
        
        $data = array(
            'user_id' => $user_id,
            'plan_id' => $plan_id,
            'status' => 'active',
            'payment_method' => $payment_method,
            'transaction_id' => isset($transaction_data['transaction_id']) ? $transaction_data['transaction_id'] : '',
            'stripe_subscription_id' => isset($transaction_data['stripe_subscription_id']) ? $transaction_data['stripe_subscription_id'] : null,
            'paypal_subscription_id' => isset($transaction_data['paypal_subscription_id']) ? $transaction_data['paypal_subscription_id'] : null,
            'expires_at' => $expires_at
        );
        
        $table = $wpdb->prefix . 'premium_subscriptions';
        $inserted = $wpdb->insert($table, $data);
        
        if ($inserted) {
            // Send confirmation email
            self::send_subscription_email($user_id, 'activated', $plan);
            
            // Fire action hook for integrations
            do_action('premium_content_subscription_created', $wpdb->insert_id, $user_id, $plan_id);
            
            return $wpdb->insert_id;
        }
        
        return false;
    }

    /**
     * Cancel subscription
     */
    public function ajax_cancel_subscription() {
        check_ajax_referer('premium_content_admin', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        $user_id = get_current_user_id();
        $subscription = self::get_user_subscription($user_id);
        
        if (!$subscription) {
            wp_send_json_error('No active subscription found');
        }
        
        $cancelled = self::cancel_subscription($subscription->id, $user_id);
        
        if ($cancelled) {
            wp_send_json_success('Subscription cancelled successfully');
        } else {
            wp_send_json_error('Failed to cancel subscription');
        }
    }

    /**
     * Cancel subscription
     */
    public static function cancel_subscription($subscription_id, $user_id = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'premium_subscriptions';
        
        // Verify ownership if user_id provided
        if ($user_id) {
            $subscription = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d AND user_id = %d",
                $subscription_id,
                $user_id
            ));
            
            if (!$subscription) {
                return false;
            }
        }
        
        $updated = $wpdb->update(
            $table,
            array(
                'status' => 'cancelled',
                'cancelled_at' => current_time('mysql')
            ),
            array('id' => $subscription_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($updated) {
            do_action('premium_content_subscription_cancelled', $subscription_id);
            return true;
        }
        
        return false;
    }

    /**
     * Get all plans
     */
    public static function get_plans($status = 'active') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'premium_plans';
        
        if ($status) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE status = %s ORDER BY price ASC",
                $status
            ));
        }
        
        return $wpdb->get_results("SELECT * FROM $table ORDER BY price ASC");
    }

    /**
     * Get single plan
     */
    public static function get_plan($plan_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'premium_plans';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $plan_id
        ));
    }

    /**
     * Create or update plan
     */
    public static function save_plan($plan_data, $plan_id = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'premium_plans';
        
        $data = array(
            'name' => sanitize_text_field($plan_data['name']),
            'description' => sanitize_textarea_field($plan_data['description']),
            'price' => floatval($plan_data['price']),
            'interval' => sanitize_text_field($plan_data['interval']),
            'features' => is_array($plan_data['features']) ? json_encode($plan_data['features']) : $plan_data['features'],
            'status' => isset($plan_data['status']) ? sanitize_text_field($plan_data['status']) : 'active'
        );
        
        if ($plan_id) {
            // Update existing plan
            $wpdb->update($table, $data, array('id' => $plan_id));
            return $plan_id;
        } else {
            // Create new plan
            $wpdb->insert($table, $data);
            return $wpdb->insert_id;
        }
    }

    /**
     * Delete plan
     */
    public static function delete_plan($plan_id) {
        global $wpdb;
        
        // Check if plan has active subscriptions
        $subscriptions_table = $wpdb->prefix . 'premium_subscriptions';
        $has_subscriptions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $subscriptions_table WHERE plan_id = %d AND status = 'active'",
            $plan_id
        ));
        
        if ($has_subscriptions > 0) {
            return false; // Cannot delete plan with active subscriptions
        }
        
        $table = $wpdb->prefix . 'premium_plans';
        return $wpdb->delete($table, array('id' => $plan_id), array('%d'));
    }

    /**
     * Check for expired subscriptions and update status
     */
    public function check_subscription_expiry() {
        // Only run once per day
        $last_check = get_transient('premium_content_expiry_check');
        if ($last_check) {
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'premium_subscriptions';
        
        // Update expired subscriptions
        $wpdb->query(
            "UPDATE $table 
            SET status = 'expired' 
            WHERE status = 'active' 
            AND expires_at IS NOT NULL 
            AND expires_at < NOW()"
        );
        
        // Set transient for 24 hours
        set_transient('premium_content_expiry_check', true, DAY_IN_SECONDS);
    }

    /**
     * Get subscription statistics for admin
     */
    public static function get_statistics() {
        global $wpdb;
        
        $subscriptions_table = $wpdb->prefix . 'premium_subscriptions';
        
        $stats = array(
            'total_active' => $wpdb->get_var("SELECT COUNT(*) FROM $subscriptions_table WHERE status = 'active'"),
            'total_cancelled' => $wpdb->get_var("SELECT COUNT(*) FROM $subscriptions_table WHERE status = 'cancelled'"),
            'total_expired' => $wpdb->get_var("SELECT COUNT(*) FROM $subscriptions_table WHERE status = 'expired'"),
            'total_revenue' => 0,
            'monthly_revenue' => 0,
            'popular_plans' => array()
        );
        
        // Calculate revenue (approximate based on active subscriptions)
        $plans_table = $wpdb->prefix . 'premium_plans';
        $revenue_query = "
            SELECT SUM(p.price) as total
            FROM $subscriptions_table s
            JOIN $plans_table p ON s.plan_id = p.id
            WHERE s.status = 'active'
        ";
        $stats['total_revenue'] = floatval($wpdb->get_var($revenue_query));
        
        // Get popular plans
        $popular_query = "
            SELECT p.name, COUNT(s.id) as subscription_count
            FROM $subscriptions_table s
            JOIN $plans_table p ON s.plan_id = p.id
            WHERE s.status = 'active'
            GROUP BY s.plan_id
            ORDER BY subscription_count DESC
            LIMIT 5
        ";
        $stats['popular_plans'] = $wpdb->get_results($popular_query);
        
        return $stats;
    }

    /**
     * Send subscription email notification
     */
    private static function send_subscription_email($user_id, $type, $plan) {
        if (premium_content_get_option('user_email_notifications', '1') !== '1') {
            return;
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }
        
        $site_name = get_bloginfo('name');
        
        switch ($type) {
            case 'activated':
                $subject = sprintf('[%s] Subscription Activated', $site_name);
                $message = sprintf(
                    "Hello %s,\n\nYour subscription to %s has been activated.\n\nPlan: %s\nPrice: $%s\n\nThank you for subscribing!\n\n%s",
                    $user->display_name,
                    $site_name,
                    $plan->name,
                    number_format($plan->price, 2),
                    home_url()
                );
                break;
            
            case 'cancelled':
                $subject = sprintf('[%s] Subscription Cancelled', $site_name);
                $message = sprintf(
                    "Hello %s,\n\nYour subscription has been cancelled. You will continue to have access until the end of your billing period.\n\n%s",
                    $user->display_name,
                    home_url()
                );
                break;
            
            case 'expired':
                $subject = sprintf('[%s] Subscription Expired', $site_name);
                $message = sprintf(
                    "Hello %s,\n\nYour subscription has expired. To regain access, please visit:\n\n%s\n\nThank you!",
                    $user->display_name,
                    get_permalink(get_option('premium_content_page_pricing'))
                );
                break;
                
            default:
                return;
        }
        
        wp_mail($user->user_email, $subject, $message);
    }
}