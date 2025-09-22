<?php
/**
 * Handles email marketing integrations for Premium Content plugin.
 */
class Premium_Content_Integrations {

    public function __construct() {
        // This class will be instantiated when needed
    }

    /**
     * Send email to configured integration services
     */
    public function send_to_integrations($email, $post_id) {
        $integration_enabled = get_option('premium_content_integration_enabled', '0');
        
        if ($integration_enabled !== '1') {
            return true; // Integration disabled, return success
        }

        $integration_type = get_option('premium_content_integration_type', 'none');
        $success = true;

        switch ($integration_type) {
            case 'mailchimp':
                $success = $this->send_to_mailchimp($email, $post_id);
                break;
            case 'zoho':
                $success = $this->send_to_zoho($email, $post_id);
                break;
            default:
                $success = true; // No integration selected
                break;
        }

        // Log the attempt
        $this->log_integration_attempt($email, $post_id, $integration_type, $success);

        return $success;
    }

    /**
     * Send email to Mailchimp
     */
    private function send_to_mailchimp($email, $post_id) {
        $api_key = get_option('premium_content_mailchimp_api_key', '');
        $list_id = get_option('premium_content_mailchimp_list_id', '');

        if (empty($api_key) || empty($list_id)) {
            error_log('Premium Content: Mailchimp API key or List ID not configured');
            return false;
        }

        // Extract datacenter from API key
        $datacenter = substr($api_key, strpos($api_key, '-') + 1);
        $url = "https://{$datacenter}.api.mailchimp.com/3.0/lists/{$list_id}/members";

        $data = array(
            'email_address' => $email,
            'status' => 'subscribed',
            'tags' => array('premium-content'),
            'merge_fields' => array(
                'SOURCE' => 'Premium Content Plugin',
                'POST_ID' => (string)$post_id
            )
        );

        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode('user:' . $api_key),
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($data),
            'timeout' => 30
        );

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            error_log('Premium Content Mailchimp Error: ' . $response->get_error_message());
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code === 200) {
            return true;
        } elseif ($response_code === 400) {
            $body_data = json_decode($response_body, true);
            if (isset($body_data['title']) && $body_data['title'] === 'Member Exists') {
                // Email already exists, consider this a success
                return true;
            }
        }

        error_log('Premium Content Mailchimp Error: ' . $response_body);
        return false;
    }

    /**
     * Send email to Zoho CRM
     */
    private function send_to_zoho($email, $post_id) {
        $access_token = get_option('premium_content_zoho_access_token', '');
        $refresh_token = get_option('premium_content_zoho_refresh_token', '');
        $client_id = get_option('premium_content_zoho_client_id', '');
        $client_secret = get_option('premium_content_zoho_client_secret', '');
        $datacenter = get_option('premium_content_zoho_datacenter', 'com');

        if (empty($access_token) || empty($refresh_token)) {
            error_log('Premium Content: Zoho tokens not configured');
            return false;
        }

        // Try to create contact with current access token
        $success = $this->create_zoho_contact($email, $post_id, $access_token, $datacenter);

        // If failed due to token expiry, try to refresh
        if (!$success && !empty($client_id) && !empty($client_secret)) {
            $new_access_token = $this->refresh_zoho_token($refresh_token, $client_id, $client_secret);
            if ($new_access_token) {
                update_option('premium_content_zoho_access_token', $new_access_token);
                $success = $this->create_zoho_contact($email, $post_id, $new_access_token, $datacenter);
            }
        }

        return $success;
    }

    /**
     * Create contact in Zoho CRM
     */
    private function create_zoho_contact($email, $post_id, $access_token, $datacenter) {
        $url = "https://www.zohoapis.{$datacenter}/crm/v2/Contacts";

        $contact_data = array(
            'data' => array(
                array(
                    'Email' => $email,
                    'Lead_Source' => 'Premium Content Plugin',
                    'Description' => 'Added via Premium Content Plugin from Post ID: ' . $post_id
                )
            )
        );

        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Zoho-oauthtoken ' . $access_token,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($contact_data),
            'timeout' => 30
        );

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            error_log('Premium Content Zoho Error: ' . $response->get_error_message());
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code === 201) {
            return true;
        } elseif ($response_code === 200) {
            // Check if contact already exists
            $body_data = json_decode($response_body, true);
            if (isset($body_data['data'][0]['status']) && $body_data['data'][0]['status'] === 'success') {
                return true;
            }
        }

        error_log('Premium Content Zoho Error: ' . $response_body);
        return false;
    }

    /**
     * Refresh Zoho access token
     */
    private function refresh_zoho_token($refresh_token, $client_id, $client_secret) {
        $url = 'https://accounts.zoho.com/oauth/v2/token';

        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
            'body' => http_build_query(array(
                'refresh_token' => $refresh_token,
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'grant_type' => 'refresh_token'
            )),
            'timeout' => 30
        );

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            error_log('Premium Content Zoho Token Refresh Error: ' . $response->get_error_message());
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code === 200) {
            $data = json_decode($response_body, true);
            if (isset($data['access_token'])) {
                return $data['access_token'];
            }
        }

        error_log('Premium Content Zoho Token Refresh Error: ' . $response_body);
        return false;
    }

    /**
     * Log integration attempts for debugging
     */
    private function log_integration_attempt($email, $post_id, $integration_type, $success) {
        $log_enabled = get_option('premium_content_integration_logging', '0');
        
        if ($log_enabled === '1') {
            $status = $success ? 'SUCCESS' : 'FAILED';
            error_log("Premium Content Integration [{$integration_type}]: {$status} - Email: {$email}, Post ID: {$post_id}");
        }
    }

    /**
     * Test API connection
     */
    public function test_connection($integration_type) {
        switch ($integration_type) {
            case 'mailchimp':
                return $this->test_mailchimp_connection();
            case 'zoho':
                return $this->test_zoho_connection();
            default:
                return array('success' => false, 'message' => 'Invalid integration type');
        }
    }

    /**
     * Test Mailchimp connection
     */
    private function test_mailchimp_connection() {
        $api_key = get_option('premium_content_mailchimp_api_key', '');
        
        if (empty($api_key)) {
            return array('success' => false, 'message' => 'API key not configured');
        }

        $datacenter = substr($api_key, strpos($api_key, '-') + 1);
        $url = "https://{$datacenter}.api.mailchimp.com/3.0/ping";

        $args = array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode('user:' . $api_key),
            ),
            'timeout' => 15
        );

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code === 200) {
            return array('success' => true, 'message' => 'Connection successful');
        } else {
            return array('success' => false, 'message' => 'Invalid API key');
        }
    }

    /**
     * Test Zoho connection
     */
    private function test_zoho_connection() {
        $access_token = get_option('premium_content_zoho_access_token', '');
        $datacenter = get_option('premium_content_zoho_datacenter', 'com');
        
        if (empty($access_token)) {
            return array('success' => false, 'message' => 'Access token not configured');
        }

        $url = "https://www.zohoapis.{$datacenter}/crm/v2/org";

        $args = array(
            'headers' => array(
                'Authorization' => 'Zoho-oauthtoken ' . $access_token,
            ),
            'timeout' => 15
        );

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code === 200) {
            return array('success' => true, 'message' => 'Connection successful');
        } else {
            return array('success' => false, 'message' => 'Invalid access token or configuration');
        }
    }
}