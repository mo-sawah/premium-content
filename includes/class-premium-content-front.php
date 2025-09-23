<?php
/**
 * Handles all frontend operations for the Premium Content plugin.
 */
class Premium_Content_Front {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        add_filter( 'the_content', array( $this, 'filter_the_content' ) );
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
     * Enqueue styles for the premium content gate with customizable colors.
     */
    public function enqueue_styles() {
        $primary_color = $this->get_premium_content_color('primary_color', '#2c3e50');
        $secondary_color = $this->get_premium_content_color('secondary_color', '#667eea');
        $border_color = $this->get_premium_content_color('border_color', '#e1e5e9');
        $text_color = $this->get_premium_content_color('text_color', '#666');
        $title_color = $this->get_premium_content_color('title_color', '#2c3e50');
        $link_color = $this->get_premium_content_color('link_color', '#667eea');
        $background_color = $this->get_premium_content_color('background_color', '#ffffff');

        $custom_css = "
        .premium-content-gate {
            width: 100%;
            margin: 40px auto;
            background: {$background_color};
            border: 2px solid {$border_color};
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            box-sizing: border-box;
        }

        .premium-content-form-wrapper {
            font-family: inherit;
            line-height: 1.6;
        }

        .premium-content-title {
            color: {$title_color};
            margin-bottom: 15px;
            font-size: 1.8em;
            font-weight: 700;
            margin-top: 5px !important;
        }
    
        .premium-content-subtitle {
            color: {$text_color};
            margin-bottom: 30px;
            font-size: 1.1em;
        }

        .premium-content-form {
            margin-bottom: 25px;
        }

        .premium-content-email-input {
            width: 100%;
            padding: 15px 5px;
            border: none;
            border-bottom: 2px solid {$border_color};
            border-radius: 0;
            background: transparent;
            font-size: 16px;
            transition: all 0.3s ease;
            box-sizing: border-box;
            margin-bottom: 25px;
            font-family: inherit;
        }

        .premium-content-email-input:focus {
            outline: none;
            border-bottom-color: {$secondary_color};
        }

        .premium-content-email-input::placeholder {
            color: #999;
        }

        .premium-content-checkbox-group {
            margin-bottom: 20px;
        }

        .premium-content-checkbox-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .premium-content-custom-checkbox {
            width: 20px;
            height: 20px;
            border: 2px solid #ddd;
            border-radius: 4px;
            margin-right: 12px;
            margin-top: 2px;
            cursor: pointer;
            position: relative;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }

        .premium-content-custom-checkbox.checked {
            background: {$secondary_color};
            border-color: {$secondary_color};
        }

        .premium-content-custom-checkbox.checked::after {
            content: '✓';
            position: absolute;
            color: white;
            font-size: 14px;
            top: -2px;
            left: 3px;
        }

        .premium-content-checkbox-text {
            font-size: 14px;
            color: {$text_color};
            cursor: pointer;
        }

        .premium-content-checkbox-text a {
            color: {$link_color};
            text-decoration: none;
        }

        .premium-content-checkbox-text a:hover {
            text-decoration: underline;
        }

        .premium-content-title h2 {
            margin-top: 5px !important;
        }
    
        .premium-content-submit-button {
            background: {$primary_color} !important;
            color: white !important;
            padding: 10px 40px !important;
            border: none !important;
            border-radius: 0 !important;
            font-size: 18px !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
            transition: all 0.3s ease !important;
            position: relative !important;
            overflow: hidden !important;
            font-family: inherit !important;
            text-transform: none !important;
            letter-spacing: normal !important;
            display: block !important;
            margin: 0 !important;
            outline: none !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            appearance: none !important;
            height: 56px !important;
        }

        .premium-content-submit-button::before {
            content: '' !important;
            position: absolute !important;
            top: 0 !important;
            left: -100% !important;
            width: 100% !important;
            height: 100% !important;
            background: {$secondary_color} !important;
            transition: left 0.3s ease !important;
            z-index: 1 !important;
        }

        .premium-content-submit-button:hover::before {
            left: 0 !important;
        }

        .premium-content-submit-button span {
            position: relative !important;
            z-index: 2 !important;
            display: block !important;
        }

        .premium-content-disclaimer {
            font-size: 13px;
            color: #888;
            margin-top: 20px;
            line-height: 1.5;
        }
    
        .premium-content-disclaimer a {
            color: {$link_color};
            text-decoration: none;
        }
    
        .premium-content-disclaimer a:hover {
            text-decoration: underline;
        }

        /* Contact Form 7 Specific Styles */
        .premium-hidden-checkbox {
            display: none !important;
        }

        /* Hide CF7 default styling for our custom elements */
        .wpcf7 .premium-content-email-input {
            border: none;
            border-bottom: 2px solid {$border_color};
            border-radius: 0;
            background: transparent;
            padding: 15px 5px;
        }

        .wpcf7 .premium-content-submit-button {
            background: {$primary_color} !important;
            border: none !important;
            padding: 10px 40px !important;
            height: 56px !important;
        }

        .wpcf7 .premium-content-checkbox-group {
            margin-bottom: 20px;
        }

        /* Hide/show checkboxes based on settings */
        .premium-checkbox1-wrapper.disabled,
        .premium-checkbox2-wrapper.disabled {
            display: none !important;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .premium-content-gate {
                padding: 30px 20px;
            }
            
            .premium-content-title {
                font-size: 1.5em;
            }
            
            .premium-content-email-input {
                padding: 12px 5px;
            }
        }
        ";
        wp_add_inline_style( 'wp-block-library', $custom_css );
    }

