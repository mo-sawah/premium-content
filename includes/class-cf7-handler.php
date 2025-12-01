<?php
/**
 * Handles Contact Form 7 integration and form management
 */
class Premium_Content_CF7_Handler {

    public function __construct() {
        // Hook into CF7 submission
        add_action('wpcf7_mail_sent', array($this, 'handle_cf7_submission'));
        
        // Add CF7 validation
        add_filter('wpcf7_validate_email*', array($this, 'validate_email'), 10, 2);
        
        // AJAX handlers
        add_action('wp_ajax_premium_create_cf7_form', array($this, 'ajax_create_cf7_form'));
        add_action('wp_ajax_premium_get_cf7_forms', array($this, 'ajax_get_cf7_forms'));
    }

    /**
     * Handle CF7 form submission
     */
    public function handle_cf7_submission($contact_form) {
        $cf7_form_id = premium_content_get_option('cf7_form_id', '');
        
        // Only process our specific premium form
        if (empty($cf7_form_id) || $contact_form->id() != intval($cf7_form_id)) {
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

        // Store email in database
        $this->store_email($email, $post_id);
        
        // Set 30-day email gate access cookie
        setcookie('premium_email_gate_access', 'granted', time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
    }

    /**
     * Store email in database
     */
    private function store_email($email, $post_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'premium_emails';
        $user_id = is_user_logged_in() ? get_current_user_id() : null;
        
        // Check if email already exists for this post
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE email = %s AND post_id = %d",
            $email,
            $post_id
        ));

        if (!$existing) {
            $wpdb->insert(
                $table,
                array(
                    'email' => $email,
                    'post_id' => $post_id,
                    'user_id' => $user_id
                ),
                array('%s', '%d', '%d')
            );
        }
    }

    /**
     * Validate email (accepts all valid emails)
     */
    public function validate_email($result, $tag) {
        // Accept all valid emails - no domain restrictions
        return $result;
    }

    /**
     * AJAX: Create new CF7 form
     */
    public function ajax_create_cf7_form() {
        check_ajax_referer('premium_content_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        if (!function_exists('wpcf7_contact_form')) {
            wp_send_json_error('Contact Form 7 plugin is not installed or activated');
        }

        $form_id = $this->create_premium_form();
        
        if ($form_id) {
            premium_content_update_option('cf7_form_id', $form_id);
            premium_content_update_option('cf7_auto_created', '1');
            
            wp_send_json_success(array(
                'form_id' => $form_id,
                'message' => 'Contact Form 7 form created successfully',
                'edit_url' => admin_url('admin.php?page=wpcf7&post=' . $form_id . '&action=edit')
            ));
        } else {
            wp_send_json_error('Failed to create form');
        }
    }

