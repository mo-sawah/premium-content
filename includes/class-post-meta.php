<?php
/**
 * Handles individual post premium content settings
 * CRITICAL: This should ONLY render on post edit screens
 */
class Premium_Content_Post_Meta {

    public function __construct() {
        // Only hook if we're in admin
        if (!is_admin()) {
            return;
        }
        
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'), 10, 2);
        add_filter('display_post_states', array($this, 'add_post_state'), 10, 2);
    }

    /**
     * Add meta box to post editor ONLY
     */
    public function add_meta_box() {
        // Multiple safety checks
        $screen = get_current_screen();
        if (!$screen || $screen->base !== 'post') {
            return;
        }
        
        add_meta_box(
            'premium_content_settings',
            '<span class="dashicons dashicons-lock" style="margin-right: 5px;"></span> Premium Content',
            array($this, 'render_meta_box'),
            'post',
            'side',
            'high'
        );
    }

    /**
     * Render meta box content
     */
    public function render_meta_box($post) {
        // Triple safety check - DO NOT render outside post editor
        if (!is_admin()) {
            return;
        }
        
        if (!$post || !isset($post->ID)) {
            return;
        }
        
        $screen = get_current_screen();
        if (!$screen || $screen->base !== 'post') {
            return;
        }
        
        wp_nonce_field('premium_content_meta_box', 'premium_content_meta_nonce');

        $access_level = get_post_meta($post->ID, '_premium_access_level', true);
        $excluded_from_count = get_post_meta($post->ID, '_premium_excluded_from_count', true);
        $required_plan = get_post_meta($post->ID, '_premium_required_plan', true);
        
        $global_mode = premium_content_get_option('access_mode', 'free');
        ?>
        <div class="premium-meta-wrapper">
            <div class="premium-meta-section">
                <label class="premium-meta-label">
                    <strong>Access Level</strong>
                </label>
                
                <div class="premium-radio-group">
                    <label class="premium-radio-option">
                        <input type="radio" name="premium_access_level" value="auto" <?php checked($access_level, ''); ?> <?php checked($access_level, 'auto'); ?>>
                        <span class="radio-label">Auto (Follow Global Settings)</span>
                        <span class="radio-description">Currently: <strong><?php echo ucfirst($global_mode); ?> Mode</strong></span>
                    </label>

                    <label class="premium-radio-option">
                        <input type="radio" name="premium_access_level" value="free" <?php checked($access_level, 'free'); ?>>
                        <span class="radio-label">Free Access</span>
                        <span class="radio-description">Always accessible to everyone</span>
                    </label>

                    <label class="premium-radio-option">
                        <input type="radio" name="premium_access_level" value="email_gate" <?php checked($access_level, 'email_gate'); ?>>
                        <span class="radio-label">Email Gate</span>
                        <span class="radio-description">Requires email, grants 30-day access</span>
                    </label>

                    <label class="premium-radio-option">
                        <input type="radio" name="premium_access_level" value="premium" <?php checked($access_level, 'premium'); ?>>
                        <span class="radio-label">Premium Only</span>
                        <span class="radio-description">Requires active subscription</span>
                    </label>
                </div>
            </div>

            <?php if ($global_mode === 'metered'): ?>
            <div class="premium-meta-section">
                <label class="premium-checkbox-option">
                    <input type="checkbox" name="premium_excluded_from_count" value="1" <?php checked($excluded_from_count, '1'); ?>>
                    <span>Exclude from article count</span>
                </label>
                <p class="description">This article won't count toward the free article limit</p>
            </div>
            <?php endif; ?>

            <div class="premium-meta-section">
                <label class="premium-meta-label">
                    <strong>Required Plan</strong> <span class="optional">(Optional)</span>
                </label>
                
                <select name="premium_required_plan" class="widefat">
                    <option value="">Any Plan</option>
                    <?php
                    $plans = Premium_Content_Subscription_Manager::get_plans('active');
                    foreach ($plans as $plan):
                    ?>
                        <option value="<?php echo esc_attr($plan->id); ?>" <?php selected($required_plan, $plan->id); ?>>
                            <?php echo esc_html($plan->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">Require specific subscription plan for access</p>
            </div>

            <div class="premium-info-box">
                <strong>Priority Order:</strong>
                <ol>
                    <li>Individual post settings (highest)</li>
                    <li>Global access mode</li>
                    <li>User subscription status</li>
                </ol>
            </div>
        </div>

        <style>
            .premium-meta-wrapper { margin: -6px -12px; padding: 12px; }
            .premium-meta-section { margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #e5e5e5; }
            .premium-meta-section:last-of-type { border-bottom: none; }
            .premium-meta-label { display: block; margin-bottom: 10px; font-size: 13px; }
            .premium-meta-label .optional { font-weight: normal; color: #666; font-size: 12px; }
            .premium-radio-group { display: flex; flex-direction: column; gap: 12px; }
            .premium-radio-option { display: flex; flex-direction: column; padding: 12px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer; transition: all 0.2s; background: #fafafa; }
            .premium-radio-option:hover { background: #f0f0f0; border-color: #999; }
            .premium-radio-option input[type="radio"] { margin: 0 8px 0 0; }
            .premium-radio-option .radio-label { font-weight: 600; color: #2c3e50; margin-bottom: 4px; }
            .premium-radio-option .radio-description { font-size: 12px; color: #666; margin-left: 24px; }
            .premium-checkbox-option { display: flex; align-items: center; cursor: pointer; }
            .premium-checkbox-option input { margin-right: 8px; }
            .premium-info-box { background: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 4px; padding: 12px; margin-top: 15px; }
            .premium-info-box strong { display: block; margin-bottom: 8px; color: #2c3e50; }
            .premium-info-box ol { margin: 8px 0 0 20px; font-size: 12px; }
            .description { margin: 6px 0 0 0; font-size: 12px; color: #666; }
        </style>
        <?php
    }

    /**
     * Save meta box data
     */
    public function save_meta($post_id, $post) {
        // Verify nonce
        if (!isset($_POST['premium_content_meta_nonce']) || 
            !wp_verify_nonce($_POST['premium_content_meta_nonce'], 'premium_content_meta_box')) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Check post type
        if ($post->post_type !== 'post') {
            return;
        }

        // Save access level
        if (isset($_POST['premium_access_level'])) {
            $access_level = sanitize_text_field($_POST['premium_access_level']);
            if (in_array($access_level, array('auto', 'free', 'email_gate', 'premium'))) {
                update_post_meta($post_id, '_premium_access_level', $access_level);
            }
        }

        // Save excluded from count
        $excluded = isset($_POST['premium_excluded_from_count']) ? '1' : '0';
        update_post_meta($post_id, '_premium_excluded_from_count', $excluded);

        // Save required plan
        if (isset($_POST['premium_required_plan'])) {
            $required_plan = intval($_POST['premium_required_plan']);
            if ($required_plan > 0) {
                update_post_meta($post_id, '_premium_required_plan', $required_plan);
            } else {
                delete_post_meta($post_id, '_premium_required_plan');
            }
        }
    }

    /**
     * Add post state indicator in post list
     */
    public function add_post_state($post_states, $post) {
        if ($post->post_type !== 'post') {
            return $post_states;
        }

        $access_level = get_post_meta($post->ID, '_premium_access_level', true);
        
        if ($access_level === 'premium') {
            $post_states['premium'] = '<span class="dashicons dashicons-lock" style="color: #d63638;"></span> Premium';
        } elseif ($access_level === 'free') {
            $post_states['free'] = '<span class="dashicons dashicons-unlock" style="color: #00a32a;"></span> Free';
        } elseif ($access_level === 'email_gate') {
            $post_states['email_gate'] = '<span class="dashicons dashicons-email" style="color: #2271b1;"></span> Email Gate';
        }

        return $post_states;
    }

    /**
     * Get post access level
     */
    public static function get_post_access_level($post_id) {
        return get_post_meta($post_id, '_premium_access_level', true);
    }

    /**
     * Check if post is excluded from count
     */
    public static function is_excluded_from_count($post_id) {
        return get_post_meta($post_id, '_premium_excluded_from_count', true) === '1';
    }

    /**
     * Get required plan for post
     */
    public static function get_required_plan($post_id) {
        return get_post_meta($post_id, '_premium_required_plan', true);
    }
}