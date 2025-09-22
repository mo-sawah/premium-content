<?php
/**
 * Handles all admin-facing functionality, including menu pages and settings.
 */
class Premium_Content_Admin {

    public function __construct() {
        add_action('admin_menu', array( $this, 'add_admin_menu' ));
        add_action('admin_init', array( $this, 'handle_emails_export' ));
        add_action('wp_ajax_test_premium_integration', array( $this, 'test_integration_ajax' ));
    }
    
    /**
     * Add main plugin menu and submenus in admin.
     */
    public function add_admin_menu() {
        add_menu_page(
            'Premium Content',
            'Premium Content',
            'manage_options',
            'premium-content',
            array( $this, 'settings_page' ),
            'dashicons-lock',
            30
        );

        add_submenu_page(
            'premium-content',
            'Settings',
            'Settings',
            'manage_options',
            'premium-content',
            array( $this, 'settings_page' )
        );

        add_submenu_page(
            'premium-content',
            'Integrations',
            'Integrations',
            'manage_options',
            'premium-integrations',
            array( $this, 'integrations_page' )
        );

        add_submenu_page(
            'premium-content',
            'Premium Emails',
            'Premium Emails',
            'manage_options',
            'premium-emails',
            array( $this, 'emails_page' )
        );
    }
    
    /**
     * Get color option with fallback to default.
     */
    private function get_premium_content_color($color_name, $default) {
        return get_option('premium_content_' . $color_name, $default);
    }

    /**
     * Get text option with fallback to default.
     */
    private function get_premium_content_text($text_name, $default) {
        return get_option('premium_content_' . $text_name, $default);
    }

