<?php
/**
 * Handles all admin-facing functionality, including menu pages and settings.
 */
class Premium_Content_Admin {

    public function __construct() {
        add_action('admin_menu', array( $this, 'add_admin_menu' ));
        add_action('admin_init', array( $this, 'handle_emails_export' ));
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
            $colors = array( 'primary_color', 'secondary_color', 'border_color', 'text_color', 'title_color', 'link_color', 'background_color' );

            foreach ($colors as $color) {
                if (isset($_POST[$color])) {
                    update_option('premium_content_' . $color, sanitize_hex_color($_POST[$color]));
                }
            }

            echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
        }

        $primary_color = $this->get_premium_content_color('primary_color', '#2c3e50');
        $secondary_color = $this->get_premium_content_color('secondary_color', '#667eea');
        $border_color = $this->get_premium_content_color('border_color', '#e1e5e9');
        $text_color = $this->get_premium_content_color('text_color', '#666');
        $title_color = $this->get_premium_content_color('title_color', '#2c3e50');
        $link_color = $this->get_premium_content_color('link_color', '#667eea');
        $background_color = $this->get_premium_content_color('background_color', '#ffffff');

        // HTML for settings page
        ?>
        <div class="wrap">
            <h1>Premium Content Settings</h1>
            <p>Customize the colors and appearance of your premium content gate.</p>
            
            <form method="post" action="">
                <?php wp_nonce_field('premium_content_settings', 'premium_content_nonce'); ?>
                
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
                                <p class="description">Used for the main title "Continue Reading This Article".</p>
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
                        <h4 style="color: <?php echo esc_attr($title_color); ?>; margin: 0 0 10px 0;">Continue Reading This Article</h4>
                        <p style="color: <?php echo esc_attr($text_color); ?>; margin: 0 0 15px 0; font-size: 14px;">Enjoy this article as well as all of our content...</p>
                        <input type="text" placeholder="Corporate Email Address" style="
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
                        ">Continue Reading</button>
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