<?php
class tdb_gallery extends td_block {

    private $slider_options = array(
        'infinite' => false,
        'autoplay' => false,
        'autoplaSpeed' => 3000,
        'swipe' => false,
        'arrows' => false,
        'prevArrow' => '<button type="button" class="slick-prev"><i class="td-icon-left-arrow"></i></button>',
        'nextArrow' => '<button type="button" class="slick-next"><i class="td-icon-right-arrow"></i></button>',
        'dots' => false,
        'slidesToShow' => 1,
        'slidesToScroll' => 1,
        'responsive' => array(
            array(
                'breakpoint' => 1141,
                'settings' => array(
                    'slidesToShow' => 1,
                    'slidesToScroll' => 1,
                )
            ),
            array(
                'breakpoint' => 1019,
                'settings' => array(
                    'slidesToShow' => 1,
                    'slidesToScroll' => 1,
                )
            ),
            array(
                'breakpoint' => 768,
                'settings' => array(
                    'slidesToShow' => 1,
                    'slidesToScroll' => 1,
                )
            )
        )
    );


    public function get_custom_css() {

        // $unique_block_class - the unique class that is on the block. use this to target the specific instance via css
        $unique_block_class = ( ( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) ? 'tdc-row .' : '' ) . $this->block_uid;

        $compiled_css = '';

        $raw_css =
            "<style>

                /* @style_general_tdb_gallery */
                .tdb_gallery {
                    transform: translateZ(0);
                }
                .tdb_gallery .tdb-gallery-wrap {
                    position: relative;
                }
                .tdb_gallery .slick-list {
                    margin-left: -5px;
                    margin-right: -5px;
                }
                .tdb_gallery .slick-track {
                    display: flex;
                    flex-wrap: wrap;
                }
                .tdb_gallery .slick-track .slick-slide {
                    padding-left: 5px;
                    padding-right: 5px;
                    transition: opacity .2s ease-in-out;
                    opacity: 1;
                    outline: none;
                }
                .tdb_gallery .slick-track .slick-slide:not(.slick-active) {
                    opacity: 0;
                    pointer-events: none;
                }
                .tdb_gallery .tdb-gi-inner {
                    position: relative;
                    padding-bottom: 50%;
                    overflow: hidden;
                }
                .tdb_gallery img {
                    position: absolute;
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }
                .tdb_gallery .tdb-gi-caption {
                    position: absolute;
                    left: 0;
                    bottom: 0;
                    width: auto;
                    max-width: 100%;
                    padding: 8px 12px 9px;
                    background-color: rgba(0, 0, 0, .7);
                    font-size: 11px;
                    line-height: 1.3;
                    color: #fff;
                    z-index: 10;
                }
                .tdb_gallery .slick-arrow {
                    position: absolute;
                    top: 50%;
                    transform: translateY(-50%);
                    padding: 0;
                    background-color: transparent;
                    font-size: 34px;
                    line-height: 1;
                    color: #fff;
                    border: none;
                    border-radius: 0;
                    outline: none !important;
                    transition: color .2s ease-in-out, opacity .2s ease-in-out;
                    -webkit-appearance: none;
                    z-index: 10;
                }
                .tdb_gallery .slick-arrow.slick-disabled {
                    opacity: .45;
                }
                .tdb_gallery .slick-arrow svg {
                    display: block;
                    width: 1em;
                    height: auto;
                }
                .tdb_gallery .slick-arrow svg,
                .tdb_gallery .slick-arrow svg * {
                    fill: currentColor;
                }
                .tdb_gallery .slick-dots {
                    list-style: none;
                    display: flex;
                    flex-wrap: wrap;
                    justify-content: center;
                    gap: .833em;
                    width: 100%;
                    font-size: 11px;
                    line-height: 1;
                    margin: 0;
                }
                .tdb_gallery .slick-dots li {
                    margin: 0;
                    line-height: 0;
                }
                .tdb_gallery .slick-dots button {
                    display: block;
                    padding: 0;
                    background-color: transparent;
                    font-size: 0;
                    border: none;
                    border-radius: 0;
                    outline: none !important;
                    -webkit-appearance: none;
                }
                .tdb_gallery .slick-dots button:after {
                    content: '';
                    display: block;
                    background-color: rgba(0, 0, 0, 0.2);
                    width: 1em;
                    height: 1em;
                    font-size: 11px;
                    border-radius: 100%;
                    transition: background-color .2s ease-in-out;
                }
                .tdb_gallery .slick-dots button:hover:after,
                .tdb_gallery .slick-dots .slick-active button:after {
                    background-color: rgba(0, 0, 0, 0.7);
                }
                
                /* @style_general_tdb_gallery_composer */
                .tdb_gallery .tdb-form-inner {
                    pointer-events: none;
                }



                /* @gap */
                .$unique_block_class .slick-list {
                    margin-left: -@gap;
                    margin-right: -@gap;
                }
                .$unique_block_class .slick-track .slick-slide {
                    padding-left: @gap;
                    padding-right: @gap;
                }


                /* @height */
                .$unique_block_class .tdb-gi-inner {
                    padding-bottom: @height;
                }
                /* @all_border */
                .$unique_block_class .tdb-gi-inner {
                    border: @all_border @all_border_style @all_border_color;
                }
                /* @border_radius */
                .$unique_block_class .tdb-gi-inner {
                    border-radius: @border_radius;
                }
                /* @show_caption */
                .$unique_block_class .tdb-gi-caption {
                    display: @show_caption;
                }
                /* @caption_pos_top */
                .$unique_block_class .tdb-gi-caption {
                    top: 0;
                    bottom: auto;
                }
                /* @caption_pos_bottom */
                .$unique_block_class .tdb-gi-caption {
                    top: auto;
                    bottom: 0;
                }
                /* @caption_width */
                .$unique_block_class .tdb-gi-caption {
                    width: @caption_width;
                }
                /* @caption_padd */
                .$unique_block_class .tdb-gi-caption {
                    padding: @caption_padd;
                }

                /* @shadow */
                .$unique_block_class .tdb-gi-inner {
                    box-shadow: @shadow;
                }
                /* @caption_bg */
                .$unique_block_class .tdb-gi-caption {
                    background-color: @caption_bg;
                }
                /* @caption_color */
                .$unique_block_class .tdb-gi-caption {
                    color: @caption_color;
                }
                
                /* @f_caption */
                .$unique_block_class .tdb-gi-caption {
                    @f_caption
                }


                /* @arrows_pos_inside */
                .$unique_block_class .slick-prev {
                    left: 13px;
                }
                .$unique_block_class .slick-next {
                    right: 13px;
                }
                /* @arrows_pos_outside */
                .$unique_block_class .slick-prev {
                    right: calc(100% + 13px);
                }
                .$unique_block_class .slick-next {
                    left: calc(100% + 13px);
                }
                /* @icon_size */
                .$unique_block_class .slick-arrow {
                    font-size: @icon_size;
                }

                /* @icon_color */
                .$unique_block_class .slick-arrow {
                    color: @icon_color;
                }
                /* @icon_color_h */
                .$unique_block_class .slick-arrow:hover {
                    color: @icon_color_h;
                }
                /* @icons_shadow */
                .$unique_block_class .slick-arrow i,
                .$unique_block_class .slick-arrow svg {
                    filter: drop-shadow(@icons_shadow);
                }


                /* @dots_pos_inside */
                .$unique_block_class .slick-dots {
                    position: absolute;
                    left: 0;
                    bottom: 13px;
                    margin-top: 0;
                }
                /* @dots_pos_outside */
                .$unique_block_class .slick-dots {
                    position: relative;
                    bottom: 0;
                    margin-top: 16px;
                }
                /* @dots_size */
                .$unique_block_class .slick-dots {
                    font-size: @dots_size;
                }
                .$unique_block_class .slick-dots button:after {
                    font-size: @dots_size;
                }

                /* @dots_color */
                .$unique_block_class .slick-dots button:after {
                    background-color: @dots_color;
                }
                /* @dots_color_h */
                .$unique_block_class .slick-dots button:hover:after,
                .$unique_block_class .slick-dots .slick-active button:after {
                    background-color: @dots_color_h;
                }
                /* @dots_shadow */
                .$unique_block_class .slick-dots button:after {
                    filter: drop-shadow(@dots_shadow);
                }

            </style>";


        $td_css_res_compiler = new td_css_res_compiler( $raw_css );
        $td_css_res_compiler->load_settings( __CLASS__ . '::cssMedia', $this->get_all_atts() );

        $compiled_css .= $td_css_res_compiler->compile_css();
        return $compiled_css;

    }