    /**
     * Test integration AJAX handler
     */
    public function test_integration_ajax() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'test_premium_integration')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $integration_type = sanitize_text_field($_POST['integration_type']);
        
        // Load integrations class
        require_once plugin_dir_path( __FILE__ ) . 'class-premium-content-integrations.php';
        $integrations = new Premium_Content_Integrations();
        
        $result = $integrations->test_connection($integration_type);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Integrations page callback function.
     */
    public function integrations_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['submit']) && wp_verify_nonce($_POST['premium_integration_nonce'], 'premium_integration_settings')) {
            // Save integration settings
            $integration_enabled = isset($_POST['integration_enabled']) ? '1' : '0';
            update_option('premium_content_integration_enabled', $integration_enabled);

            $integration_type = sanitize_text_field($_POST['integration_type']);
            update_option('premium_content_integration_type', $integration_type);

            $integration_logging = isset($_POST['integration_logging']) ? '1' : '0';
            update_option('premium_content_integration_logging', $integration_logging);

            // Save Mailchimp settings
            if (isset($_POST['mailchimp_api_key'])) {
                update_option('premium_content_mailchimp_api_key', sanitize_text_field($_POST['mailchimp_api_key']));
            }
            if (isset($_POST['mailchimp_list_id'])) {
                update_option('premium_content_mailchimp_list_id', sanitize_text_field($_POST['mailchimp_list_id']));
            }

            // Save Zoho settings
            if (isset($_POST['zoho_client_id'])) {
                update_option('premium_content_zoho_client_id', sanitize_text_field($_POST['zoho_client_id']));
            }
            if (isset($_POST['zoho_client_secret'])) {
                update_option('premium_content_zoho_client_secret', sanitize_text_field($_POST['zoho_client_secret']));
            }
            if (isset($_POST['zoho_access_token'])) {
                update_option('premium_content_zoho_access_token', sanitize_text_field($_POST['zoho_access_token']));
            }
            if (isset($_POST['zoho_refresh_token'])) {
                update_option('premium_content_zoho_refresh_token', sanitize_text_field($_POST['zoho_refresh_token']));
            }
            if (isset($_POST['zoho_datacenter'])) {
                update_option('premium_content_zoho_datacenter', sanitize_text_field($_POST['zoho_datacenter']));
            }

            echo '<div class="notice notice-success"><p>Integration settings saved successfully!</p></div>';
        }

        // Get current values
        $integration_enabled = get_option('premium_content_integration_enabled', '0');
        $integration_type = get_option('premium_content_integration_type', 'none');
        $integration_logging = get_option('premium_content_integration_logging', '0');
        
        $mailchimp_api_key = get_option('premium_content_mailchimp_api_key', '');
        $mailchimp_list_id = get_option('premium_content_mailchimp_list_id', '');
        
        $zoho_client_id = get_option('premium_content_zoho_client_id', '');
        $zoho_client_secret = get_option('premium_content_zoho_client_secret', '');
        $zoho_access_token = get_option('premium_content_zoho_access_token', '');
        $zoho_refresh_token = get_option('premium_content_zoho_refresh_token', '');
        $zoho_datacenter = get_option('premium_content_zoho_datacenter', 'com');

        ?>
        <div class="wrap">
            <h1>Email Marketing Integrations</h1>
            <p>Connect your premium content to Mailchimp or Zoho CRM to automatically add subscribers.</p>
            
            <form method="post" action="">
                <?php wp_nonce_field('premium_integration_settings', 'premium_integration_nonce'); ?>
                
                <!-- General Settings -->
                <h2>General Settings</h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="integration_enabled">Enable Integration</label></th>
                            <td>
                                <input type="checkbox" id="integration_enabled" name="integration_enabled" value="1" <?php checked($integration_enabled, '1'); ?> />
                                <p class="description">Enable automatic sending of emails to your chosen marketing platform.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="integration_type">Integration Type</label></th>
                            <td>
                                <select id="integration_type" name="integration_type">
                                    <option value="none" <?php selected($integration_type, 'none'); ?>>None</option>
                                    <option value="mailchimp" <?php selected($integration_type, 'mailchimp'); ?>>Mailchimp</option>
                                    <option value="zoho" <?php selected($integration_type, 'zoho'); ?>>Zoho CRM</option>
                                </select>
                                <p class="description">Choose your email marketing platform.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="integration_logging">Enable Logging</label></th>
                            <td>
                                <input type="checkbox" id="integration_logging" name="integration_logging" value="1" <?php checked($integration_logging, '1'); ?> />
                                <p class="description">Log integration attempts for debugging (check WordPress error logs).</p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Mailchimp Settings -->
                <div id="mailchimp-settings" style="display: <?php echo $integration_type === 'mailchimp' ? 'block' : 'none'; ?>;">
                    <h2>Mailchimp Settings</h2>
                    <div class="integration-instructions">
                        <h4>How to get your Mailchimp API Key and List ID:</h4>
                        <ol>
                            <li>Log into your <a href="https://mailchimp.com/login/" target="_blank">Mailchimp account</a></li>
                            <li>Go to <strong>Profile → Extras → API keys</strong></li>
                            <li>Create a new API key or copy an existing one</li>
                            <li>For List ID: Go to <strong>Audience → All contacts → Settings → Audience name and campaign defaults</strong></li>
                            <li>Find the "Audience ID" at the bottom of the page</li>
                        </ol>
                    </div>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><label for="mailchimp_api_key">API Key</label></th>
                                <td>
                                    <input type="password" id="mailchimp_api_key" name="mailchimp_api_key" value="<?php echo esc_attr($mailchimp_api_key); ?>" class="regular-text" />
                                    <p class="description">Your Mailchimp API key (format: xxxxxxxxxxxxxxxxxx-us1)</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="mailchimp_list_id">List/Audience ID</label></th>
                                <td>
                                    <input type="text" id="mailchimp_list_id" name="mailchimp_list_id" value="<?php echo esc_attr($mailchimp_list_id); ?>" class="regular-text" />
                                    <p class="description">Your Mailchimp list/audience ID</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p>
                        <button type="button" class="button" onclick="testIntegration('mailchimp')">Test Mailchimp Connection</button>
                        <span id="mailchimp-test-result"></span>
                    </p>
                </div>

                <!-- Zoho Settings -->
                <div id="zoho-settings" style="display: <?php echo $integration_type === 'zoho' ? 'block' : 'none'; ?>;">
                    <h2>Zoho CRM Settings</h2>
                    <div class="integration-instructions">
                        <h4>How to set up Zoho CRM integration:</h4>
                        <ol>
                            <li>Go to <a href="https://api-console.zoho.com/" target="_blank">Zoho API Console</a></li>
                            <li>Create a new "Server-based Applications" client</li>
                            <li>Note down the Client ID and Client Secret</li>
                            <li>Generate authorization code using scope: <code>ZohoCRM.modules.ALL</code></li>
                            <li>Exchange authorization code for access and refresh tokens</li>
                            <li><strong>Detailed setup guide:</strong> <a href="https://www.zoho.com/crm/developer/docs/api/v2/oauth-overview.html" target="_blank">Zoho OAuth Documentation</a></li>
                        </ol>
                    </div>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><label for="zoho_client_id">Client ID</label></th>
                                <td>
                                    <input type="text" id="zoho_client_id" name="zoho_client_id" value="<?php echo esc_attr($zoho_client_id); ?>" class="regular-text" />
                                    <p class="description">Your Zoho application Client ID</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="zoho_client_secret">Client Secret</label></th>
                                <td>
                                    <input type="password" id="zoho_client_secret" name="zoho_client_secret" value="<?php echo esc_attr($zoho_client_secret); ?>" class="regular-text" />
                                    <p class="description">Your Zoho application Client Secret</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="zoho_access_token">Access Token</label></th>
                                <td>
                                    <textarea id="zoho_access_token" name="zoho_access_token" rows="3" class="large-text"><?php echo esc_textarea($zoho_access_token); ?></textarea>
                                    <p class="description">Your Zoho access token</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="zoho_refresh_token">Refresh Token</label></th>
                                <td>
                                    <textarea id="zoho_refresh_token" name="zoho_refresh_token" rows="3" class="large-text"><?php echo esc_textarea($zoho_refresh_token); ?></textarea>
                                    <p class="description">Your Zoho refresh token</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="zoho_datacenter">Data Center</label></th>
                                <td>
                                    <select id="zoho_datacenter" name="zoho_datacenter">
                                        <option value="com" <?php selected($zoho_datacenter, 'com'); ?>>Global (.com)</option>
                                        <option value="eu" <?php selected($zoho_datacenter, 'eu'); ?>>Europe (.eu)</option>
                                        <option value="in" <?php selected($zoho_datacenter, 'in'); ?>>India (.in)</option>
                                        <option value="com.au" <?php selected($zoho_datacenter, 'com.au'); ?>>Australia (.com.au)</option>
                                        <option value="jp" <?php selected($zoho_datacenter, 'jp'); ?>>Japan (.jp)</option>
                                    </select>
                                    <p class="description">Select your Zoho data center region</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p>
                        <button type="button" class="button" onclick="testIntegration('zoho')">Test Zoho Connection</button>
                        <span id="zoho-test-result"></span>
                    </p>
                </div>
                
                <?php submit_button('Save Integration Settings'); ?>
            </form>
        </div>

        <style>
            .integration-instructions {
                background: #f0f8ff;
                border: 1px solid #ddd;
                border-radius: 5px;
                padding: 15px;
                margin: 20px 0;
            }
            .integration-instructions h4 {
                margin-top: 0;
                color: #2c3e50;
            }
            .integration-instructions ol {
                margin: 10px 0;
            }
            .integration-instructions code {
                background: #f4f4f4;
                padding: 2px 4px;
                border-radius: 3px;
            }
            .test-success {
                color: #46b450;
                font-weight: bold;
            }
            .test-error {
                color: #dc3232;
                font-weight: bold;
            }
        </style>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const integrationTypeSelect = document.getElementById('integration_type');
                const mailchimpSettings = document.getElementById('mailchimp-settings');
                const zohoSettings = document.getElementById('zoho-settings');

                function toggleIntegrationSettings() {
                    const selectedType = integrationTypeSelect.value;
                    
                    mailchimpSettings.style.display = selectedType === 'mailchimp' ? 'block' : 'none';
                    zohoSettings.style.display = selectedType === 'zoho' ? 'block' : 'none';
                }

                integrationTypeSelect.addEventListener('change', toggleIntegrationSettings);
                toggleIntegrationSettings(); // Initialize
            });

            function testIntegration(type) {
                const resultElement = document.getElementById(type + '-test-result');
                resultElement.innerHTML = '<span style="color: #666;">Testing connection...</span>';

                const formData = new FormData();
                formData.append('action', 'test_premium_integration');
                formData.append('integration_type', type);
                formData.append('nonce', '<?php echo wp_create_nonce('test_premium_integration'); ?>');

                fetch(ajaxurl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        resultElement.innerHTML = '<span class="test-success">✓ ' + data.data.message + '</span>';
                    } else {
                        resultElement.innerHTML = '<span class="test-error">✗ ' + data.data.message + '</span>';
                    }
                })
                .catch(error => {
                    resultElement.innerHTML = '<span class="test-error">✗ Connection test failed</span>';
                });
            }
        </script>
        <?php
    }

    /**
     * Settings page callback function.
     */
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['reset_colors']) && wp_verify_nonce($_POST['premium_content_reset_nonce'], 'premium_content_reset')) {
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
                update_option('premium_content_' . $key, $value);
            }

            echo '<div class="notice notice-success"><p>Colors reset to defaults successfully!</p></div>';
        }

        if (isset($_POST['submit']) && wp_verify_nonce($_POST['premium_content_nonce'], 'premium_content_settings')) {
            // Save colors
            $colors = array( 'primary_color', 'secondary_color', 'border_color', 'text_color', 'title_color', 'link_color', 'background_color' );
            foreach ($colors as $color) {
                if (isset($_POST[$color])) {
                    update_option('premium_content_' . $color, sanitize_hex_color($_POST[$color]));
                }
            }

            // Save mode settings
            $enable_all_posts = isset($_POST['enable_all_posts']) ? '1' : '0';
            update_option('premium_content_enable_all_posts', $enable_all_posts);

            // Save text settings
            $text_fields = array(
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

            foreach ($text_fields as $field => $default) {
                if (isset($_POST[$field])) {
                    update_option('premium_content_' . $field, wp_kses_post($_POST[$field]));
                }
            }

            echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
        }

        // Get current values
        $primary_color = $this->get_premium_content_color('primary_color', '#2c3e50');
        $secondary_color = $this->get_premium_content_color('secondary_color', '#667eea');
        $border_color = $this->get_premium_content_color('border_color', '#e1e5e9');
        $text_color = $this->get_premium_content_color('text_color', '#666');
        $title_color = $this->get_premium_content_color('title_color', '#2c3e50');
        $link_color = $this->get_premium_content_color('link_color', '#667eea');
        $background_color = $this->get_premium_content_color('background_color', '#ffffff');
        
        $enable_all_posts = get_option('premium_content_enable_all_posts', '0');
        $main_title = $this->get_premium_content_text('main_title', 'Continue Reading This Article');
        $subtitle = $this->get_premium_content_text('subtitle', 'Enjoy this article as well as all of our content, including E-Guides, news, tips and more.');
        $email_placeholder = $this->get_premium_content_text('email_placeholder', 'Corporate Email Address');
        $button_text = $this->get_premium_content_text('button_text', 'Continue Reading');
        $checkbox1_text = $this->get_premium_content_text('checkbox1_text', 'I agree to [site_name] and its group companies processing my personal information to provide information relevant to my professional interests via phone, email, and similar methods. My profile may be enhanced with additional professional details.');
        $checkbox2_text = $this->get_premium_content_text('checkbox2_text', 'I agree to [site_name]\'s <a href="[terms_of_use_link]" target="_blank">Partners</a> processing my personal information for direct marketing, including contact via phone, email, and similar methods regarding information relevant to my professional interests.');
        $disclaimer_text = $this->get_premium_content_text('disclaimer_text', 'By registering or signing into your [site_name] account, you agree to [site_name]\'s <a href="[terms_of_use_link]" target="_blank">Terms of Use</a> and consent to the processing of your personal information as described in our <a href="[privacy_policy_link]" target="_blank">Privacy Policy</a>. By submitting this form, you acknowledge that your personal information will be transferred to [site_name]\'s servers in the United States. California residents, please refer to our <a href="[ccpa_privacy_notice_link]" target="_blank">CCPA Privacy Notice</a>.');
        $terms_of_use_url = $this->get_premium_content_text('terms_of_use_url', '#');
        $ccpa_privacy_notice_url = $this->get_premium_content_text('ccpa_privacy_notice_url', '#');

        // HTML for settings page
        ?>
        <div class="wrap">
            <h1>Premium Content Settings</h1>
            <p>Customize the colors, text, and behavior of your premium content gate.</p>
            
            <form method="post" action="">
                <?php wp_nonce_field('premium_content_settings', 'premium_content_nonce'); ?>
                
                <!-- Mode Settings -->
                <h2>Mode Settings</h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="enable_all_posts">Enable for All Posts</label></th>
                            <td>
                                <input type="checkbox" id="enable_all_posts" name="enable_all_posts" value="1" <?php checked($enable_all_posts, '1'); ?> />
                                <p class="description">When enabled, the paywall will appear on ALL posts (old and new) by default, not just those tagged with "premium". Posts tagged with "premium" will still work as before.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Text Settings -->
                <h2>Text Settings</h2>
                <p><strong>Available placeholders:</strong> [site_name], [privacy_policy_link], [terms_of_use_link], [ccpa_privacy_notice_link]</p>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="main_title">Main Title</label></th>
                            <td>
                                <input type="text" id="main_title" name="main_title" value="<?php echo esc_attr($main_title); ?>" class="regular-text" />
                                <p class="description">The main heading text above the form.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="subtitle">Subtitle</label></th>
                            <td>
                                <textarea id="subtitle" name="subtitle" rows="2" class="large-text"><?php echo esc_textarea($subtitle); ?></textarea>
                                <p class="description">The descriptive text below the main title.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="email_placeholder">Email Placeholder</label></th>
                            <td>
                                <input type="text" id="email_placeholder" name="email_placeholder" value="<?php echo esc_attr($email_placeholder); ?>" class="regular-text" />
                                <p class="description">Placeholder text for the email input field.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="button_text">Button Text</label></th>
                            <td>
                                <input type="text" id="button_text" name="button_text" value="<?php echo esc_attr($button_text); ?>" class="regular-text" />
                                <p class="description">Text displayed on the submit button.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="checkbox1_text">First Checkbox Text</label></th>
                            <td>
                                <textarea id="checkbox1_text" name="checkbox1_text" rows="3" class="large-text"><?php echo esc_textarea($checkbox1_text); ?></textarea>
                                <p class="description">Text for the first consent checkbox.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="checkbox2_text">Second Checkbox Text</label></th>
                            <td>
                                <textarea id="checkbox2_text" name="checkbox2_text" rows="3" class="large-text"><?php echo esc_textarea($checkbox2_text); ?></textarea>
                                <p class="description">Text for the second consent checkbox. HTML allowed.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="disclaimer_text">Disclaimer Text</label></th>
                            <td>
                                <textarea id="disclaimer_text" name="disclaimer_text" rows="4" class="large-text"><?php echo esc_textarea($disclaimer_text); ?></textarea>
                                <p class="description">Legal disclaimer text at the bottom. HTML allowed.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="terms_of_use_url">Terms of Use URL</label></th>
                            <td>
                                <input type="url" id="terms_of_use_url" name="terms_of_use_url" value="<?php echo esc_attr($terms_of_use_url); ?>" class="regular-text" />
                                <p class="description">URL for [terms_of_use_link] placeholder.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ccpa_privacy_notice_url">CCPA Privacy Notice URL</label></th>
                            <td>
                                <input type="url" id="ccpa_privacy_notice_url" name="ccpa_privacy_notice_url" value="<?php echo esc_attr($ccpa_privacy_notice_url); ?>" class="regular-text" />
                                <p class="description">URL for [ccpa_privacy_notice_link] placeholder.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Color Settings -->
                <h2>Color Settings</h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="primary_color">Primary Color</label></th>
                            <td>
                                <input type="color" id="primary_color" name="primary_color" value="<?php echo esc_attr($primary_color); ?>" class="color-picker" />
                                <p class="description">Used for the main button background and primary elements.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="secondary_color">Secondary Color</label></th>
                            <td>
                                <input type="color" id="secondary_color" name="secondary_color" value="<?php echo esc_attr($secondary_color); ?>" class="color-picker" />
                                <p class="description">Used for button hover effects and active states.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="border_color">Border Color</label></th>
                            <td>
                                <input type="color" id="border_color" name="border_color" value="<?php echo esc_attr($border_color); ?>" class="color-picker" />
                                <p class="description">Used for form borders and container outlines.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="text_color">Text Color</label></th>
                            <td>
                                <input type="color" id="text_color" name="text_color" value="<?php echo esc_attr($text_color); ?>" class="color-picker" />
                                <p class="description">Used for body text and descriptions.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="title_color">Title Color</label></th>
                            <td>
                                <input type="color" id="title_color" name="title_color" value="<?php echo esc_attr($title_color); ?>" class="color-picker" />
                                <p class="description">Used for the main title.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="link_color">Link Color</label></th>
                            <td>
                                <input type="color" id="link_color" name="link_color" value="<?php echo esc_attr($link_color); ?>" class="color-picker" />
                                <p class="description">Used for all links in the form.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="background_color">Background Color</label></th>
                            <td>
                                <input type="color" id="background_color" name="background_color" value="<?php echo esc_attr($background_color); ?>" class="color-picker" />
                                <p class="description">Used for the form container background.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="color-preview-section">
                    <h3>Preview</h3>
                    <div class="premium-content-preview" style="
                        background: <?php echo esc_attr($background_color); ?>;
                        border: 2px solid <?php echo esc_attr($border_color); ?>;
                        border-radius: 10px;
                        padding: 20px;
                        max-width: 500px;
                        margin: 20px 0;
                    ">
                        <h4 style="color: <?php echo esc_attr($title_color); ?>; margin: 0 0 10px 0;"><?php echo esc_html($main_title); ?></h4>
                        <p style="color: <?php echo esc_attr($text_color); ?>; margin: 0 0 15px 0; font-size: 14px;"><?php echo esc_html($subtitle); ?></p>
                        <input type="text" placeholder="<?php echo esc_attr($email_placeholder); ?>" style="
                            width: 100%; 
                            padding: 8px 5px; 
                            border: none; 
                            border-bottom: 2px solid <?php echo esc_attr($border_color); ?>;
                            background: transparent;
                            margin-bottom: 15px;
                        " readonly />
                        <button type="button" style="
                            background: <?php echo esc_attr($primary_color); ?>;
                            color: white;
                            padding: 8px 20px;
                            border: none;
                            width: 100%;
                            cursor: not-allowed;
                        "><?php echo esc_html($button_text); ?></button>
                        <p style="color: <?php echo esc_attr($text_color); ?>; font-size: 12px; margin: 10px 0 0 0;">
                            Links will appear in <span style="color: <?php echo esc_attr($link_color); ?>;">this color</span>.
                        </p>
                    </div>
                </div>
                
                <?php submit_button('Save Settings'); ?>
            </form>
            
            <div class="reset-section" style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd;">
                <h3>Reset to Defaults</h3>
                <p>Click the button below to reset all colors to their default values.</p>
                <form method="post" action="" onsubmit="return confirm('Are you sure you want to reset all colors to defaults?');">
                    <?php wp_nonce_field('premium_content_reset', 'premium_content_reset_nonce'); ?>
                    <input type="hidden" name="reset_colors" value="1" />
                    <?php submit_button('Reset to Defaults', 'secondary'); ?>
                </form>
            </div>
        </div>

        <style>
            .color-picker { width: 100px; height: 40px; border: 1px solid #ddd; cursor: pointer; }
            .form-table th { width: 200px; }
            .color-preview-section { margin: 30px 0; }
            .reset-section { background: #f9f9f9; padding: 20px; border-radius: 5px; }
        </style>
        <?php
    }

    /**
     * Premium emails page callback function.
     */
    public function emails_page() {
        global $wpdb;

        if (!current_user_can('manage_options')) {
            return;
        }

        $table_name = $wpdb->prefix . 'smart_mag_premium_emails';
        $emails = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC" );
        ?>
        <div class="wrap">
            <h1>Premium Emails</h1>
            <p>This is a list of all emails collected from premium articles.</p>
            
            <?php if ( $emails ) : ?>
                <div class="email-stats" style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0;">
                    <h3>Statistics</h3>
                    <p><strong>Total Emails Collected:</strong> <?php echo count($emails); ?></p>
                    <p><strong>Latest Submission:</strong> <?php echo esc_html($emails[0]->created_at); ?></p>
                    <?php
                    $integration_enabled = get_option('premium_content_integration_enabled', '0');
                    $integration_type = get_option('premium_content_integration_type', 'none');
                    if ($integration_enabled === '1' && $integration_type !== 'none') {
                        echo '<p><strong>Integration:</strong> ' . ucfirst($integration_type) . ' (Enabled)</p>';
                    } else {
                        echo '<p><strong>Integration:</strong> Disabled - <a href="' . admin_url('admin.php?page=premium-integrations') . '">Configure Integration</a></p>';
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="tablenav top">
                <div class="alignleft actions">
                    <form method="get" action="">
                        <input type="hidden" name="page" value="premium-emails" />
                        <input type="text" name="search" placeholder="Search emails..." value="<?php echo isset($_GET['search']) ? esc_attr($_GET['search']) : ''; ?>" />
                        <input type="submit" class="button" value="Search" />
                        <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                            <a href="<?php echo admin_url('admin.php?page=premium-emails'); ?>" class="button">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
                <div class="alignright actions">
                    <a href="<?php echo admin_url('admin.php?page=premium-emails&export=csv'); ?>" class="button button-secondary">Export CSV</a>
                </div>
            </div>
            
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th style="width: 30%;">Email Address</th>
                        <th style="width: 40%;">Post Title</th>
                        <th style="width: 20%;">Date Submitted</th>
                        <th style="width: 10%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $emails ) : ?>
                        <?php 
                        $filtered_emails = $emails;
                        if (isset($_GET['search']) && !empty($_GET['search'])) {
                            $search = strtolower($_GET['search']);
                            $filtered_emails = array_filter($emails, function($email) use ($search) {
                                return strpos(strtolower($email->email), $search) !== false ||
                                       strpos(strtolower(get_the_title($email->post_id)), $search) !== false;
                            });
                        }
                        ?>
                        
                        <?php if (!empty($filtered_emails)): ?>
                            <?php foreach ( $filtered_emails as $email ) : 
                                $post_title = get_the_title($email->post_id);
                                $post_url = get_permalink($email->post_id);
                            ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($email->email); ?></strong>
                                        <div class="email-domain" style="color: #666; font-size: 12px;">
                                            Domain: <?php echo esc_html(substr(strrchr($email->email, "@"), 1)); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url($post_url); ?>" target="_blank" title="View Post">
                                            <?php echo esc_html($post_title ? $post_title : 'Post #' . $email->post_id); ?>
                                        </a>
                                        <div class="post-id" style="color: #666; font-size: 12px;">
                                            ID: <?php echo esc_html($email->post_id); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                        $date = new DateTime($email->created_at);
                                        echo $date->format('M j, Y \a\t g:i A'); 
                                        ?>
                                        <div style="color: #666; font-size: 12px;">
                                            <?php echo human_time_diff(strtotime($email->created_at), current_time('timestamp')) . ' ago'; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url($post_url); ?>" class="button button-small" target="_blank">View</a>
                                        <button class="button button-small button-link-delete" onclick="deleteEmail(<?php echo $email->id; ?>)" title="Delete Email">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">No emails found matching your search criteria.</td>
                            </tr>
                        <?php endif; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4">No emails have been collected yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <script>
            function deleteEmail(emailId) {
                if (confirm('Are you sure you want to delete this email?')) {
                    var formData = new FormData();
                    formData.append('action', 'delete_premium_email');
                    formData.append('email_id', emailId);
                    formData.append('nonce', '<?php echo wp_create_nonce('delete_premium_email'); ?>');

                    fetch(ajaxurl, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error deleting email: ' + data.data);
                        }
                    })
                    .catch(error => {
                        alert('Error deleting email');
                    });
                }
            }
        </script>
        <?php
    }
    
    /**
     * Handle CSV export.
     */
    public function handle_emails_export() {
        if (isset($_GET['page']) && $_GET['page'] === 'premium-emails' && isset($_GET['export']) && $_GET['export'] === 'csv') {
            if (!current_user_can('manage_options')) {
                wp_die('Permission denied');
            }

            global $wpdb;
            $table_name = $wpdb->prefix . 'smart_mag_premium_emails';
            $emails = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="premium-emails-' . date('Y-m-d') . '.csv"');
            header('Pragma: no-cache');
            header('Expires: 0');

            $output = fopen('php://output', 'w');
            fputcsv($output, array('Email Address', 'Post Title', 'Post ID', 'Date Submitted'));

            foreach ($emails as $email) {
                $post_title = get_the_title($email->post_id);
                fputcsv($output, array(
                    $email->email,
                    $post_title ? $post_title : 'Post #' . $email->post_id,
                    $email->post_id,
                    $email->created_at
                ));
            }

            fclose($output);
            exit;
        }
    }
}