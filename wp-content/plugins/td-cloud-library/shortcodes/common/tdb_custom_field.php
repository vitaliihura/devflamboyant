<?php

/**
 * Class tdb_single_custom_field
 */

class tdb_single_custom_field extends td_block {

    static $style_selector = '';
	static $style_atts_prefix = '';
	static $style_atts_uid = '';
	static $module_template_part_index = '';


    /**
     * Disable loop block features. This block does not use a loop and it doesn't need to run a query.
     */
    function __construct() {

        /* --
        -- Check to see if the element is being called into a tdb module template
        -- */
        if( td_global::get_in_tdb_module_template() ) {

            global $tdb_module_template_params;


            /* -- Set the current module template part index, used for ensuring -- */
		    /* -- uniqueness between template parts of the same type -- */
            if( isset( $tdb_module_template_params['shortcodes'][get_class($this)] ) ) {
                $tdb_module_template_params['shortcodes'][get_class($this)]++;
            } else {
                $tdb_module_template_params['shortcodes'][get_class($this)] = 0;
            }

            self::$module_template_part_index = $tdb_module_template_params['shortcodes'][get_class($this)];

            // In composer, add an extra random string to ensure uniqueness
            if( tdc_state::is_live_editor_ajax() || tdc_state::is_live_editor_iframe() || is_admin() ) {
                $uniquid = uniqid();
                $newuniquid = '';
                while ( strlen( $newuniquid ) < 3 ) {
                    $newuniquid .= $uniquid[rand(0, 12)];
                }

                self::$module_template_part_index .= '_' . $newuniquid;
            }


            /* -- Set the template part unique style vars -- */
            // Set the style atts prefix
            self::$style_atts_prefix = 'tdb_mts_';

            // Set the style atts uid
            self::$style_atts_uid = $tdb_module_template_params['template_class'] . '_' . get_class($this) . '_' . self::$module_template_part_index;

        } else {

	        // reset static properties
	        self::$style_selector = '';
	        self::$style_atts_prefix = '';
	        self::$style_atts_uid = '';
	        self::$module_template_part_index = '';

        }

        parent::disable_loop_block_features();

    }


