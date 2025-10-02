<?php
/**
 * Handles PayPal payment processing and webhooks
 */
class Premium_Content_PayPal_Handler {

    private $client_id;
    private $client_secret;
    private $test_mode;
    private $api_url;

    public function __construct() {
        $this->test_mode = premium_content_get_option('paypal_test_mode', '1') === '1';
        $this->client_id = premium_content_get_option('paypal_client_id', '');
        $this->client_secret = premium_content_get_option('paypal_client_secret', '');
        $this->api_url = $this->test_mode 
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';

        // AJAX handlers
        add_action('wp_ajax_premium_create_paypal_order', array($this, 'ajax_create_order'));
        add_action('wp_ajax_nopriv_premium_create_paypal_order', array($this, 'ajax_create_order'));
        add_action('wp_ajax_premium_capture_paypal_order', array($this, 'ajax_capture_order'));
        add_action('wp_ajax_nopriv_premium_capture_paypal_order', array($this, 'ajax_capture_order'));
        
        // Webhook handler
        add_action('wp_ajax_premium_paypal_webhook', array($this, 'handle_webhook'));
        add_action('wp_ajax_nopriv_premium_paypal_webhook', array($this, 'handle_webhook'));
    }

    /**
     * Create PayPal Order
     */
    public function ajax_create_order() {
        check_ajax_referer('premium_checkout', 'nonce');

        if (empty($this->client_id) || empty($this->client_secret)) {
            wp_send_json_error('PayPal is not configured');
        }

        $plan_id = isset($_POST['plan_id']) ? intval($_POST['plan_id']) : 0;
        $plan = Premium_Content_Subscription_Manager::get_plan($plan_id);

        if (!$plan) {
            wp_send_json_error('Invalid plan');
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('You must be logged in');
        }

        try {
            $access_token = $this->get_access_token();

            $order_data = array(
                'intent' => 'CAPTURE',
                'purchase_units' => array(array(
                    'description' => $plan->name,
                    'amount' => array(
                        'currency_code' => 'USD',
                        'value' => number_format($plan->price, 2, '.', '')
                    ),
                    'custom_id' => json_encode(array(
                        'user_id' => $user_id,
                        'plan_id' => $plan_id
                    ))
                )),
                'application_context' => array(
                    'return_url' => add_query_arg('paypal_order_id', 'ORDERID', get_permalink(get_option('premium_content_page_thank_you'))),
                    'cancel_url' => get_permalink(get_option('premium_content_page_pricing')),
                    'brand_name' => get_bloginfo('name'),
                    'user_action' => 'PAY_NOW'
                )
            );

            $response = $this->paypal_request('/v2/checkout/orders', $order_data, 'POST', $access_token);

            if (isset($response['id'])) {
                wp_send_json_success(array(
                    'order_id' => $response['id'],
                    'approve_url' => $this->get_approve_url($response['links'])
                ));
            } else {
                wp_send_json_error('Failed to create order');
            }

        } catch (Exception $e) {
            $this->log_error('Order creation failed: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Capture PayPal Order (after user approves)
     */
    public function ajax_capture_order() {
        check_ajax_referer('premium_checkout', 'nonce');

        $order_id = isset($_POST['order_id']) ? sanitize_text_field($_POST['order_id']) : '';

        if (empty($order_id)) {
            wp_send_json_error('Missing order ID');
        }

        try {
            $access_token = $this->get_access_token();
            $response = $this->paypal_request('/v2/checkout/orders/' . $order_id . '/capture', array(), 'POST', $access_token);

            if ($response['status'] === 'COMPLETED') {
                // Extract metadata
                $custom_id = $response['purchase_units'][0]['payments']['captures'][0]['custom_id'] ?? '';
                $metadata = json_decode($custom_id, true);

                if ($metadata && isset($metadata['user_id']) && isset($metadata['plan_id'])) {
                    $transaction_data = array(
                        'transaction_id' => $response['id'],
                        'paypal_subscription_id' => null,
                    );

                    Premium_Content_Subscription_Manager::create_subscription(
                        $metadata['user_id'],
                        $metadata['plan_id'],
                        'paypal',
                        $transaction_data
                    );

                    wp_send_json_success(array('message' => 'Payment captured successfully'));
                } else {
                    wp_send_json_error('Invalid metadata');
                }
            } else {
                wp_send_json_error('Payment not completed');
            }

        } catch (Exception $e) {
            $this->log_error('Capture failed: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Handle PayPal Webhook
     */
    public function handle_webhook() {
        $payload = @file_get_contents('php://input');
        $headers = getallheaders();

        $this->log_debug('Webhook received');

        try {
            $event = json_decode($payload, true);

            if (!$event || !isset($event['event_type'])) {
                throw new Exception('Invalid webhook payload');
            }

            $this->log_debug('Event type: ' . $event['event_type']);

            switch ($event['event_type']) {
                case 'PAYMENT.CAPTURE.COMPLETED':
                    $this->handle_payment_completed($event['resource']);
                    break;

                case 'BILLING.SUBSCRIPTION.ACTIVATED':
                    $this->handle_subscription_activated($event['resource']);
                    break;

                case 'BILLING.SUBSCRIPTION.CANCELLED':
                    $this->handle_subscription_cancelled($event['resource']);
                    break;

                case 'BILLING.SUBSCRIPTION.EXPIRED':
                    $this->handle_subscription_expired($event['resource']);
                    break;
            }

            http_response_code(200);
            echo json_encode(array('success' => true));

        } catch (Exception $e) {
            $this->log_error('Webhook error: ' . $e->getMessage());
            http_response_code(400);
            echo json_encode(array('error' => $e->getMessage()));
        }

        exit;
    }

    /**
     * Handle payment completed
     */
    private function handle_payment_completed($resource) {
        $this->log_debug('Payment completed: ' . $resource['id']);
    }

    /**
     * Handle subscription activated
     */
    private function handle_subscription_activated($resource) {
        $this->log_debug('Subscription activated: ' . $resource['id']);
    }

    /**
     * Handle subscription cancelled
     */
    private function handle_subscription_cancelled($resource) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'premium_subscriptions';
        
        $wpdb->update(
            $table,
            array('status' => 'cancelled', 'cancelled_at' => current_time('mysql')),
            array('paypal_subscription_id' => $resource['id']),
            array('%s', '%s'),
            array('%s')
        );
        
        $this->log_debug('Subscription cancelled: ' . $resource['id']);
    }

    /**
     * Handle subscription expired
     */
    private function handle_subscription_expired($resource) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'premium_subscriptions';
        
        $wpdb->update(
            $table,
            array('status' => 'expired'),
            array('paypal_subscription_id' => $resource['id']),
            array('%s'),
            array('%s')
        );
        
        $this->log_debug('Subscription expired: ' . $resource['id']);
    }

    /**
     * Get PayPal access token
     */
    private function get_access_token() {
        $response = wp_remote_post($this->api_url . '/v1/oauth2/token', array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($this->client_id . ':' . $this->client_secret),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
            'body' => 'grant_type=client_credentials',
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($body['access_token'])) {
            throw new Exception('Failed to get access token');
        }

        return $body['access_token'];
    }

    /**
     * Make PayPal API request
     */
    private function paypal_request($endpoint, $data = array(), $method = 'GET', $access_token = null) {
        $url = $this->api_url . $endpoint;

        $args = array(
            'method' => $method,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $access_token,
            ),
            'timeout' => 30,
        );

        if ($method === 'POST' && !empty($data)) {
            $args['body'] = json_encode($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    /**
     * Get approve URL from links array
     */
    private function get_approve_url($links) {
        foreach ($links as $link) {
            if ($link['rel'] === 'approve') {
                return $link['href'];
            }
        }
        return null;
    }

    /**
     * Test PayPal connection
     */
    public static function test_connection() {
        $handler = new self();
        
        if (empty($handler->client_id) || empty($handler->client_secret)) {
            return array('success' => false, 'message' => 'Credentials not configured');
        }

        try {
            $handler->get_access_token();
            return array('success' => true, 'message' => 'Connection successful');
        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    /**
     * Log debug message
     */
    private function log_debug($message) {
        if (premium_content_get_option('debug_mode', '0') === '1') {
            error_log('Premium Content PayPal: ' . $message);
        }
    }

    /**
     * Log error message
     */
    private function log_error($message) {
        error_log('Premium Content PayPal Error: ' . $message);
    }
}