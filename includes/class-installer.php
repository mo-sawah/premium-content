<?php
/**
 * Handles plugin installation, database creation, and initial setup
 */
class Premium_Content_Installer {

    /**
     * Run on plugin activation
     */
    public static function activate() {
        self::create_tables();
        self::create_pages();
        self::set_default_options();
        self::create_default_cf7_form();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set activation flag
        update_option('premium_content_activated', time());
    }

    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Subscription Plans Table
        $table_plans = $wpdb->prefix . 'premium_plans';
        $sql_plans = "CREATE TABLE $table_plans (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            price decimal(10,2) NOT NULL DEFAULT '0.00',
            interval varchar(20) NOT NULL DEFAULT 'monthly',
            features longtext,
            stripe_price_id varchar(255),
            paypal_plan_id varchar(255),
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql_plans);

        // Subscriptions Table
        $table_subscriptions = $wpdb->prefix . 'premium_subscriptions';
        $sql_subscriptions = "CREATE TABLE $table_subscriptions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            plan_id bigint(20) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            payment_method varchar(20) NOT NULL,
            transaction_id varchar(255),
            stripe_subscription_id varchar(255),
            paypal_subscription_id varchar(255),
            started_at datetime DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime,
            cancelled_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY plan_id (plan_id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_subscriptions);

        // Article View Tracking Table
        $table_views = $wpdb->prefix . 'premium_article_views';
        $sql_views = "CREATE TABLE $table_views (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_identifier varchar(255) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            post_id bigint(20) NOT NULL,
            ip_address varchar(45),
            user_agent varchar(255),
            viewed_at datetime DEFAULT CURRENT_TIMESTAMP,
            view_month varchar(7) NOT NULL,
            PRIMARY KEY (id),
            KEY user_identifier (user_identifier),
            KEY user_id (user_id),
            KEY post_id (post_id),
            KEY view_month (view_month),
            KEY composite_idx (user_identifier, view_month)
        ) $charset_collate;";
        dbDelta($sql_views);

        // Email Collection Table (keeping for CF7 mode)
        $table_emails = $wpdb->prefix . 'premium_emails';
        $sql_emails = "CREATE TABLE $table_emails (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            post_id bigint(20) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY email (email),
            KEY post_id (post_id)
        ) $charset_collate;";
        dbDelta($sql_emails);

        // Insert default plans
        self::create_default_plans();
    }

    /**
     * Create default subscription plans
     */
    private static function create_default_plans() {
        global $wpdb;
        $table_plans = $wpdb->prefix . 'premium_plans';
        
        // Check if plans already exist
        $existing = $wpdb->get_var("SELECT COUNT(*) FROM $table_plans");
        if ($existing > 0) {
            return;
        }

        $default_plans = array(
            array(
                'name' => 'Basic Monthly',
                'description' => 'Access to all premium content',
                'price' => 9.99,
                'interval' => 'monthly',
                'features' => json_encode(array(
                    'Unlimited article access',
                    'Ad-free experience',
                    'Email support'
                )),
                'status' => 'active'
            ),
            array(
                'name' => 'Pro Yearly',
                'description' => 'Best value - Save 20%',
                'price' => 99.99,
                'interval' => 'yearly',
                'features' => json_encode(array(
                    'Everything in Basic',
                    'Priority support',
                    'Early access to new content',
                    'Exclusive newsletter'
                )),
                'status' => 'active'
            ),
            array(
                'name' => 'Lifetime Access',
                'description' => 'One-time payment for lifetime access',
                'price' => 299.99,
                'interval' => 'lifetime',
                'features' => json_encode(array(
                    'Everything in Pro',
                    'Lifetime updates',
                    'VIP support',
                    'All future features included'
                )),
                'status' => 'active'
            )
        );

        foreach ($default_plans as $plan) {
            $wpdb->insert($table_plans, $plan);
        }
    }

    /**
     * Create required pages
     */
    private static function create_pages() {
        $pages = array(
            'pricing' => array(
                'title' => 'Pricing Plans',
                'content' => '[premium_pricing_table]', // ✓ Correct
            ),
            'checkout' => array(
                'title' => 'Checkout',
                'content' => '[premium_checkout]', // ✓ Correct
            ),
            'account' => array(
                'title' => 'My Account',
                'content' => '[premium_account_dashboard]', // ✓ Correct
            ),
            'thank-you' => array(
                'title' => 'Thank You',
                'content' => '[premium_thank_you]', // ✓ Correct
            )
        );

        foreach ($pages as $slug => $page_data) {
            // Check if page already exists
            $page = get_page_by_path($slug);
            
            if (!$page) {
                $page_id = wp_insert_post(array(
                    'post_title' => $page_data['title'],
                    'post_content' => $page_data['content'],
                    'post_name' => $slug,
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'comment_status' => 'closed',
                    'ping_status' => 'closed'
                ));

                if ($page_id) {
                    // Store page ID in options
                    update_option('premium_content_page_' . str_replace('-', '_', $slug), $page_id);
                }
            } else {
                // Update option with existing page ID
                update_option('premium_content_page_' . str_replace('-', '_', $slug), $page->ID);
            }
        }
    }

    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        $defaults = array(
            // Access Control
            'access_mode' => 'free', // free, metered, email_gate, premium
            
            // Allowed post types and categories
            'allowed_post_types' => array('post'),
            'allowed_categories' => array(),
            
            // Metered Paywall Settings
            'metered_limit' => 3,
            'metered_period' => 'monthly', // monthly, weekly, daily
            'metered_show_counter' => '1',
            'metered_counter_position' => 'top', // top, bottom, floating
            
            // CF7 Settings
            'cf7_form_id' => '',
            'cf7_auto_created' => '0',
            
            // Payment Settings
            'stripe_enabled' => '0',
            'stripe_test_mode' => '1',
            'stripe_test_publishable_key' => '',
            'stripe_test_secret_key' => '',
            'stripe_live_publishable_key' => '',
            'stripe_live_secret_key' => '',
            
            'paypal_enabled' => '0',
            'paypal_test_mode' => '1',
            'paypal_client_id' => '',
            'paypal_client_secret' => '',
            
            // Design Settings
            'primary_color' => '#2c3e50',
            'secondary_color' => '#667eea',
            'border_color' => '#e1e5e9',
            'text_color' => '#666666',
            'background_color' => '#ffffff',
            
            // Text Settings
            'paywall_title' => 'Subscribe to Continue Reading',
            'paywall_description' => 'Get unlimited access to all premium content',
            'counter_text' => 'You have {remaining} free articles remaining',
            'limit_reached_text' => 'You\'ve reached your free article limit for this month',
            
            // Email Settings
            'admin_email_notifications' => '1',
            'user_email_notifications' => '1',
            
            // Advanced
            'exclude_admins' => '1',
            'cache_compatibility' => '0',
            'debug_mode' => '0'
        );

        foreach ($defaults as $key => $value) {
            if (get_option('premium_content_' . $key) === false) {
                add_option('premium_content_' . $key, $value);
            }
        }
    }

    /**
     * Create default Contact Form 7 form
     */
    private static function create_default_cf7_form() {
        // Check if CF7 is active
        if (!function_exists('wpcf7_contact_form')) {
            return;
        }

        // Check if form already created
        if (get_option('premium_content_cf7_auto_created') === '1') {
            return;
        }

        $form_title = 'Premium Content Subscription Form';
        $form_content = '[email* premium_email class:premium-email-input placeholder "Your Email Address"]

[checkbox premium_consent use_label_element "I agree to receive updates and premium content"]

[hidden post_id default:get]

[submit class:premium-submit-button "Get Access"]';

        $form_settings = 'demo_mode: off
subscribers_only: off';

        $form_messages = array();

        $args = array(
            'post_type' => 'wpcf7_contact_form',
            'post_status' => 'publish',
            'post_title' => $form_title,
        );

        $form_id = wp_insert_post($args);

        if ($form_id) {
            update_post_meta($form_id, '_form', $form_content);
            update_post_meta($form_id, '_mail', array(
                'active' => false,
                'subject' => '[_site_title] "Premium Content Access"',
                'sender' => '[_site_title] <wordpress@' . $_SERVER['HTTP_HOST'] . '>',
                'recipient' => get_option('admin_email'),
                'body' => 'Email: [premium_email]' . "\n" . 'Post ID: [post_id]',
                'additional_headers' => '',
                'attachments' => '',
                'use_html' => false,
                'exclude_blank' => false,
            ));

            // Store form ID
            update_option('premium_content_cf7_form_id', $form_id);
            update_option('premium_content_cf7_auto_created', '1');
        }
    }

    /**
     * Clean up on plugin uninstall (called from uninstall.php)
     */
    public static function uninstall() {
        global $wpdb;

        // Delete tables
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}premium_plans");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}premium_subscriptions");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}premium_article_views");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}premium_emails");

        // Delete options
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'premium_content_%'");

        // Delete pages (optional - commented out to preserve content)
        // $page_ids = array(
        //     get_option('premium_content_page_pricing'),
        //     get_option('premium_content_page_checkout'),
        //     get_option('premium_content_page_account'),
        //     get_option('premium_content_page_thank_you')
        // );
        // foreach ($page_ids as $page_id) {
        //     if ($page_id) {
        //         wp_delete_post($page_id, true);
        //     }
        // }

        // Clear all caches
        wp_cache_flush();
    }
}