<?php

class tdb_single_toc extends td_block {

    public function get_custom_css() {
        // $unique_block_class - the unique class that is on the block. use this to target the specific instance via css
        $unique_block_class = ((td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax()) ? 'tdc-row .' : '') . $this->block_uid;

        $compiled_css = '';

        $raw_css =
            "<style>
                  
                /* @style_general_tdb_single_toc */
                .tdb_single_toc {
                    transform: translateZ(0);
                    font-family: -apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,Oxygen-Sans,Ubuntu,Cantarell,\"Helvetica Neue\",sans-serif;
                }
                .tdb_single_toc .tdb-block-inner {
                    padding: 20px 18px;
                    background-color: #fff;
                }
                .tdb_single_toc .tdb-stoc-title {
                    margin: 0 0 15px;
                    font-size: 20px;
                    line-height: 1.3;
                    font-weight: 500;
                }
                .tdb_single_toc ul {
                    list-style: none;
                    margin: 0;
                    width: 100%;
                }
                .tdb_single_toc ul li {
                    display: flex;
                    align-items: baseline;
                    flex-wrap: wrap;
                    font-size: 14px;
                    line-height: 1.4;
                    color: #444;
                }
                .tdb_single_toc .tdb-block-inner > ul > li {
                    margin-left: 0;
                }
                .tdb_single_toc a {
                    flex: 1;
                    margin-bottom: 4px;
                    color: inherit;
                }
                .tdb_single_toc a:hover {
                    color: #4db2ec;
                }
                .tdb_single_toc .tdb-block-inner > ul > li:last-child a {
                    margin-bottom: 0;
                }
                
                
                
                /* @title_space */
                body .$unique_block_class .tdb-stoc-title {
                    margin-bottom: @title_space;
                }
                
                /* @list_style_numeric */
                .$unique_block_class ul {
                    counter-reset: List;
                }
                .$unique_block_class ul li {
                    counter-increment: List;
                }
                .$unique_block_class ul li a:before {
                    content: counters(List,'.') '.';
                    margin-right: 10px;
                    opacity: .8;
                }
                /* @list_style_circle */
                .$unique_block_class ul li a:before {
                    display: inline-block;
                    content: '';
                    position: relative;
                    top: -2px;
                    width: .429em;
                    height: .429em;
                    margin-right: 10px;
                    background-color: #444;
                    border-radius: 100%;
                    opacity: .4;
                }
                /* @list_style_none */
                .$unique_block_class .tdb-block-inner > ul > li {
                    margin-left: 0;
                }
                
                /* @align_center */
                .$unique_block_class .tdb-block-inner {
                text-align: center;
                }
              
				.$unique_block_class ul li {
					justify-content: center;
				}
				
				/* @align_right */
				.$unique_block_class .tdb-block-inner {
                    text-align: right;
                }
				.$unique_block_class ul li{
					justify-content: right;
				}
				
				/* @align_left */
				.$unique_block_class .tdb-block-inner {
                    text-align: left;
                }
				.$unique_block_class ul li{
					justify-content: left;
				}
				
                
                /* @padding */
                body .$unique_block_class .tdb-block-inner {
                    padding: @padding;
                }
                
                /* @all_border_size */
                body .$unique_block_class .tdb-block-inner {
                    border-width: @all_border_size;
                    border-style: @all_border_style;
                    border-color: @all_border_color;
                }
                /* @radius */
                body .$unique_block_class .tdb-block-inner {
                    border-radius: @radius;
                }
                
                /* @h_space */
                body .$unique_block_class a {
                    margin-bottom: @h_space;
                }
                
                
                
                /* @title_color */
                body .$unique_block_class .tdb-stoc-title {
                    color: @title_color;
                }
                
                /* @bg */
                body .$unique_block_class .tdb-block-inner {
                    background-color: @bg;
                }
                
                /* @symbol_color_numeric */
                body .$unique_block_class ul li a:before {
                    color: @symbol_color_numeric;
                    opacity: 1;
                }
                /* @symbol_color_circle */
                body .$unique_block_class ul li a:before {
                    background-color: @symbol_color_circle;
                    opacity: 1;
                }
                
                /* @h_color */
                body .$unique_block_class a {
                    color: @h_color;
                }
                /* @h_color_h */
                body .$unique_block_class a:hover {
                    color: @h_color_h;
                }
                                
                /* @f_title */
                body .$unique_block_class .tdb-stoc-title {
                    @f_title
                }
                
                /* @f_h */
                body .$unique_block_class ul li {
                    @f_h
                }
                
            </style>";


        $td_css_res_compiler = new td_css_res_compiler( $raw_css );
        $td_css_res_compiler->load_settings( __CLASS__ . '::cssMedia', $this->get_all_atts() );

        $compiled_css .= $td_css_res_compiler->compile_css();
        return $compiled_css;
    }

