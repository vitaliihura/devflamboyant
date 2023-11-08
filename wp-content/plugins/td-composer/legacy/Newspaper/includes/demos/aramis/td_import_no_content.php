<?php

/*  ----------------------------------------------------------------------------
	SUBSCRIPTION - start phase 1
*/
if (is_plugin_active('td-subscription/td-subscription.php') ) {
    update_option('users_can_register', true);
    global $wpdb;
    $disable_wizard = $wpdb->get_var( "SELECT value FROM tds_options WHERE name = 'disable_wizard'");
    if ( empty($disable_wizard)) {

        td_demo_subscription::add_account_details( array(
                'company_name' => 'Demo Company',
                'billing_cui' => '75864589',
                'billing_j' => '10/120/2021',
                'billing_address' => '2656 Farm Meadow Drive',
                'billing_city' => 'Tucson',
                'billing_country' => 'Arizona',
                'billing_email' => 'yourcompany@website.com',
                'billing_bank_account' => 'NL43INGB4186520410',
                'billing_post_code' => '85712',
                'billing_vat_number' => '75864589',
                'options' => 'a:1:{s:15:"td_demo_content";i:1;}',
            )
        );

        td_demo_subscription::add_payment_bank( array(
                'account_name' => 'Alpha Bank Account',
                'account_number' => '123456',
                'bank_name' => 'Alpha Bank',
                'routing_number' => '123456',
                'iban' => 'NL43INGB4186520410',
                'bic_swift' => '123456',
                'description' => 'Make your payment directly into our bank account. Please use your Subscription ID as the payment reference. Your subscription will be activated when the funds are cleared in our account.',
                'instruction' => 'Payment method instructions go here.',
                'is_active' => '1',
                'options' => 'a:1:{s:15:"td_demo_content";i:1;}',
            )
        );
        
        td_demo_subscription::add_option( array(
            'name' => 'td_demo_content',
            'value' => '1',
        )
    );

    

    }



    $plan_yearly_plan_id = td_demo_subscription::add_plan( array(
            'name' => 'Yearly Plan',
            'price' => '100',
            'months_in_cycle' => '12',
            'trial_days' => '0',
            'is_free' => '0',
            'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:17:"7061adcb319173d16";}',
        )
    );

    $plan_free_plan_id = td_demo_subscription::add_plan( array(
            'name' => 'Free Plan',
            'price' => '',
            'months_in_cycle' => '',
            'trial_days' => '0',
            'is_free' => '1',
            'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:17:"9861adcb31917fb52";}',
        )
    );

    $plan_monthly_plan_id = td_demo_subscription::add_plan( array(
            'name' => 'Monthly Plan',
            'price' => '10',
            'months_in_cycle' => '1',
            'trial_days' => '0',
            'is_free' => '0',
            'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:17:"9361adcb31918c266";}',
        )
    );

    $page_payment_page_id_id = td_demo_content::add_page(array(
        'title' => 'Checkout - aramis',
        'file' => 'tds_checkout.txt',
    ));

    td_demo_subscription::add_option( array(
            'name' => 'payment_page_id',
            'value' => $page_payment_page_id_id,
        )
    );

    $page_my_account_page_id_id = td_demo_content::add_page(array(
        'title' => 'My Account - aramis',
        'file' => 'tds_my_account.txt',
    ));

    td_demo_subscription::add_option( array(
            'name' => 'my_account_page_id',
            'value' => $page_my_account_page_id_id,
        )
    );

    $page_create_account_page_id_id = td_demo_content::add_page(array(
        'title' => 'Login/Register - aramis',
        'file' => 'tds_login_register.txt',
    ));

    td_demo_subscription::add_option( array(
            'name' => 'create_account_page_id',
            'value' => $page_create_account_page_id_id,
        )
    );

    td_demo_subscription::add_option( array(
            'name' => 'go_wizard',
            'value' => '1',
        )
    );

    td_demo_subscription::add_option( array(
            'name' => 'wizard_company_complete',
            'value' => '1',
        )
    );

    td_demo_subscription::add_option( array(
            'name' => 'wizard_payments_complete',
            'value' => '1',
        )
    );

    td_demo_subscription::add_option( array(
            'name' => 'wizard_plans_complete',
            'value' => '1',
        )
    );

    td_demo_subscription::add_option( array(
            'name' => 'wizard_locker_complete',
            'value' => '1',
        )
    );

    td_demo_subscription::add_option( array(
            'name' => 'disable_wizard',
            'value' => '1',
        )
    );

}