    public function get_custom_css() {

		$style_atts_prefix = self::$style_atts_prefix;
		$style_atts_uid = self::$style_atts_uid;


        /* -- Set the style selector -- */
        $style_selector = '';

        $in_composer = td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax();
        $in_element = td_global::get_in_element();
		if( $in_element && $in_composer ) {
			$style_selector .= 'tdc-row-composer .tdc-column-composer .';
		} else if( $in_element || $in_composer ) {
			$style_selector .= 'tdc-row .tdc-column .';
		}

        // Check to see if the element is being called into a tdb module template
        if( td_global::get_in_tdb_module_template() ) {
            global $tdb_module_template_params;

            $style_selector = $tdb_module_template_params['template_class'] . ' .' . $style_selector .  get_class($this) . '_' . self::$module_template_part_index;
        } else {
            $style_selector .= $this->block_uid;
        }


        $compiled_css = '';

        $raw_css =
            "<style>

                /* @style_general_tdb_single_custom_field */
                .tdb_single_custom_field {
                    font-size: 14px;
                    line-height: 1.6;
                }
                .tdb_single_custom_field .tdb-block-inner {
                    display: flex;
                }
                .tdb_single_custom_field .tdb-cf-icon {
                    align-self: center;
                    position: relative;
                }
                .tdb_single_custom_field .tdb-cf-icon-svg svg {
                    display: block;
                    width: 14px;
                    height: auto;
                }
                .tdb_single_custom_field .tdb-sacff-img-wrapp {
                    max-width: 100%;
                }
                .tdb_single_custom_field .tdb-sacff-img {
                    display: block;
                    width: 100%;
                    height: auto;
                }
                .tdb_single_custom_field .tdb-sacff-terms {
                    display: flex;
                    flex-wrap: wrap;
                }
                .tdb_single_custom_field .tdb-sacff-term {
                    position: relative;
                    margin: 0 5px 0 0;
                    padding: 5px 8px;
                    font-size: 12px;
                    line-height: 1;
                    color: #fff;
                }
                .tdb_single_custom_field .tdb-sacff-term-bg {
                    position: absolute;
                    top: 0;
                    left: 0;
                    background-color: #222;
                    border: 1px solid #222;
                    width: 100%;
                    height: 100%;
                    z-index: -1;
                }
                .tdb_single_custom_field .tdb-sacff-term:hover .tdb-sacff-term-bg {
                    opacity: .9;
                }
                .tdb_single_custom_field.tdb-sacff-type-textarea .tdb-sacff-txt {
                    white-space: pre-wrap;
                }
                .tdb_single_custom_field.tdb-sacff-type-textarea .tdb-sacff-txt {
                    white-space: pre-wrap;
                }
                .tdb-sacff-list-icon li {
                  list-style: none;
                  margin-left: 0;
                }
                
                /* @" . $style_atts_prefix . "display$style_atts_uid */
                body .$style_selector .tdb-block-inner {
                    flex-direction: @" . $style_atts_prefix . "display$style_atts_uid;
                }
                
                /* @" . $style_atts_prefix . "make_inline$style_atts_uid */
                body .$style_selector {
                    display: inline-block;
                }
                
                /* @" . $style_atts_prefix . "horiz_align$style_atts_uid */
                body .$style_selector .tdb-block-inner {
                    justify-content: @" . $style_atts_prefix . "horiz_align$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "horiz_align_txt$style_atts_uid */
                body .$style_selector .tdb-sacff-txt,
                body .$style_selector .tdb-cf-add-txt {
                    text-align: @" . $style_atts_prefix . "horiz_align_txt$style_atts_uid;
                }
                
                /* @" . $style_atts_prefix . "vert_align$style_atts_uid */
                body .$style_selector .tdb-block-inner {
                    align-items: @" . $style_atts_prefix . "vert_align$style_atts_uid;
                }
                
                /* @" . $style_atts_prefix . "add_txt_width$style_atts_uid */
                body .$style_selector .tdb-cf-add-txt {
                    width: @" . $style_atts_prefix . "add_txt_width$style_atts_uid;
                }
                
                /* @" . $style_atts_prefix . "add_txt_space$style_atts_uid */
                body .$style_selector .tdb-cf-add-txt {
                    margin: @" . $style_atts_prefix . "add_txt_space$style_atts_uid;
                }
                
                /* @" . $style_atts_prefix . "icon_size$style_atts_uid */
                body .$style_selector i.tdb-cf-icon {
                    font-size: @" . $style_atts_prefix . "icon_size$style_atts_uid;
                }
                body .$style_selector .tdb-cf-icon-svg svg {
                    width: @" . $style_atts_prefix . "icon_size$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "icon_space$style_atts_uid */
                body .$style_selector .tdb-cf-icon {
                    margin: @" . $style_atts_prefix . "icon_space$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "icon_align$style_atts_uid */
                body .$style_selector .tdb-cf-icon {
                    top: @" . $style_atts_prefix . "icon_align$style_atts_uid;
                }
                
                
                /* @" . $style_atts_prefix . "img_width$style_atts_uid */
                body .$style_selector .tdb-sacff-img-wrapp {
                    width: @" . $style_atts_prefix . "img_width$style_atts_uid;
                }                
                
                /* @" . $style_atts_prefix . "tax_padding$style_atts_uid */
                body .$style_selector .tdb-sacff-term {
                    padding: @" . $style_atts_prefix . "tax_padding$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "tax_space$style_atts_uid */
                body .$style_selector .tdb-sacff-term {
                    margin: @" . $style_atts_prefix . "tax_space$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "tax_skew$style_atts_uid */
                body .$style_selector .tdb-sacff-term-bg {
                    transform: skew(@" . $style_atts_prefix . "tax_skew$style_atts_uid);
                    -webkit-transform: skew(@" . $style_atts_prefix . "tax_skew$style_atts_uid);
                }
                /* @" . $style_atts_prefix . "tax_border$style_atts_uid */
                body .$style_selector .tdb-sacff-term-bg {
                    border-width: @" . $style_atts_prefix . "tax_border$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "tax_radius$style_atts_uid */
                body .$style_selector .tdb-sacff-term-bg {
                    border-radius: @" . $style_atts_prefix . "tax_radius$style_atts_uid;
                }
                
                
                /* @" . $style_atts_prefix . "txt_color$style_atts_uid */
                body .$style_selector,
                body .$style_selector .tdb-sacff-txt a,
                body .$style_selector i.tdb-cf-icon {
                    color: @" . $style_atts_prefix . "txt_color$style_atts_uid;
                }
                body .$style_selector .tdb-cf-icon-svg svg {
                    fill: @" . $style_atts_prefix . "txt_color$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "txt_color_h$style_atts_uid */
                body .$style_selector .tdb-sacff-txt a:hover {
                    color: @" . $style_atts_prefix . "txt_color_h$style_atts_uid;
                }
                
                /* @" . $style_atts_prefix . "list_color$style_atts_uid */
                body .$style_selector .tdb-sacff-list li,
                .tdb-sacff-list i.tdb-cf-icon {
                    color: @" . $style_atts_prefix . "list_color$style_atts_uid;
                }
                
                /* @" . $style_atts_prefix . "add_txt_color$style_atts_uid */
                body .$style_selector .tdb-cf-add-txt {
                    color: @" . $style_atts_prefix . "add_txt_color$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "icon_color$style_atts_uid */
                body .$style_selector i.tdb-cf-icon {
                    color: @" . $style_atts_prefix . "icon_color$style_atts_uid;
                }
                body .$style_selector .tdb-cf-icon-svg svg {
                    fill: @" . $style_atts_prefix . "icon_color$style_atts_uid;
                }
                
                /* @" . $style_atts_prefix . "tax_color$style_atts_uid */
                body .$style_selector .tdb-sacff-term {
                    color: @" . $style_atts_prefix . "tax_color$style_atts_uid !important;
                }
                /* @" . $style_atts_prefix . "tax_color_h$style_atts_uid */
                body .$style_selector .tdb-sacff-term:hover {
                    color: @" . $style_atts_prefix . "tax_color_h$style_atts_uid !important;
                }
                /* @" . $style_atts_prefix . "tax_bg_solid$style_atts_uid */
                body .$style_selector .tdb-sacff-term-bg {
                    background-color: @" . $style_atts_prefix . "tax_bg_solid$style_atts_uid !important;
                }
                /* @" . $style_atts_prefix . "tax_bg_gradient$style_atts_uid */
                body .$style_selector .tdb-sacff-term-bg {
                    @" . $style_atts_prefix . "tax_bg_gradient$style_atts_uid
                }
                /* @" . $style_atts_prefix . "tax_bg_h_solid$style_atts_uid */
                body .$style_selector .tdb-sacff-term:hover .tdb-sacff-term-bg {
                    background-color: @" . $style_atts_prefix . "tax_bg_h_solid$style_atts_uid !important;
                }
                /* @" . $style_atts_prefix . "tax_bg_h_gradient$style_atts_uid */
                body .$style_selector .tdb-sacff-term:hover .tdb-sacff-term-bg {
                    @" . $style_atts_prefix . "tax_bg_h_gradient$style_atts_uid
                }
                /* @" . $style_atts_prefix . "tax_border_color_solid$style_atts_uid */
                body .$style_selector .tdb-sacff-term-bg {
                    border-color: @" . $style_atts_prefix . "tax_border_color_solid$style_atts_uid !important;
                }
                /* @" . $style_atts_prefix . "tax_border_color_params */
                body .$style_selector .tdb-sacff-term-bg {
                    border-image: linear-gradient(@" . $style_atts_prefix . "tax_border_color_params$style_atts_uid);
				    border-image: -webkit-linear-gradient(@" . $style_atts_prefix . "tax_border_color_params$style_atts_uid);
				    border-image-slice: 1;
				    transition: none;
                }
                body .$style_selector .tdb-sacff-term:hover .tdb-sacff-term-bg {
                    border-image: linear-gradient(@" . $style_atts_prefix . "tax_border_color_h$style_atts_uid, @" . $style_atts_prefix . "tax_border_color_h$style_atts_uid);
				    border-image: -webkit-linear-gradient(@" . $style_atts_prefix . "tax_border_color_h$style_atts_uid, @" . $style_atts_prefix . "tax_border_color_h$style_atts_uid);
				    border-image-slice: 1;
				    transition: none;
                }
                /* @" . $style_atts_prefix . "tax_border_color_h$style_atts_uid */
                body .$style_selector .tdb-sacff-term-bg {
                    border-color: @" . $style_atts_prefix . "tax_border_color_h$style_atts_uid !important;
                }
                
                
                /* @" . $style_atts_prefix . "f_txt$style_atts_uid */
                body .$style_selector {
                    @" . $style_atts_prefix . "f_txt$style_atts_uid
                }
                /* @" . $style_atts_prefix . "f_list$style_atts_uid */
                body .$style_selector li {
                    @" . $style_atts_prefix . "f_list$style_atts_uid
                }
                /* @" . $style_atts_prefix . "f_add$style_atts_uid */
                body .$style_selector .tdb-cf-add-txt {
                    @" . $style_atts_prefix . "f_add$style_atts_uid
                }
                /* @" . $style_atts_prefix . "f_tax$style_atts_uid */
                body .$style_selector .tdb-sacff-term {
                    @" . $style_atts_prefix . "f_tax$style_atts_uid
                }
				
			</style>";


        $td_css_res_compiler = new td_css_res_compiler( $raw_css );
        $td_css_res_compiler->load_settings( __CLASS__ . '::cssMedia', $this->get_all_atts() );

        $compiled_css .= $td_css_res_compiler->compile_css();
        return $compiled_css;
    }

    static function cssMedia( $res_ctx ) {

		$style_atts_prefix = self::$style_atts_prefix;
		$style_atts_uid = self::$style_atts_uid;

        $res_ctx->load_settings_raw( 'style_general_tdb_single_custom_field', 1 );

        /*-- LAYOUT -- */
        $display = $res_ctx->get_shortcode_att('display');
        if( $display == '' ) {
            $display = 'row';
        }
        $res_ctx->load_settings_raw($style_atts_prefix . 'display' . $style_atts_uid, $display);

        // make inline
        $res_ctx->load_settings_raw($style_atts_prefix . 'make_inline' . $style_atts_uid, $res_ctx->get_shortcode_att('make_inline'));

        // horizontal & vertical align
        $horiz_align = $res_ctx->get_shortcode_att('horiz_align');
        $vert_align = $res_ctx->get_shortcode_att('vert_align');

        if( $horiz_align == '' || $horiz_align == 'content-horiz-left' ) {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'horiz_align_txt' . $style_atts_uid, 'left' );
        } else if( $horiz_align == 'content-horiz-center' ) {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'horiz_align_txt' . $style_atts_uid, 'center' );
        } else if( $horiz_align == 'content-horiz-right' ) {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'horiz_align_txt' . $style_atts_uid, 'right' );
        }

        if( $display == 'row' ) {
            if( $horiz_align == '' || $horiz_align == 'content-horiz-left' ) {
                $res_ctx->load_settings_raw( $style_atts_prefix . 'horiz_align' . $style_atts_uid, 'flex-start' );
            } else if( $horiz_align == 'content-horiz-center' ) {
                $res_ctx->load_settings_raw( $style_atts_prefix . 'horiz_align' . $style_atts_uid, 'center' );
            } else if( $horiz_align == 'content-horiz-right' ) {
                $res_ctx->load_settings_raw( $style_atts_prefix . 'horiz_align' . $style_atts_uid, 'flex-end' );
            }

            if( $vert_align == '' || $vert_align == 'content-vert-baseline' ) {
                $res_ctx->load_settings_raw( $style_atts_prefix . 'vert_align' . $style_atts_uid, 'baseline' );
            } else if( $vert_align == 'content-vert-top' ) {
                $res_ctx->load_settings_raw( $style_atts_prefix . 'vert_align' . $style_atts_uid, 'flex-start' );
            } else if( $vert_align == 'content-vert-center' ) {
                $res_ctx->load_settings_raw( $style_atts_prefix . 'vert_align' . $style_atts_uid, 'center' );
            } else if( $vert_align == 'content-vert-bottom' ) {
                $res_ctx->load_settings_raw( $style_atts_prefix . 'vert_align' . $style_atts_uid, 'flex-end' );
            }
        } else if ( $display == 'column' ) {
            if( $horiz_align == '' || $horiz_align == 'content-horiz-left' ) {
                $res_ctx->load_settings_raw( $style_atts_prefix . 'vert_align' . $style_atts_uid, 'flex-start' );
            } else if( $horiz_align == 'content-horiz-center' ) {
                $res_ctx->load_settings_raw( $style_atts_prefix . 'vert_align' . $style_atts_uid, 'center' );
            } else if( $horiz_align == 'content-horiz-right' ) {
                $res_ctx->load_settings_raw( $style_atts_prefix . 'vert_align' . $style_atts_uid, 'flex-end' );
            }

            if( $vert_align == '' || $vert_align == 'content-vert-baseline' ) {
                $res_ctx->load_settings_raw( $style_atts_prefix . 'horiz_align' . $style_atts_uid, 'baseline' );
            } else if( $vert_align == 'content-vert-top' ) {
                $res_ctx->load_settings_raw( $style_atts_prefix . 'horiz_align' . $style_atts_uid, 'flex-start' );
            } else if( $vert_align == 'content-vert-center' ) {
                $res_ctx->load_settings_raw( $style_atts_prefix . 'horiz_align' . $style_atts_uid, 'center' );
            } else if( $vert_align == 'content-vert-right' ) {
                $res_ctx->load_settings_raw( $style_atts_prefix . 'horiz_align' . $style_atts_uid, 'flex-end' );
            }
        }


        // additional text width
        $add_txt_space = $res_ctx->get_shortcode_att('add_txt_space');
        $add_txt_space .= $add_txt_space != '' && is_numeric( $add_txt_space ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'add_txt_space' . $style_atts_uid, $add_txt_space );

        // additional text space
        $add_txt_width = $res_ctx->get_shortcode_att('add_txt_width');
        $add_txt_width .= $add_txt_width != '' && is_numeric( $add_txt_width ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'add_txt_width' . $style_atts_uid, $add_txt_width );


        // icon size
        $icon_size = $res_ctx->get_shortcode_att('icon_size');
        $icon_size .= $icon_size != '' && is_numeric( $icon_size ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'icon_size' . $style_atts_uid, $icon_size );

        // icon space
        $icon_space = $res_ctx->get_shortcode_att('icon_space');
        $icon_space .= $icon_space != '' && is_numeric( $icon_space ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'icon_space' . $style_atts_uid, $icon_space );

        // icon align
        $res_ctx->load_settings_raw($style_atts_prefix . 'icon_align' . $style_atts_uid, $res_ctx->get_shortcode_att('icon_align') . 'px');


        // image width
        $img_width = $res_ctx->get_shortcode_att('img_width');
        $img_width .= $img_width != '' && is_numeric( $img_width ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'img_width' . $style_atts_uid, $img_width );


        // tax padding
        $tax_padding = $res_ctx->get_shortcode_att('tax_padding');
        $tax_padding .= $tax_padding != '' && is_numeric( $tax_padding ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'tax_padding' . $style_atts_uid, $tax_padding );

        // tax_space
        $tax_space = $res_ctx->get_shortcode_att('tax_space');
        $tax_space .= $tax_space != '' && is_numeric( $tax_space ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'tax_space' . $style_atts_uid, $tax_space );

        // tax_skew
        $tax_skew = $res_ctx->get_shortcode_att('tax_skew');
        if ( $tax_skew != 0 || !empty($tax_skew) ) {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'tax_skew' . $style_atts_uid, $tax_skew . 'deg' );
        }

        // tax border
        $res_ctx->load_settings_raw( $style_atts_prefix . 'tax_border' . $style_atts_uid, $res_ctx->get_shortcode_att('tax_border') . 'px' );

        // tax_radius
        $tax_radius = $res_ctx->get_shortcode_att('tax_radius');
        if ( $tax_radius != 0 || !empty($tax_radius) ) {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'tax_radius' . $style_atts_uid, $tax_radius . 'px' );
        }


        /*-- COLORS -- */
        $res_ctx->load_settings_raw($style_atts_prefix . 'txt_color' . $style_atts_uid, $res_ctx->get_shortcode_att('txt_color'));
        $res_ctx->load_settings_raw($style_atts_prefix . 'txt_color_h' . $style_atts_uid, $res_ctx->get_shortcode_att('txt_color_h'));
        $res_ctx->load_settings_raw($style_atts_prefix . 'add_txt_color' . $style_atts_uid, $res_ctx->get_shortcode_att('add_txt_color'));
        $res_ctx->load_settings_raw($style_atts_prefix . 'icon_color' . $style_atts_uid, $res_ctx->get_shortcode_att('icon_color'));

        $res_ctx->load_settings_raw($style_atts_prefix . 'list_color' . $style_atts_uid, $res_ctx->get_shortcode_att('list_color'));

        $res_ctx->load_settings_raw( $style_atts_prefix . 'tax_color' . $style_atts_uid, $res_ctx->get_shortcode_att('tax_color') );
        $res_ctx->load_settings_raw( $style_atts_prefix . 'tax_color_h' . $style_atts_uid, $res_ctx->get_shortcode_att('tax_color_h') );
        $res_ctx->load_color_settings( 'tax_bg', $style_atts_prefix . 'tax_bg_solid' . $style_atts_uid, $style_atts_prefix . 'tax_bg_gradient' . $style_atts_uid, '', '' );
        $res_ctx->load_color_settings( 'tax_bg_h', $style_atts_prefix . 'tax_bg_h_solid' . $style_atts_uid, $style_atts_prefix . 'tax_bg_h_gradient' . $style_atts_uid, '', '', '' );
        $res_ctx->load_color_settings( 'tax_border_color', $style_atts_prefix . 'tax_border_color_solid' . $style_atts_uid, $style_atts_prefix . 'tax_border_color_gradient' . $style_atts_uid, $style_atts_prefix . 'tax_border_color_gradient_1' . $style_atts_uid, $style_atts_prefix . 'tax_border_color_params' . $style_atts_uid, '' );
        $res_ctx->load_settings_raw( $style_atts_prefix . 'tax_border_color_h' . $style_atts_uid, $res_ctx->get_shortcode_att('tax_border_color_h') );


        /*-- FONTS -- */
        $res_ctx->load_font_settings( 'f_txt', '', $style_atts_prefix, $style_atts_uid );
        $res_ctx->load_font_settings( 'f_list', '', $style_atts_prefix, $style_atts_uid );
        $res_ctx->load_font_settings( 'f_add', '', $style_atts_prefix, $style_atts_uid );
        $res_ctx->load_font_settings( 'f_tax', '', $style_atts_prefix, $style_atts_uid );

    }


    function render( $atts, $content = null ) {

        parent::render( $atts ); // sets the live atts, $this->atts, $this->block_uid, $this->td_query (it runs the query)

        global $tdb_module_template_params, $tdb_state_single, $tdb_state_category, $tdb_state_tag, $tdb_state_author, $tdb_state_attachment, $tdb_state_single_page;

        $custom_field_data = array();

        if( $tdb_module_template_params !== NULL ) {
            $post_obj = $tdb_module_template_params['post_obj'];

            // Create a dummy field data array
            $dummy_field_data = array(
                'value' => 'Sample field data',
                'type' => 'text',
                'meta_exists' => true,
            );

            $custom_field_data = array(
                'value' => '',
                'type' => '',
                'meta_exists' => false,
            );

            if ( gettype($post_obj) === 'object' && get_class($post_obj) === 'WP_Post' ) {

                $post_obj_id = $post_obj->ID;

                $field_name = '';
                if( $this->get_att( 'wp_field' ) != '' ) {
                    $field_name = $this->get_att( 'wp_field' );
                } else {
                    $field_name = $this->get_att( 'acf_field' );
                }

                if( $field_name != '' ) {
                    if( strpos( $field_name, 'td_source_title' ) === 0) {
                        $source_post_id = get_post_meta( $post_obj_id, 'tdc-parent-post-id', true );

                        if ( !empty( $source_post_id ) ) {
                            if( $field_name == 'td_source_title_with_url' ) {
                                $custom_field_data['value'] .= '<a href="' . esc_url( get_permalink( $source_post_id ) ) . '">';
                            }
                                $custom_field_data['value'] .= get_the_title($source_post_id);
                            if( $field_name == 'td_source_title_with_url' ) {
                                $custom_field_data['value'] .= '</a>';
                            }
                            $custom_field_data['type'] = 'text';
                            $custom_field_data['meta_exists'] = true;
                        }
                    } else {
                        $custom_field_data = td_util::get_acf_field_data( $field_name, $post_obj_id );

                        if( !$custom_field_data['meta_exists'] ) {
                            if( metadata_exists('post', $post_obj_id, $field_name ) ) {
                                $custom_field_data['value'] = get_post_meta( $post_obj_id, $field_name, true );
                                $custom_field_data['type'] = 'text';
                                $custom_field_data['meta_exists'] = true;
                            }
                        }
                    }
                }

                if( empty( $custom_field_data['value'] ) && ( tdc_state::is_live_editor_iframe() || tdc_state::is_live_editor_ajax() ) ) {
                    // If we are in composer, display dummy data only if we
                    // are editing the actual module
                    if( tdb_state_template::get_template_type() == 'module' ) {
                        $custom_field_data = $dummy_field_data;
                    }
                }

            } else {
                $custom_field_data = $dummy_field_data;
            }
        } else {
            switch( tdb_state_template::get_template_type() ) {
                case 'cpt':
                case 'single':
                    $custom_field_data = $tdb_state_single->post_custom_field->__invoke( $atts );
                    break;

                case 'category':
                    $custom_field_data = $tdb_state_category->category_custom_field->__invoke( $atts );
                    break;

                case 'cpt_tax':
                    $tdb_state_category->set_tax();
                    $custom_field_data = $tdb_state_category->category_custom_field->__invoke( $atts );
                    break;

                case 'tag':
                    $custom_field_data = $tdb_state_tag->tag_custom_field->__invoke( $atts );
                    break;

                case 'author':
                    $custom_field_data = $tdb_state_author->author_custom_field->__invoke( $atts );
                    break;

                case 'attachment':
                    $custom_field_data = $tdb_state_attachment->attachment_custom_field->__invoke( $atts );
                    break;

                default:
                    $custom_field_data = $tdb_state_single_page->page_custom_field->__invoke( $atts );
                    break;
            }
        }


        $buffy = ''; //output buffer


        // display restrictions
        $hide_for_user_type = $this->get_att( 'hide_for_user_type' );
        if( $hide_for_user_type != '' ) {
            if( !( td_util::tdc_is_live_editor_ajax() || td_util::tdc_is_live_editor_iframe() ) &&
                (
                    ( $hide_for_user_type == 'logged-in' && is_user_logged_in() ) ||
                    ( $hide_for_user_type == 'guests' && !is_user_logged_in() )
                )
            ) {
                return $buffy;
            }
        } else {
            $author_plan_ids = $this->get_att('author_plan_id');
            $all_users_plan_ids = $this->get_att('logged_plan_id');

            if( !td_util::plan_limit($author_plan_ids, $all_users_plan_ids) ) {
                return $buffy;
            }
        }



        if( empty($custom_field_data['value']) ) {
            return $buffy;
        }


        // URL
        $url = '';
        if( $this->get_att('url') != '' ) {
            $url = td_util::get_custom_field_value_from_string($this->get_att('url'));
        }

        $open_in_new_window = '';
        if( $this->get_att('open_in_new_window') != '' ) {
            $open_in_new_window = 'target="blank"';
        }

        $url_rel = '';
        if( $this->get_att('url_rel') != '' ) {
            $url_rel = 'rel="' . $this->get_att('url_rel') . '"';
        }


        // additional text
        $add_txt = td_util::get_custom_field_value_from_string($this->get_att('add_txt'));
        $add_txt_buffy = '';

        if( $add_txt != '' ) {
            $add_txt_buffy = '<div class="tdb-cf-add-txt">' . $add_txt . '</div>';
        }

        $add_txt_pos = $this->get_att('add_txt_pos');


        // icon
        $icon = $this->get_icon_att( 'tdicon' );
        $tdicon_data = '';
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $tdicon_data = 'data-td-svg-icon="' . $this->get_att('tdicon') . '"';
        }
        $icon_buffy = '';
        if ( $icon != '' ) {
            if( base64_encode( base64_decode( $icon ) ) == $icon ) {
                $icon_buffy = '<span class="tdb-cf-icon tdb-cf-icon-svg" ' . $tdicon_data . '>' . base64_decode( $icon ) . '</span>';
            } else {
                $icon_buffy = '<i class="tdb-cf-icon ' . $icon . '"></i>';
            }
        }

        $icon_pos = $this->get_att('icon_pos');
        // flag icon on list
        $show_icon = true;
        // for checkbox & select cf types
        $display_list = $this->get_att('display_list');

        // additional classes array
        $additional_classes_array = array('tdb-sacff-type-' . $custom_field_data['type']);

        // Check to see if the element is being called into a tdb module template
        if( td_global::get_in_tdb_module_template() ) {
            $additional_classes_array[] = get_class($this) . '_' . self::$module_template_part_index;
        }


        $buffy .= '<div class="' . $this->get_block_classes($additional_classes_array) . '" ' . $this->get_block_html_atts() . '>';

            //get the block css
            $buffy .= $this->get_block_css();

            //get the js for this block
            $buffy .= $this->get_block_js();


            $buffy .= '<div class="tdb-block-inner td-fix-index">';
                $field_value_buffy = '';

                if( $custom_field_data['type'] == 'image' ) {

                    $img_url = '';
                    $img_title = '';
                    $img_alt = '';

                    if( is_array( $custom_field_data['value'] ) ) {
                        $img_url = $custom_field_data['value']['url'];
                        $img_title = 'title="' . $custom_field_data['value']['title'] . '"';
                        $img_alt = 'alt="' . $custom_field_data['value']['alt'] . '"';
                    } else if( is_string( $custom_field_data['value'] ) ) {
                        $img_url = $custom_field_data['value'];
                        $img_id = attachment_url_to_postid($img_url);

                        if( $img_id ) {
                            $img_info = get_post( $img_id );

                            if( $img_info ) {
                                $img_title = 'title="' . $img_info->post_title . '"';
                                $img_alt = 'alt="' . get_post_meta($img_id, '_wp_attachment_image_alt', true ) . '"';
                            }
                        }
                    } else if ( is_numeric( $custom_field_data['value'] ) ) {
                        $img_id = $custom_field_data['value'];

                        $img_info = get_post( $img_id );

                        if( $img_info ) {
                            $img_url = $img_info->guid;
                            $img_title = 'title="' . $img_info->post_title . '"';
                            $img_alt = 'alt="' . get_post_meta($img_id, '_wp_attachment_image_alt', true ) . '"';
                        }
                    }

                    if( $img_url != '' ) {
                        $img_wrapp_tag = 'div';
                        $img_wrapp_link = '';
                        if( $url != '' ) {
                            $img_wrapp_tag = 'a';
                            $img_wrapp_link = 'href="' . $url . '" ' . $open_in_new_window . ' ' . $url_rel;
                        }

                        $field_value_buffy .= '<' . $img_wrapp_tag . ' class="tdb-sacff-img-wrapp" ' . $img_wrapp_link . '>';
                            $field_value_buffy .= '<img class="tdb-sacff-img" src="' . $img_url . '" ' . $img_title . ' ' . $img_alt . ' />';
                        $field_value_buffy .= '</' . $img_wrapp_tag . '>';
                    }

                } else if( $custom_field_data['type'] == 'taxonomy' ) {

                    $field_values = $custom_field_data['value'];
                    $terms = array();

                    foreach ( $field_values as $field_value ) {
                        $term_type = $custom_field_data['taxonomy'];
                        $term_data = $field_value;
                        if( is_numeric( $field_value ) ) {
                            $term_data = get_term_by('term_id', $field_value, $term_type);
                        }

                        if( $term_data ) {
                            $term_color = '';

                            if( $term_type == 'category' ) {
                                $term_color = td_util::get_category_option( $term_data->term_id, 'tdc_color' );
                            } else {
                                $term_color = get_term_meta( $term_data->term_id, 'tdb_filter_color', true );
                            }

                            $terms[] = array(
                                'id' => $term_data->term_id,
                                'name' => $term_data->name,
                                'url' => get_term_link($term_data->term_id, $term_type),
                                'color' => $term_color
                            );
                        }
                    }

                    $term_style = $this->get_att('tax_style');

                    if( !empty( $terms ) ) {
                        $field_value_buffy .= '<div class="tdb-sacff-terms">';
                            foreach ( $terms as $term ) {
                                $term_color_text = '';
                                $term_color_bg_border = '';

                                if( $term['color'] != '' ) {
                                    $text_color_readable = td_util::readable_colour( $term['color'], 200, 'rgba(0, 0, 0, 0.9)', '#fff' );
                                    if ( $text_color_readable != '#fff' ) {
                                        $term_color_text = 'style="color:' . $text_color_readable . '"';
                                    }

                                    if( $term_style == '' ) {
                                        $term_color_bg_border = 'style="background-color:' . $term['color'] . '; border-color:' . $term['color']  . '"';
                                    } else if( $term_style == 'bordered' ) {
                                        $term_color_bg_border = 'style="background-color:' . td_util::hex2rgba($term['color'], 0.85) . '; border-color:' . $term['color'] . '"';
                                    } else if ( $term_style == 'rainbow' ) {
                                        $term_color_bg_border = 'style="background-color:' . td_util::hex2rgba($term['color'], 0.2) . '; border-color:' . td_util::hex2rgba($term['color'], 0.05) . '"';
                                        $term_color_text = 'style="color:' . $term['color'] . '"';
                                    }
                                }

                                $field_value_buffy .= '<a class="tdb-sacff-term" href="' . $term['url'] .'" ' . $term_color_text . '>';
                                    $field_value_buffy .= '<span class="tdb-sacff-term-bg" ' . $term_color_bg_border . '></span>';
                                    $field_value_buffy .= $term['name'];
                                $field_value_buffy .= '</a>';
                            }
                        $field_value_buffy .= '</div>';
                    }

                } else {

                    $field_value = $custom_field_data['value'];

                    if ( $custom_field_data['type'] == 'email' || $custom_field_data['type'] == 'url' ) {
                        if( $url == '' ) {
                            $url = $custom_field_data['value'];
                        }
                    }

                    if ( $display_list && ( $custom_field_data['type'] == 'checkbox' || $custom_field_data['type'] == 'select' ) ) {
                        $icon_on_list_class = '';
                        if ($icon_buffy !== '') {
                            $icon_on_list_class = 'tdb-sacff-list-icon';
                            $show_icon = false;
                        }
                        $field_value_buffy .= '<div class="tdb-sacff-list">';
                            $field_value_buffy .= '<ul class="' . $icon_on_list_class . '">';
                            foreach ( $field_value as $key => $value ) {
                                $field_value_buffy .= '<li>';
                                if ($icon_buffy !== '') {
                                    $field_value_buffy .= $icon_buffy;
                                }
                                $field_value_buffy .= $value;
                                $field_value_buffy .= '</li>';
                            }
                            $field_value_buffy .= '</ul>';
                        $field_value_buffy .= '</div>';

                    } else {
                        $field_value_buffy .= '<div class="tdb-sacff-txt">';
                        if( $url != '' ) {
                            $field_value_buffy .= '<a href="' . ( $custom_field_data['type'] == 'email' ? 'mailto:' : '' ) . $url . '" ' . $open_in_new_window . ' ' . $url_rel . '>';
                        }

                        if( is_array( $field_value ) ) {
                            foreach ( $field_value as $key => $value ) {
                                if( is_array( $value ) ) {
                                    $field_value_buffy .= $value['label'];
                                } else if( td_util::isAssocArray( $field_value ) ) {
                                    if( $key == 'label' ) {
                                        $field_value_buffy .= $value;
                                    }
                                } else {
                                    $field_value_buffy .= $value;
                                }

                                if( $key != array_key_last( $field_value ) ) {
                                    $field_value_buffy .= ', ';
                                }
                            }
                        } else {
                            $field_value_buffy .= $field_value;
                        }

                        if( $url != '' ) {
                            $field_value_buffy .= '</a>';
                        }
                        $field_value_buffy .= '</div>';
                    }

                }


                // Additional text and icon, before the custom field value
                if( $icon_buffy != '' && $show_icon && ( $icon_pos == '' || ( $icon_pos == 'before_add' && $add_txt == '' ) ) ) {
                    $buffy .= $icon_buffy;
                }

                if( $add_txt != '' && $add_txt_pos == '' ) {
                    if( $icon_pos == 'before_add' ) {
                        $buffy .= $icon_buffy;
                    }

                    $buffy .= $add_txt_buffy;

                    if( $icon_pos == 'after_add' ) {
                        $buffy .= $icon_buffy;
                    }
                }


                // The custom field value
                $buffy .= $field_value_buffy;


                // Additional text and icon, after the custom field value
                if ($icon_buffy != '' && $show_icon &&  ($icon_pos == 'after' || ($icon_pos == 'after_add' && $add_txt == ''))) {
                    $buffy .= $icon_buffy;
                }

                if ($add_txt != '' && $add_txt_pos == 'after') {
                    if ($icon_pos == 'before_add') {
                        $buffy .= $icon_buffy;
                    }

                    $buffy .= $add_txt_buffy;

                    if ($icon_pos == 'after_add') {
                        $buffy .= $icon_buffy;
                    }
                }

            $buffy .= '</div>';

        $buffy .= '</div> <!-- ./block -->';

        return $buffy;
    }

}