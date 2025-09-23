<?php
/**
 * Handles Contact Form 7 integration for Premium Content plugin.
 */
class Premium_Content_CF7 {

    public function __construct() {
        // Hook into Contact Form 7 submission
        add_action('wpcf7_mail_sent', array($this, 'handle_cf7_submission'));
        
        // Add custom validation
        add_filter('wpcf7_validate_email*', array($this, 'validate_cf7_email'), 10, 2);
        add_filter('wpcf7_validate_checkbox', array($this, 'validate_cf7_checkboxes'), 10, 2);
        
        // Add AJAX action for form unlock
        add_action('wp_ajax_premium_cf7_unlock', array($this, 'handle_cf7_unlock'));
        add_action('wp_ajax_nopriv_premium_cf7_unlock', array($this, 'handle_cf7_unlock'));
    }

    /**
     * Handle Contact Form 7 form submission
     */
    public function handle_cf7_submission($contact_form) {
        $form_mode = get_option('premium_content_form_mode', 'native');
        $cf7_form_id = get_option('premium_content_cf7_form_id', '');
        
        // Only process if we're in CF7 mode and this is our premium form
        if ($form_mode !== 'cf7' || empty($cf7_form_id) || $contact_form->id() != intval($cf7_form_id)) {
            return;
        }

        $submission = WPCF7_Submission::get_instance();
        if (!$submission) {
            return;
        }

        $posted_data = $submission->get_posted_data();
        
        $email = isset($posted_data['premium_email']) ? sanitize_email($posted_data['premium_email']) : '';
        $post_id = isset($posted_data['post_id']) ? intval($posted_data['post_id']) : 0;
        
        if (!$email || !$post_id) {
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'smart_mag_premium_emails';
        
        // Check if email already exists for this post
        $existing_entry = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE email = %s AND post_id = %d",
            $email,
            $post_id
        ));

        if ($existing_entry == 0) {
            // Insert new email
            $wpdb->insert(
                $table_name,
                array(
                    'email' => $email,
                    'post_id' => $post_id,
                ),
                array('%s', '%d')
            );
        }

        // Send to integrations
        $this->send_to_integrations($email, $post_id);
        
        // Set unlock cookie
        setcookie('premium_content_global_unlock', 'unlocked', time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN);
    }

    /**
     * Validate email field (corporate domains only)
     */
    public function validate_cf7_email($result, $tag) {
        $form_mode = get_option('premium_content_form_mode', 'native');
        if ($form_mode !== 'cf7') {
            return $result;
        }

        $name = $tag->name;
        if ($name !== 'premium_email') {
            return $result;
        }

        $value = isset($_POST[$name]) ? sanitize_email($_POST[$name]) : '';
        
        if ($value) {
            $personal_domains = array('gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com', 'aol.com', 'icloud.com', 'live.com', 'msn.com', 'ymail.com', 'rocketmail.com', 'mail.com');
            $email_domain = substr(strrchr($value, "@"), 1);
            
            if (in_array(strtolower($email_domain), $personal_domains)) {
                $result->invalidate($tag, 'Please use a corporate email address. Personal email addresses (Gmail, Yahoo, Outlook, etc.) are not accepted.');
            }
        }

        return $result;
    }

    /**
     * Validate checkboxes based on enabled settings
     */
    public function validate_cf7_checkboxes($result, $tag) {
        $form_mode = get_option('premium_content_form_mode', 'native');
        if ($form_mode !== 'cf7') {
            return $result;
        }

        $cf7_form_id = get_option('premium_content_cf7_form_id', '');
        
        // Only validate our specific premium form
        $submission = WPCF7_Submission::get_instance();
        if (!$submission) {
            return $result;
        }
        
        $contact_form = $submission->get_contact_form();
        if (!$contact_form || $contact_form->id() != intval($cf7_form_id)) {
            return $result;
        }

        $name = $tag->name;
        $enable_checkbox1 = get_option('premium_content_enable_checkbox1', '1');
        $enable_checkbox2 = get_option('premium_content_enable_checkbox2', '1');
        
        // Get the form content to check what checkboxes actually exist
        $form_content = $contact_form->prop('form');
        $checkbox1_exists = strpos($form_content, 'checkbox1') !== false;
        $checkbox2_exists = strpos($form_content, 'checkbox2') !== false;
        
        // Only validate checkboxes that exist in the form AND are enabled in settings
        if ($name === 'checkbox1' && $checkbox1_exists && $enable_checkbox1 === '1') {
            $value = isset($_POST[$name]) ? $_POST[$name] : array();
            if (empty($value)) {
                $result->invalidate($tag, 'You must agree to the first consent requirement.');
            }
        }
        
        if ($name === 'checkbox2' && $checkbox2_exists && $enable_checkbox2 === '1') {
            $value = isset($_POST[$name]) ? $_POST[$name] : array();
            if (empty($value)) {
                $result->invalidate($tag, 'You must agree to the second consent requirement.');
            }
        }

        return $result;
    }

    /**
     * Handle AJAX unlock request (for already submitted emails)
     */
    public function handle_cf7_unlock() {
        if (!isset($_POST['email']) || !isset($_POST['post_id'])) {
            wp_send_json_error('Missing data');
        }

        $email = sanitize_email($_POST['email']);
        $post_id = intval($_POST['post_id']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'smart_mag_premium_emails';
        
        $existing_entry = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE email = %s AND post_id = %d",
            $email,
            $post_id
        ));

        if ($existing_entry > 0) {
            setcookie('premium_content_global_unlock', 'unlocked', time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN);
            wp_send_json_success('Content unlocked');
        } else {
            wp_send_json_error('Email not found');
        }
    }

    /**
     * Send to integrations
     */
    private function send_to_integrations($email, $post_id) {
        require_once plugin_dir_path(__FILE__) . 'class-premium-content-integrations.php';
        $integrations = new Premium_Content_Integrations();
        return $integrations->send_to_integrations($email, $post_id);
    }
}