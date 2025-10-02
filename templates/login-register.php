<?php
/**
 * Template: Login & Registration Page
 * Displays login and registration forms
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Redirect if already logged in
if (is_user_logged_in()) {
    wp_redirect(get_permalink(get_option('premium_content_page_account')));
    exit;
}

$redirect_to = isset($_GET['redirect_to']) ? esc_url($_GET['redirect_to']) : get_permalink(get_option('premium_content_page_account'));
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'login';
?>

<div class="premium-auth-wrapper">
    <div class="premium-auth-container">
        <div class="auth-tabs">
            <button class="auth-tab <?php echo $action === 'login' ? 'active' : ''; ?>" data-tab="login">
                Sign In
            </button>
            <button class="auth-tab <?php echo $action === 'register' ? 'active' : ''; ?>" data-tab="register">
                Create Account
            </button>
        </div>

        <!-- Login Form -->
        <div id="login-form" class="auth-form <?php echo $action === 'login' ? 'active' : ''; ?>">
            <h2>Welcome Back</h2>
            <p class="auth-description">Sign in to access your account</p>

            <?php
            $login_args = array(
                'echo' => true,
                'redirect' => $redirect_to,
                'form_id' => 'premium-loginform',
                'label_username' => __('Email or Username'),
                'label_password' => __('Password'),
                'label_remember' => __('Remember Me'),
                'label_log_in' => __('Sign In'),
                'id_username' => 'user_login',
                'id_password' => 'user_pass',
                'id_remember' => 'rememberme',
                'id_submit' => 'wp-submit',
                'remember' => true,
                'value_username' => '',
                'value_remember' => false
            );
            wp_login_form($login_args);
            ?>

            <div class="auth-footer">
                <a href="<?php echo esc_url(wp_lostpassword_url($redirect_to)); ?>" class="forgot-password">
                    Forgot your password?
                </a>
            </div>
        </div>

        <!-- Registration Form -->
        <div id="register-form" class="auth-form <?php echo $action === 'register' ? 'active' : ''; ?>">
            <h2>Create Account</h2>
            <p class="auth-description">Join us to access premium content</p>

            <?php
            // Check if registration is enabled
            if (!get_option('users_can_register')) {
                ?>
                <div class="premium-alert premium-alert-warning">
                    <strong>Registration Disabled</strong>
                    <p>User registration is currently disabled. Please contact the site administrator.</p>
                </div>
                <?php
            } else {
                ?>
                <form name="registerform" id="registerform" action="<?php echo esc_url(site_url('wp-login.php?action=register', 'login_post')); ?>" method="post">
                    <div class="form-group">
                        <label for="user_login">Username <span class="required">*</span></label>
                        <input type="text" name="user_login" id="user_login" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="user_email">Email <span class="required">*</span></label>
                        <input type="email" name="user_email" id="user_email" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="user_password">Password <span class="required">*</span></label>
                        <input type="password" name="user_password" id="user_password" class="form-control" required>
                        <small class="form-text">Must be at least 8 characters long</small>
                    </div>

                    <div class="form-group">
                        <label for="user_password_confirm">Confirm Password <span class="required">*</span></label>
                        <input type="password" name="user_password_confirm" id="user_password_confirm" class="form-control" required>
                    </div>

                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" name="accept_terms" required>
                            I agree to the <a href="<?php echo esc_url(home_url('/terms')); ?>" target="_blank">Terms of Service</a> and <a href="<?php echo esc_url(home_url('/privacy')); ?>" target="_blank">Privacy Policy</a>
                        </label>
                    </div>

                    <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">
                    
                    <button type="submit" name="wp-submit" id="wp-submit" class="btn-submit">
                        Create Account
                    </button>
                </form>

                <div id="register-error" class="premium-alert premium-alert-error" style="display: none; margin-top: 16px;">
                    <p id="register-error-message"></p>
                </div>
                <?php
            }
            ?>
        </div>

        <div class="auth-alternate">
            <p>Need help? <a href="<?php echo esc_url(home_url('/contact')); ?>">Contact Support</a></p>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.auth-tab').on('click', function() {
        var tab = $(this).data('tab');
        
        $('.auth-tab').removeClass('active');
        $(this).addClass('active');
        
        $('.auth-form').removeClass('active');
        $('#' + tab + '-form').addClass('active');
        
        // Update URL without reload
        var newUrl = new URL(window.location);
        newUrl.searchParams.set('action', tab);
        window.history.pushState({}, '', newUrl);
    });

    // Registration form validation
    $('#registerform').on('submit', function(e) {
        var password = $('#user_password').val();
        var passwordConfirm = $('#user_password_confirm').val();
        var username = $('#user_login').val();
        var email = $('#user_email').val();
        
        // Clear previous errors
        $('#register-error').hide();
        
        // Password length check
        if (password.length < 8) {
            e.preventDefault();
            showRegisterError('Password must be at least 8 characters long.');
            return false;
        }
        
        // Password match check
        if (password !== passwordConfirm) {
            e.preventDefault();
            showRegisterError('Passwords do not match.');
            return false;
        }
        
        // Username validation
        if (username.length < 3) {
            e.preventDefault();
            showRegisterError('Username must be at least 3 characters long.');
            return false;
        }
        
        // Email validation
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            e.preventDefault();
            showRegisterError('Please enter a valid email address.');
            return false;
        }
    });
    
    function showRegisterError(message) {
        $('#register-error-message').text(message);
        $('#register-error').slideDown();
        
        setTimeout(function() {
            $('#register-error').slideUp();
        }, 5000);
    }
    
    // Show password toggle
    $('<button type="button" class="toggle-password" tabindex="-1">Show</button>').insertAfter('#user_password, #user_password_confirm');
    
    $('.toggle-password').on('click', function() {
        var input = $(this).prev('input');
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            $(this).text('Hide');
        } else {
            input.attr('type', 'password');
            $(this).text('Show');
        }
    });
});
</script>

<style>
.premium-auth-wrapper {
    max-width: 500px;
    margin: 60px auto;
    padding: 20px;
}

.premium-auth-container {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.auth-tabs {
    display: flex;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}

.auth-tab {
    flex: 1;
    padding: 16px;
    background: transparent;
    border: none;
    font-size: 16px;
    font-weight: 600;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s;
    border-bottom: 3px solid transparent;
}

.auth-tab:hover {
    color: #2c3e50;
    background: #f3f4f6;
}

.auth-tab.active {
    color: #667eea;
    background: white;
    border-bottom-color: #667eea;
}

.auth-form {
    display: none;
    padding: 32px;
}

.auth-form.active {
    display: block;
}

.auth-form h2 {
    margin: 0 0 8px 0;
    font-size: 24px;
    color: #2c3e50;
}

.auth-description {
    margin: 0 0 24px 0;
    color: #6b7280;
    font-size: 14px;
}

.form-group {
    margin-bottom: 20px;
    position: relative;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    color: #374151;
    font-size: 14px;
}

.required {
    color: #d63638;
}

.form-control,
#premium-loginform input[type="text"],
#premium-loginform input[type="password"] {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 15px;
    transition: all 0.2s;
    box-sizing: border-box;
}

.form-control:focus,
#premium-loginform input[type="text"]:focus,
#premium-loginform input[type="password"]:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-text {
    display: block;
    margin-top: 4px;
    font-size: 12px;
    color: #6b7280;
}

.checkbox-group {
    padding: 12px;
    background: #f9fafb;
    border-radius: 8px;
}

.checkbox-group label {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-weight: 400;
    font-size: 13px;
    margin: 0;
}

.checkbox-group input[type="checkbox"] {
    margin-top: 2px;
    flex-shrink: 0;
}

.checkbox-group a {
    color: #667eea;
    text-decoration: none;
}

.checkbox-group a:hover {
    text-decoration: underline;
}

.btn-submit,
#premium-loginform .button-primary {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-submit:hover,
#premium-loginform .button-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

#premium-loginform {
    margin: 0;
}

#premium-loginform p {
    margin-bottom: 16px;
}

#premium-loginform label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    color: #374151;
    font-size: 14px;
}

#premium-loginform .login-remember {
    display: flex;
    align-items: center;
    gap: 8px;
}

#premium-loginform .login-remember label {
    margin: 0;
    font-weight: 400;
}

.auth-footer {
    margin-top: 20px;
    text-align: center;
}

.forgot-password {
    color: #667eea;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
}

.forgot-password:hover {
    text-decoration: underline;
}

.auth-alternate {
    padding: 20px;
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
    text-align: center;
}

.auth-alternate p {
    margin: 0;
    color: #6b7280;
    font-size: 14px;
}

.auth-alternate a {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
}

.auth-alternate a:hover {
    text-decoration: underline;
}

.toggle-password {
    position: absolute;
    right: 12px;
    top: 36px;
    background: none;
    border: none;
    color: #667eea;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    padding: 4px 8px;
}

.toggle-password:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .premium-auth-wrapper {
        margin: 40px 20px;
    }
    
    .auth-form {
        padding: 24px;
    }
}
</style>