    /**
     * AJAX: Get list of CF7 forms
     */
    public function ajax_get_cf7_forms() {
        check_ajax_referer('premium_content_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        if (!function_exists('wpcf7_contact_form')) {
            wp_send_json_error('Contact Form 7 plugin is not installed');
        }

        $forms = get_posts(array(
            'post_type' => 'wpcf7_contact_form',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        $form_list = array();
        foreach ($forms as $form) {
            $form_list[] = array(
                'id' => $form->ID,
                'title' => $form->post_title
            );
        }

        wp_send_json_success($form_list);
    }

    /**
     * Create premium CF7 form
     */
    private function create_premium_form() {
        if (!function_exists('wpcf7_contact_form')) {
            return false;
        }

        $form_title = 'Premium Content Access Form';
        
        // Form content with proper styling classes
        $form_content = '<div class="premium-cf7-wrapper">
    <div class="premium-form-field">
        [email* premium_email class:premium-email-input placeholder "Your Email Address"]
    </div>
    
    <div class="premium-form-consent">
        [checkbox premium_consent use_label_element "I agree to receive updates about premium content"]
    </div>
    
    [hidden post_id default:get]
    
    <div class="premium-form-submit">
        [submit class:premium-submit-button "Get Instant Access"]
    </div>
</div>';

        $args = array(
            'post_type' => 'wpcf7_contact_form',
            'post_status' => 'publish',
            'post_title' => $form_title,
        );

        $form_id = wp_insert_post($args);

        if ($form_id) {
            // Set form content
            update_post_meta($form_id, '_form', $form_content);
            
            // Disable email sending (we handle storage ourselves)
            update_post_meta($form_id, '_mail', array(
                'active' => false,
                'subject' => '[_site_title] Premium Content Access',
                'sender' => '[_site_title] <wordpress@' . $_SERVER['HTTP_HOST'] . '>',
                'recipient' => get_option('admin_email'),
                'body' => 'Email: [premium_email]' . "\n" . 'Post ID: [post_id]',
                'additional_headers' => '',
                'attachments' => '',
                'use_html' => false,
                'exclude_blank' => false,
            ));

            // Set additional settings
            update_post_meta($form_id, '_additional_settings', '');
            
            // Set messages
            $messages = array(
                'mail_sent_ok' => 'Thank you! You now have access to this content.',
                'mail_sent_ng' => 'There was an error. Please try again.',
                'validation_error' => 'Please check your email address.',
                'spam' => 'Your submission was flagged as spam.',
                'accept_terms' => 'You must accept the terms and conditions.',
                'invalid_email' => 'Please enter a valid email address.'
            );
            
            update_post_meta($form_id, '_messages', $messages);

            return $form_id;
        }

        return false;
    }

    /**
     * Get form template code for manual creation
     */
    public static function get_form_template() {
        return '<div class="premium-cf7-wrapper">
    <div class="premium-form-field">
        [email* premium_email class:premium-email-input placeholder "Your Email Address"]
    </div>
    
    <div class="premium-form-consent">
        [checkbox premium_consent use_label_element "I agree to receive updates about premium content"]
    </div>
    
    [hidden post_id default:get]
    
    <div class="premium-form-submit">
        [submit class:premium-submit-button "Get Instant Access"]
    </div>
</div>';
    }

    /**
     * Check if CF7 is installed and active
     */
    public static function is_cf7_active() {
        return function_exists('wpcf7_contact_form');
    }

    /**
     * Get all CF7 forms as dropdown options
     */
    public static function get_forms_dropdown() {
        if (!self::is_cf7_active()) {
            return array();
        }

        $forms = get_posts(array(
            'post_type' => 'wpcf7_contact_form',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        $options = array();
        foreach ($forms as $form) {
            $options[$form->ID] = $form->post_title;
        }

        return $options;
    }

    /**
     * Render CF7 form in content gate
     */
    public static function render_form_in_gate($post_id) {
        $cf7_form_id = premium_content_get_option('cf7_form_id', '');
        
        if (empty($cf7_form_id)) {
            return '<p class="premium-error">No form selected. Please configure Contact Form 7 in settings.</p>';
        }

        if (!self::is_cf7_active()) {
            return '<p class="premium-error">Contact Form 7 plugin is not active.</p>';
        }

        $form = wpcf7_contact_form($cf7_form_id);
        if (!$form || !$form->id()) {
            return '<p class="premium-error">Selected form not found.</p>';
        }

        // Add post ID to form dynamically
        add_filter('wpcf7_form_hidden_fields', function($fields) use ($post_id) {
            $fields['post_id'] = $post_id;
            return $fields;
        });

        return do_shortcode('[contact-form-7 id="' . intval($cf7_form_id) . '"]');
    }

    /**
     * Get collected emails for admin view
     */
    public static function get_collected_emails($limit = 100, $offset = 0) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'premium_emails';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));
    }

    /**
     * Get email collection statistics
     */
    public static function get_email_statistics() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'premium_emails';
        
        return array(
            'total_emails' => $wpdb->get_var("SELECT COUNT(*) FROM $table"),
            'unique_emails' => $wpdb->get_var("SELECT COUNT(DISTINCT email) FROM $table"),
            'registered_users' => $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE user_id IS NOT NULL"),
            'this_month' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE created_at >= %s",
                date('Y-m-01 00:00:00')
            ))
        );
    }

    /**
     * Export emails to CSV
     */
    public static function export_emails_csv() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'premium_emails';
        $emails = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="premium-emails-' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fputcsv($output, array('Email', 'Post Title', 'Post ID', 'User ID', 'Date'));

        foreach ($emails as $email) {
            $post_title = get_the_title($email->post_id);
            fputcsv($output, array(
                $email->email,
                $post_title ?: 'Post #' . $email->post_id,
                $email->post_id,
                $email->user_id ?: 'Guest',
                $email->created_at
            ));
        }

        fclose($output);
        exit;
    }
}