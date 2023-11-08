<div class="td-meta-box-inside">

    <!-- post option general -->
    <div class="td-page-option-panel td-post-option-general td-page-option-panel-active td-cpt-option-general">

        <!-- if tds subscription plugin is active add locker settings -->
        <?php

        if ( defined('TD_SUBSCRIPTION' ) ) {

            // get panel cpt locker option status
	        $post_type = get_post_type();
	        $tds_custom_post_locker = $post_type ? td_util::get_ctp_option( $post_type, 'tds_custom_post_locker' ) : '';
	        if ( !empty( $tds_custom_post_locker ) ) {

                ?>

                <!-- locker state checkbox -->
                <div class="td-meta-box-row">
                    <?php $mb->the_field('tds_lock_content'); ?>
                    <span class="td-page-o-custom-label">Lock Content: <?php td_util::tooltip_html('<h3>Full content lock:</h3><p>The post content will be locked for non-subscribed users. The content unlocks only if users subscribe to your emailing list.</p>', 'right'); ?></span>
                    <input id="tds-lock-content"
                           class=""
                           style="position: relative; top: 7px; margin: 0; left: 3px;"
                           type="checkbox"
                           name="<?php $mb->the_name(); ?>"
                           value="1"
                        <?php if ( $mb->get_the_value() ) echo ' checked="checked"'; ?>
                    />
                </div>

                <!-- locker select -->
                <div class="td-meta-box-row">
            <span class="td-page-o-custom-label">Locker: <?php td_util::tooltip_html('<h3>#ID - Name - Custom Slug (optional):</h3><p>The Custom Slug has higher priority then ID, if it\'s set.</p>', 'right'); ?></span>
		    <?php

		    // locker id
		    $mb->the_field('tds_locker');

		    // the default locker id
		    $default_locker_id = (int) get_option( 'tds_default_locker_id' );

		    // default list select state
		    $def_locker_select_state = ( $mb->have_value() ) ? '' : ' selected="selected"';

		    function get_select_state( $locker, $ref_value ) {
			    $tds_locker_types = get_post_meta( $locker->ID, 'tds_locker_types', true );
			    if ( ( !empty( $tds_locker_types['tds_locker_slug'] ) && $ref_value === $tds_locker_types['tds_locker_slug'] ) || ( is_numeric( $ref_value ) && $locker->ID === intval( $ref_value ) ) ) {
				    return ' selected="selected"';
			    }
			    return '';
		    }

		    ?>
            <div class="td-select-style-overwrite td-inline-block-wrap">
			    <?php

			    // get tds lockers
			    $tds_lockers = get_posts(
				    array(
					    'post_type' => 'tds_locker',
					    'post_status' => 'publish',
					    'numberposts' => -1, // get all, no limit
					    'post__not_in' => array( $default_locker_id ), // exclude default locker
				    )
			    );

			    ?>
                <select name="<?php $mb->the_name(); ?>" class="td-panel-dropdown">
                    <option value="<?php echo $default_locker_id; ?>"<?php echo $def_locker_select_state; ?>>#<?php echo $default_locker_id ?> - Default Locker</option>
				    <?php

				    $locker_edit_url = null;

				    if ( !empty( $tds_lockers ) && is_array( $tds_lockers ) ) {
					    foreach ( $tds_lockers as $locker ) {

						    $custom_slug = '';
						    $tds_locker_types = get_post_meta( $locker->ID, 'tds_locker_types', true );
						    if (!empty($tds_locker_types['tds_locker_slug'])) {
							    $custom_slug = $tds_locker_types['tds_locker_slug'];
						    }

						    $current_state = get_select_state( $locker, $mb->get_the_value() );
						    if ( !empty( $current_state ) && empty( $locker_edit_url) ) {
							    $locker_edit_url = get_edit_post_link( $locker );
						    }

						    ?>
                            <option value="<?php echo ( empty( $custom_slug ) ? $locker->ID : $custom_slug ) ?>"<?php echo $current_state ?>>
							    <?php echo '#' . $locker->ID . ' - ' . $locker->post_title . ( empty( $custom_slug ) ? '' : ' - Custom slug: ' . $custom_slug )  ?>
                            </option>
						    <?php
					    }
				    }
				    ?>
                </select>
            </div>

		    <?php

		    if ( !empty( $locker_edit_url ) ) {
			    ?>

                <a href="<?php echo $locker_edit_url ?>" target="_blank" style="position: absolute;
                left: 400px;
                top: 7px;
                display: block;
                width: 120px;
                font-size: 13px;
                font-weight: bold;
                line-height: 19px;">Edit Current Locker</a>

			    <?php
		    }

		    ?>
        </div>

	            <?php

	        }
        }

        ?>

        <!-- primary taxonomy term -->
        <div class="td-meta-box-row">
            <span class="td-page-o-custom-label">
                Primary taxonomy:
                <?php
                td_util::tooltip_html('
                        <h3>Primary taxonomy term explained:</h3>
                        <p>The Primary taxonomy term will be used as a category label that appears on the thumbs and the category breadcrumb</p>
                        
                        <p>How the Primary taxonomy term is picked</p>
                        <ul>
                            <li><strong>Manually</strong> - If you select it from this box, this post will inherit all the settings form the <i>Primary term</i>.</li>
                            <li><strong>If the post has only one taxonomy term</strong> - that will be the <i>Primary term</i></li>
                            <li><strong>If the post has multiple categories and no manual Primary category</strong>, the theme will pick the first term from the terms of this post ordered alphabetically</li>
                        </ul>
                    ', 'right')
                ?>
            </span>
            <?php $mb->the_field('td_primary_cat');

            ?>
            <div class="td-select-style-overwrite td-inline-block-wrap">
                <select name="<?php $mb->the_name(); ?>" class="td-panel-dropdown">
                    <option value="">Auto select a term of taxonomy</option>
                    <?php
                    $post_type = get_post_type();
                    $td_taxonomies = get_object_taxonomies($post_type);
                    if ( !empty($td_taxonomies) ) {
                        $td_taxonomy_terms = get_terms($td_taxonomies);

                        foreach ($td_taxonomy_terms as $td_term) {

                            $td_term_name = $td_term->name;
                            $td_term_id = $td_term->term_id;

                            ?>
                            <option
                                value="<?php echo $td_term_id ?>"<?php $mb->the_select_state($td_term_id); ?>><?php echo $td_term_name ?></option>
                            <?php

                        }

                    }?>
                </select>
            </div>
            <span class="td-page-o-info">If the posts has multiple taxonomy terms, the one selected here will be used for settings and it appears in the category labels.</span>
        </div>

        <!-- sidebar position -->
        <div class="td-meta-box-row">
            <span class="td-page-o-custom-label">
                Sidebar position:
            </span>
            <?php
            echo td_panel_generator::visual_select_o(array(
                'ds' => 'td_post_theme_settings',
                'item_id' => '',
                'option_id' => 'td_sidebar_position',
                'values' => array(
                    array('text' => '', 'title' => '', 'val' => '', 'class' => 'td-sidebar-position-default', 'img' => TDC_URL_LEGACY_COMMON . '/wp_booster/wp-admin/images/panel/sidebar/sidebar-default.png'),
                    array('text' => '', 'title' => 'Sidebar Left', 'val' => 'sidebar_left', 'class' => 'td-sidebar-position-left', 'img' => TDC_URL_LEGACY_COMMON . '/wp_booster/wp-admin/images/panel/sidebar/sidebar-left.png'),
                    array('text' => '', 'title' => 'No Sidebar', 'val' => 'no_sidebar', 'class' => 'td-no-sidebar', 'img' => TDC_URL_LEGACY_COMMON . '/wp_booster/wp-admin/images/panel/sidebar/sidebar-full.png'),
                    array('text' => '', 'title' => 'Sidebar Right', 'val' => 'sidebar_right', 'class' => 'td-sidebar-position-right','img' => TDC_URL_LEGACY_COMMON . '/wp_booster/wp-admin/images/panel/sidebar/sidebar-right.png')
                ),
                'selected_value' => $mb->get_the_value('td_sidebar_position')
            ));
            ?>
        </div>

        <!-- custom sidebar -->
        <div class="td-meta-box-row">
            <span class="td-page-o-custom-label">
                Custom sidebar:
            </span>
            <?php
            echo td_panel_generator::sidebar_pulldown(array(
                'ds' => 'td_post_theme_settings',
                'item_id' => '',
                'option_id' => 'td_sidebar',
                'selected_value' => $mb->get_the_value('td_sidebar')
            ));
            ?>
        </div>
        <div class="td-meta-box-row">
            <?php $mb->the_field('td_subtitle'); ?>
            <span class="td-page-o-custom-label td_text_area_label">Subtitle:</span>
            <textarea name="<?php $mb->the_name(); ?>" class="td-textarea-subtitle"><?php $mb->the_value(); ?></textarea>
            <span class="td-page-o-info">This text will appear under on the CPT Source shortcode</span>
        </div>

        <div class="td-meta-box-row td-meta-box-gallery-imgs">
            <?php $mb->the_field('td_gallery_imgs'); ?>
            <span class="td-page-o-custom-label td_text_area_label">Gallery:</span>

            <input type="hidden" class="td-gallery-imgs-ids" name="<?php $mb->the_name(); ?>" value="<?php $mb->the_value(); ?>" />

            <div class="td-gallery-imgs-items">
                <div class="td-gipi-inner">
                    <?php 
                        $gallery_imgs_ids = $mb->get_the_value('td_gallery_imgs');

                        if( $gallery_imgs_ids ) {
                            $gallery_imgs_ids = explode(',', $gallery_imgs_ids);

                            $buffy = '';

                            foreach( $gallery_imgs_ids as $gallery_img_id ) {
                                $gallery_img_url = wp_get_attachment_image_src( $gallery_img_id );

                                if( $gallery_img_url ) {
                                    $buffy .= '<div class="td-gipi-item" data-img-id="' . $gallery_img_id . '">';
                                        $buffy .= '<div class="td-gipi-item-inner">';
                                            $buffy .= '<img src="' . $gallery_img_url[0] . '" />';

                                            $buffy .= '<div class="td-gipi-item-delete">X</div>';
                                        $buffy .= '</div>';
                                    $buffy .= '</div>';
                                }
                            }

                            echo $buffy;
                        }
                    ?>

                    <div class="td-gipi-item td-gipi-add">
                        <div class="td-gipi-item-inner">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M256 512c141.4 0 256-114.6 256-256S397.4 0 256 0S0 114.6 0 256S114.6 512 256 512zM232 344V280H168c-13.3 0-24-10.7-24-24s10.7-24 24-24h64V168c0-13.3 10.7-24 24-24s24 10.7 24 24v64h64c13.3 0 24 10.7 24 24s-10.7 24-24 24H280v64c0 13.3-10.7 24-24 24s-24-10.7-24-24z"/></svg>
                        </div>
                    </div>
                </div>
            </div>

            <span class="td-page-o-info">Add images which could be used to display a gallery on a post.</span>
        </div>

        <div class="td-meta-box-row">
            <?php $mb->the_field('td_source'); ?>
            <span class="td-page-o-custom-label">Source name:</span>
            <input class="td-input-text-post-settings" type="text" name="<?php $mb->the_name(); ?>" value="<?php $mb->the_value(); ?>"/>
            <span class="td-page-o-info">This name will appear at the end of the article in the "source" spot on the CPT Source shortcode </span>
        </div>

        <div class="td-meta-box-row">
            <?php $mb->the_field('td_source_url'); ?>
            <span class="td-page-o-custom-label">Source url:</span>
            <input class="td-input-text-post-settings" type="text" name="<?php $mb->the_name(); ?>" value="<?php $mb->the_value(); ?>"/>
            <span class="td-page-o-info">Full url to the source</span>
        </div>

        <div class="td-meta-box-row">
            <?php $mb->the_field('td_via'); ?>
            <span class="td-page-o-custom-label">Via name:</span>
            <input class="td-input-text-post-settings" type="text" name="<?php $mb->the_name(); ?>" value="<?php $mb->the_value(); ?>"/>
            <span class="td-page-o-info">Via (your source) name, this will appear at the end of the article in the "via" spot on the CPT Via shortcode</span>

        </div>

        <div class="td-meta-box-row">
            <?php $mb->the_field('td_via_url'); ?>
            <span class="td-page-o-custom-label">Via url:</span>
            <input class="td-input-text-post-settings" type="text" name="<?php $mb->the_name(); ?>" value="<?php $mb->the_value(); ?>"/>
            <span class="td-page-o-info">Full url for via</span>
        </div>

        <div class="td-meta-box-row">
            <?php $mb->the_field('td_custom_cat_name'); ?>
            <span class="td-page-o-custom-label">Custom Label:</span>
            <input class="td-input-text-post-settings" type="text" name="<?php $mb->the_name(); ?>" value="<?php $mb->the_value(); ?>"/>
            <span class="td-page-o-info">Custom Category Label name, this will appear on flex modules/blocks like a category tag</span>

        </div>
        <div class="td-meta-box-row">
            <?php $mb->the_field('td_custom_cat_name_url'); ?>
            <span class="td-page-o-custom-label">Custom Label url:</span>
            <input class="td-input-text-post-settings" type="text" name="<?php $mb->the_name(); ?>" value="<?php $mb->the_value(); ?>"/>
            <span class="td-page-o-info">Full url for Custom Label</span>
        </div>

    </div> <!-- /post option general -->
    
</div>

