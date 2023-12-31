<?php


class tdb_smart_list_2 extends td_smart_list {
    protected $atts = array();

    function __construct($atts) {
        $this->atts = $atts;
    }

    protected function render_before_list_wrap() {
        $buffy = '';

        //wrapper with id for smart list wrapper type 4
        $buffy .= '<div class="tdb_smart_list_2">';

        return $buffy;
    }


    protected function render_list_item($item_array, $current_item_id, $current_item_number, $total_items_number) {

        $sm_title_tag = 'h2';
        if ( isset($this->atts['sm_title_tag']) ) {
            $sm_title_tag = $this->atts['sm_title_tag'];
        }

        $buffy = '';

        //creating each slide
        $buffy .= '<div class="tdb-item">';

            //get the title
            $smart_list_2_title = '';
            if(!empty($item_array['title'])) {
                $smart_list_2_title = $item_array['title'];
            }

            //adding description
            if(!empty($item_array['description'])) {
                $buffy .= '<div class="tdb-sml-info">';
                    $buffy .= '<div class="tdb-number-and-title">';
                        $buffy .= '<' . $sm_title_tag . '>';
                            $buffy .= '<div class="tdb-sml-current-item-nr"><span>' . $current_item_number. '</span></div>';
                            $buffy .= '<span class="tdb-sml-current-item-title">' . $smart_list_2_title . '</span>';
                        $buffy .= '</' . $sm_title_tag . '>';
                    $buffy .= '</div>';

                    $buffy .= '<span class="tdb-sml-description">' . $item_array['description'] . '</span>';
                $buffy .= '</div>';
            }


            //get image link target
            $first_img_link_target = $item_array['first_img_link_target'];

            //get image src
            $first_img_src = td_util::attachment_get_full_info($item_array['first_img_id'])['src'];

            $first_img_info = wp_get_attachment_image_src( $item_array['first_img_id'], 'thumbnail' );

            //image caption
            $first_img_caption = $item_array['first_img_caption'];
        
            //image alt
            $first_img_alt = td_util::attachment_get_full_info($item_array['first_img_id'])['alt'];

            if (!empty($first_img_info[0])) {

                // class used by magnific popup
                $smart_list_lightbox = 	td_util::get_option('tds_smart_list_modal_image') !== 'hide' ? " td-lightbox-enabled" : '';

                // if a custom link is set use it
                if (!empty($item_array['first_img_link']) && $first_img_src != $item_array['first_img_link']) {
                    $first_img_src = $item_array['first_img_link'];

                    // remove the magnific popup class for custom links
                    $smart_list_lightbox = "";

                    $buffy .= '<div class="tdb-sml-figure">';
                    $buffy .= '<figure class="tdb-slide-smart-list-figure' . $smart_list_lightbox . '">';
                    $buffy .= '<a class="td-sml-link-to-image" href="' . $first_img_src. '" data-caption="' . esc_attr($first_img_caption, ENT_QUOTES) . '" ' . $first_img_link_target . '>';
                    $buffy .= '<img src="' . $first_img_info[0] . '" alt="' . $first_img_alt . '"/>';
                    $buffy .= '</a>';
                    $buffy .= '</figure>';

                    if( !empty( $first_img_caption ) ) {
                        $buffy .= '<figcaption class="tdb-sml-caption"><div>' . $first_img_caption . '</div></figcaption>';
                    }
                    $buffy .= '</div>';
                } elseif ( td_util::get_option('tds_smart_list_modal_image') === 'hide') {
                    $buffy .= '<div class="tdb-sml-figure">';
                    $buffy .= '<figure class="tdb-slide-smart-list-figure' . $smart_list_lightbox . '">';
                    $buffy .= '<img src="' . $first_img_info[0] . '" alt="' . $first_img_alt . '"/>';
                    $buffy .= '</figure>';

                    if( !empty( $first_img_caption ) ) {
                        $buffy .= '<figcaption class="tdb-sml-caption"><div>' . $first_img_caption . '</div></figcaption>';
                    }
                    $buffy .= '</div>';
                } else {
                    $buffy .= '<div class="tdb-sml-figure">';
                    $buffy .= '<figure class="tdb-slide-smart-list-figure' . $smart_list_lightbox . '">';
                    $buffy .= '<a class="td-sml-link-to-image" href="' . $first_img_src. '" data-caption="' . esc_attr($first_img_caption, ENT_QUOTES) . '" ' . $first_img_link_target . '>';
                    $buffy .= '<img src="' . $first_img_info[0] . '" alt="' . $first_img_alt . '"/>';
                    $buffy .= '</a>';
                    $buffy .= '</figure>';

                    if( !empty( $first_img_caption ) ) {
                        $buffy .= '<figcaption class="tdb-sml-caption"><div>' . $first_img_caption . '</div></figcaption>';
                    }
                    $buffy .= '</div>';
                }


            }

        $buffy .= '</div>';

        return $buffy;
    }


    protected function render_after_list_wrap() {
        $buffy = '';
        $buffy .= '</div>'; // /.tdb_smart_list_2  wrapper with id

        return $buffy;
    }
}