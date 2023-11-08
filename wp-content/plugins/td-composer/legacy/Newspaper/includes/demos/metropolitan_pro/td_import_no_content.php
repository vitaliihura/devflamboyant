<?php


/*  ----------------------------------------------------------------------------
	SUBSCRIPTION - start phase 1
*/
update_option('users_can_register', true);
global $wpdb;
$disable_wizard = $wpdb->get_var( "SELECT value FROM tds_options WHERE name = 'disable_wizard'");
if ( empty($disable_wizard)) {

    td_demo_subscription::add_account_details( array(
            'company_name' => 'Metropolitan PRO',
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



$plan_monthly_plan_id = td_demo_subscription::add_plan( array(
        'name' => 'Monthly Plan',
        'price' => '10',
        'months_in_cycle' => '1',
        'trial_days' => '0',
        'is_free' => '0',
        'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"8661b763881264e";}',
    )
);

$plan_yearly_plan_id = td_demo_subscription::add_plan( array(
        'name' => 'Yearly Plan',
        'price' => '100',
        'months_in_cycle' => '12',
        'trial_days' => '0',
        'is_free' => '0',
        'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"2461b7638815318";}',
    )
);

$plan_free_plan_id = td_demo_subscription::add_plan( array(
        'name' => 'Free Plan',
        'price' => '',
        'months_in_cycle' => '',
        'trial_days' => '0',
        'is_free' => '1',
        'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"4861b7638817a37";}',
    )
);

$plan_trial_plan_id = td_demo_subscription::add_plan( array(
        'name' => 'Trial Plan',
        'price' => '',
        'months_in_cycle' => '',
        'trial_days' => '',
        'is_free' => '1',
        'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"5661b7638819fa7";}',
    )
);

$plan_weekly_plan_id = td_demo_subscription::add_plan( array(
        'name' => 'Weekly Plan',
        'price' => '5',
        'months_in_cycle' => '',
        'trial_days' => '',
        'is_free' => '0',
        'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"4361b763881c76c";}',
    )
);

$plan_one_time_id = td_demo_subscription::add_plan( array(
        'name' => 'One Time',
        'price' => '4',
        'months_in_cycle' => '',
        'trial_days' => '',
        'is_free' => '0',
        'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"4961b763881f146";}',
    )
);

$page_payment_page_id_id = td_demo_content::add_page(array(
    'title' => 'Checkout - metropolitan_pro',
    'file' => 'tds_checkout.txt',
));

td_demo_subscription::add_option( array(
        'name' => 'payment_page_id',
        'value' => $page_payment_page_id_id,
    )
);

$page_my_account_page_id_id = td_demo_content::add_page(array(
    'title' => 'My Account - metropolitan_pro',
    'file' => 'tds_my_account.txt',
));

td_demo_subscription::add_option( array(
        'name' => 'my_account_page_id',
        'value' => $page_my_account_page_id_id,
    )
);

$page_create_account_page_id_id = td_demo_content::add_page(array(
    'title' => 'Login/Register - metropolitan_pro',
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


/*  ----------------------------------------------------------------------------
	SUBSCRIPTION - end phase 1
*/


/*  ----------------------------------------------------------------------------
	PAGES
*/
$page_tds_switching_plans_wizard_id = td_demo_content::add_page(array(
    'title' => 'Switching plans wizard',
    'file' => 'tds_switching_plans_wizard.txt',
    'demo_unique_id' => '4661b76388a088f',
));


/*  ----------------------------------------------------------------------------
	SUBSCRIPTION - start phase 2
*/

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
            'tds_title' => 'This Content is for Subscribers Only',
            'tds_message' => 'To unlock this article, please SUBSCRIBE NOW!',
            'tds_submit_btn_text' => 'Yes, I want exclusive content!',
            'tds_pp_msg' => 'I consent to processing of my data according to <a href=\"#\">Terms of Use</a> & <a href=\"#\">Privacy Policy</a>',
        ),
        'tds_payable' => 'paid_subscription',
        'tds_paid_subs_page_id' => $page_tds_switching_plans_wizard_id,
        'tds_paid_subs_plan_ids' => [$plan_monthly_plan_id,$plan_yearly_plan_id,$plan_free_plan_id],
        'tds_locker_styles' => array(
            'tds_bg_color' => '#ffffff',
            'all_tds_shadow' => '20',
            'all_tds_shadow_color' => '#e0e0e0',
            'tds_message_color' => '#000000',
            'tds_submit_btn_text_color' => '#ffffff',
            'tds_submit_btn_text_color_h' => '#ffffff',
            'tds_submit_btn_bg_color' => '#40ce9f',
            'tds_submit_btn_bg_color_h' => '#2386dd',
            'tds_after_btn_text_color' => '#000000',
            'tds_pp_msg_color' => '#000000',
            'tds_pp_msg_links_color' => '#236fe0',
            'tds_pp_msg_links_color_h' => '#279ef9',
            'tds_general_font_family' => '394',
            'tds_title_font_family' => '467',
            'tds_title_font_size' => '28',
            'tds_title_font_weight' => '400',
            'tds_title_font_spacing' => '-1',
            'tds_message_font_size' => '16',
            'tds_message_font_weight' => '600',
            'tds_submit_btn_text_font_size' => '12',
            'tds_submit_btn_text_font_weight' => '600',
            'tds_submit_btn_text_font_transform' => 'uppercase',
            'tds_submit_btn_text_font_spacing' => '1',
            'tds_after_btn_text_font_size' => '12',
            'tds_after_btn_text_font_weight' => '600',
            'tds_pp_msg_font_size' => '12',
            'tds_pp_msg_font_weight' => '600',
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

td_util::update_option('tds_demo_options', 'a:1:{s:5:"plans";a:6:{i:0;a:2:{s:9:"unique_id";s:15:"8661b763881264e";s:4:"name";s:12:"Monthly Plan";}i:1;a:2:{s:9:"unique_id";s:15:"2461b7638815318";s:4:"name";s:11:"Yearly Plan";}i:2;a:2:{s:9:"unique_id";s:15:"4861b7638817a37";s:4:"name";s:9:"Free Plan";}i:3;a:2:{s:9:"unique_id";s:15:"5661b7638819fa7";s:4:"name";s:10:"Trial Plan";}i:4;a:2:{s:9:"unique_id";s:15:"4361b763881c76c";s:4:"name";s:11:"Weekly Plan";}i:5;a:2:{s:9:"unique_id";s:15:"4961b763881f146";s:4:"name";s:8:"One Time";}}}');


/*  ----------------------------------------------------------------------------
	CLOUD TEMPLATES
*/
$template_header_template_main_metropolitan_pro_id = td_demo_content::add_cloud_template(array(
    'title' => 'Header Template Main – Metropolitan PRO',
    'file' => 'header_cloud_template.txt',
    'template_type' => 'header',
));

td_demo_misc::update_global_header_template( 'tdb_template_' . $template_header_template_main_metropolitan_pro_id);


update_post_meta( $template_header_template_main_metropolitan_pro_id, 'header_mobile_menu_id', $menu_td_demo_header_menu_id);


$template_author_template_metropolitan_pro_id = td_demo_content::add_cloud_template(array(
    'title' => 'Author Template - Metropolitan PRO',
    'file' => 'author_cloud_template.txt',
    'template_type' => 'author',
));

td_demo_misc::update_global_author_template( 'tdb_template_' . $template_author_template_metropolitan_pro_id);


$template_category_template_metropolitan_pro_id = td_demo_content::add_cloud_template(array(
    'title' => 'Category Template - Metropolitan PRO',
    'file' => 'cat_cloud_template.txt',
    'template_type' => 'category',
));

td_demo_misc::update_global_category_template( 'tdb_template_' . $template_category_template_metropolitan_pro_id);


$template_tag_template_metropolitan_pro_id = td_demo_content::add_cloud_template(array(
    'title' => 'Tag Template - Metropolitan PRO',
    'file' => 'tag_cloud_template.txt',
    'template_type' => 'tag',
));

td_demo_misc::update_global_tag_template( 'tdb_template_' . $template_tag_template_metropolitan_pro_id);


$template_search_template_metropolitan_pro_id = td_demo_content::add_cloud_template(array(
    'title' => 'Search Template - Metropolitan PRO',
    'file' => 'search_cloud_template.txt',
    'template_type' => 'search',
));

td_demo_misc::update_global_search_template( 'tdb_template_' . $template_search_template_metropolitan_pro_id);


$template_single_template_metropolitan_pro_id = td_demo_content::add_cloud_template(array(
    'title' => 'Single Template - Metropolitan PRO',
    'file' => 'post_cloud_template.txt',
    'template_type' => 'single',
));

td_util::update_option('td_default_site_post_template', 'tdb_template_' . $template_single_template_metropolitan_pro_id);


$template_404_template_metropolitan_pro_id = td_demo_content::add_cloud_template(array(
    'title' => '404 Template - Metropolitan PRO',
    'file' => '404_cloud_template.txt',
    'template_type' => '404',
    'header_template_id' => $template_header_template_overlay_metropolitan_pro_id,
));

td_demo_misc::update_global_404_template( 'tdb_template_' . $template_404_template_metropolitan_pro_id);


$template_header_template_overlay_metropolitan_pro_id = td_demo_content::add_cloud_template(array(
    'title' => 'Header Template Overlay - Metropolitan PRO',
    'file' => 'header_cloud_template_overlay.txt',
    'template_type' => 'header',
));

$template_date_template_metropolitan_pro_id = td_demo_content::add_cloud_template(array(
    'title' => 'Date Template - Metropolitan PRO',
    'file' => 'date_cloud_template.txt',
    'template_type' => 'date',
));

td_demo_misc::update_global_date_template( 'tdb_template_' . $template_date_template_metropolitan_pro_id);


$template_footer_template_metropolitan_pro_id = td_demo_content::add_cloud_template(array(
    'title' => 'Footer Template - Metropolitan PRO',
    'file' => 'footer_cloud_template.txt',
    'template_type' => 'footer',
));

td_demo_misc::update_global_footer_template( 'tdb_template_' . $template_footer_template_metropolitan_pro_id);


/*  ----------------------------------------------------------------------------
	HOMEPAGE
*/
$page_home_id = td_demo_content::add_page(array(
    'title' => 'Home',
    'file' => 'home.txt',
    'homepage' => true,
    'header_template_id' => $template_header_template_overlay_metropolitan_pro_id,
    'demo_unique_id' => '7661b76388a4418',
));


/*  ----------------------------------------------------------------------------
	GENERAL SETTINGS
*/
td_demo_misc::update_background('', false);

td_demo_misc::update_background_mobile('td_pic_1');

td_demo_misc::update_background_login('td_pic_1');

td_demo_misc::update_background_header('');

td_demo_misc::update_background_footer('');
