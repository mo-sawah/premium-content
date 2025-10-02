<?php
/**
 * Template: Login & Registration - Modern Design
 */

if (!defined('ABSPATH')) {
    exit;
}

if (is_user_logged_in()) {
    wp_redirect(get_permalink(get_option('premium_content_page_account')));
    exit;
}

$redirect_to = isset($_GET['redirect_to']) ? esc_url($_GET['redirect_to']) : get_permalink(get_option('premium_content_page_account'));
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'login';
?>

<div class="pcp-auth-wrapper">
    <div class="auth-container">
        <!-- Tabs -->
        <div class="auth-tabs">
            <button class="auth-tab <?php echo $action === 'login' ? 'active' : ''; ?>" data-tab="login">
                <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
                    <path d="M11 7L9.6 8.4l2.6 2.6H2v2h10.2l-2.6 2.6L11 17l5-5-5-5zm9 12h-8v2h8c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-8v2h8v14z"/>
                </svg>
                Sign In
            </button>
            <button class="auth-tab <?php echo $action === 'register' ? 'active' : ''; ?>" data-tab="register">
                <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
                    <path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
                Create Account
            </button>
        </div>

        <!-- Login Form -->
        <div id="login-form" class="auth-form <?php echo $action === 'login' ? 'active' : ''; ?>">
            <div class="form-header">
                <h2>Welcome Back</h2>
                <p>Sign in to access your account and premium content</p>
            </div>

            <?php
            $login_args = array(
                'echo' => true,
                'redirect' => $redirect_to,
                'form_id' => 'pcp-login-form',
                'label_username' => '',
                'label_password' => '',
                'label_remember' => __('Remember Me'),
                'label_log_in' => __('Sign In'),
                'id_username' => 'user_login',
                'id_password' => 'user_pass',
                'id_remember' => 'rememberme',
                'id_submit' => 'wp-submit',
                'remember' => true,
            );
            
            // Custom login form
            ?>
            <form name="loginform" id="pcp-login-form" action="<?php echo esc_url(site_url('wp-login.php', 'login_post')); ?>" method="post">
                <div class="form-group">
                    <label for="user_login">Email or Username</label>
                    <div class="input-wrapper">
                        <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                        </svg>
                        <input type="text" name="log" id="user_login" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="user_pass">Password</label>
                    <div class="input-wrapper">
                        <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                            <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                        </svg>
                        <input type="password" name="pwd" id="user_pass" class="form-control" required>
                        <button type="button" class="toggle-password" tabindex="-1">
                            <svg class="eye-open" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                            </svg>
                            <svg class="eye-closed" viewBox="0 0 24 24" fill="currentColor" width="20" height="20" style="display: none;">
                                <path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="rememberme" id="rememberme" value="forever">
                        <span>Remember me</span>
                    </label>
                    <a href="<?php echo esc_url(wp_lostpassword_url($redirect_to)); ?>" class="forgot-link">
                        Forgot password?
                    </a>
                </div>

                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">
                
                <button type="submit" name="wp-submit" class="pcp-btn primary large">
                    Sign In
                </button>
            </form>
        </div>

        <!-- Register Form -->
        <div id="register-form" class="auth-form <?php echo $action === 'register' ? 'active' : ''; ?>">
            <div class="form-header">
                <h2>Create Account</h2>
                <p>Join us to unlock exclusive content and features</p>
            </div>

            <?php if (!get_option('users_can_register')): ?>
                <div class="form-alert error">
                    <svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <strong>Registration Disabled</strong>
                        <p>User registration is currently disabled. Please contact support.</p>
                    </div>
                </div>
            <?php else: ?>
                <form name="registerform" id="pcp-register-form" method="post">
                    <div class="form-group">
                        <label for="reg_username">Username</label>
                        <div class="input-wrapper">
                            <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                            </svg>
                            <input type="text" name="user_login" id="reg_username" class="form-control" required>
                        </div>
                        <small class="field-hint">Minimum 3 characters</small>
                    </div>

                    <div class="form-group">
                        <label for="reg_email">Email Address</label>
                        <div class="input-wrapper">
                            <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                                <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                            </svg>
                            <input type="email" name="user_email" id="reg_email" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reg_password">Password</label>
                        <div class="input-wrapper">
                            <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                                <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                            </svg>
                            <input type="password" name="user_password" id="reg_password" class="form-control" required>
                            <button type="button" class="toggle-password" tabindex="-1">
                                <svg class="eye-open" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                                </svg>
                                <svg class="eye-closed" viewBox="0 0 24 24" fill="currentColor" width="20" height="20" style="display: none;">
                                    <path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>
                                </svg>
                            </button>
                        </div>
                        <small class="field-hint">Minimum 8 characters</small>
                    </div>

                    <div class="form-group">
                        <label for="reg_password_confirm">Confirm Password</label>
                        <div class="input-wrapper">
                            <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                                <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                            </svg>
                            <input type="password" name="user_password_confirm" id="reg_password_confirm" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="accept_terms" required>
                            <span>I agree to the <a href="<?php echo esc_url(home_url('/terms')); ?>" target="_blank">Terms</a> and <a href="<?php echo esc_url(home_url('/privacy')); ?>" target="_blank">Privacy Policy</a></span>
                        </label>
                    </div>

                    <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">
                    
                    <button type="submit" name="wp-submit" class="pcp-btn primary large">
                        Create Account
                    </button>
                </form>

                <div id="register-error" class="form-alert error" style="display: none;">
                    <svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <strong>Error</strong>
                        <p id="register-error-message"></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="auth-footer">
            <p>Need help? <a href="<?php echo esc_url(home_url('/contact')); ?>">Contact Support</a></p>
        </div>
    </div>