    static function cssMedia( $res_ctx ) {

        /*-- GENERAL STYLES -- */
        $res_ctx->load_settings_raw( 'style_general_tdb_single_toc', 1 );
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $res_ctx->load_settings_raw( 'style_general_tdb_single_toc_composer', 1 );
        }



        /*-- LAYOUT -- */
        // Title space
        $title_space = $res_ctx->get_shortcode_att('title_space');
        if( $title_space != '' && is_numeric( $title_space ) ) {
            $res_ctx->load_settings_raw( 'title_space', $title_space . 'px' );
        }


        // List style
        $list_style = $res_ctx->get_shortcode_att('list_style');
        if( $list_style == '' || $list_style == 'numeric' ) {
            $res_ctx->load_settings_raw( 'list_style_numeric', 1 );
        } else if( $list_style == 'circle' ) {
            $res_ctx->load_settings_raw( 'list_style_circle', 1 );
        } else if( $list_style == 'none' ) {
            $res_ctx->load_settings_raw( 'list_style_none', 1 );
        }

        // content align
        $content_align = $res_ctx->get_shortcode_att('content_align_horiz');
        if ( $content_align == 'content-horiz-center' ) {
            $res_ctx->load_settings_raw( 'align_center', 1 );
        } else if ( $content_align == 'content-horiz-right' ) {
            $res_ctx->load_settings_raw( 'align_right', 1 );
        } else if ( $content_align == 'content-horiz-left' ) {
            $res_ctx->load_settings_raw( 'align_left', 1 );
        }


        // List padding
        $padding = $res_ctx->get_shortcode_att('padd');
        $res_ctx->load_settings_raw( 'padding', $padding );
        if( $padding != '' && is_numeric( $padding ) ) {
            $res_ctx->load_settings_raw( 'padding', $padding . 'px' );
        }


        // Border size
        $all_border_size = $res_ctx->get_shortcode_att('all_border_size');
        $res_ctx->load_settings_raw( 'all_border_size', $all_border_size );
        if( $all_border_size != '' ) {
            if( is_numeric( $all_border_size ) ) {
                $res_ctx->load_settings_raw( 'all_border_size', $all_border_size . 'px' );
            }
        } else {
            $res_ctx->load_settings_raw( 'all_border_size', '1px' );
        }

        // Border style
        $all_border_style = $res_ctx->get_shortcode_att('all_border_style');
        if( $all_border_style == '' ) {
            $all_border_style = 'solid';
        }
        $res_ctx->load_settings_raw( 'all_border_style', $all_border_style );

        // Border radius
        $radius = $res_ctx->get_shortcode_att('radius');
        $res_ctx->load_settings_raw( 'radius', $radius );
        if( $radius != '' ) {
            if( is_numeric( $radius ) ) {
                $res_ctx->load_settings_raw( 'radius', $radius . 'px' );
            }
        } else {
            $res_ctx->load_settings_raw( 'radius', '8px' );
        }


        // Spacing between headings
        $h_space = $res_ctx->get_shortcode_att('h_space');
        $res_ctx->load_settings_raw( 'h_space', $h_space );
        if( $h_space != '' && is_numeric( $h_space ) ) {
            $res_ctx->load_settings_raw( 'h_space', $h_space . 'px' );
        }




