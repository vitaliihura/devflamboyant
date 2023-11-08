<!-- LOGIN MODAL -->
<?php


//if (td_util::get_option('tds_login_sign_in_widget') == 'show') {

    //check if admin allow registration
    $users_can_register = get_option('users_can_register');
    $login_captcha_html = '';
    $register_captcha_html = '';
    $show_captcha = td_util::get_option('tds_captcha');
    $captcha_site_key = td_util::get_option('tds_captcha_site_key');

    if ($show_captcha == 'show' && $captcha_site_key != '') {
        $login_captcha_html = '<input type="hidden" id="gRecaptchaResponseL" name="gRecaptchaResponse" data-sitekey="' . $captcha_site_key . '" >';
        $register_captcha_html = '<input type="hidden" id="gRecaptchaResponseR" name="gRecaptchaResponse" data-sitekey="' . $captcha_site_key . '" >';
    }

    $fb_login_enabled = td_util::get_option('tds_social_login_fb_enable');
    $fb_login_app_id = td_util::get_option('tds_social_login_fb_app_id');

    $fb_login_btn = '';
    if( is_ssl() && $fb_login_enabled == 'true' && $fb_login_app_id != '' ) {
        $fb_login_btn = '<button class="td-login-social td-login-fb td-login-fb-modal">' . __td('Log in With Facebook', TD_THEME_NAME) . '</button>';
    }

    //add the Register tab to the modal window if `Anyone can register` check
    $users_can_register_link = '';
    $users_can_register_form = '';
    if ( $users_can_register == 1 ) {

        $users_can_register_link = '<a id="register-link">' . __td('Create an account', TD_THEME_NAME) . '</a>';
        $users_can_register_form = '
                <div id="td-register-div" class="td-login-form-div td-display-none td-login-modal-wrap">
                    <div class="td-login-panel-title">' . __td('Create an account', TD_THEME_NAME) . '</div>
                    <div class="td-login-panel-descr">' . __td('Welcome! Register for an account', TD_THEME_NAME) .'</div>
                    <div class="td_display_err"></div>
                    <form id="registerForm" action="#" method="post">
                        <div class="td-login-inputs"><input class="td-login-input" type="text" name="register_email" id="register_email" value="" required><label for="register_email">' . __td('your email', TD_THEME_NAME) .'</label></div>
                        <div class="td-login-inputs"><input class="td-login-input" type="text" name="register_user" id="register_user" value="" required><label for="register_user">' . __td('your username', TD_THEME_NAME) .'</label></div>
                        <input type="button" name="register_button" id="register_button" class="wpb_button btn td-login-button" value="' . __td('Register', TD_THEME_NAME) . '">
                        ' . $register_captcha_html . '
                    </form>      

                    ' . $fb_login_btn . '
                    
                    <div class="td-login-info-text">' . __td('A password will be e-mailed to you.', TD_THEME_NAME) . '</div>
                    ' . td_util::get_the_privacy_policy_link('<div class="td-login-info-text">', '</div>') . '
                </div>';
    }




    echo '
                <div id="login-form" class="white-popup-block mfp-hide mfp-with-anim td-login-modal-wrap">
                    <div class="td-login-wrap">
                        <a href="#" aria-label="Back" class="td-back-button"><i class="td-icon-modal-back"></i></a>
                        <div id="td-login-div" class="td-login-form-div td-display-block">
                            <div class="td-login-panel-title">' . __td('Sign in', TD_THEME_NAME) . '</div>
                            <div class="td-login-panel-descr">' . __td('Welcome! Log into your account', TD_THEME_NAME) . '</div>
                            <div class="td_display_err"></div>
                            <form id="loginForm" action="#" method="post">
                                <div class="td-login-inputs"><input class="td-login-input" autocomplete="username" type="text" name="login_email" id="login_email" value="" required><label for="login_email">' . __td('your username', TD_THEME_NAME) . '</label></div>
                                <div class="td-login-inputs"><input class="td-login-input" autocomplete="current-password" type="password" name="login_pass" id="login_pass" value="" required><label for="login_pass">' . __td('your password', TD_THEME_NAME) . '</label></div>
                                <input type="button"  name="login_button" id="login_button" class="wpb_button btn td-login-button" value="' . __td('Login', TD_THEME_NAME) . '">
                                ' . $login_captcha_html . '
                            </form>

                            ' . $fb_login_btn . '

                            <div class="td-login-info-text"><a href="#" id="forgot-pass-link">' . __td('Forgot your password? Get help', TD_THEME_NAME) . '</a></div>
                            
                            
                            ' . $users_can_register_link . '
                            ' . td_util::get_the_privacy_policy_link('<div class="td-login-info-text">', '</div>') . '
                        </div>

                        ' . $users_can_register_form . '

                         <div id="td-forgot-pass-div" class="td-login-form-div td-display-none">
                            <div class="td-login-panel-title">' . __td('Password recovery', TD_THEME_NAME) . '</div>
                            <div class="td-login-panel-descr">' . __td('Recover your password', TD_THEME_NAME) . '</div>
                            <div class="td_display_err"></div>
                            <form id="forgotpassForm" action="#" method="post">
                                <div class="td-login-inputs"><input class="td-login-input" type="text" name="forgot_email" id="forgot_email" value="" required><label for="forgot_email">' . __td('your email', TD_THEME_NAME) . '</label></div>
                                <input type="button" name="forgot_button" id="forgot_button" class="wpb_button btn td-login-button" value="' . __td('Send My Password', TD_THEME_NAME) . '">
                            </form>
                            <div class="td-login-info-text">' . __td('A password will be e-mailed to you.', TD_THEME_NAME) . '</div>
                        </div>
                        
                        
                    </div>
                </div>
                ';