</div>

<style>
.pcp-auth-wrapper {
    max-width: 480px;
    margin: 60px auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.auth-container {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
}

/* Tabs */
.auth-tabs {
    display: flex;
    background: #f9fafb;
    border-bottom: 2px solid #e5e7eb;
}

.auth-tab {
    flex: 1;
    padding: 16px 20px;
    background: transparent;
    border: none;
    font-size: 1rem;
    font-weight: 600;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s;
    border-bottom: 3px solid transparent;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.auth-tab:hover {
    color: #111827;
    background: #f3f4f6;
}

.auth-tab.active {
    color: #2563eb;
    background: white;
    border-bottom-color: #2563eb;
}

/* Forms */
.auth-form {
    display: none;
    padding: 36px 32px;
}

.auth-form.active {
    display: block;
}

.form-header {
    margin-bottom: 28px;
    text-align: center;
}

.form-header h2 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #111827;
    margin: 0 0 8px 0;
}

.form-header p {
    color: #6b7280;
    margin: 0;
    font-size: 0.938rem;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
}

.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.input-wrapper svg:first-child {
    position: absolute;
    left: 14px;
    color: #9ca3af;
    pointer-events: none;
}

.form-control {
    width: 100%;
    padding: 12px 14px 12px 44px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.2s;
    font-family: inherit;
}

.form-control:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.toggle-password {
    position: absolute;
    right: 12px;
    background: transparent;
    border: none;
    color: #9ca3af;
    cursor: pointer;
    padding: 4px;
    display: flex;
    align-items: center;
}

.toggle-password:hover {
    color: #6b7280;
}

.field-hint {
    display: block;
    margin-top: 6px;
    font-size: 0.813rem;
    color: #6b7280;
}

/* Form Options */
.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.875rem;
    color: #374151;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: #2563eb;
}

.checkbox-group {
    padding: 16px;
    background: #f9fafb;
    border-radius: 8px;
}

.checkbox-group .checkbox-label span {
    line-height: 1.5;
}

.checkbox-group a {
    color: #2563eb;
    text-decoration: none;
}

.checkbox-group a:hover {
    text-decoration: underline;
}

.forgot-link {
    font-size: 0.875rem;
    color: #2563eb;
    text-decoration: none;
    font-weight: 600;
}

.forgot-link:hover {
    text-decoration: underline;
}

/* Alert */
.form-alert {
    display: flex;
    gap: 12px;
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    }

.form-alert.error {
    background: #fef2f2;
    border: 1px solid #fca5a5;
}

.form-alert svg {
    color: #dc2626;
    flex-shrink: 0;
    margin-top: 2px;
}

.form-alert strong {
    display: block;
    color: #991b1b;
    font-weight: 600;
    margin-bottom: 4px;
    font-size: 0.875rem;
}