    static function cssMedia( $res_ctx ) {

        /*-- GENERAL STYLES -- */
        $res_ctx->load_settings_raw( 'style_general_tdb_gallery', 1 );
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $res_ctx->load_settings_raw( 'style_general_tdb_gallery_composer', 1 );
        }



        /*-- GENERAL -- */
        /* -- Layout -- */
        $gap = $res_ctx->get_shortcode_att( 'gap' );
        $gap = is_numeric( $gap ) ? ($gap / 2) . 'px' : $gap;
        $res_ctx->load_settings_raw( 'gap', $gap );



        /*-- SLIDES -- */
        /* -- Layout -- */
        // Height
        $height = $res_ctx->get_shortcode_att( 'height' );
        $height .= is_numeric( $height ) ? 'px' : '';
        $res_ctx->load_settings_raw( 'height', $height );

        // Border size
        $all_border = $res_ctx->get_shortcode_att( 'all_border' );
        $all_border .= is_numeric( $all_border ) ? 'px' : '';
        $res_ctx->load_settings_raw( 'all_border', $all_border );

        // Border style
        $all_border_style = $res_ctx->get_shortcode_att( 'all_border_style' );
        $all_border_style = $all_border_style != '' ? $all_border_style : 'solid';
        $res_ctx->load_settings_raw( 'all_border_style', $all_border_style );