/*  ----------------------------------------------------------------------------
	SUBSCRIPTION - end phase 1
*/

/*  ----------------------------------------------------------------------------
	PAGES
*/
$page_homepage_id = td_demo_content::add_page(array(
    'title' => 'Homepage',
    'file' => 'homepage.txt',
    'homepage' => true,
));

$page_tds_switching_plans_wizard_id = td_demo_content::add_page(array(
    'title' => 'Switching plans wizard',
    'file' => 'tds_switching_plans_wizard.txt',
));



/*  ----------------------------------------------------------------------------
	SUBSCRIPTION - start phase 2
*/
if (is_plugin_active('td-subscription/td-subscription.php') ) {


    /*  ----------------------------------------------------------------------------
        SUBSCRIPTIONS
    */
// add locker
    $post_tds_default_wizard_locker_id = td_demo_content::add_post( array(
            'post_type' => 'tds_locker',
            'title' => 'Wizard Locker (default)',
            'file' => 'post_default.txt',
            'categories_id_array' => [],
            'tds_locker_settings' => array(
                'tds_title' => 'Members Only Content',
                'tds_message' => 'Become a member today to gain unrestricted access to all content across this platform. Enjoy the benefits of reading exclusive and up to date articles straight from the comforts of your home.',
                'tds_submit_btn_text' => 'Subscribe',
                'tds_after_btn_text' => 'Enjoy Fast Content Done Right.',
                'tds_pp_msg' => 'I consent to processing of my data according to <a href=\"#\">Terms of Use</a> & <a href=\"#\">Privacy Policy</a>',
            ),
            'tds_payable' => 'paid_subscription',
            'tds_paid_subs_page_id' => $page_tds_switching_plans_wizard_id,
            'tds_paid_subs_plan_ids' => [$plan_yearly_plan_id,$plan_free_plan_id,$plan_monthly_plan_id],
            'tds_locker_styles' => array(
                'tds_bg_color' => '#ffffff',
                'all_tds_border' => '1px',
                'all_tds_border_color' => '#000000',
                'tds_title_color' => '#000000',
                'tds_message_color' => '#000000',
                'tds_submit_btn_text_color' => '#ffffff',
                'tds_submit_btn_text_color_h' => '#0a0a0a',
                'tds_submit_btn_bg_color' => '#000000',
                'tds_submit_btn_bg_color_h' => '#e5764e',
                'tds_after_btn_text_color' => '#f7c654',
                'tds_pp_checked_color' => '#e5764e',
                'tds_pp_check_border_color' => '#000000',
                'tds_pp_check_border_color_f' => '#e5764e',
                'tds_pp_msg_color' => '#000000',
                'tds_pp_msg_links_color' => '#e5764e',
                'tds_pp_msg_links_color_h' => '#f7c654',
                'tds_general_font_family' => '854',
                'tds_title_font_family' => '149',
                'tds_title_font_size' => '30',
                'tds_title_font_line_height' => '1.2',
                'tds_title_font_weight' => '900',
                'tds_message_font_family' => '854',
                'tds_message_font_size' => '16',
                'tds_message_font_line_height' => '1.4',
                'tds_message_font_weight' => '400',
                'tds_submit_btn_text_font_family' => '854',
                'tds_submit_btn_text_font_size' => '14',
                'tds_submit_btn_text_font_weight' => '400',
                'tds_submit_btn_text_font_transform' => 'uppercase',
                'tds_submit_btn_text_font_spacing' => '1',
                'tds_after_btn_text_font_family' => '854',
                'tds_after_btn_text_font_size' => '10',
                'tds_after_btn_text_font_transform' => 'uppercase',
                'tds_after_btn_text_font_spacing' => '1',
                'tds_pp_msg_font_family' => '854',
                'tds_pp_msg_font_size' => '12',
                'tds_pp_msg_font_line_height' => '1.4',
                'tds_pp_msg_font_style' => 'italic',
            ),
        )
    );

// add post meta for default locker
    td_demo_content::add_locker_meta( array(
            'tds_locker_id' => (int) get_option( 'tds_default_locker_id' ),
            'tds_locker_meta' => array(
                'tds_locker_settings' => array(
                    'tds_title' => 'This Content Is Only For Subscribers',
                    'tds_message' => 'Please subscribe to unlock this content. Enter your email to get access.',
                    'tds_input_placeholder' => 'Please enter your email address.',
                    'tds_submit_btn_text' => 'Subscribe to unlock',
                    'tds_after_btn_text' => 'Your email address is 100% safe from spam!',
                    'tds_pp_msg' => 'I consent to processing of my data according to <a href=\"#\">Terms of Use</a> & <a href=\"#\">Privacy Policy</a>',
                ),
            )
        )
    );

    td_util::update_option('tds_demo_options', 'a:1:{s:5:"plans";a:3:{i:0;a:2:{s:9:"unique_id";s:17:"7061adcb319173d16";s:4:"name";s:11:"Yearly Plan";}i:1;a:2:{s:9:"unique_id";s:17:"9861adcb31917fb52";s:4:"name";s:9:"Free Plan";}i:2;a:2:{s:9:"unique_id";s:17:"9361adcb31918c266";s:4:"name";s:12:"Monthly Plan";}}}');

}