.form-alert p {
    color: #991b1b;
    margin: 0;
    font-size: 0.813rem;
}

/* Button */
.pcp-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 24px;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    text-decoration: none;
    border: 2px solid transparent;
    cursor: pointer;
    transition: all 0.2s;
    font-family: inherit;
}

.pcp-btn.large {
    width: 100%;
    padding: 16px 32px;
    font-size: 1.063rem;
}

.pcp-btn.primary {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: white;
}

.pcp-btn.primary:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(37, 99, 235, 0.4);
}

.pcp-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Footer */
.auth-footer {
    padding: 20px 32px;
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
    text-align: center;
}

.auth-footer p {
    margin: 0;
    color: #6b7280;
    font-size: 0.875rem;
}

.auth-footer a {
    color: #2563eb;
    text-decoration: none;
    font-weight: 600;
}

.auth-footer a:hover {
    text-decoration: underline;
}

/* Responsive */
@media (max-width: 640px) {
    .pcp-auth-wrapper {
        margin: 40px 16px;
    }
    
    .auth-form {
        padding: 28px 24px;
    }
    
    .auth-tab {
        padding: 14px 16px;
        font-size: 0.938rem;
    }
    
    .form-header h2 {
        font-size: 1.5rem;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    'use strict';
    
    // Tab switching
    $('.auth-tab').on('click', function() {
        var tab = $(this).data('tab');
        
        $('.auth-tab').removeClass('active');
        $(this).addClass('active');
        
        $('.auth-form').removeClass('active');
        $('#' + tab + '-form').addClass('active');
        
        // Update URL
        var newUrl = new URL(window.location);
        newUrl.searchParams.set('action', tab);
        window.history.pushState({}, '', newUrl);
    });
    
    // Toggle password visibility
    $('.toggle-password').on('click', function() {
        var $input = $(this).siblings('.form-control');
        var $eyeOpen = $(this).find('.eye-open');
        var $eyeClosed = $(this).find('.eye-closed');
        
        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $eyeOpen.hide();
            $eyeClosed.show();
        } else {
            $input.attr('type', 'password');
            $eyeOpen.show();
            $eyeClosed.hide();
        }
    });
    
    // Registration validation
    $('#pcp-register-form').on('submit', function(e) {
        var username = $('#reg_username').val();
        var email = $('#reg_email').val();
        var password = $('#reg_password').val();
        var passwordConfirm = $('#reg_password_confirm').val();
        var termsAccepted = $('input[name="accept_terms"]').is(':checked');
        
        $('#register-error').hide();
        
        // Username validation
        if (username.length < 3) {
            e.preventDefault();
            showRegisterError('Username must be at least 3 characters long');
            return false;
        }
        
        // Email validation
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            e.preventDefault();
            showRegisterError('Please enter a valid email address');
            return false;
        }
        
        // Password validation
        if (password.length < 8) {
            e.preventDefault();
            showRegisterError('Password must be at least 8 characters long');
            return false;
        }
        
        // Password match validation
        if (password !== passwordConfirm) {
            e.preventDefault();
            showRegisterError('Passwords do not match');
            return false;
        }
        
        // Terms validation
        if (!termsAccepted) {
            e.preventDefault();
            showRegisterError('You must accept the Terms and Privacy Policy');
            return false;
        }
        
        // If all validations pass, submit via AJAX
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        var originalText = $btn.text();
        
        $btn.prop('disabled', true).text('Creating account...');
        
        $.ajax({
            url: '<?php echo esc_url(site_url('wp-login.php?action=register', 'login_post')); ?>',
            type: 'POST',
            data: $form.serialize(),
            success: function(response) {
                // WordPress registration successful
                window.location.href = $form.find('input[name="redirect_to"]').val();
            },
            error: function(xhr) {
                var errorMsg = 'Registration failed. Please try again.';
                if (xhr.responseText) {
                    // Try to extract error message
                    var $response = $(xhr.responseText);
                    var error = $response.find('#login_error').text();
                    if (error) {
                        errorMsg = error.trim();
                    }
                }
                showRegisterError(errorMsg);
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });
    
    function showRegisterError(message) {
        $('#register-error-message').text(message);
        $('#register-error').slideDown();
        
        setTimeout(function() {
            $('#register-error').slideUp();
        }, 6000);
    }
});
</script>