        // Border radius
        $border_radius = $res_ctx->get_shortcode_att( 'border_radius' );
        $border_radius .= is_numeric( $border_radius ) ? 'px' : '';
        $res_ctx->load_settings_raw( 'border_radius', $border_radius );

        // Show caption
        $show_caption = $res_ctx->get_shortcode_att( 'show_caption' );
        $show_caption = $show_caption != '' ? $show_caption : 'block';
        $res_ctx->load_settings_raw( 'show_caption', $show_caption );

        // Caption position
        $caption_pos = $res_ctx->get_shortcode_att( 'caption_pos' );
        $caption_pos = $caption_pos != '' ? $caption_pos : 'bottom';
        $res_ctx->load_settings_raw( 'caption_pos_' . $caption_pos, 1 );

        // Caption width
        $caption_width = $res_ctx->get_shortcode_att( 'caption_width' );
        $caption_width .= is_numeric( $caption_width ) ? 'px' : '';
        $res_ctx->load_settings_raw( 'caption_width', $caption_width );

        // Caption padding
        $caption_padd = $res_ctx->get_shortcode_att( 'caption_padd' );
        $caption_padd .= is_numeric( $caption_padd ) ? 'px' : '';
        $res_ctx->load_settings_raw( 'caption_padd', $caption_padd );


        /* -- Style -- */
        $all_border_color = $res_ctx->get_shortcode_att( 'all_border_color' );
        $all_border_color = $all_border_color != '' ? $all_border_color : '#000';
        $res_ctx->load_settings_raw( 'all_border_color', $all_border_color );
        $res_ctx->load_shadow_settings( 0, 0, 0, 0, '#000', 'shadow' );

        $res_ctx->load_settings_raw( 'caption_bg', $res_ctx->get_shortcode_att( 'caption_bg' ) );
        $res_ctx->load_settings_raw( 'caption_color', $res_ctx->get_shortcode_att( 'caption_color' ) );


        /* -- Fonts -- */
        $res_ctx->load_font_settings( 'f_caption' );



        /*-- NAVIGATION -- */
        /* -- Arrows layout -- */
        // Position
        $arrows_pos = $res_ctx->get_shortcode_att('arrows_pos');
        $arrows_pos = $arrows_pos != '' ? $arrows_pos : 'inside';
        $res_ctx->load_settings_raw( 'arrows_pos_' . $arrows_pos, 1 );