    /**
     * Helper function to replace placeholders in the content gate text.
     */
    private function replace_placeholders($text) {
        $site_name = get_bloginfo('name');
        $text = str_replace('[site_name]', $site_name, $text);

        $privacy_policy_url = get_privacy_policy_url();
        $text = str_replace('[privacy_policy_link]', esc_url($privacy_policy_url), $text);

        $terms_of_use_url = $this->get_premium_content_text('terms_of_use_url', '#');
        $text = str_replace('[terms_of_use_link]', esc_url($terms_of_use_url), $text);

        $ccpa_privacy_notice_url = $this->get_premium_content_text('ccpa_privacy_notice_url', '#');
        $text = str_replace('[ccpa_privacy_notice_link]', esc_url($ccpa_privacy_notice_url), $text);

        return $text;
    }

    /**
     * Check if post should display premium content gate
     */
    private function should_show_premium_gate() {
        $enable_all_posts = get_option('premium_content_enable_all_posts', '0');
        
        // If "enable all posts" is checked, show on all posts
        if ($enable_all_posts === '1' && is_single()) {
            return true;
        }
        
        // Otherwise, only show on posts tagged with "premium" (original behavior)
        return is_main_query() && has_tag('premium');
    }

    /**
     * Generate the appropriate form based on the selected mode
     */
    private function generate_form_html($post_id, $main_title, $subtitle, $disclaimer_text) {
        $form_mode = get_option('premium_content_form_mode', 'native');
        
        if ($form_mode === 'cf7') {
            return $this->generate_cf7_form($post_id, $main_title, $subtitle, $disclaimer_text);
        } else {
            return $this->generate_native_form($post_id, $main_title, $subtitle, $disclaimer_text);
        }
    }

