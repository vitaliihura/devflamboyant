<?php

class tdb_form_submit extends td_block {

    public function get_custom_css() {

        // $unique_block_class
        $unique_block_class = $this->block_uid;

        $compiled_css = '';

        /** @noinspection CssInvalidAtRule */

        $raw_css =
            "<style>

                /* @style_general_tdb_form_submit */
                .tdb_form_submit {
                    transform: translateZ(0);
                    margin-bottom: 28px;
                    font-family: -apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,Oxygen-Sans,Ubuntu,Cantarell,\"Helvetica Neue\",sans-serif;
                    font-size: 14px;
                }
                .tdb_form_submit .tdb-block-inner {
                    display: flex;
                    flex-direction: column;
                }
                .tdb_form_submit .tdb-s-content {
                    min-height: auto;
                }
                .tdb_form_submit .tdb-s-form-footer {
                    margin-top: 0;
                }
                .tdb_form_submit .tdb-s-btn {
                    min-width: auto;
                }
                .tdb_form_submit .tdb-s-btn svg {
                    fill: #fff;
                }
                .tdb_form_submit .tdb-s-notif:not(.tdb-fs-limit-reached) {
                    margin-bottom: 28px;
                }
                .tdb_form_submit .tds-pb-icon {
                    position: relative;
                }
                
                /* @style_general_tdb_form_submit_composer */
                .tdb_form_submit .tdb-block-inner {
                    pointer-events: none;
                }
                
                /* @all_border */
                body .$unique_block_class .tdb-s-btn {
                    border: @all_border @all_border_style @all_border_color;
                }
                /* @btn_radius */
                body .$unique_block_class .tdb-s-btn {
                    border-radius: @btn_radius;
                }
                 /* @border_color_h */
                body .$unique_block_class .tdb-s-btn:hover {
                    border-color: @border_color_h;
                }
                
                /* @padding */
                body .$unique_block_class .tdb-s-btn {
                    padding: @padding;
                }
                
                /* @min_width */
                body .$unique_block_class .tdb-s-btn {
                    min-width: @min_width;
                }
                
                /* @display_default */
                body .$unique_block_class {
                    display: block;
                }
                body .$unique_block_class .tdb-s-btn {
                    width: auto;
                }
                /* @display_inline */
                body .$unique_block_class {
                    display: inline-block;
                }
                /* @display_full */
                body .$unique_block_class {
                    display: block;
                }
                body .$unique_block_class .tdb-block-inner {
                    align-items: stretch;
                }
                body .$unique_block_class .tdb-s-btn {
                    width: 100%;
                }
                
                /* @horiz_align */
                body .$unique_block_class .tdb-s-form-footer,
                body .$unique_block_class .tdb-s-btn {
                    justify-content: @horiz_align;
                }
                
                
                /* @icon_size */
                body .$unique_block_class i {
                    font-size: @icon_size;
                }
                body .$unique_block_class svg {
                    width: @icon_size;
                }
                
                /* @icon_align */
                body .$unique_block_class .tds-pb-icon {
                    top: @icon_align;
                }
                
                /* @icon_space */
                body .$unique_block_class .tds-pb-icon {
                    margin: @icon_space;
                }
                
                /* @notif_radius */
                body .$unique_block_class .tdb-s-notif {
                    border-radius: @notif_radius;
                }
                
                /* @accent_color */
                body .$unique_block_class .tdb-s-btn {
                    background-color: @accent_color;
                }
                
                /* @btn_color */
                body .$unique_block_class .tdb-s-btn {
                    color: @btn_color;
                }
                body .$unique_block_class .tdb-s-btn svg {
                    fill: @btn_color;
                }
                /* @btn_color_h */
                body .$unique_block_class .tdb-s-btn:hover {
                    color: @btn_color_h;
                }
                body .$unique_block_class .tdb-s-btn:hover svg {
                    fill: @btn_color_h;
                }
                /* @btn_bg_h */
                body .$unique_block_class .tdb-s-btn:hover {
                    background-color: @btn_bg_h;
                }
                
                /* @notif_info_color */
                body .$unique_block_class .tdb-s-notif-info {
                    color: @notif_info_color;
                }
                /* @notif_info_bg */
                body .$unique_block_class .tdb-s-notif-info {
                    background-color: @notif_info_bg;
                }
                /* @notif_succ_color */
                body .$unique_block_class .tdb-s-notif-success {
                    color: @notif_succ_color;
                }
                /* @notif_succ_bg */
                body .$unique_block_class .tdb-s-notif-success {
                    background-color: @notif_succ_bg;
                }
                /* @notif_error_color */
                body .$unique_block_class .tdb-s-notif-error {
                    color: @notif_error_color;
                }
                /* @notif_error_bg */
                body .$unique_block_class .tdb-s-notif-error {
                    background-color: @notif_error_bg;
                }
                
                
                /* @f_text */
                body .$unique_block_class .tdb-s-btn {
                    @f_text
                }

            </style>";

        $td_css_res_compiler = new td_css_res_compiler( $raw_css );
        $td_css_res_compiler->load_settings( __CLASS__ . '::cssMedia', $this->get_all_atts() );

        $compiled_css .= $td_css_res_compiler->compile_css();

        return $compiled_css;

    }

