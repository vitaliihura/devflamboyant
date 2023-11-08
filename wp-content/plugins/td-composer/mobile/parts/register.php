<?php
//check if admin allow registration
$users_can_register = get_option('users_can_register');

$users_can_register_form = '';
$login_captcha_html = '';
$register_captcha_html = '';
$show_captcha = td_util::get_option('tds_captcha');
$captcha_site_key = td_util::get_option('tds_captcha_site_key');

if ($show_captcha == 'show' && $captcha_site_key != '') {
    $login_captcha_html = '<input type="hidden" id="gRecaptchaResponseMobL" name="gRecaptchaResponse" data-sitekey="' . $captcha_site_key . '" >';
    $register_captcha_html = '<input type="hidden" id="gRecaptchaResponseMobR" name="gRecaptchaResponse" data-sitekey="' . $captcha_site_key . '" >';
}

$fb_login_enabled = td_util::get_option('tds_social_login_fb_enable');
$fb_login_app_id = td_util::get_option('tds_social_login_fb_app_id');

$fb_login_btn = '';
if( $fb_login_enabled == 'true' && $fb_login_app_id != '' ) {
    $fb_login_btn = '<button class="td-login-social td-login-fb td-login-fb-mob">' . __td('Log in With Facebook', TD_THEME_NAME) . '</button>';
}

if ($users_can_register == 1) {

	//add the Register tab to the modal window if `Anyone can register` chec
	//$users_can_register_tab = ' / <a id="register-link">' . __td('REGISTER', TD_THEME_NAME) . '</a>';

	$users_can_register_form = '
            <div id="td-register-div" class="td-login-animation td-login-hide">
            	<!-- close button -->
	            <div class="td-register-close">
	                <span class="td-back-button"><i class="td-icon-read-down"></i></span>
	                <div class="td-login-title">' . __td('Sign up', TD_THEME_NAME) . '</div>
	                <!-- close button -->
		            <div class="td-mobile-close">
		                <span><i class="td-icon-close-mobile"></i></span>
		            </div>
	            </div>
            	<div class="td-login-panel-title"><span>' . __td('Welcome!', TD_THEME_NAME) . '</span>' . __td('Register for an account', TD_THEME_NAME) .'</div>
                <div class="td-login-form-wrap">
	                <div class="td_display_err"></div>
	                <form id="registerForm" action="#" method="post">
	                <div class="td-login-inputs"><input class="td-login-input" type="text" name="register_email" id="register_email" value="" required><label for="register_email">' . __td('your email', TD_THEME_NAME) .'</label></div>
	                <div class="td-login-inputs"><input class="td-login-input" type="text" name="register_user" id="register_user" value="" required><label for="register_user">' . __td('your username', TD_THEME_NAME) .'</label></div>
	                <input type="button" name="register_button" id="register_button" class="wpb_button btn td-login-button" value="' . __td('REGISTER', TD_THEME_NAME) . '">
	                                    ' . $register_captcha_html . '
										' . $fb_login_btn . '
                    </form>
	                <div class="td-login-info-text">' . __td('A password will be e-mailed to you.', TD_THEME_NAME) . '</div>
	                
	                ' . td_util::get_the_privacy_policy_link('<div class="td-login-info-text">', '</div>') . '
                </div>
            </div>';
}

echo '
            <div id="td-login-div" class="td-login-animation td-login-hide">
            	<!-- close button -->
	            <div class="td-login-close">
	                <span class="td-back-button"><i class="td-icon-read-down"></i></span>
	                <div class="td-login-title">' . __td('Sign in', TD_THEME_NAME) . '</div>
	                <!-- close button -->
		            <div class="td-mobile-close">
		                <span><i class="td-icon-close-mobile"></i></span>
		            </div>
	            </div>
	            <div class="td-login-form-wrap">
	                <div class="td-login-panel-title"><span>' . __td('Welcome!', TD_THEME_NAME) . '</span>' . __td('Log into your account', TD_THEME_NAME) .'</div>
	                <div class="td_display_err"></div>
                    <form id="loginForm" action="#" method="post">
	                <div class="td-login-inputs"><input class="td-login-input" type="text" autocomplete="username" name="login_email" id="login_email" value="" required><label>' . __td('your username', TD_THEME_NAME) .'</label></div>
	                <div class="td-login-inputs"><input class="td-login-input" autocomplete="current-password" type="password" name="login_pass" id="login_pass" value="" required><label>' . __td('your password', TD_THEME_NAME) .'</label></div>
	                <input type="button" name="login_button" id="login_button" class="td-login-button" value="' . __td('LOG IN', TD_THEME_NAME) . '">
	                                    ' . $login_captcha_html . '
										' . $fb_login_btn . '
	                </form>
	                <div class="td-login-info-text">
	                    <a href="#" id="forgot-pass-link">' . __td('Forgot your password?', TD_THEME_NAME) . '</a>
	                </div>
	                
	                 ' . td_util::get_the_privacy_policy_link('<div class="td-login-info-text">', '</div>') . '
                </div>
            </div>

            ' . $users_can_register_form . '

            <div id="td-forgot-pass-div" class="td-login-animation td-login-hide">
                <!-- close button -->
	            <div class="td-forgot-pass-close">
	                <a href="#" class="td-back-button"><i class="td-icon-read-down"></i></a>
	                <div class="td-login-title">' . __td('Password recovery', TD_THEME_NAME) . '</div>
	            </div>
	            <div class="td-login-form-wrap">
	                <div class="td-login-panel-title">' . __td('Recover your password', TD_THEME_NAME) .'</div>
	                <div class="td_display_err"></div>
	                <form id="forgotpassForm" action="#" method="post">
	                <div class="td-login-inputs"><input class="td-login-input" type="text" name="forgot_email" id="forgot_email" value="" required><label>' . __td('your email', TD_THEME_NAME) .'</label></div>
	                <input type="button" name="forgot_button" id="forgot_button" class="wpb_button btn td-login-button" value="' . __td('Send My Pass', TD_THEME_NAME) . '">
                    </form>
                </div>
            </div>
';