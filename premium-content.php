<?php
/**
 * Plugin Name: Premium Content
 * Description: Truncates premium articles and prompts for an email to continue reading.
 * Version: 1.2.3
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
}

// Include necessary files.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-premium-content-ajax.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-premium-content-front.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-premium-content-admin.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-premium-content-meta-badge.php';

// Instantiate the classes to hook into WordPress.
new Premium_Content_Ajax();
new Premium_Content_Front();
new Premium_Content_Admin();
new Premium_Content_Meta_Badge();