    static function cssMedia( $res_ctx ) {

        /*-- GENERAL STYLES -- */
        $res_ctx->load_settings_raw( 'style_general_tdb_form_submit', 1 );
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $res_ctx->load_settings_raw( 'style_general_tdb_form_submit_composer', 1 );
        }



        /*-- LAYOUT -- */
        // border size
        $border_size = $res_ctx->get_shortcode_att('all_border');
        $res_ctx->load_settings_raw('all_border', $border_size);
        if( $border_size != '' && is_numeric( $border_size ) ) {
            $res_ctx->load_settings_raw('all_border', $border_size . 'px');
        }
        // border style
        $all_border_style = $res_ctx->get_shortcode_att('all_border_style');
        if( $all_border_style != '' ) {
            $res_ctx->load_settings_raw('all_border_style', $res_ctx->get_shortcode_att('all_border_style'));
        } else {
            $res_ctx->load_settings_raw('all_border_style', 'solid');
        }
        // buttons border radius
        $btn_radius = $res_ctx->get_shortcode_att('btn_radius');
        $res_ctx->load_settings_raw( 'btn_radius', $btn_radius );
        if( $btn_radius != '' && is_numeric( $btn_radius ) ) {
            $res_ctx->load_settings_raw( 'btn_radius', $btn_radius . 'px' );
        }

        $all_border_color = $res_ctx->get_shortcode_att('all_border_color');
        if( $all_border_color != '' ) {
            $res_ctx->load_settings_raw('all_border_color', $all_border_color);
        } else {
            $res_ctx->load_settings_raw('all_border_color', '#000');
        }
        $res_ctx->load_settings_raw('border_color_h', $res_ctx->get_shortcode_att('border_color_h'));

        // button padding
        $padding = $res_ctx->get_shortcode_att('padd');
        $res_ctx->load_settings_raw('padding', $padding);
        if( $padding != '' && is_numeric( $padding ) ) {
            $res_ctx->load_settings_raw('padding', $padding . 'px');
        }

        // button min width
        $min_width = $res_ctx->get_shortcode_att('min_width');
        $res_ctx->load_settings_raw('min_width', $min_width);
        if( $min_width != '' && is_numeric( $min_width ) ) {
            $res_ctx->load_settings_raw('min_width', $min_width . 'px');
        }

        // button display
        $display = $res_ctx->get_shortcode_att('display');
        if( $display == 'default' || $display == '' ) {
            $res_ctx->load_settings_raw('display_default', 1);
        } else if( $display == 'inline' ) {
            $res_ctx->load_settings_raw('display_inline', 1);
        } else if ( $display == 'full' ) {
            $res_ctx->load_settings_raw('display_full', 1);
        }

        // button horizontal align
        $horiz_align = $res_ctx->get_shortcode_att('horiz_align');
        if( $horiz_align == 'content-horiz-left' ) {
            $res_ctx->load_settings_raw('horiz_align', 'flex-start');
        } else if( $horiz_align == 'content-horiz-center' ) {
            $res_ctx->load_settings_raw('horiz_align', 'center');
        } else if( $horiz_align == 'content-horiz-right' ) {
            $res_ctx->load_settings_raw('horiz_align', 'flex-end');
        }


        // button icon size
        $icon_size = $res_ctx->get_shortcode_att('icon_size');
        $res_ctx->load_settings_raw('icon_size', $icon_size);
        if( $icon_size != '' && is_numeric( $icon_size ) ) {
            $res_ctx->load_settings_raw('icon_size', $icon_size . 'px');
        }

