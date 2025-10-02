<?php
/**
 * Plugin Name: Premium Content Pro
 * Description: Advanced content monetization with metered paywall, subscriptions, and payment processing.
 * Version: 2.0.15
 * Author: Mohamed Sawah
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}



// Define plugin constants
define('PREMIUM_CONTENT_VERSION', '2.0.15');
define('PREMIUM_CONTENT_PATH', plugin_dir_path(__FILE__));
define('PREMIUM_CONTENT_URL', plugin_dir_url(__FILE__));

// Check if tables exist and create them if needed
add_action('plugins_loaded', 'premium_content_check_tables', 1);
function premium_content_check_tables() {
    global $wpdb;
    
    $table_plans = $wpdb->prefix . 'premium_plans';
    
    // Check if main table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_plans'") != $table_plans) {
        // Tables don't exist, run installer
        require_once plugin_dir_path(__FILE__) . 'includes/class-installer.php';
        Premium_Content_Installer::activate();
    }
}

/**
 * Plugin activation hook
 */
register_activation_hook(__FILE__, 'premium_content_activate');
function premium_content_activate() {
    require_once PREMIUM_CONTENT_PATH . 'includes/class-installer.php';
    Premium_Content_Installer::activate();
}

/**
 * Plugin deactivation hook
 */
register_deactivation_hook(__FILE__, 'premium_content_deactivate');
function premium_content_deactivate() {
    // Clear scheduled events if any
    wp_clear_scheduled_hook('premium_content_daily_cleanup');
}

/**
 * Load plugin text domain for translations
 */
add_action('plugins_loaded', 'premium_content_load_textdomain');
function premium_content_load_textdomain() {
    load_plugin_textdomain('premium-content', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

/**
 * Enqueue admin styles and scripts
 */
add_action('admin_enqueue_scripts', 'premium_content_admin_assets');
function premium_content_admin_assets($hook) {
    // Only load on plugin pages
    if (strpos($hook, 'premium-content') === false && $hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }

    wp_enqueue_style(
        'premium-content-admin',
        PREMIUM_CONTENT_URL . 'assets/css/admin.css',
        array(),
        PREMIUM_CONTENT_VERSION
    );

    wp_enqueue_script(
        'premium-content-admin',
        PREMIUM_CONTENT_URL . 'assets/js/admin.js',
        array('jquery'),
        PREMIUM_CONTENT_VERSION,
        true
    );

    wp_localize_script('premium-content-admin', 'premiumContentAdmin', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('premium_content_admin'),
        'strings' => array(
            'confirmDelete' => __('Are you sure you want to delete this?', 'premium-content'),
            'processing' => __('Processing...', 'premium-content'),
            'error' => __('An error occurred. Please try again.', 'premium-content'),
        )
    ));
}

/**
 * Enqueue frontend styles and scripts
 */
add_action('wp_enqueue_scripts', 'premium_content_frontend_assets');
function premium_content_frontend_assets() {
    wp_enqueue_style(
        'premium-content-frontend',
        PREMIUM_CONTENT_URL . 'assets/css/frontend.css',
        array(),
        PREMIUM_CONTENT_VERSION
    );

    // Only load paywall script on single posts/pages
    if (is_singular()) {
        wp_enqueue_script(
            'premium-content-paywall',
            PREMIUM_CONTENT_URL . 'assets/js/metered-paywall.js',
            array('jquery'),
            PREMIUM_CONTENT_VERSION,
            true
        );

        wp_localize_script('premium-content-paywall', 'premiumContentPaywall', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('premium_content_paywall'),
            'postId' => get_the_ID(),
            'isUserLoggedIn' => is_user_logged_in(),
            'strings' => array(
                'articlesRemaining' => __('You have %d free articles remaining this month', 'premium-content'),
                'lastArticle' => __('This is your last free article!', 'premium-content'),
                'limitReached' => __('You\'ve reached your free article limit', 'premium-content'),
            )
        ));
    }

    // Enqueue dashboard styles on user dashboard page
    if (is_page('account') || is_page('pricing') || is_page('checkout')) {
        wp_enqueue_style(
            'premium-content-dashboard',
            PREMIUM_CONTENT_URL . 'assets/css/dashboard.css',
            array(),
            PREMIUM_CONTENT_VERSION
        );
    }

    if (is_page('checkout')) {
        wp_enqueue_script(
            'premium-content-checkout',
            PREMIUM_CONTENT_URL . 'assets/js/checkout.js',
            array('jquery'),
            PREMIUM_CONTENT_VERSION,
            true
        );

        wp_localize_script('premium-content-checkout', 'premiumCheckout', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('premium_checkout'),
            'strings' => array(
                'processing' => __('Processing...', 'premium-content'),
                'error' => __('An error occurred. Please try again.', 'premium-content'),
            )
        ));
    }
}

/**
 * Include required files
 */
require_once PREMIUM_CONTENT_PATH . 'includes/class-installer.php';
require_once PREMIUM_CONTENT_PATH . 'includes/class-admin.php';
require_once PREMIUM_CONTENT_PATH . 'includes/class-cf7-handler.php';
require_once PREMIUM_CONTENT_PATH . 'includes/class-metered-paywall.php';
require_once PREMIUM_CONTENT_PATH . 'includes/class-subscription-manager.php';
require_once PREMIUM_CONTENT_PATH . 'includes/class-stripe-handler.php';
require_once PREMIUM_CONTENT_PATH . 'includes/class-paypal-handler.php';
require_once PREMIUM_CONTENT_PATH . 'includes/class-user-dashboard.php';
require_once PREMIUM_CONTENT_PATH . 'includes/class-page-generator.php';
require_once PREMIUM_CONTENT_PATH . 'includes/class-post-meta.php';

/**
 * Initialize plugin classes
 */
add_action('plugins_loaded', 'premium_content_init');
function premium_content_init() {
    new Premium_Content_Admin();
    new Premium_Content_CF7_Handler();
    new Premium_Content_Metered_Paywall();
    new Premium_Content_Subscription_Manager();
    new Premium_Content_Stripe_Handler();
    new Premium_Content_PayPal_Handler();
    new Premium_Content_User_Dashboard();
    new Premium_Content_Post_Meta();
}

/**
 * Add settings link on plugins page
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'premium_content_action_links');
function premium_content_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=premium-content') . '">' . __('Settings', 'premium-content') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

/**
 * Helper function to get plugin option
 */
function premium_content_get_option($option_name, $default = '') {
    return get_option('premium_content_' . $option_name, $default);
}

/**
 * Helper function to update plugin option
 */
function premium_content_update_option($option_name, $value) {
    return update_option('premium_content_' . $option_name, $value);
}

/**
 * Check if user has active subscription
 */
function premium_content_user_has_subscription($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    return Premium_Content_Subscription_Manager::user_has_active_subscription($user_id);
}

/**
 * Get user's article view count for current month
 */
function premium_content_get_user_view_count($identifier = null) {
    return Premium_Content_Metered_Paywall::get_view_count($identifier);
}

/**
 * Check if content should be locked
 */
function premium_content_should_lock_content($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    return Premium_Content_Metered_Paywall::should_show_paywall($post_id);
}