<?php


class tdb_smart_list_5 extends td_smart_list {
    protected $use_pagination = true; // set this to true to use rela pagination on this template
    protected $atts = array();

    function __construct($atts) {
        $this->atts = $atts;
    }


    protected function render_before_list_wrap() {
        $buffy = '';

        //wrapper with id for smart list wrapper type 7
        $buffy .= '<div class="tdb_smart_list_5">';

        return $buffy;
    }


    protected function render_list_item($item_array, $current_item_id, $current_item_number, $total_items_number) {

        $buffy = '';

        //creating each slide
        $buffy .= '<div class="tdb-item">';

            $buffy .= '<div class="tdb-sml-head">';
                // render the pagination
                $buffy .= $this->callback_render_pagination();

            $sm_title_tag = 'h2';
            if ( isset($this->atts['sm_title_tag']) ) {
                $sm_title_tag = $this->atts['sm_title_tag'];
            }
            $buffy .= '<' . $sm_title_tag . '><span class="tdb-sml-current-item-title">' . $current_item_number. '. ' . $item_array['title'] . '</span></' . $sm_title_tag . '>';
            $buffy .= '</div>';


            //adding description
            if(!empty($item_array['description'])) {
                $buffy .= '<span class="tdb-sml-description">' . $item_array['description'] . '</span>';
            }


            // ad smart list
            $buffy .= $this->atts['sm_ad'];


            //get image link target
            $first_img_link_target = $item_array['first_img_link_target'];

            //get image src
            $first_img_src = td_util::attachment_get_full_info($item_array['first_img_id'])['src'];

            //image type and width
            $first_image_size = 'td_1068x0';
            if( isset( $this->atts['first_image_size'] ) && $this->atts['first_image_size'] != '' ) {
                $first_image_size = $this->atts['first_image_size'];
            }

            $first_img_info = wp_get_attachment_image_src( $item_array['first_img_id'], $first_image_size );
            $image_type = $first_image_size;
            $image_width = $first_img_info[1];

            //image caption
            $first_img_caption = $item_array['first_img_caption'];

            //image alt
            $first_img_alt = td_util::attachment_get_full_info($item_array['first_img_id'])['alt'];

        if (!empty($first_img_info[0])) {

                //retina image
                $srcset_sizes = td_util::get_srcset_sizes($item_array['first_img_id'], $image_type, $image_width, $first_img_info[0]);

                // class used by magnific popup
            $smart_list_lightbox = 	td_util::get_option('tds_smart_list_modal_image') !== 'hide' ? " td-lightbox-enabled" : '';

                // if a custom link is set use it
                if (!empty($item_array['first_img_link']) && $first_img_src != $item_array['first_img_link']) {
                    $first_img_src = $item_array['first_img_link'];

                    // remove the magnific popup class for custom links
                    $smart_list_lightbox = "";
                    $buffy .= '
                            <figure class="tdb-slide-smart-list-figure td-slide-smart-list-7' . $smart_list_lightbox . '">
                                <a class="td-sml-link-to-image" href="' . $first_img_src . '" data-caption="' . esc_attr($first_img_caption, ENT_QUOTES) . '" ' . $first_img_link_target . ' >
                                    <img src="' . $first_img_info[0] . '"' . $srcset_sizes . ' alt="' . $first_img_alt . '"/>
                                </a>
                            </figure>
                            <figcaption class="tdb-sml-caption"><div>' . $first_img_caption . '</div></figcaption>
                            ';
                } elseif ( td_util::get_option('tds_smart_list_modal_image') === 'hide') {
                    $buffy .= '
                            <figure class="tdb-slide-smart-list-figure td-slide-smart-list-7' . $smart_list_lightbox . '">
                                    <img src="' . $first_img_info[0] . '"' . $srcset_sizes . ' alt="' . $first_img_alt . '"/>
                            </figure>
                            <figcaption class="tdb-sml-caption"><div>' . $first_img_caption . '</div></figcaption>
                            ';
                } else {
                    $buffy .= '
                            <figure class="tdb-slide-smart-list-figure td-slide-smart-list-7' . $smart_list_lightbox . '">
                                <a class="td-sml-link-to-image" href="' . $first_img_src . '" data-caption="' . esc_attr($first_img_caption, ENT_QUOTES) . '" ' . $first_img_link_target . ' >
                                    <img src="' . $first_img_info[0] . '"' . $srcset_sizes . ' alt="' . $first_img_alt . '"/>
                                </a>
                            </figure>
                            <figcaption class="tdb-sml-caption"><div>' . $first_img_caption . '</div></figcaption>
                            ';
                }


            }

        $buffy .= '</div>';

        // render the pagination
        $buffy .= $this->callback_render_pagination();

        return $buffy;
    }



    protected function render_after_list_wrap() {
        $buffy = '';
        $buffy .= '</div>'; // /.tdb_smart_list_5 wrapper with id

        return $buffy;
    }






}