/*  ----------------------------------------------------------------------------
	SUBSCRIPTION - end phase 2
*/


/*  ----------------------------------------------------------------------------
	CLOUD TEMPLATES
*/
$template_date_template_id = td_demo_content::add_cloud_template(array(
    'title' => 'Aramis - Date Template',
    'file' => 'date_cloud_template.txt',
    'template_type' => 'date',
));

td_demo_misc::update_global_date_template( 'tdb_template_' . $template_date_template_id);


$template_tag_template_id = td_demo_content::add_cloud_template(array(
    'title' => 'Aramis - Tag Template',
    'file' => 'tag_cloud_template.txt',
    'template_type' => 'tag',
));

$template_404_template_id = td_demo_content::add_cloud_template(array(
    'title' => 'Aramis - 404 Template',
    'file' => '404_cloud_template.txt',
    'template_type' => '404',
));

$template_search_template_id = td_demo_content::add_cloud_template(array(
    'title' => 'Aramis - Search Template',
    'file' => 'search_cloud_template.txt',
    'template_type' => 'search',
));

td_demo_misc::update_global_search_template( 'tdb_template_' . $template_search_template_id);


$template_category_template_id = td_demo_content::add_cloud_template(array(
    'title' => 'Aramis - Category Template',
    'file' => 'cat_cloud_template.txt',
    'template_type' => 'category',
));

td_demo_misc::update_global_category_template( 'tdb_template_' . $template_category_template_id);


$template_author_template_id = td_demo_content::add_cloud_template(array(
    'title' => 'Aramis - Author Template',
    'file' => 'author_cloud_template.txt',
    'template_type' => 'author',
));

td_demo_misc::update_global_author_template( 'tdb_template_' . $template_author_template_id);


$template_single_template_id = td_demo_content::add_cloud_template(array(
    'title' => 'Aramis - Single Template',
    'file' => 'post_cloud_template.txt',
    'template_type' => 'single',
));

td_util::update_option('td_default_site_post_template', 'tdb_template_' . $template_single_template_id);


$template_footer_template_id = td_demo_content::add_cloud_template(array(
    'title' => 'Aramis - Footer Template',
    'file' => 'footer_cloud_template.txt',
    'template_type' => 'footer',
));

td_demo_misc::update_global_footer_template( 'tdb_template_' . $template_footer_template_id);


$template_header_template_id = td_demo_content::add_cloud_template(array(
    'title' => 'Aramis - Header Template',
    'file' => 'header_cloud_template.txt',
    'template_type' => 'header',
));

td_demo_misc::update_global_header_template( 'tdb_template_' . $template_header_template_id);


update_post_meta( $template_header_template_id, 'header_mobile_menu_id', $menu_td_demo_header_menu_id);



/*  ----------------------------------------------------------------------------
	GENERAL SETTINGS
*/
td_demo_misc::update_background('', false);

td_demo_misc::update_background_mobile('');

td_demo_misc::update_background_login('');

td_demo_misc::update_background_header('');

td_demo_misc::update_background_footer('');

td_demo_misc::update_footer_text('');

td_demo_misc::update_logo(array('normal' => '','retina' => '','mobile' => '',));

td_demo_misc::update_footer_logo(array('normal' => '','retina' => '',));

td_demo_misc::add_social_buttons(array());

$generated_css = td_css_generator();
if ( function_exists('tdsp_css_generator') ) {
    $generated_css .= tdsp_css_generator();
}
td_util::update_option( 'tds_user_compile_css', $generated_css );