    /**
     * Generate Contact Form 7 form
     */
    private function generate_cf7_form($post_id, $main_title, $subtitle, $disclaimer_text) {
        $cf7_form_id = get_option('premium_content_cf7_form_id', '');
        
        if (empty($cf7_form_id)) {
            return '<p style="color: red; background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">Contact Form 7 ID is not configured. Please check your settings.</p>';
        }

        if (!function_exists('wpcf7_contact_form')) {
            return '<p style="color: red; background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">Contact Form 7 plugin is not active. Please install and activate Contact Form 7.</p>';
        }

        // Check if form exists
        $contact_form = wpcf7_contact_form($cf7_form_id);
        if (!$contact_form || !$contact_form->id()) {
            return '<p style="color: red; background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">Contact Form 7 form with ID ' . esc_html($cf7_form_id) . ' not found. Please check your form ID in the settings.</p>';
        }

        $enable_checkbox1 = get_option('premium_content_enable_checkbox1', '1');
        $enable_checkbox2 = get_option('premium_content_enable_checkbox2', '1');
        $checkbox1_text = $this->get_premium_content_text('checkbox1_text', 'I agree to [site_name] and its group companies processing my personal information to provide information relevant to my professional interests via phone, email, and similar methods. My profile may be enhanced with additional professional details.');
        $checkbox2_text = $this->get_premium_content_text('checkbox2_text', 'I agree to [site_name]\'s <a href="[terms_of_use_link]" target="_blank">Partners</a> processing my personal information for direct marketing, including contact via phone, email, and similar methods regarding information relevant to my professional interests.');
        
        $form_html = '
            <div id="premium-content-gate" class="premium-content-gate">
                <div class="premium-content-form-wrapper">
                    <h2 class="premium-content-title">' . esc_html($main_title) . '</h2>
                    <p class="premium-content-subtitle">' . esc_html($subtitle) . '</p>
                    ' . do_shortcode('[contact-form-7 id="' . intval($cf7_form_id) . '"]') . '
                    <p class="premium-content-disclaimer">' . wp_kses_post($this->replace_placeholders($disclaimer_text)) . '</p>
                </div>
            </div>
        ';

        // Add JavaScript for CF7 form handling
        $script_html = '
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    var premiumGate = document.getElementById("premium-content-gate");
                    var truncatedContent = document.getElementById("truncated-content");
                    var fullContent = document.getElementById("full-content");
                    
                    if (!premiumGate) return;
                    
                    // Set post ID for the hidden field
                    var postIdField = premiumGate.querySelector(\'input[name="post_id"]\');
                    if (postIdField) {
                        postIdField.value = ' . $post_id . ';
                    }

                    // Handle checkbox visibility based on settings
                    var checkbox1Enabled = ' . ($enable_checkbox1 === '1' ? 'true' : 'false') . ';
                    var checkbox2Enabled = ' . ($enable_checkbox2 === '1' ? 'true' : 'false') . ';
                    
                    var checkbox1Wrapper = premiumGate.querySelector(".premium-checkbox1-wrapper");
                    var checkbox2Wrapper = premiumGate.querySelector(".premium-checkbox2-wrapper");
                    
                    if (!checkbox1Enabled && checkbox1Wrapper) {
                        checkbox1Wrapper.classList.add("disabled");
                    }
                    if (!checkbox2Enabled && checkbox2Wrapper) {
                        checkbox2Wrapper.classList.add("disabled");
                    }

                    // Replace placeholder text
                    var checkboxTexts = premiumGate.querySelectorAll(".premium-content-checkbox-text");
                    checkboxTexts.forEach(function(element, index) {
                        var text = element.innerHTML;
                        if (index === 0 && checkbox1Enabled) {
                            text = ' . json_encode(wp_kses_post($this->replace_placeholders($checkbox1_text))) . ';
                        } else if (index === 1 && checkbox2Enabled) {
                            text = ' . json_encode(wp_kses_post($this->replace_placeholders($checkbox2_text))) . ';
                        } else if (!checkbox1Enabled && index === 0 && checkbox2Enabled) {
                            text = ' . json_encode(wp_kses_post($this->replace_placeholders($checkbox2_text))) . ';
                        }
                        element.innerHTML = text;
                    });

                    // Custom checkbox handling
                    function setupCustomCheckboxes() {
                        var customCheckboxes = premiumGate.querySelectorAll(".premium-content-custom-checkbox");
                        customCheckboxes.forEach(function(customBox) {
                            var target = customBox.getAttribute("data-target");
                            
                            customBox.addEventListener("click", function() {
                                customBox.classList.toggle("checked");
                                
                                // Try to find the hidden checkbox (it might be created dynamically by CF7)
                                var hiddenCheckbox = premiumGate.querySelector(\'input[name="\' + target + \'"]\');
                                if (hiddenCheckbox) {
                                    hiddenCheckbox.checked = customBox.classList.contains("checked");
                                    if (customBox.classList.contains("checked")) {
                                        hiddenCheckbox.value = "1";
                                    } else {
                                        hiddenCheckbox.value = "";
                                    }
                                } else {
                                    // If hidden checkbox doesn\'t exist yet, create it temporarily for form submission
                                    var tempInput = document.createElement("input");
                                    tempInput.type = "hidden";
                                    tempInput.name = target;
                                    tempInput.value = customBox.classList.contains("checked") ? "1" : "";
                                    customBox.parentNode.appendChild(tempInput);
                                }
                            });
                        });
                    }
                    
                    setupCustomCheckboxes();

                    // Handle CF7 form submission success
                    document.addEventListener("wpcf7mailsent", function(event) {
                        if (event.detail.contactFormId == ' . intval($cf7_form_id) . ') {
                            if (truncatedContent) truncatedContent.style.display = "none";
                            if (premiumGate) premiumGate.style.display = "none";
                            if (fullContent) fullContent.style.display = "block";
                        }
                    });
                });
            </script>
        ';

        return $form_html . $script_html;
    }

