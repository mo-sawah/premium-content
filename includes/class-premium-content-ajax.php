<?php
/**
 * Handles all AJAX operations for the Premium Content plugin.
 */
class Premium_Content_Ajax {

    public function __construct() {
        add_action( 'wp_ajax_nopriv_smart_mag_premium_content', array( $this, 'handle_ajax_submission' ) );
        add_action( 'wp_ajax_smart_mag_premium_content', array( $this, 'handle_ajax_submission' ) );
        add_action( 'wp_ajax_delete_premium_email', array( $this, 'handle_delete_email' ) );
    }

    /**
     * Handle AJAX form submission for premium email collection.
     */
    public function handle_ajax_submission() {
        global $wpdb;

        if ( ! isset( $_POST['premium_nonce'] ) || ! wp_verify_nonce( $_POST['premium_nonce'], 'premium_email_nonce' ) ) {
            wp_send_json_error( 'Security check failed.' );
        }

        $email = isset( $_POST['premium_email'] ) ? sanitize_email( $_POST['premium_email'] ) : '';
        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        $checkbox1 = isset( $_POST['checkbox1'] ) ? $_POST['checkbox1'] : '';
        $checkbox2 = isset( $_POST['checkbox2'] ) ? $_POST['checkbox2'] : '';
        $checkbox1_enabled = isset( $_POST['checkbox1_enabled'] ) ? $_POST['checkbox1_enabled'] : '1';
        $checkbox2_enabled = isset( $_POST['checkbox2_enabled'] ) ? $_POST['checkbox2_enabled'] : '1';

        // Validate each checkbox individually if enabled
        $validation_errors = array();

        if ( $checkbox1_enabled === '1' && empty($checkbox1) ) {
            $validation_errors[] = 'You must agree to the first consent requirement.';
        }

        if ( $checkbox2_enabled === '1' && empty($checkbox2) ) {
            $validation_errors[] = 'You must agree to the second consent requirement.';
        }

        if ( !empty($validation_errors) ) {
            wp_send_json_error( implode(' ', $validation_errors) );
        }

        if ( ! is_email( $email ) ) {
            wp_send_json_error( 'Invalid email address.' );
        }

        // Check for corporate email domains.
        $personal_domains = array('gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com', 'aol.com', 'icloud.com', 'live.com', 'msn.com', 'ymail.com', 'rocketmail.com', 'mail.com');
        $email_domain = substr(strrchr($email, "@"), 1);
        
        if (in_array(strtolower($email_domain), $personal_domains)) {
            wp_send_json_error( 'Please use a corporate email address. Personal email addresses (Gmail, Yahoo, Outlook, etc.) are not accepted.' );
        }

        // FIXED: Only check enabled checkboxes instead of both
        // Remove the old logic that always checked both checkboxes
        // The individual validation above already handles this correctly

        // Check if the email already exists for this post.
        $table_name = $wpdb->prefix . 'smart_mag_premium_emails';
        $existing_entry = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE email = %s AND post_id = %d",
            $email,
            $post_id
        ) );

        if ( $existing_entry > 0 ) {
            setcookie('premium_content_global_unlock', 'unlocked', time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN);
            wp_send_json_success( 'Email already submitted.' );
        }

        // Insert the new email into the database.
        $wpdb->insert(
            $table_name,
            array(
                'email' => $email,
                'post_id' => $post_id,
            ),
            array(
                '%s',
                '%d',
            )
        );
        
        // Send to integrations (Mailchimp/Zoho)
        $integration_success = $this->send_to_integrations($email, $post_id);
        
        // Set global unlock cookie
        setcookie('premium_content_global_unlock', 'unlocked', time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN);

        // Return success message with integration status
        $message = 'Email saved successfully.';
        if (!$integration_success) {
            $message .= ' Note: There was an issue sending to your email marketing platform, but your submission was recorded.';
        }

        wp_send_json_success( $message );
    }

    /**
     * Send email to configured integrations
     */
    private function send_to_integrations($email, $post_id) {
        // Load integrations class
        require_once plugin_dir_path( __FILE__ ) . 'class-premium-content-integrations.php';
        $integrations = new Premium_Content_Integrations();
        
        return $integrations->send_to_integrations($email, $post_id);
    }
    
    /**
     * Handle email deletion via AJAX.
     */
    public function handle_delete_email() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        if (!wp_verify_nonce($_POST['nonce'], 'delete_premium_email')) {
            wp_send_json_error('Security check failed');
        }

        global $wpdb;
        $email_id = intval($_POST['email_id']);
        $table_name = $wpdb->prefix . 'smart_mag_premium_emails';

        $result = $wpdb->delete($table_name, array('id' => $email_id), array('%d'));

        if ($result !== false) {
            wp_send_json_success('Email deleted successfully');
        } else {
            wp_send_json_error('Failed to delete email');
        }
    }
}