        /*-- COLORS -- */
        $res_ctx->load_settings_raw( 'title_color', $res_ctx->get_shortcode_att('title_color') );

        $res_ctx->load_settings_raw( 'bg', $res_ctx->get_shortcode_att('bg') );

        $all_border_color = $res_ctx->get_shortcode_att('all_border_color');
        if( $all_border_color == '' ) {
            $all_border_color = '#e3e3e5';
        }
        $res_ctx->load_settings_raw( 'all_border_color', $all_border_color );

        $symbol_color = $res_ctx->get_shortcode_att('symbol_color');
        if( $list_style == '' || $list_style == 'numeric' ) {
            $res_ctx->load_settings_raw('symbol_color_numeric', $symbol_color);
        } else if( $list_style == 'circle' ) {
            $res_ctx->load_settings_raw('symbol_color_circle', $symbol_color);
        }

        $res_ctx->load_settings_raw( 'h_color', $res_ctx->get_shortcode_att('h_color') );
        $res_ctx->load_settings_raw( 'h_color_h', $res_ctx->get_shortcode_att('h_color_h') );




        /*-- FONTS -- */
        $res_ctx->load_font_settings( 'f_title' );
        $res_ctx->load_font_settings( 'f_h' );

    }

    /**
     * Disable loop block features. This block does not use a loop and it doesn't need to run a query.
     */
    function __construct() {
        parent::disable_loop_block_features();
    }


    function render( $atts, $content = null ) {

        parent::render( $atts ); // sets the live atts, $this->atts, $this->block_uid, $this->td_query (it runs the query)

        global $tdb_state_single;
        $post_table_of_contents = $tdb_state_single->post_table_of_contents->__invoke( $this->get_all_atts() );


        /* -- Custom title -- */
        $title_text = $this->get_att( 'title' );
        $title_tag = $this->get_att( 'title_tag' );
        if( $title_tag == '' ) {
            $title_tag = 'h3';
        }

        $title_html = '';
        if( $title_text != '' ) {
            $title_html = '<' . $title_tag . ' class="tdb-stoc-title">' . $title_text . '</' . $title_tag . '>';
        }


        /* -- Smooth scroll -- */
        $smooth_scroll = $this->get_att('smooth_scroll') != '';
        $scroll_offset = $this->get_att('scroll_offset') != '' ? $this->get_att('scroll_offset') : 20;
        $scroll_duration = $this->get_att('scroll_duration') != '' ? $this->get_att('scroll_duration') : 500;


        $buffy = ''; //output buffer

        if( empty($post_table_of_contents) ) {
            return $buffy;
        }


        $buffy .= '<div class="' . $this->get_block_classes() . '" ' . $this->get_block_html_atts() . '>';

            //get the block css
            $buffy .= $this->get_block_css();

            //get the js for this block
            $buffy .= $this->get_block_js();


            $buffy .= '<div class="tdb-block-inner td-fix-index">';

                $buffy .= $title_html;

                $buffy .= $post_table_of_contents;

            $buffy .= '</div>';


            if( !( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) ) {
            ob_start();
            ?>
            <script>
                /* global jQuery:{} */
                jQuery().ready(function () {

                    let uid = '<?php echo $this->block_uid ?>',
                        $blockObj = jQuery('.<?php echo $this->block_uid ?>');

                    let tdbTOCItem = new tdbTOC.item();
                    // block uid
                    tdbTOCItem.uid = uid;
                    // block object
                    tdbTOCItem.blockObj = $blockObj;

                    // smooth scroll
                    tdbTOCItem.smoothScroll = '<?php echo $smooth_scroll ?>';
                    tdbTOCItem.scrollOffset = '<?php echo $scroll_offset ?>';
                    tdbTOCItem.scrollDuration = '<?php echo $scroll_duration ?>';

                    tdbTOC.addItem(tdbTOCItem);

                });
            </script>
            <?php
            td_js_buffer::add_to_footer( "\n" . td_util::remove_script_tag( ob_get_clean() ) );
        }

        $buffy .= '</div>';


        return $buffy;

    }

}