        // button icon space
        $icon_pos = $res_ctx->get_shortcode_att('icon_pos');
        $icon_space = $res_ctx->get_shortcode_att('icon_space');
        if( $icon_pos == 'before' ) {
            if( $icon_space != '' ){
                if( is_numeric( $icon_space ) ) {
                    $res_ctx->load_settings_raw('icon_space', '0 ' . $icon_space . 'px 0 0');
                } else {
                    $res_ctx->load_settings_raw('icon_space', '0 ' . $icon_space . '0 0');
                }
            } else {
                $res_ctx->load_settings_raw('icon_space', '0 10px 0 0');
            }
        } else {
            if( $icon_space != '' ){
                if( is_numeric( $icon_space ) ) {
                    $res_ctx->load_settings_raw('icon_space', '0 0 0 ' . $icon_space . 'px');
                } else {
                    $res_ctx->load_settings_raw('icon_space', '0 0 0 ' . $icon_space);
                }
            } else {
                $res_ctx->load_settings_raw('icon_space', '0 0 0 10px');
            }
        }

        // button icon vertical align
        $res_ctx->load_settings_raw('icon_align', $res_ctx->get_shortcode_att('icon_align') . 'px');




        // notifications border radius
        $notif_radius = $res_ctx->get_shortcode_att('notif_radius');
        $res_ctx->load_settings_raw( 'notif_radius', $notif_radius );
        if( $notif_radius != '' && is_numeric( $notif_radius ) ) {
            $res_ctx->load_settings_raw( 'notif_radius', $notif_radius . 'px' );
        }



        /*-- COLORS -- */
        $accent_color = $res_ctx->get_shortcode_att('accent_color');
        $res_ctx->load_settings_raw( 'accent_color', $accent_color );

        $res_ctx->load_settings_raw( 'btn_color', $res_ctx->get_shortcode_att('btn_color') );
        $res_ctx->load_settings_raw( 'btn_color_h', $res_ctx->get_shortcode_att('btn_color_h') );
        $res_ctx->load_settings_raw( 'btn_bg_h', $res_ctx->get_shortcode_att('btn_bg_h') );

        $notif_info_color = $res_ctx->get_shortcode_att('notif_info_color');
        $res_ctx->load_settings_raw( 'notif_info_color', $notif_info_color );
        if( !empty( $notif_info_color ) ) {
            $res_ctx->load_settings_raw('notif_info_bg', td_util::hex2rgba($notif_info_color, 0.08));
        }

        $notif_succ_color = $res_ctx->get_shortcode_att('notif_succ_color');
        $res_ctx->load_settings_raw( 'notif_succ_color', $notif_succ_color );
        if( !empty( $notif_succ_color ) ) {
            $res_ctx->load_settings_raw('notif_succ_bg', td_util::hex2rgba($notif_succ_color, 0.1));
        }

        $notif_error_color = $res_ctx->get_shortcode_att('notif_error_color');
        $res_ctx->load_settings_raw( 'notif_error_color', $notif_error_color );
        if( !empty( $notif_error_color ) ) {
            $res_ctx->load_settings_raw('notif_error_bg', td_util::hex2rgba($notif_error_color, 0.12));
        }



