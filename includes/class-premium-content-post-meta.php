<?php
/**
 * New class to handle individual post premium content settings
 * Create a new file: includes/class-premium-content-post-meta.php
 */
class Premium_Content_Post_Meta {

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_premium_content_metabox'));
        add_action('save_post', array($this, 'save_premium_content_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Add metabox to post edit screen
     */
    public function add_premium_content_metabox() {
        add_meta_box(
            'premium_content_settings',
            'Premium Content Settings',
            array($this, 'premium_content_metabox_callback'),
            'post',
            'side',
            'default'
        );
    }

    /**
     * Metabox callback function
     */
    public function premium_content_metabox_callback($post) {
        // Add nonce for security
        wp_nonce_field('premium_content_meta_nonce', 'premium_content_meta_nonce');

        // Get current value
        $premium_content_setting = get_post_meta($post->ID, '_premium_content_setting', true);
        
        // Default to 'auto' if not set
        if (empty($premium_content_setting)) {
            $premium_content_setting = 'auto';
        }

        // Get some context about what auto would mean for this post
        $auto_status = $this->get_auto_status_for_post($post);
        ?>
        <div class="premium-content-meta-wrapper">
            <p><strong>Control premium content lock for this specific post:</strong></p>
            
            <div class="premium-content-radio-group">
                <label class="premium-content-radio-item">
                    <input type="radio" name="premium_content_setting" value="auto" <?php checked($premium_content_setting, 'auto'); ?> />
                    <span class="radio-label">Auto (follow global settings)</span>
                    <div class="radio-description">
                        Current auto status: <strong><?php echo esc_html($auto_status['status']); ?></strong>
                        <br><small><?php echo esc_html($auto_status['reason']); ?></small>
                    </div>
                </label>

                <label class="premium-content-radio-item">
                    <input type="radio" name="premium_content_setting" value="enabled" <?php checked($premium_content_setting, 'enabled'); ?> />
                    <span class="radio-label">Force Enable</span>
                    <div class="radio-description">
                        <small>Always show premium content lock for this post, regardless of global settings.</small>
                    </div>
                </label>

                <label class="premium-content-radio-item">
                    <input type="radio" name="premium_content_setting" value="disabled" <?php checked($premium_content_setting, 'disabled'); ?> />
                    <span class="radio-label">Force Disable</span>
                    <div class="radio-description">
                        <small>Never show premium content lock for this post, regardless of global settings.</small>
                    </div>
                </label>
            </div>

            <div class="premium-content-info">
                <h4>Priority Order:</h4>
                <ol>
                    <li><strong>This setting</strong> (highest priority)</li>
                    <li>Premium tag</li>
                    <li>Date-based rules</li>
                    <li>Enable for all posts</li>
                </ol>
                <p><small>Individual post settings always override global settings.</small></p>
            </div>
        </div>

        <style>
            .premium-content-meta-wrapper {
                margin: 10px 0;
            }
            .premium-content-radio-group {
                margin: 15px 0;
            }
            .premium-content-radio-item {
                display: block;
                margin-bottom: 15px;
                cursor: pointer;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
                background: #fafafa;
                transition: background 0.2s ease;
            }
            .premium-content-radio-item:hover {
                background: #f0f0f0;
            }
            .premium-content-radio-item input[type="radio"] {
                margin-right: 8px;
            }
            .radio-label {
                font-weight: 600;
                color: #2c3e50;
            }
            .radio-description {
                margin-top: 5px;
                font-size: 12px;
                color: #666;
                margin-left: 20px;
            }
            .premium-content-info {
                margin-top: 20px;
                padding: 10px;
                background: #e7f3ff;
                border: 1px solid #b3d9ff;
                border-radius: 4px;
            }
            .premium-content-info h4 {
                margin: 0 0 8px 0;
                font-size: 13px;
                color: #2c3e50;
            }
            .premium-content-info ol {
                margin: 8px 0 8px 20px;
                font-size: 12px;
            }
            .premium-content-info p {
                margin: 8px 0 0 0;
                font-size: 11px;
                font-style: italic;
            }
        </style>
        <?php
    }

    /**
     * Get what the auto setting would result in for this post
     */
    private function get_auto_status_for_post($post) {
        $post_date = $post->post_date;
        $enable_all_posts = get_option('premium_content_enable_all_posts', '0');
        $enable_after_date = get_option('premium_content_enable_after_date', '0');
        $enable_before_date = get_option('premium_content_enable_before_date', '0');
        $after_date = get_option('premium_content_after_date', '');
        $before_date = get_option('premium_content_before_date', '');

        // Check if post has premium tag
        if (has_tag('premium', $post->ID)) {
            return array(
                'status' => 'ENABLED',
                'reason' => 'Post is tagged with "premium"'
            );
        }

        // Check date-based rules
        if ($enable_after_date === '1' && !empty($after_date)) {
            if (strtotime($post_date) >= strtotime($after_date)) {
                return array(
                    'status' => 'ENABLED',
                    'reason' => 'Post published on/after ' . date('M j, Y', strtotime($after_date))
                );
            }
        }

        if ($enable_before_date === '1' && !empty($before_date)) {
            if (strtotime($post_date) < strtotime($before_date)) {
                return array(
                    'status' => 'ENABLED',
                    'reason' => 'Post published before ' . date('M j, Y', strtotime($before_date))
                );
            }
        }

        // Check enable all posts
        if ($enable_all_posts === '1') {
            return array(
                'status' => 'ENABLED',
                'reason' => 'Global "Enable for all posts" is active'
            );
        }

        return array(
            'status' => 'DISABLED',
            'reason' => 'No matching rules found'
        );
    }

    /**
     * Save metabox data
     */
    public function save_premium_content_meta($post_id) {
        // Check if our nonce is set and verify it
        if (!isset($_POST['premium_content_meta_nonce']) || 
            !wp_verify_nonce($_POST['premium_content_meta_nonce'], 'premium_content_meta_nonce')) {
            return;
        }

        // Check if user has permission to edit this post
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check post type
        if (get_post_type($post_id) !== 'post') {
            return;
        }

        // Save the data
        if (isset($_POST['premium_content_setting'])) {
            $setting = sanitize_text_field($_POST['premium_content_setting']);
            
            // Validate the setting value
            if (in_array($setting, array('auto', 'enabled', 'disabled'))) {
                update_post_meta($post_id, '_premium_content_setting', $setting);
            }
        }
    }

    /**
     * Enqueue admin scripts for better UX
     */
    public function enqueue_admin_scripts($hook) {
        global $post;

        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }

        if (!$post || $post->post_type !== 'post') {
            return;
        }

        wp_add_inline_script('jquery', '
            jQuery(document).ready(function($) {
                // Update auto status when radio changes
                $("input[name=\'premium_content_setting\']").change(function() {
                    if ($(this).val() === "auto") {
                        // Could add AJAX call here to refresh auto status if needed
                    }
                });
                
                // Add visual feedback for different states
                $("input[name=\'premium_content_setting\']").each(function() {
                    var $label = $(this).closest(".premium-content-radio-item");
                    if ($(this).is(":checked")) {
                        $label.css("background", "#e7f3ff");
                        $label.css("border-color", "#0073aa");
                    }
                });
                
                $("input[name=\'premium_content_setting\']").change(function() {
                    $(".premium-content-radio-item").css("background", "#fafafa").css("border-color", "#ddd");
                    $(this).closest(".premium-content-radio-item").css("background", "#e7f3ff").css("border-color", "#0073aa");
                });
            });
        ');
    }

    /**
     * Get individual post setting (used by other classes)
     */
    public static function get_post_premium_setting($post_id) {
        return get_post_meta($post_id, '_premium_content_setting', true);
    }
}