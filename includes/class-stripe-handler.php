<?php
/**
 * Handles Stripe payment processing and webhooks
 */
class Premium_Content_Stripe_Handler {

    private $api_key;
    private $test_mode;

    public function __construct() {
        $this->test_mode = premium_content_get_option('stripe_test_mode', '1') === '1';
        $this->api_key = $this->test_mode 
            ? premium_content_get_option('stripe_test_secret_key', '')
            : premium_content_get_option('stripe_live_secret_key', '');

        // AJAX handlers
        add_action('wp_ajax_premium_create_stripe_checkout', array($this, 'ajax_create_checkout'));
        add_action('wp_ajax_nopriv_premium_create_stripe_checkout', array($this, 'ajax_create_checkout'));
        
        // Webhook handler
        add_action('wp_ajax_premium_stripe_webhook', array($this, 'handle_webhook'));
        add_action('wp_ajax_nopriv_premium_stripe_webhook', array($this, 'handle_webhook'));
    }

    /**
     * Create Stripe Checkout Session
     */
    public function ajax_create_checkout() {
        check_ajax_referer('premium_checkout', 'nonce');

        if (empty($this->api_key)) {
            wp_send_json_error('Stripe is not configured');
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

        $user = wp_get_current_user();

        try {
            // Create Stripe Checkout Session
            $session_data = array(
                'payment_method_types' => array('card'),
                'line_items' => array(array(
                    'price_data' => array(
                        'currency' => 'usd',
                        'product_data' => array(
                            'name' => $plan->name,
                            'description' => $plan->description,
                        ),
                        'unit_amount' => intval($plan->price * 100), // Convert to cents
                        'recurring' => $plan->interval !== 'lifetime' ? array(
                            'interval' => $plan->interval === 'yearly' ? 'year' : 'month'
                        ) : null,
                    ),
                    'quantity' => 1,
                )),
                'mode' => $plan->interval === 'lifetime' ? 'payment' : 'subscription',
                'success_url' => add_query_arg('session_id', '{CHECKOUT_SESSION_ID}', get_permalink(get_option('premium_content_page_thank_you'))),
                'cancel_url' => get_permalink(get_option('premium_content_page_pricing')),
                'customer_email' => $user->user_email,
                'metadata' => array(
                    'user_id' => $user_id,
                    'plan_id' => $plan_id,
                    'site_url' => home_url(),
                ),
            );

            // Remove recurring for lifetime plans
            if ($plan->interval === 'lifetime') {
                unset($session_data['line_items'][0]['price_data']['recurring']);
            }

            $session = $this->stripe_request('checkout/sessions', $session_data, 'POST');

            if (isset($session['id'])) {
                wp_send_json_success(array(
                    'session_id' => $session['id'],
                    'url' => $session['url']
                ));
            } else {
                wp_send_json_error('Failed to create checkout session');
            }

        } catch (Exception $e) {
            $this->log_error('Checkout creation failed: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Handle Stripe Webhook
     */
    public function handle_webhook() {
        $payload = @file_get_contents('php://input');
        $sig_header = isset($_SERVER['HTTP_STRIPE_SIGNATURE']) ? $_SERVER['HTTP_STRIPE_SIGNATURE'] : '';
        
        $webhook_secret = $this->test_mode
            ? premium_content_get_option('stripe_test_webhook_secret', '')
            : premium_content_get_option('stripe_live_webhook_secret', '');

        if (empty($webhook_secret)) {
            $this->log_error('Webhook secret not configured');
            http_response_code(400);
            exit;
        }

        try {
            $event = $this->verify_webhook_signature($payload, $sig_header, $webhook_secret);
            
            $this->log_debug('Webhook received: ' . $event['type']);

            switch ($event['type']) {
                case 'checkout.session.completed':
                    $this->handle_checkout_completed($event['data']['object']);
                    break;

                case 'customer.subscription.updated':
                    $this->handle_subscription_updated($event['data']['object']);
                    break;

                case 'customer.subscription.deleted':
                    $this->handle_subscription_cancelled($event['data']['object']);
                    break;

                case 'invoice.payment_succeeded':
                    $this->handle_payment_succeeded($event['data']['object']);
                    break;

                case 'invoice.payment_failed':
                    $this->handle_payment_failed($event['data']['object']);
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
     * Handle checkout completed
     */
    private function handle_checkout_completed($session) {
        $user_id = isset($session['metadata']['user_id']) ? intval($session['metadata']['user_id']) : 0;
        $plan_id = isset($session['metadata']['plan_id']) ? intval($session['metadata']['plan_id']) : 0;

        if (!$user_id || !$plan_id) {
            $this->log_error('Missing user_id or plan_id in checkout session');
            return;
        }

        $subscription_id = isset($session['subscription']) ? $session['subscription'] : null;
        
        $transaction_data = array(
            'transaction_id' => $session['id'],
            'stripe_subscription_id' => $subscription_id,
        );

        Premium_Content_Subscription_Manager::create_subscription($user_id, $plan_id, 'stripe', $transaction_data);
        
        $this->log_debug('Subscription created for user ' . $user_id . ' with plan ' . $plan_id);
    }

    /**
     * Handle subscription updated
     */
    private function handle_subscription_updated($subscription) {
        // Handle subscription changes (upgrades, downgrades, etc.)
        $this->log_debug('Subscription updated: ' . $subscription['id']);
    }

    /**
     * Handle subscription cancelled
     */
    private function handle_subscription_cancelled($subscription) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'premium_subscriptions';
        
        $wpdb->update(
            $table,
            array('status' => 'cancelled', 'cancelled_at' => current_time('mysql')),
            array('stripe_subscription_id' => $subscription['id']),
            array('%s', '%s'),
            array('%s')
        );
        
        $this->log_debug('Subscription cancelled: ' . $subscription['id']);
    }

    /**
     * Handle payment succeeded
     */
    private function handle_payment_succeeded($invoice) {
        $this->log_debug('Payment succeeded for invoice: ' . $invoice['id']);
    }

    /**
     * Handle payment failed
     */
    private function handle_payment_failed($invoice) {
        $this->log_error('Payment failed for invoice: ' . $invoice['id']);
    }

    /**
     * Make Stripe API request
     */
    private function stripe_request($endpoint, $data = array(), $method = 'GET') {
        $url = 'https://api.stripe.com/v1/' . $endpoint;
        
        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
            'timeout' => 30,
        );

        if ($method === 'POST' && !empty($data)) {
            $args['body'] = http_build_query($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        if (isset($decoded['error'])) {
            throw new Exception($decoded['error']['message']);
        }

        return $decoded;
    }

    /**
     * Verify webhook signature
     */
    private function verify_webhook_signature($payload, $sig_header, $secret) {
        // Simple signature verification (production should use Stripe's library)
        $elements = explode(',', $sig_header);
        $timestamp = null;
        $signatures = array();

        foreach ($elements as $element) {
            list($key, $value) = explode('=', $element, 2);
            if ($key === 't') {
                $timestamp = $value;
            } elseif ($key === 'v1') {
                $signatures[] = $value;
            }
        }

        if (!$timestamp || empty($signatures)) {
            throw new Exception('Invalid signature format');
        }

        $signed_payload = $timestamp . '.' . $payload;
        $expected_signature = hash_hmac('sha256', $signed_payload, $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected_signature, $signature)) {
                return json_decode($payload, true);
            }
        }

        throw new Exception('Invalid signature');
    }

    /**
     * Test Stripe connection
     */
    public static function test_connection() {
        $handler = new self();
        
        if (empty($handler->api_key)) {
            return array('success' => false, 'message' => 'API key not configured');
        }

        try {
            $handler->stripe_request('balance');
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
            error_log('Premium Content Stripe: ' . $message);
        }
    }

    /**
     * Log error message
     */
    private function log_error($message) {
        error_log('Premium Content Stripe Error: ' . $message);
    }
}