        // Size
        $icon_size = $res_ctx->get_shortcode_att( 'icon_size' );
        $icon_size .= is_numeric( $icon_size ) ? 'px' : '';
        $res_ctx->load_settings_raw( 'icon_size', $icon_size );


        /* -- Arrows style -- */
        $res_ctx->load_settings_raw( 'icon_color', $res_ctx->get_shortcode_att( 'icon_color' ) );
        $res_ctx->load_settings_raw( 'icon_color_h', $res_ctx->get_shortcode_att( 'icon_color_h' ) );
        $res_ctx->load_shadow_settings( 3, 0, 0, 0, 'rgba(0, 0, 0, 0.3)', 'icons_shadow', '', true );


        
        /* -- Dots layout -- */
        // Position
        $dots_pos = $res_ctx->get_shortcode_att('dots_pos');
        $dots_pos = $dots_pos != '' ? $dots_pos : 'inside';
        $res_ctx->load_settings_raw( 'dots_pos_' . $dots_pos, 1 );

        // Size
        $dots_size = $res_ctx->get_shortcode_att( 'dots_size' );
        $dots_size .= is_numeric( $dots_size ) ? 'px' : '';
        $res_ctx->load_settings_raw( 'dots_size', $dots_size );


        /* -- Dots style -- */
        $res_ctx->load_settings_raw( 'dots_color', $res_ctx->get_shortcode_att( 'dots_color' ) );
        $res_ctx->load_settings_raw( 'dots_color_h', $res_ctx->get_shortcode_att( 'dots_color_h' ) );
        $res_ctx->load_shadow_settings( 3, 0, 0, 0, 'rgba(0, 0, 0, 0.3)', 'dots_shadow', '', true );

    }


    /**
     * Disable loop block features. This block does not use a loop and it doesn't need to run a query.
     */
    function __construct() {
        parent::disable_loop_block_features();
    }


    function render( $atts, $content = null ) {

        parent::render($atts); // sets the live atts, $this->atts, $this->block_uid, $this->td_query (it runs the query)

        // Template type
        $template_type = tdb_state_template::get_template_type();


        // Gallery source
        $source = $this->get_att('source');


        // ACF field
        $acf_field = $this->get_att('acf_field');




        /* --
        -- Slider options
        -- */
        /* -- Autoplay -- */
        // Enable
        $this->slider_options['infinite'] = $this->get_att('infinite') != '';

        // Enable
        $this->slider_options['autoplay'] = $this->get_att('autoplay') != '';
        
        // Autoplay speed
        $this->slider_options['autoplaSpeed'] = $this->get_att('autoplay_speed') != '' && is_numeric( $this->get_att('autoplay_speed') ) ? intval($this->get_att('autoplay_speed')) : 3000;

        
        /* -- Navigation -- */
        $this->slider_options['swipe'] = $this->get_att('enable_swipe') != '';
        $this->slider_options['arrows'] = $this->get_att('enable_arrows') != '';
        $this->slider_options['dots'] = $this->get_att('enable_dots') != '';

        // Arrows
        $icon_prev = $this->get_icon_att( 'tdicon_prev' );
        if ( !empty( $icon_prev ) ) {
            if( base64_encode( base64_decode( $icon_prev ) ) == $icon_prev ) {
                $icon_prev_data = '';
                if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
                    $icon_prev_data = 'data-td-svg-icon="' . $this->get_att('tdicon_prev') . '"';
                }
                $this->slider_options['prevArrow'] = '<button type="button" class="slick-prev" ' . $icon_prev_data . '>' . base64_decode( $icon_prev ) . '</button>';
            } else {
                $this->slider_options['prevArrow'] = '<button type="button" class="slick-prev"><i class="' . $icon_prev . '"></i></button>';
            }
        }

        $icon_next = $this->get_icon_att( 'tdicon_next' );
        if ( !empty( $icon_next ) ) {
            if( base64_encode( base64_decode( $icon_next ) ) == $icon_next ) {
                $icon_next_data = '';
                if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
                    $icon_next_data = 'data-td-svg-icon="' . $this->get_att('tdicon_next') . '"';
                }
                $this->slider_options['nextArrow'] = '<button type="button" class="slick-next" ' . $icon_next_data . '>' . base64_decode( $icon_next ) . '</button>';
            } else {
                $this->slider_options['nextArrow'] = '<button type="button" class="slick-next"><i class="' . $icon_next . '"></i></button>';
            }
        }


        /* -- Responsive options -- */
        // Media screens
        $media_screens = array(
            '1141' => 'landscape',
            '1019' => 'portrait',
            '768' => 'phone'
        );

        // Slides per row
        $per_row = $this->get_att('per_row');
        $per_row_default = 1;

        if( td_util::is_base64($per_row) ) {
            $per_row_decoded = json_decode( base64_decode( $per_row ) );

            if( property_exists($per_row_decoded, 'all') ) {
                $per_row_default = intval($per_row_decoded->all);
            }

            foreach( $this->slider_options['responsive'] as &$responsive_setting ) {
                $media_screen = $media_screens[$responsive_setting['breakpoint']];

                if( property_exists($per_row_decoded, $media_screen) ) {
                    $responsive_setting['settings']['slidesToShow'] = intval($per_row_decoded->{$media_screen});
                } else {
                    $responsive_setting['settings']['slidesToShow'] = $per_row_default;
                }
            }
        } else {
            $per_row_default = $per_row != '' && is_numeric($per_row) ? intval($per_row) : $per_row_default;
        }

        $this->slider_options['slidesToShow'] = $per_row_default;

        // Slides to scroll
        $slides_to_scroll = $this->get_att('slides_to_scroll');
        $slides_to_scroll_default = 1;

        if( td_util::is_base64($slides_to_scroll) ) {
            $slides_to_scroll_decoded = json_decode( base64_decode( $slides_to_scroll ) );

            if( property_exists($slides_to_scroll_decoded, 'all') ) {
                $slides_to_scroll_default = intval($slides_to_scroll_decoded->all);
            }

            foreach( $this->slider_options['responsive'] as &$responsive_setting ) {
                $media_screen = $media_screens[$responsive_setting['breakpoint']];

                if( property_exists($slides_to_scroll_decoded, $media_screen) ) {
                    $responsive_setting['settings']['slidesToScroll'] = intval($slides_to_scroll_decoded->{$media_screen});
                } else {
                    $responsive_setting['settings']['slidesToScroll'] = $slides_to_scroll_default;
                }
            }
        } else {
            $slides_to_scroll_default = $slides_to_scroll != '' && is_numeric($slides_to_scroll) ? intval($slides_to_scroll) : $slides_to_scroll_default;
        }

        $this->slider_options['slidesToScroll'] = $slides_to_scroll_default;




        /* --
        -- RETRIEVE THE GALLERY IMAGES
        -- */
        /* -- Retrieve the gallery images -- */
        $gallery_images = array();
        $block_error = '';

        if( $source == '' ) {

            $block_error = td_util::get_block_error(
                'Gallery',
                'Please select a source for the gallery.');

        } else {

            switch ( $source ) {
                case 'post_gallery':
                    // Throw an error if the shortcode is not in a post/cpt template
                    if( $template_type != 'single' && $template_type != 'cpt' ) {
                        $block_error = td_util::get_block_error(
                            'Gallery',
                            '\'Post gallery\' was selected as the source, but the current template is not a post or CPT.');
                    } else {
                        global $tdb_state_single;
                        $gallery_images = $tdb_state_single->post_gallery->__invoke( $atts );
                    }

                    break;

                case 'acf_field':
                    // Throw an error if the ACF plugin is disabled
                    if( !class_exists( 'ACF' ) ) {
                        $block_error = td_util::get_block_error(
                            'Gallery',
                            'The Advanced Custom Fields (ACF) plugin is disabled.');
                    } else if( empty( $acf_field ) ) {
                        $block_error = td_util::get_block_error(
                            'Gallery',
                            'Please select an ACF field first.');
                    } else {

                        global $tdb_state_single, $tdb_state_category, $tdb_state_tag, $tdb_state_author, $tdb_state_attachment, $tdb_state_single_page;

                        switch( $template_type ) {
                            case 'cpt':
                            case 'single':
                                $gallery_images = $tdb_state_single->post_gallery->__invoke( $atts );
                                break;
                            case 'category':
                                $gallery_images = $tdb_state_category->category_gallery->__invoke( $atts );
                                break;
                            case 'cpt_tax':
                                $tdb_state_category->set_tax();
                                $gallery_images = $tdb_state_category->category_gallery->__invoke( $atts );
                                break;
                            case 'tag':
                                $gallery_images = $tdb_state_tag->tag_gallery->__invoke( $atts );
                                break;
                            case 'author':
                                $gallery_images = $tdb_state_author->author_gallery->__invoke( $atts );
                                break;
                            case 'attachment':
                                $gallery_images = $tdb_state_attachment->attachment_gallery->__invoke( $atts );
                                break;
                            default:
                                $gallery_images = $tdb_state_single_page->page_gallery->__invoke( $atts );
                                break;
                        }

                    }

                    break;
            }

        }



        /* --
        -- RENDER THE SHORTCODE
        -- */
        $buffy = '';

        if( empty( $gallery_images ) && empty( $block_error ) ) {
            return $buffy;
        }

        $buffy .= '<div class="' . $this->get_block_classes() . '" ' . $this->get_block_html_atts() . '>';

            //get the block css
            $buffy .= $this->get_block_css();

            //get the js for this block
            $buffy .= $this->get_block_js();


            $buffy .= '<div class="tdb-block-inner td-fix-index">';

                if( !empty( $block_error ) ) {
                    $buffy .= $block_error;
                } else if( !empty( $gallery_images ) ) {
                    $buffy .= '<div class="tdb-gallery-wrap">';
                        foreach( $gallery_images as $gallery_image ) {
                            $buffy .= '<div class="tdb-gallery-image">';
                                $buffy .= '<div class="tdb-gi-inner">';
                                    $buffy .= '<img src="' . $gallery_image['url'] . '"' .
                                                    ( !empty( $gallery_image['alt'] ) ? ' alt="' . $gallery_image['alt'] . '"' : '' ) .
                                                    ( !empty( $gallery_image['title'] ) ? ' title="' . $gallery_image['title'] . '"' : '' ) .
                                                ' />';

                                    if( !empty( $gallery_image['caption'] ) ) {
                                        $buffy .= '<span class="tdb-gi-caption">' . $gallery_image['caption'] . '</span>';
                                    }
                                $buffy .= '</div>';
                            $buffy .= '</div>';
                        }
                    $buffy .= '</div>';
                }

            $buffy .= '</div>';


            /* -- Load the SlickJS script only if it has not already -- */
            /* -- been loaded -- */
            $buffy .= td_external_scripts::render_script('//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', 'tdb-slick-js');


            /* -- Initialize SlickJS on the gallery -- */
            ob_start();
            ?>
            <script>
                /* global jQuery:{} */
                jQuery(document).ready(function () {

                    let $blockObj = jQuery('.<?php echo $this->block_uid ?>'),
                        $galleryWrap = $blockObj.find('.tdb-gallery-wrap');

                    if( $galleryWrap.length ) {
                        $galleryWrap.slick(<?php echo json_encode($this->slider_options) ?>);
                    }

                });
            </script>
            <?php
            td_js_buffer::add_to_footer( "\n" . td_util::remove_script_tag( ob_get_clean() ) );

        $buffy .= '</div>';

        return $buffy;

    }

    
    function js_tdc_callback_ajax() {
        $buffy = '';

        // add a new composer block - that one has the delete callback
        $buffy .= $this->js_tdc_get_composer_block();

        ob_start();

        ?>
        <script>
            /* global jQuery:{} */
            (function () {

                if( jQuery.fn.slick === undefined ) {
                    jQuery.getScript('//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', function() {
                        applySlick();
                    });
                } else {
                    applySlick();
                }

            })();

            function applySlick() {
                let $blockObj = jQuery('.<?php echo $this->block_uid ?>'),
                    $galleryWrap = $blockObj.find('.tdb-gallery-wrap');

                if( $galleryWrap.length ) {
                    $galleryWrap.slick(<?php echo json_encode($this->slider_options) ?>);
                }
            }
        </script>
        <?php

        return $buffy . td_util::remove_script_tag( ob_get_clean() );
    }

}