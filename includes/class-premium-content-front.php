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
            content: 'âœ"';
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
     * Filter the post content to truncate premium articles.
     */
    public function filter_the_content( $content ) {
        if ( $this->should_show_premium_gate() ) {
            $post_id = get_the_ID();
            $cookie_name = 'premium_content_' . $post_id;
            
            if ( isset($_COOKIE[$cookie_name]) && $_COOKIE[$cookie_name] == 'unlocked' ) {
                return $content;
            }

            $truncate_length = 500;
            $truncated_content = substr( $content, 0, $truncate_length );
            $nonce = wp_create_nonce( 'premium_email_nonce' );

            // Get customizable text content
            $main_title = $this->get_premium_content_text('main_title', 'Continue Reading This Article');
            $subtitle = $this->get_premium_content_text('subtitle', 'Enjoy this article as well as all of our content, including E-Guides, news, tips and more.');
            $email_placeholder = $this->get_premium_content_text('email_placeholder', 'Corporate Email Address');
            $button_text = $this->get_premium_content_text('button_text', 'Continue Reading');
            $checkbox1_text = $this->get_premium_content_text('checkbox1_text', 'I agree to [site_name] and its group companies processing my personal information to provide information relevant to my professional interests via phone, email, and similar methods. My profile may be enhanced with additional professional details.');
            $checkbox2_text = $this->get_premium_content_text('checkbox2_text', 'I agree to [site_name]\'s <a href="[terms_of_use_link]" target="_blank">Partners</a> processing my personal information for direct marketing, including contact via phone, email, and similar methods regarding information relevant to my professional interests.');
            $disclaimer_text = $this->get_premium_content_text('disclaimer_text', 'By registering or signing into your [site_name] account, you agree to [site_name]\'s <a href="[terms_of_use_link]" target="_blank">Terms of Use</a> and consent to the processing of your personal information as described in our <a href="[privacy_policy_link]" target="_blank">Privacy Policy</a>. By submitting this form, you acknowledge that your personal information will be transferred to [site_name]\'s servers in the United States. California residents, please refer to our <a href="[ccpa_privacy_notice_link]" target="_blank">CCPA Privacy Notice</a>.');

            $form_html = '
                <div id="premium-content-gate" class="premium-content-gate">
                    <div class="premium-content-form-wrapper">
                        <h2 class="premium-content-title">' . esc_html($main_title) . '</h2>
                        <p class="premium-content-subtitle">' . esc_html($subtitle) . '</p>
                        <form id="premium-content-form" class="premium-content-form">
                            <input type="email" name="premium_email" placeholder="' . esc_attr($email_placeholder) . '" required class="premium-content-email-input">
                            
                            <div class="premium-content-checkbox-group">
                                <div class="premium-content-checkbox-item">
                                    <div class="premium-content-custom-checkbox" onclick="togglePremiumCheckbox(this, \'checkbox1\')"></div>
                                    <input type="hidden" name="checkbox1" value="">
                                    <div class="premium-content-checkbox-text">' . wp_kses_post($this->replace_placeholders($checkbox1_text)) . '</div>
                                </div>
                                
                                <div class="premium-content-checkbox-item">
                                    <div class="premium-content-custom-checkbox" onclick="togglePremiumCheckbox(this, \'checkbox2\')"></div>
                                    <input type="hidden" name="checkbox2" value="">
                                    <div class="premium-content-checkbox-text">' . wp_kses_post($this->replace_placeholders($checkbox2_text)) . '</div>
                                </div>
                            </div>
                            
                            <input type="hidden" name="premium_nonce" value="' . esc_attr( $nonce ) . '">
                            <input type="hidden" name="post_id" value="' . esc_attr( $post_id ) . '">
                            <button type="submit" class="premium-content-submit-button"><span>' . esc_html($button_text) . '</span></button>
                        </form>
                        <p class="premium-content-disclaimer">' . wp_kses_post($this->replace_placeholders($disclaimer_text)) . '</p>
                    </div>
                </div>
            ';

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

                                var checkbox1 = form.querySelector(\'input[name="checkbox1"]\').value;
                                var checkbox2 = form.querySelector(\'input[name="checkbox2"]\').value;
                                
                                if (!checkbox1 || !checkbox2) {
                                    alert("You must agree to both terms to continue reading.");
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

            return '
                <div id="truncated-content">
                    ' . $truncated_content . '...
                </div>'
                . $form_html . '
                <div id="full-content" style="display:none;">
                    ' . substr($content, $truncate_length) . '
                </div>'
                . $script_html;
        }

        return $content;
    }
}