        /*-- FONTS -- */
        $res_ctx->load_font_settings( 'f_text' );

    }

    function __construct() {
        parent::disable_loop_block_features();
    }

    function render( $atts, $content = null ) {

        parent::render( $atts );

        $in_composer = td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax();

        // Form group class
        $form_group_class = $this->get_att('group_class');
        if( $form_group_class != '' ) {
            $form_group_class = '.' . $form_group_class . ' ';
        }

        // Current user
        $current_user = wp_get_current_user();
        $current_user_id = $current_user->ID;
        $is_current_user_admin = in_array('administrator', $current_user->roles);


        /*-- CREATE POST FROM FORM SETTINGS -- */
        // The ID of the post we are currently on
        $curr_post_id = '';
        if( $this->get_att('link_to_curr_post') != '' ) {
            $curr_template_type = tdb_state_template::get_template_type();

            if( $curr_template_type == 'single' || $curr_template_type == 'cpt' ) {
                global $tdb_state_single;
                if ( !empty($tdb_state_single->get_wp_query()) ){
                    $curr_post_id = $tdb_state_single->get_wp_query()->post->ID;
                }
            }
        }

        // The post ID from the url
        $save_into_post_id = '';
        if ( isset($_GET['post_id']) && !$in_composer ) {
            $_get_post = get_post($_GET['post_id']);

            if( $_get_post && ( $_get_post->post_author == $current_user_id || $is_current_user_admin ) ) {
                $save_into_post_id = $_GET['post_id'];
            }
        }

        // Post type
        $post_type = $this->get_att( 'post_type' );
        if ( $post_type == '' ) {
            $post_type = 'post';
        }

        // Post format
        $post_format = $this->get_att( 'post_format' );
        if ( $post_format == '' ) {
            $post_format = 'standard';
        }

        // Post status
        $post_status = $this->get_att( 'post_status' );
        if ( $post_status == '' ) {
            $post_status = 'draft';
        }

        // Custom title field
        $custom_title_field = $this->get_att( 'custom_title_field' );

        // Success URL
        $success_url = $this->get_att( 'success_url' );


        /*-- CURRENT USER INFO -- */
        // Current user plan limits
        $limit_from = $this->get_att('limit_from') != '' ? $this->get_att('limit_from') : 'shortcode';
        $add_new_posts_limit = -1;
        $limit_reached = false;

        if( is_user_logged_in() ) {

            if( $limit_from == 'shortcode' || !defined( 'TD_SUBSCRIPTION' ) ) {

                $add_new_posts_limit = $this->get_att('limit_def') != '' ? $this->get_att('limit_def') : -1;

                $current_user_posts = get_posts(array(
                    'post_type' => $post_type,
                    'post_status' => array('publish', 'draft', 'private'),
                    'numberposts' => -1,
                    'author' => $current_user_id,
                ));

                if( $add_new_posts_limit > -1 && ( count( $current_user_posts ) >= $add_new_posts_limit ) ) {
                    $limit_reached = true;
                }

            } else {

                $add_new_posts_limit = 0;

                if( defined( 'TD_SUBSCRIPTION' ) && method_exists( 'tds_util', 'get_user_subscriptions' ) ) {
                    $user_subscriptions = tds_util::get_user_subscriptions($current_user_id, null, array('active', 'free'));
                    if( $user_subscriptions ) {
                        foreach( $user_subscriptions as $user_subscription ) {
                            if( isset( $user_subscription['plan_posts_remaining'] ) ) {
                                $plan_posts_remaining = unserialize($user_subscription['plan_posts_remaining']);
            
                                if( !empty( $plan_posts_remaining ) ) {
                                    foreach( $plan_posts_remaining as $remaining_post_type => $remaining_posts ) {
                                        if( $remaining_post_type != $post_type ) {
                                            continue;
                                        }
            
                                        if( $remaining_posts == '' ) {
                                            continue;
                                        }
            
                                        $add_new_posts_limit += $remaining_posts;
                                    }
                                }
                            }
                        }
                    }

                    if( $add_new_posts_limit == 0 ) {
                        $limit_reached = true;
                    }

                }

            }

        }


        // Limit notification message
        $limit_notif = rawurldecode( base64_decode( strip_tags( $this->get_att('limit_notif') ) ) );
        if( $limit_notif == '' ) {
            $limit_notif = __td( 'You have reached your limit of posting new articles.', TD_THEME_NAME );
        }


        /*-- ENABLE POST CREATE -- */
        $authenticated_users = $this->get_att('authenticated_users');
        $enable_post_create_att = $this->get_att('enable_post_create');
        $enable_post_create_for_guests = !( $authenticated_users != '' && !is_user_logged_in() );

	    // $in_composer set $td_posts_form_submit_enable_post_create meta on current template
	    if ( $in_composer ) {

		    // get the current page/tpl id
		    $post_id = tdb_util::get_get_val('post_id');

		    // set the current page/tpl id
		    $curr_tpl_id = $post_id ?: '';

            // set enable_post_create status
            $td_posts_form_submit_enable_post_create = $enable_post_create_att != '';

            // set enable_form_emailing status
            $td_posts_form_submit_enable_form_emailing = $this->get_att( 'enable_form_emailing' ) != '';

		    // add the td_posts_form_submit_ enable_post_create/enable_form_emailing custom meta fields, using these fields we will enable/disable post create/form emailing on tdb_posts_form_on_submit ajax handler
		    // @see tdb_ajax > tdb_posts_form_on_submit
		    update_post_meta( $curr_tpl_id, 'td_posts_form_submit_enable_post_create', $td_posts_form_submit_enable_post_create );
		    update_post_meta( $curr_tpl_id, 'td_posts_form_submit_enable_form_emailing', $td_posts_form_submit_enable_form_emailing );

	    } else {

            // set the current page/tpl id
            if( tdb_state_template::get_template_type() !== NULL ) {
                $curr_tpl_id = tdb_state_template::get_wp_query()->post->ID;
            } else {
                global $wp_query;
                $curr_tpl_id = $wp_query ? $wp_query->post->ID : '';
            }

        }

        //echo '<pre class="td-container">';
        //echo '$curr_tpl_id: ';
        //print_r($curr_tpl_id);
        //echo '<br>$td_posts_form_submit_enable post_create: ';
        //print_r( get_post_meta( $curr_tpl_id, 'td_posts_form_submit_enable_post_create', true ) );
        //echo '<br>$td_posts_form_submit_enable form_emailing: ';
        //print_r( get_post_meta( $curr_tpl_id, 'td_posts_form_submit_enable_form_emailing', true ) );
        //echo '</pre>';

        $enable_post_create = false;
        if( $save_into_post_id != '' ||
            (
                $enable_post_create_att != '' &&
                $enable_post_create_for_guests &&
                ( $is_current_user_admin || !$limit_reached )
            )
        ) {
            $enable_post_create = true;
        }

        $cf_input_email_list = $this->get_att('cf_input_email_list') !== '' ? $this->get_att('cf_input_email_list') : '' ;
        $email_list = $this->get_att('list') !== '' ? $this->get_att('list') : '';


        /*-- SEND EMAIL FROM FORM SETTINGS -- */
        $enable_form_emailing = $this->get_att( 'enable_form_emailing' ) != '';
        $send_to_admin = $this->get_att( 'send_to_admin' ) != '';
        $send_to_author = $this->get_att( 'send_to_author' ) != '';
        $send_to_custom_email = $this->get_att( 'send_to_custom_email' );
        $email_subject = $this->get_att( 'email_subject' );

        $send_to_email_field = $this->get_att( 'send_to_email_field' );
        $email_field_value = td_util::get_custom_field_value_from_string('{cf_' . $send_to_email_field . '}');

        // captcha
	    $buffy_captcha = '';
	    $tds_captcha = td_util::get_option('tds_captcha');
	    $tds_captcha_site_key = td_util::get_option('tds_captcha_site_key');

	    if ( $tds_captcha == 'show' && $tds_captcha_site_key != '' ) {
		    $buffy_captcha .= '<input type="hidden" id="g-recaptcha" name="gRecaptchaResponse" data-sitekey="' . $tds_captcha_site_key . '" >';
	    }

        /*-- SUBMIT BUTTON -- */
        // text
        $submit_btn_txt = $this->get_att('submit_txt');
        if( $submit_btn_txt == '' ) {
            $submit_btn_txt = __td( 'Submit', TD_THEME_NAME );
        }
        if( $save_into_post_id != '' ) {
            $submit_btn_txt = $this->get_att('update_txt');
            if( $submit_btn_txt == '' ) {
                $submit_btn_txt = __td( 'Update', TD_THEME_NAME );
            }
        }
        // icon
        $icon = $this->get_icon_att( 'tdicon' );
        $tdicon_data = '';
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $tdicon_data = 'data-td-svg-icon="' . $this->get_att('tdicon') . '"';
        }
        $buffy_icon = '';
        if ( !empty( $icon ) ) {
            if( base64_encode( base64_decode( $icon ) ) == $icon ) {
                $buffy_icon .= '<span class="tds-pb-icon tds-pb-icon-svg" ' . $tdicon_data . '>' . base64_decode( $icon ) . '</span>';
            } else {
                $buffy_icon .= '<i class="tds-pb-icon ' . $icon . '"></i>';
            }
        }
        // icon position
        $icon_pos = $this->get_att('icon_pos' );

        $required_field_error = __td( 'This field is required!', TD_THEME_NAME );
        $blank_fields_msg = __td( 'Some required fields have been left blank.', TD_THEME_NAME );
        $valid_email_msg = __td( 'Please enter a valid email address.', TD_THEME_NAME );
        $lower_msg = __td( 'The number cannot be lower than', TD_THEME_NAME );
        $higher_msg = __td( 'The number cannot be higher than', TD_THEME_NAME );


        $buffy = '';

        if( (
                ( !$enable_post_create_att && !$enable_form_emailing ) ||
                ( !$enable_form_emailing && $enable_post_create_att != '' && !$enable_post_create_for_guests )
            ) &&
            !( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() )
        ) {
            return $buffy;
        }

        $buffy .= '<div class="' . $this->get_block_classes() . '" ' . $this->get_block_html_atts() . '>';

            $buffy .= $this->get_block_css(); // get block css

            $buffy .= $this->get_block_js(); // get block js


            $buffy .= '<div class="tdb-block-inner td-fix-index tdb-s-form tdb-s-content">';
                if( !$enable_post_create && !$enable_form_emailing ) {
                    $buffy .= '<div class="tdb-s-notif tdb-s-notif-info tdb-fs-limit-reached"><div class="tdb-s-notif-descr">' . $limit_notif . '</div></div>';
                } else {
                    $buffy .= '<div class="tdb-s-form-footer">';
                        $buffy .= $buffy_captcha;
                        $buffy .= '<button class="tdb-s-btn">';
                            if ( $icon_pos == 'before' ) {
                                $buffy .= $buffy_icon;
                            }

                            $buffy .= $submit_btn_txt;

                            if ( $icon_pos == '' ) {
                                $buffy .= $buffy_icon;
                            }
                        $buffy .= '</button>';
                    $buffy .= '</div>';
                }
            $buffy .= '</div>';


            if( !( tdc_state::is_live_editor_iframe() || tdc_state::is_live_editor_ajax() ) ) {
                ob_start();
                ?>
                <script>
                    /* global jQuery:{} */
                    jQuery(document).ready( function($) {

                        let uid = '<?php echo $this->block_uid ?>',
                            $blockObj = jQuery('.<?php echo $this->block_uid ?>'),
                            $submitBtn = $blockObj.find('.tdb-s-btn');

                        let tdbCustomFormItem = new tdbCustomForms.item();
                        // block uid
                        tdbCustomFormItem.uid = uid;
                        // block object
                        tdbCustomFormItem.blockObj = $blockObj;
                        // form type
                        tdbCustomFormItem.formType = 'post';
                        // form group class
                        tdbCustomFormItem.formGroupClass = '<?php echo $form_group_class ?>';
                        // current user ID
                        tdbCustomFormItem.currentUserID = '<?php echo $current_user_id ?>';
                        // post form specific variables
                        tdbCustomFormItem.successURL = '<?php echo $success_url ?>';
                        tdbCustomFormItem.customPostTitleField = '<?php echo $custom_title_field ?>';
                        tdbCustomFormItem.cfInputEmailList = '<?php echo $cf_input_email_list ?>';
                        tdbCustomFormItem.emailList = '<?php echo $email_list ?>';
                        tdbCustomFormItem.enablePostCreate = '<?php echo $enable_post_create ?>';
                        tdbCustomFormItem.postID = '<?php echo $save_into_post_id ?>';
                        tdbCustomFormItem.postType = '<?php echo $post_type ?>';
                        tdbCustomFormItem.postFormat = '<?php echo $post_format ?>';
                        tdbCustomFormItem.postStatus = '<?php echo $post_status ?>';
                        tdbCustomFormItem.linkToPostID = '<?php echo $curr_post_id ?>';
                        tdbCustomFormItem.enableEmailSubmit = '<?php echo $enable_form_emailing ?>';
                        tdbCustomFormItem.sendEmailToAdmin = '<?php echo $send_to_admin ?>';
                        tdbCustomFormItem.sendEmailToAuthor = '<?php echo $send_to_author ?>';
                        tdbCustomFormItem.sendEmailToCustomAddr = '<?php echo $send_to_custom_email ?>';
                        tdbCustomFormItem.sendEmailToEmailFromField = '<?php echo ( filter_var($email_field_value, FILTER_VALIDATE_EMAIL) ? $email_field_value : '' ) ?>';
                        tdbCustomFormItem.emailSubject = '<?php echo $email_subject ?>';
                        tdbCustomFormItem.required_field_error = '<?php echo $required_field_error ?>';
                        tdbCustomFormItem.blank_fields_msg = '<?php echo $blank_fields_msg ?>';
                        tdbCustomFormItem.valid_email_msg = '<?php echo $valid_email_msg ?>';
                        tdbCustomFormItem.lower_msg = '<?php echo $lower_msg ?>';
                        tdbCustomFormItem.higher_msg = '<?php echo $higher_msg ?>';

                        tdbCustomFormItem._nonce = '<?php echo wp_create_nonce(__CLASS__); ?>';

                        // set the id of the current template on which the shortcode is rendered
                        tdbCustomFormItem.tpl_id = '<?php echo $curr_tpl_id ?>';

                        tdbCustomForms.addItem(tdbCustomFormItem);

                    });

                </script>
                <?php
                td_js_buffer::add_to_footer( "\n" . td_util::remove_script_tag( ob_get_clean() ) );
            }

        $buffy .= '</div>';

        return $buffy;
    }


}