    /**
     * Generate native form (existing functionality)
     */
    private function generate_native_form($post_id, $main_title, $subtitle, $disclaimer_text) {
        $nonce = wp_create_nonce( 'premium_email_nonce' );
        $email_placeholder = $this->get_premium_content_text('email_placeholder', 'Corporate Email Address');
        $button_text = $this->get_premium_content_text('button_text', 'Continue Reading');
        $checkbox1_text = $this->get_premium_content_text('checkbox1_text', 'I agree to [site_name] and its group companies processing my personal information to provide information relevant to my professional interests via phone, email, and similar methods. My profile may be enhanced with additional professional details.');
        $checkbox2_text = $this->get_premium_content_text('checkbox2_text', 'I agree to [site_name]\'s <a href="[terms_of_use_link]" target="_blank">Partners</a> processing my personal information for direct marketing, including contact via phone, email, and similar methods regarding information relevant to my professional interests.');

        // Check individual checkbox settings
        $enable_checkbox1 = get_option('premium_content_enable_checkbox1', '1');
        $enable_checkbox2 = get_option('premium_content_enable_checkbox2', '1');
        
        // Generate checkbox HTML based on individual settings
        $checkbox_html = '';
        
        if ($enable_checkbox1 === '1' || $enable_checkbox2 === '1') {
            $checkbox_html .= '<div class="premium-content-checkbox-group">';
            
            // First checkbox
            if ($enable_checkbox1 === '1') {
                $checkbox_html .= '
                    <div class="premium-content-checkbox-item">
                        <div class="premium-content-custom-checkbox" onclick="togglePremiumCheckbox(this, \'checkbox1\')"></div>
                        <input type="hidden" name="checkbox1" value="">
                        <div class="premium-content-checkbox-text">' . wp_kses_post($this->replace_placeholders($checkbox1_text)) . '</div>
                    </div>';
            }
            
            // Second checkbox
            if ($enable_checkbox2 === '1') {
                $checkbox_html .= '
                    <div class="premium-content-checkbox-item">
                        <div class="premium-content-custom-checkbox" onclick="togglePremiumCheckbox(this, \'checkbox2\')"></div>
                        <input type="hidden" name="checkbox2" value="">
                        <div class="premium-content-checkbox-text">' . wp_kses_post($this->replace_placeholders($checkbox2_text)) . '</div>
                    </div>';
            }
            
            $checkbox_html .= '</div>';
        }

        $form_html = '
            <div id="premium-content-gate" class="premium-content-gate">
                <div class="premium-content-form-wrapper">
                    <h2 class="premium-content-title">' . esc_html($main_title) . '</h2>
                    <p class="premium-content-subtitle">' . esc_html($subtitle) . '</p>
                    <form id="premium-content-form" class="premium-content-form">
                        <input type="email" name="premium_email" placeholder="' . esc_attr($email_placeholder) . '" required class="premium-content-email-input">
                        
                        ' . $checkbox_html . '
                        
                        <input type="hidden" name="premium_nonce" value="' . esc_attr( $nonce ) . '">
                        <input type="hidden" name="post_id" value="' . esc_attr( $post_id ) . '">
                        <input type="hidden" name="checkbox1_enabled" value="' . esc_attr($enable_checkbox1) . '">
                        <input type="hidden" name="checkbox2_enabled" value="' . esc_attr($enable_checkbox2) . '">
                        <button type="submit" class="premium-content-submit-button"><span>' . esc_html($button_text) . '</span></button>
                    </form>
                    <p class="premium-content-disclaimer">' . wp_kses_post($this->replace_placeholders($disclaimer_text)) . '</p>
                </div>
            </div>
        ';

        // Native form JavaScript
        $script_html = '
            <script>
                function togglePremiumCheckbox(checkbox, inputName) {
                    checkbox.classList.toggle("checked");
                    const hiddenInput = checkbox.parentNode.querySelector(\'input[name="\' + inputName + \'"]\');
                    if (checkbox.classList.contains("checked")) {
                        hiddenInput.value = "1";
                    } else {
                        hiddenInput.value = "";
                    }
                }

                document.addEventListener("DOMContentLoaded", function() {
                    var form = document.getElementById("premium-content-form");
                    var contentGate = document.getElementById("premium-content-gate");
                    var truncatedContent = document.getElementById("truncated-content");
                    var fullContent = document.getElementById("full-content");

                    if (form) {
                        form.addEventListener("submit", function(e) {
                            e.preventDefault();

                            var checkbox1Enabled = form.querySelector(\'input[name="checkbox1_enabled"]\').value;
                            var checkbox2Enabled = form.querySelector(\'input[name="checkbox2_enabled"]\').value;
                            
                            // Validate only enabled checkboxes
                            var validationErrors = [];
                            
                            if (checkbox1Enabled === "1") {
                                var checkbox1 = form.querySelector(\'input[name="checkbox1"]\').value;
                                if (!checkbox1) {
                                    validationErrors.push("You must agree to the first consent requirement.");
                                }
                            }
                            
                            if (checkbox2Enabled === "1") {
                                var checkbox2 = form.querySelector(\'input[name="checkbox2"]\').value;
                                if (!checkbox2) {
                                    validationErrors.push("You must agree to the second consent requirement.");
                                }
                            }
                            
                            if (validationErrors.length > 0) {
                                alert(validationErrors.join("\\n"));
                                return;
                            }

                            var formData = new FormData(form);
                            formData.append("action", "smart_mag_premium_content");

                            fetch("' . esc_url( admin_url( 'admin-ajax.php' ) ) . '", {
                                method: "POST",
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    truncatedContent.style.display = "none";
                                    contentGate.style.display = "none";
                                    fullContent.style.display = "block";
                                } else {
                                    alert(data.data || "An error occurred. Please try again.");
                                }
                            })
                            .catch(error => {
                                console.error("Error:", error);
                                alert("An error occurred. Please try again.");
                            });
                        });
                    }
                });
            </script>
        ';

        return $form_html . $script_html;
    }

    /**
     * Filter the post content to truncate premium articles.
     */
    public function filter_the_content( $content ) {
        if ( $this->should_show_premium_gate() ) {
            // Check for global unlock cookie
            if ( isset($_COOKIE['premium_content_global_unlock']) && $_COOKIE['premium_content_global_unlock'] == 'unlocked' ) {
                return $content;
            }

            $post_id = get_the_ID();
            $truncate_length = 500;
            $truncated_content = substr( $content, 0, $truncate_length );

            // Get customizable text content
            $main_title = $this->get_premium_content_text('main_title', 'Continue Reading This Article');
            $subtitle = $this->get_premium_content_text('subtitle', 'Enjoy this article as well as all of our content, including E-Guides, news, tips and more.');
            $disclaimer_text = $this->get_premium_content_text('disclaimer_text', 'By registering or signing into your [site_name] account, you agree to [site_name]\'s <a href="[terms_of_use_link]" target="_blank">Terms of Use</a> and consent to the processing of your personal information as described in our <a href="[privacy_policy_link]" target="_blank">Privacy Policy</a>. By submitting this form, you acknowledge that your personal information will be transferred to [site_name]\'s servers in the United States. California residents, please refer to our <a href="[ccpa_privacy_notice_link]" target="_blank">CCPA Privacy Notice</a>.');

            // Generate form based on selected mode
            $form_html = $this->generate_form_html($post_id, $main_title, $subtitle, $disclaimer_text);

            return '
                <div id="truncated-content">
                    ' . $truncated_content . '...
                </div>'
                . $form_html . '
                <div id="full-content" style="display:none;">
                    ' . substr($content, $truncate_length) . '
                </div>';
        }

        return $content;
    }
}