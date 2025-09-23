<?php
/**
 * Plugin Name: Premium Content
 * Description: Truncates premium articles and prompts for an email to continue reading.
 * Version: 1.4.4
 * Author: Mohamed Sawah
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


function custom_premium_content_styles() {
    // Path for CSS file inside an 'assets' folder
    $css_file_path = plugin_dir_path( __FILE__ ) . 'assets/premium-content.css';

    if ( file_exists( $css_file_path ) ) {
        wp_enqueue_style(
            'premium-content-fix',
            plugin_dir_url( __FILE__ ) . 'assets/premium-content.css',
            array(),
            filemtime( $css_file_path ),
            'all'
        );
    }
}

// Ensure it loads after the theme styles by setting a high priority
add_action('wp_enqueue_scripts', 'custom_premium_content_styles', 99);

/**
 * Hook to create a custom database table on plugin activation.
 */
register_activation_hook( __FILE__, 'smart_mag_premium_content_install' );
function smart_mag_premium_content_install() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'smart_mag_premium_emails';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        email varchar(255) NOT NULL,
        post_id bigint(20) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    // Set default color options
    $default_colors = array(
        'primary_color' => '#2c3e50',
        'secondary_color' => '#667eea',
        'border_color' => '#e1e5e9',
        'text_color' => '#666',
        'title_color' => '#2c3e50',
        'link_color' => '#667eea',
        'background_color' => '#ffffff'
    );

    foreach ($default_colors as $key => $value) {
        if (get_option('premium_content_' . $key) === false) {
            add_option('premium_content_' . $key, $value);
        }
    }

    // Set default text options
    $default_texts = array(
        'enable_all_posts' => '0',
        'form_mode' => 'native', // NEW - form mode setting
        'cf7_form_id' => '', // NEW - Contact Form 7 form ID
        'enable_checkbox1' => '1',  // NEW - first checkbox enabled by default
        'enable_checkbox2' => '1', 
        'main_title' => 'Continue Reading This Article',
        'subtitle' => 'Enjoy this article as well as all of our content, including E-Guides, news, tips and more.',
        'email_placeholder' => 'Corporate Email Address',
        'button_text' => 'Continue Reading',
        'checkbox1_text' => 'I agree to [site_name] and its group companies processing my personal information to provide information relevant to my professional interests via phone, email, and similar methods. My profile may be enhanced with additional professional details.',
        'checkbox2_text' => 'I agree to [site_name]\'s <a href="[terms_of_use_link]" target="_blank">Partners</a> processing my personal information for direct marketing, including contact via phone, email, and similar methods regarding information relevant to my professional interests.',
        'disclaimer_text' => 'By registering or signing into your [site_name] account, you agree to [site_name]\'s <a href="[terms_of_use_link]" target="_blank">Terms of Use</a> and consent to the processing of your personal information as described in our <a href="[privacy_policy_link]" target="_blank">Privacy Policy</a>. By submitting this form, you acknowledge that your personal information will be transferred to [site_name]\'s servers in the United States. California residents, please refer to our <a href="[ccpa_privacy_notice_link]" target="_blank">CCPA Privacy Notice</a>.',
        'terms_of_use_url' => '#',
        'ccpa_privacy_notice_url' => '#'
    );

    foreach ($default_texts as $key => $value) {
        if (get_option('premium_content_' . $key) === false) {
            add_option('premium_content_' . $key, $value);
        }
    }

    // Set default integration options
    $default_integrations = array(
        'integration_enabled' => '0',
        'integration_type' => 'none',
        'integration_logging' => '0',
        'mailchimp_api_key' => '',
        'mailchimp_list_id' => '',
        'zoho_client_id' => '',
        'zoho_client_secret' => '',
        'zoho_access_token' => '',
        'zoho_refresh_token' => '',
        'zoho_datacenter' => 'com'
    );

    foreach ($default_integrations as $key => $value) {
        if (get_option('premium_content_' . $key) === false) {
            add_option('premium_content_' . $key, $value);
        }
    }
}

// Include necessary files.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-premium-content-ajax.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-premium-content-front.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-premium-content-admin.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-premium-content-meta-badge.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-premium-content-integrations.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-premium-content-cf7.php'; // NEW

// Instantiate the classes to hook into WordPress.
new Premium_Content_Ajax();
new Premium_Content_Front();
new Premium_Content_Admin();
new Premium_Content_Meta_Badge();
new Premium_Content_CF7(); // NEW