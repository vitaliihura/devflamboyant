<?php

/**
 * Class tdb_form_taxonomies
 */

class tdb_form_taxonomies extends td_block {

    private $curr_post_id = '';
    private $term_type = '';
    private $disable_for_guests = false;

    public function get_custom_css() {
        // $unique_block_class - the unique class that is on the block. use this to target the specific instance via css
        $in_composer = td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax();
        $in_element = td_global::get_in_element();
        $unique_block_class_prefix = '';
        if( $in_element || $in_composer ) {
            $unique_block_class_prefix = 'tdc-row';

            if( $in_element && $in_composer ) {
                $unique_block_class_prefix = 'tdc-row-composer';
            }
        }
        $general_block_class = $unique_block_class_prefix ? '.' . $unique_block_class_prefix : '';
        $unique_block_class = ( $unique_block_class_prefix ? $unique_block_class_prefix . ' .' : '' ) . ( $in_composer ? 'tdc-column .' : '' ) . $this->block_uid;

        $compiled_css = '';

        $raw_css =
            "<style>

                /* @style_general_tdb_form_taxonomies */
                .tdb_form_taxonomies {
                    transform: translateZ(0);
                    margin-bottom: 28px;
                    font-family: -apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,Oxygen-Sans,Ubuntu,Cantarell,\"Helvetica Neue\",sans-serif;
                    font-size: 14px;
                }
                .tdb_form_taxonomies a:not(.tdb-s-btn) {
                    color: #0489FC;
                }
                .tdb_form_taxonomies .tdb-s-content:after {
                    margin-top: -10px;
                    margin-left: -10px;
                    width: 17px;
                    height: 17px;
                    border-width: 2px;
                }
                .tdb_form_taxonomies .tdb-ft-limit-warning,
                .tdb_form_taxonomies .tdb-ft-limit-warning li {
                    display: none;
                }
                .tdb_form_taxonomies .tdb-s-form .tdb-ft-limit-warning {
                    margin-top: 0;
                    margin-bottom: 28px;
                    padding-bottom: 19px;
                }
                .tdb_form_taxonomies .tdb-ft-limit-warning li {
                    margin-bottom: 6px;
                }
                .tdb_form_taxonomies .tdb-s-form-group {
                    display: flex;
                }
                .tdb_form_taxonomies .tdb-s-form-label {
                    display: flex;
                    align-items: center;
                    flex-wrap: wrap;
                }
                .tdb_form_taxonomies [data-display=\"checkbox\"] .tdb-s-form-label {
                    margin-bottom: 12px;
                }
                .tdb_form_taxonomies .tdb-s-form-label-inner {
                    order: 1;
                }
                .tdb_form_taxonomies .tdb-ft-create-term {
                    order: 2;
                    justify-content: flex-start;
                    margin-left: auto;
                }
                .tdb_form_taxonomies .tdb-ft-checkbox-search {
                    order: 3;
                    width: 24%;
                    height: 23px;
                    min-height: 23px;
                    margin-left: auto;
                    padding: 0 8px 2px;
                    font-size: .857em;
                    border-color: #e4e5e9;
                    border-radius: 3px;
                }
                .tdb_form_taxonomies .tdb-ft-create-term + .tdb-ft-checkbox-search {
                    margin-left: 13px;
                }
                .tdb_form_taxonomies .tdb-ft-checkboxes {
                    max-height: 150px;
                    margin-bottom: -7px;
                    overflow-y: auto;
                    scrollbar-width: thin;
                }
                .tdb_form_taxonomies .tdb-ft-checkboxes::-webkit-scrollbar {
                    width: 3px;
                }
                .tdb_form_taxonomies .tdb-ft-checkboxes::-webkit-scrollbar-track {
                    background-color: transparent;
                }
                .tdb_form_taxonomies .tdb-ft-checkboxes::-webkit-scrollbar-thumb {
                    background-color: #ddd;
                }
                .tdb_form_taxonomies .tdb-s-form-checkboxes-wrap {
                    width: 100%;
                }
                .tdb_form_taxonomies .tdb-ft-all-terms .tdb-s-form-checkboxes-wrap {
                    display: block;
                    margin-left: 0;
                    margin-right: 0;
                    column-count: 3;
                    column-gap: 26px;
                }
                 .tdb_form_taxonomies .tdb-ft-all-terms .tdb-s-form-check {
                    padding-left: 0;
                    padding-right: 0;
                 }
                 .tdb_form_taxonomies .tdb-s-form-checkboxes-wrap .tdb-s-form-check:last-child {
                    margin-bottom: 7px;
                 }
                .tdb_form_taxonomies .tdb-s-form-check,
                .tdb_form_taxonomies .tdb-s-form-check input,
                .tdb_form_taxonomies .tdb-s-fc-check,
                .tdb_form_taxonomies .tdb-s-fc-title {
                    pointer-events: none;
                }
                .tdb_form_taxonomies .tdb-s-form-group:not(.tdb-ft-all-terms) .tdb-s-form-check {
                    width: 33.3333%;
                }
                .tdb_form_taxonomies .tdb-ft-all-terms .tdb-s-form-check:last-child {
                    margin-bottom: 7px;
                }
                .tdb_form_taxonomies .tdb-s-fc-label {
                    pointer-events: auto;
                }
                .tdb-ft-create-term-modal .tdb-s-modal {
                    min-width: 600px;
                    max-width: 600px;
                }
                .tdb-ft-create-term-modal .tdb-s-form-content .tdb-s-notif {
                    margin-top: 21px;
                }
                .tdb-ft-create-term-modal .tdb-s-fc-inner .tdb-s-notif {
                    margin-left: 8px;
                    margin-right: 8px;
                }
                .tdb-ft-create-term-modal .tdb-s-fc-inner {
                    margin: 0 -8px;
                }
                .tdb-ft-create-term-modal .tdb-s-form-group,
                .tdb-ft-create-term-modal .tdb-s-fg-error-msg {
                    padding: 0 8px;
                }
                .tdb-ft-create-term-modal .tdb-s-form-group:not(:last-child) {
                    margin-bottom: 17px;
                }
                .tdb-ft-create-term-modal .tdb-s-form-group-name {
                    flex: 1;
                }
                .tdb-ft-create-term-modal .tdb-s-form-group-parent {
                    width: 50%;
                }
                .tdb-ft-create-term-modal .tdb-s-form-group-descr {
                    margin-bottom: 0 !important;
                }
                .tdb-ft-create-term-modal .tdb-s-content {
                    min-height: 0;
                }
                .tdb-ft-create-term-modal .tdb-s-content-loading:after {
                    margin-top: -10px;
                    margin-left: -10px;
                    width: 17px;
                    height: 17px;
                    border-width: 2px;
                }
                
                /* @style_general_tdb_form_taxonomies_composer */
                .tdb_form_taxonomies .tdb-block-inner {
                    pointer-events: none;
                }
                
                
                
                /* @all_input_display_row */
                body .$unique_block_class .tdb-s-form-group {
                    flex-direction: column;
                    align-items: stretch;
                }
                body .$unique_block_class .tdb-s-form .tdb-s-form-label {
                    width: 100%;
                    margin: 0 0 8px;
                }
                body .$unique_block_class .tdb-s-form .tdb-s-form-label-descr {
                    margin-bottom: 2px;
                }
                body .$unique_block_class .tdb-s-form .tdb-ft-checkbox-search {
                    width: 24%;
                    margin: 0 0 0 auto;
                }
                body .$unique_block_class .tdb-s-form .tdb-ft-create-term {
                    order: 2;
                    width: auto;
                    margin: 0 0 0 auto;
                }
                body .$unique_block_class .tdb-s-form .tdb-ft-create-term + .tdb-ft-checkbox-search {
                    margin: 0 0 0 13px;
                }
                body .$unique_block_class .tdb-s-form .tdb-ft-checkboxes,
                body .$unique_block_class .tdb-s-form .tdb-s-form-select-wrap {
                    width: 100%;
                }
                
                /* @all_input_display_columns */
                body .$unique_block_class .tdb-s-form-group {
                    flex-direction: row;
                    align-items: flex-start;
                }
                body .$unique_block_class .tdb-s-form .tdb-s-form-label {
                    width: @all_label_width;
                    margin: 0 24px 0 0;
                }
                body .$unique_block_class .tdb-s-form .tdb-s-form-label-descr {
                    margin-bottom: 0;
                }
                body .$unique_block_class .tdb-s-form .tdb-ft-checkbox-search {
                    width: 100%;
                    margin: 8px 0 0;
                }
                body .$unique_block_class .tdb-s-form .tdb-ft-create-term {
                    order: 4;
                    width: 100%;
                    margin: 8px 0 0;
                }
                body .$unique_block_class .tdb-s-form .tdb-ft-checkboxes,
                body .$unique_block_class .tdb-s-form .tdb-s-form-select-wrap {
                    flex: 1;
                }
                
                
                /* @all_input_border */
                body .$unique_block_class .tdb-s-form .tdb-s-form-input,
                body .$unique_block_class .tdb-s-form .tdb-s-form-select-wrap .select2-selection,
                body .tdb-s-select2-$unique_block_class.select2-dropdown,
                body .tdb-s-select2-$unique_block_class .select2-search__field,
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-form .tdb-s-form-input {
                    border: @all_input_border @all_input_border_style @all_input_border_color;
                }
                body .$unique_block_class .tdb-s-form .tdb-s-form-check .tdb-s-fc-check {
                    border: 2px solid @all_input_border_color;
                }
                /* @input_radius */
                body .$unique_block_class .tdb-s-form .tdb-s-form-input:not(.tdb-ft-checkbox-search),
                body .$unique_block_class .tdb-s-form .tdb-s-form-select-wrap .select2-selection,
                body .tdb-s-select2-$unique_block_class.select2-dropdown,
                body .tdb-s-select2-$unique_block_class .select2-search__field,
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-form .tdb-s-form-input {
                    border-radius: @input_radius;
                }
                
                /* @drop_height */
                body .tdb-s-select2-$unique_block_class .select2-results__options {
                    max-height: @drop_height;
                }
                
                /* @check_col */
                body .$unique_block_class .tdb-s-form-group:not(.tdb-ft-all-terms) .tdb-s-form-check {
                    width: @check_col;
                }
                /* @check_col_all_terms */
                body .$unique_block_class .tdb-ft-all-terms .tdb-s-form-checkboxes-wrap {
                    column-count: @check_col_all_terms;
                }
                
                /* @check_height */
                body .$unique_block_class .tdb-ft-checkboxes {
                    max-height: @check_height;
                }
                
                /* @modal_width */
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-modal {
                    min-width: @modal_width;
                    max-width: @modal_width;
                }
                /* @modal_radius */
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-modal {
                    border-radius: @modal_radius;
                }
                
                /* @btn_radius */
                body .$unique_block_class .tdb-s-btn,
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-btn {
                    border-radius: @btn_radius;
                }
                
                /* @notif_radius */
                body .$unique_block_class .tdb-s-notif,
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-notif {
                    border-radius: @notif_radius;
                }
                
                
                /* @accent_color */
                body .$unique_block_class a:not(.tdb-s-btn),
                body .$unique_block_class .tdb-s-btn-hollow,
                body .$unique_block_class .tdb-s-btn-simple,
                body .tdb-ft-create-term-modal-$unique_block_class a:not(.tdb-s-btn),
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-btn-hollow,
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-btn-simple {
                    color: @accent_color;
                }
                body .$unique_block_class .tdb-s-btn:not(.tdb-s-btn-hollow):not(.tdb-s-btn-simple),
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-btn:not(.tdb-s-btn-hollow):not(.tdb-s-btn-simple),
                body .$unique_block_class .tdb-s-form .tdb-s-form-check .tdb-s-fc-check:after {
                    background-color: @accent_color;
                }
                body .$unique_block_class .tdb-s-btn-hollow,
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-btn-hollow {
                    border-color: @accent_color;
                }
                body .$unique_block_class .tdb-s-form .tdb-s-form-group:not(.tdb-s-fg-error) .tdb-s-form-input:focus:not([readonly]),
                body .$unique_block_class .tdb-s-form .tdb-s-form-check input:checked + .tdb-s-fc-check,
                body .$unique_block_class .tdb-s-form .tdb-s-form-select-wrap .select2-container--open .select2-selection,
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-form-group:not(.tdb-s-fg-error) .tdb-s-form-input:focus:not([readonly]) {
                    border-color: @accent_color !important;
                }
                /* @input_outline_accent_color */
                body .$unique_block_class .tdb-s-form .tdb-s-form-group:not(.tdb-s-fg-error) .tdb-s-form-input:focus:not([readonly]),
                body .$unique_block_class .tdb-s-form .tdb-s-form-check input:checked + .tdb-s-fc-check,
                body .$unique_block_class .tdb-s-form .tdb-s-form-select-wrap .select2-container--open .select2-selection,
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-form-group:not(.tdb-s-fg-error) .tdb-s-form-input:focus:not([readonly]) {
                    outline-color: @input_outline_accent_color;
                }
                
                /* @label_color */
                body .$unique_block_class .tdb-s-form .tdb-s-form-label,
                body .$unique_block_class .tdb-s-form .tdb-s-form-check .tdb-s-fc-title,
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-form .tdb-s-form-label {
                    color: @label_color;
                }
                /* @descr_color */
                body .$unique_block_class .tdb-s-form .tdb-s-form-label-descr {
                    color: @descr_color;
                }
                /* @input_color */
                body .$unique_block_class .tdb-s-form .tdb-s-form-input,
                body .$unique_block_class .tdb-s-form .tdb-s-form-select-wrap .select2-selection,
                body .tdb-s-select2-$unique_block_class .select2-search__field,
                body .tdb-s-select2-$unique_block_class .select2-results__options,
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-form .tdb-s-form-input {
                    color: @input_color;
                }
                body .$unique_block_class .tdb-s-form .tdb-s-form-input:-webkit-autofill,
                body .$unique_block_class .tdb-s-form .tdb-s-form-input:-webkit-autofill:hover,
                body .$unique_block_class .tdb-s-form .tdb-s-form-input:-webkit-autofill:focus,
                body .$unique_block_class .tdb-s-form .tdb-s-form-input:-webkit-autofill:active,
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-form .tdb-s-form-input:-webkit-autofil,
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-form .tdb-s-form-input:-webkit-autofill:hover,
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-form .tdb-s-form-input:-webkit-autofill:focus,
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-form .tdb-s-form-input:-webkit-autofill:active {
                    -webkit-text-fill-color: @input_color;
                }
                body .$unique_block_class .tdb-s-form .tdb-s-form-select-wrap .tdb-s-form-select-icon {
                    fill: @input_color;
                }
                /* @input_bg */
                body .$unique_block_class .tdb-s-form .tdb-s-form-input,
                body .$unique_block_class .tdb-s-form .tdb-s-form-check .tdb-s-fc-check,
                body .$unique_block_class .tdb-s-form .tdb-s-form-select-wrap .select2-selection,
                body .tdb-s-select2-$unique_block_class.select2-dropdown,
                body .tdb-s-select2-$unique_block_class .select2-search__field,
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-form .tdb-s-form-input {
                    background-color: @input_bg;
                }
                body .$unique_block_class .tdb-s-form .tdb-s-form-input:-webkit-autofill,
                body .$unique_block_class .tdb-s-form .tdb-s-form-input:-webkit-autofill:hover,
                body .$unique_block_class .tdb-s-form .tdb-s-form-input:-webkit-autofill:focus,
                body .$unique_block_class .tdb-s-form .tdb-s-form-input:-webkit-autofill:active,
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-form .tdb-s-form-input:-webkit-autofil,
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-form .tdb-s-form-input:-webkit-autofill:hover,
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-form .tdb-s-form-input:-webkit-autofill:focus,
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-form .tdb-s-form-input:-webkit-autofill:active {
                    -webkit-box-shadow: 0 0 0 1000px @input_bg inset !important;
                }
                /* @input_select2_outline_color */
                body .tdb-s-select2-$unique_block_class.select2-dropdown {
                    outline-color: @input_select2_outline_color;
                }
                
                /* @option_color_h */
                body .tdb-s-select2-$unique_block_class .select2-results__options li:hover {
                    color: @option_color_h;
                }
                /* @option_bg_h */
                body .tdb-s-select2-$unique_block_class .select2-results__options li:hover {
                    background-color: @option_bg_h;
                }
                
                /* @modal_bg */
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-modal {
                    background-color: @modal_bg;
                }
                /* @modal_overlay_solid */
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-modal-bg {
                    background-color: @modal_overlay_solid;
                }
                /* @modal_overlay_gradient */
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-modal-bg {
                    @modal_overlay_gradient
                }
                /* @modal_title */
                body .tdb-ft-create-term-modal-$unique_block_class h3.tdb-s-modal-title {
                    color: @modal_title;
                }
                /* @modal_close */
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-modal-header .tdb-s-modal-close {
                    fill: @modal_close;
                }
                /* @modal_sep */
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-modal-header {
                    border-bottom-color: @modal_sep;
                }
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-modal-footer {
                    border-top-color: @modal_sep;
                }
                /* @modal_shadow */
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-modal {
                    box-shadow: @modal_shadow;
                }
                
                /* @btn_color */
                body .$unique_block_class .tdb-s-btn,
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-btn {
                    color: @btn_color;
                }
                /* @btn_color_h */
                body .$unique_block_class .tdb-s-btn:hover,
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-btn:hover {
                    color: @btn_color_h;
                }
                /* @btn_bg_h */
                body .$unique_block_class .tdb-s-btn:not(.tdb-s-btn-hollow):not(.tdb-s-btn-simple):hover,
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-btn:not(.tdb-s-btn-hollow):not(.tdb-s-btn-simple):hover {
                    background-color: @btn_bg_h;
                }
                body .$unique_block_class .tdb-s-btn-hollow:hover,
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-btn-hollow:hover {
                    border-color: @btn_bg_h;
                }
                
                /* @notif_warning_color */
                body .$unique_block_class .tdb-s-notif-warning,
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-notif-warning {
                    color: @notif_warning_color;
                }
                /* @notif_warning_bg */
                body .$unique_block_class .tdb-s-notif-warning,
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-notif-warning {
                    background-color: @notif_warning_bg;
                }
                /* @notif_error_color */
                body .$unique_block_class .tdb-s-notif-error,
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-notif-error {
                    color: @notif_error_color;
                }
                /* @notif_error_bg */
                body .$unique_block_class .tdb-s-notif-error,
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-notif-error {
                    background-color: @notif_error_bg;
                }
                
                
                /* @f_text */
                body .$unique_block_class,
                body .tdb-s-select2-$unique_block_class.select2-dropdown,
                body .tdb-ft-create-term-modal-$unique_block_class .tdb-s-modal {
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
        $res_ctx->load_settings_raw( 'style_general_tdb_form_taxonomies', 1 );
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $res_ctx->load_settings_raw( 'style_general_tdb_form_taxonomies_composer', 1 );
        }



        /*-- LAYOUT -- */
        // inputs display
        $all_input_display = $res_ctx->get_shortcode_att('all_input_display');
        if( $all_input_display == '' || $all_input_display == 'row' ) {
            $res_ctx->load_settings_raw( 'all_input_display_row', 1 );
        } else {
            $res_ctx->load_settings_raw( 'all_input_display_columns', 1 );
        }

        // labels width
        $all_label_width = $res_ctx->get_shortcode_att('all_label_width');
        $res_ctx->load_settings_raw( 'all_label_width', $all_label_width );
        if( $all_label_width != '' ) {
            if( is_numeric( $all_label_width ) ) {
                $res_ctx->load_settings_raw( 'all_label_width', $all_label_width . 'px' );
            }
        } else {
            $res_ctx->load_settings_raw( 'all_label_width', '30%' );
        }

        // inputs border size
        $all_input_border = $res_ctx->get_shortcode_att('all_input_border');
        $res_ctx->load_settings_raw( 'all_input_border', $all_input_border );
        if( $all_input_border == '' ) {
            $res_ctx->load_settings_raw( 'all_input_border', '2px' );
        } else {
            if( is_numeric( $all_input_border ) ) {
                $res_ctx->load_settings_raw( 'all_input_border', $all_input_border . 'px' );
            }
        }

        // inputs border style
        $all_input_border_style = $res_ctx->get_shortcode_att('all_input_border_style');
        if( $all_input_border_style != '' ) {
            $res_ctx->load_settings_raw( 'all_input_border_style', $all_input_border_style );
        } else {
            $res_ctx->load_settings_raw( 'all_input_border_style', 'solid' );
        }

        // dropdown list height
        $drop_height = $res_ctx->get_shortcode_att('drop_height');
        $res_ctx->load_settings_raw( 'drop_height', $drop_height );
        if( $drop_height != '' && is_numeric( $drop_height ) ) {
            $res_ctx->load_settings_raw( 'drop_height', $drop_height . 'px' );
        }

        // checkboxes columns
        $show_all_terms = $res_ctx->get_shortcode_att('show_all_terms');
        $check_col = $res_ctx->get_shortcode_att('check_col');
        if( $check_col == '' ) {
            $check_col = '1';
        }
        if( $show_all_terms != '' ) {
            $res_ctx->load_settings_raw( 'check_col_all_terms', $check_col );
        } else {
            $check_col_width = '';

            switch ( $check_col ) {
                case '1':
                    $check_col_width = '100%';
                    break;
                case '2':
                    $check_col_width = '50%';
                    break;
                case '3':
                    $check_col_width = '33.3333%';
                    break;
                case '4':
                    $check_col_width = '25%';
                    break;
                case '5':
                    $check_col_width = '20%';
                    break;
                case '6':
                    $check_col_width = '16.6666%';
                    break;
            }

            $res_ctx->load_settings_raw( 'check_col', $check_col_width );
        }


        // checkboxes group height
        $check_height = $res_ctx->get_shortcode_att('check_height');
        $res_ctx->load_settings_raw( 'check_height', $check_height );
        if( $check_height != '' && is_numeric( $check_height ) ) {
            $res_ctx->load_settings_raw( 'check_height', $check_height . 'px' );
        }


        // notifications border radius
        $notif_radius = $res_ctx->get_shortcode_att('notif_radius');
        $res_ctx->load_settings_raw( 'notif_radius', $notif_radius );
        if( $notif_radius != '' && is_numeric( $notif_radius ) ) {
            $res_ctx->load_settings_raw( 'notif_radius', $notif_radius . 'px' );
        }


        // modal width
        $modal_width = $res_ctx->get_shortcode_att('modal_width');
        $res_ctx->load_settings_raw( 'modal_width', $modal_width );
        if( $modal_width != '' && is_numeric( $modal_width ) ) {
            $res_ctx->load_settings_raw( 'modal_width', $modal_width . 'px' );
        }

        // modal border radius
        $modal_radius = $res_ctx->get_shortcode_att('modal_radius');
        $res_ctx->load_settings_raw( 'modal_radius', $modal_radius );
        if( $modal_radius != '' && is_numeric( $modal_radius ) ) {
            $res_ctx->load_settings_raw( 'modal_radius', $modal_radius . 'px' );
        }


        // buttons border radius
        $btn_radius = $res_ctx->get_shortcode_att('btn_radius');
        $res_ctx->load_settings_raw( 'btn_radius', $btn_radius );
        if( $btn_radius != '' && is_numeric( $btn_radius ) ) {
            $res_ctx->load_settings_raw( 'btn_radius', $btn_radius . 'px' );
        }


        // notifications border radius
        $notif_radius = $res_ctx->get_shortcode_att('notif_radius');
        $res_ctx->load_settings_raw( 'notif_radius', $notif_radius );
        if( $notif_radius != '' && is_numeric( $notif_radius ) ) {
            $res_ctx->load_settings_raw( 'notif_radius', $notif_radius . 'px' );
        }



        /*-- COLORS -- */
        $accent_color = $res_ctx->get_shortcode_att('accent_color');
        $res_ctx->load_settings_raw( 'accent_color', $accent_color );
        if( !empty( $accent_color ) ) {
            $res_ctx->load_settings_raw('input_outline_accent_color', td_util::hex2rgba($accent_color, 0.1));
        }

        $res_ctx->load_settings_raw( 'label_color', $res_ctx->get_shortcode_att('label_color') );
        $res_ctx->load_settings_raw( 'descr_color', $res_ctx->get_shortcode_att('descr_color') );
        $res_ctx->load_settings_raw( 'input_color', $res_ctx->get_shortcode_att('input_color') );
        $res_ctx->load_settings_raw( 'input_bg', $res_ctx->get_shortcode_att('input_bg') );
        $all_input_border_color = $res_ctx->get_shortcode_att('all_input_border_color');
        if( $all_input_border_color != '' ) {
            $res_ctx->load_settings_raw( 'all_input_border_color', $all_input_border_color );
            $res_ctx->load_settings_raw( 'input_select2_outline_color', td_util::hex2rgba($all_input_border_color, 0.18));
        } else {
            $res_ctx->load_settings_raw( 'all_input_border_color', '#D7D8DE' );
        }
        $res_ctx->load_settings_raw( 'option_color_h', $res_ctx->get_shortcode_att('option_color_h') );
        $res_ctx->load_settings_raw( 'option_bg_h', $res_ctx->get_shortcode_att('option_bg_h') );

        $res_ctx->load_settings_raw( 'modal_bg', $res_ctx->get_shortcode_att('modal_bg') );
        $res_ctx->load_color_settings( 'modal_overlay', 'modal_overlay_solid', 'modal_overlay_gradient', '', '' );
        $res_ctx->load_settings_raw( 'modal_title', $res_ctx->get_shortcode_att('modal_title') );
        $res_ctx->load_settings_raw( 'modal_close', $res_ctx->get_shortcode_att('modal_close') );
        $res_ctx->load_settings_raw( 'modal_sep', $res_ctx->get_shortcode_att('modal_sep') );
        $res_ctx->load_shadow_settings( 4, 0, 2, 0, 'rgba(0, 0, 0, .12)', 'modal_shadow' );

        $res_ctx->load_settings_raw( 'btn_color', $res_ctx->get_shortcode_att('btn_color') );
        $res_ctx->load_settings_raw( 'btn_color_h', $res_ctx->get_shortcode_att('btn_color_h') );
        $res_ctx->load_settings_raw( 'btn_bg_h', $res_ctx->get_shortcode_att('btn_bg_h') );

        $notif_warning_color = $res_ctx->get_shortcode_att('notif_warning_color');
        $res_ctx->load_settings_raw( 'notif_warning_color', $notif_warning_color );
        if( !empty( $notif_warning_color ) ) {
            $res_ctx->load_settings_raw('notif_warning_bg', td_util::hex2rgba($notif_warning_color, 0.08));
        }

        $notif_error_color = $res_ctx->get_shortcode_att('notif_error_color');
        $res_ctx->load_settings_raw( 'notif_error_color', $notif_error_color );
        if( !empty( $notif_error_color ) ) {
            $res_ctx->load_settings_raw('notif_error_bg', td_util::hex2rgba($notif_error_color, 0.12));
        }



        /*-- FONTS -- */
        $res_ctx->load_font_settings( 'f_text' );

    }

    /**
     * Disable loop block features. This block does not use a loop and it doesn't need to run a query.
     */
    function __construct() {
        parent::disable_loop_block_features();
    }


    function render( $atts, $content = null ) {

        parent::render( $atts ); // sets the live atts, $this->atts, $this->block_uid, $this->td_query (it runs the query)

        // currently, logged-in user
        $current_user = wp_get_current_user();
        $current_user_id = $current_user->ID;
        $is_current_user_admin = in_array('administrator', $current_user->roles);


        // taxonomy types
        $this->term_type = $this->get_att('tax_types') != '' ? $this->get_att('tax_types') : 'category';


        // required field
        $required = $this->get_att('required');
        if( $required != '' ) {
            $required = 1;
        } else {
            $required = 0;
        }


        // Enable only for authenticated users
        $authenticated_users = $this->get_att('authenticated_users');
        $this->disable_for_guests = $authenticated_users != '' && !is_user_logged_in();

        // current post id
        if ( isset($_GET['post_id']) && !( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) ) {
            $post = get_post($_GET['post_id']);

            if( $post && ( $post->post_author == $current_user_id || $is_current_user_admin ) ) {
                $this->curr_post_id = $_GET['post_id'];
            }
        }


        // disable limits for admins
        $disable_limits = $this->get_att('disable_limits');

        // limit reached notification messages
        $parent_limit_notif = rawurldecode( base64_decode( strip_tags( $this->get_att('parent_limit_notif') ) ) );
        $child_limit_notif = rawurldecode( base64_decode( strip_tags( $this->get_att('child_limit_notif') ) ) );
        $sub_child_limit_notif = rawurldecode( base64_decode( strip_tags( $this->get_att('sub_child_limit_notif') ) ) );


        $show_all_terms = $this->get_att('show_all_terms') != '';
        $disable_search = $this->get_att('disable_search') != '';
        $enable_term_create = $this->get_att('term_create') != '';

        $order_by = $this->get_att('orderby') != '' ? $this->get_att('orderby') : 'name';
        $order = $this->get_att('order') != '' ? $this->get_att('order') : 'ASC';


        // parent terms
        $term_args = array(
            'taxonomy' => $this->term_type,
            'hide_empty' => 0,
            'orderby' => $order_by,
            'order' => $order
        );

        if( !$show_all_terms ) {
            $term_args['parent'] = 0;
        }

        $parent_terms = get_terms($term_args);
        $parent_terms_final = array();
        if( !empty( $parent_terms ) && !is_wp_error( $parent_terms ) ) {
            $parent_terms_final = $this->build_terms_array($parent_terms, 0);
        }

        // parent terms display
        $parent_display = $this->get_att('parent_display') != '' ? $this->get_att('parent_display') : 'dropdown';

        // parent terms limit
        $parent_limit = -1;

        if( $parent_display == 'checkbox' ) {
            $parent_limit_def = $this->get_att('parent_limit_def');

            if( $parent_limit_def != '' ) {
                $parent_limit = $parent_limit_def;

                if( defined( 'TD_SUBSCRIPTION' ) && method_exists( 'tds_util', 'is_user_subscribed_to_plan' ) && $parent_limit > -1 ) {
                    for ($i = 0; $i < 5; $i++) {
                        $plan_ids = explode(',', $this->get_att('parent_limit_plans_' . $i . '_id'));
                        $plan_limit = $this->get_att('parent_limit_plans_' . $i . '_limit') != '' ? $this->get_att('parent_limit_plans_' . $i . '_limit') : -1;

                        foreach ( $plan_ids as $plan_id ) {
                            if( tds_util::is_user_subscribed_to_plan( $current_user->ID, $plan_id ) ) {
                                $parent_limit = $plan_limit == -1 ? $plan_limit : max($parent_limit, $plan_limit);
                            }
                        }
                    }
                }
            }
        }

        // child terms
        $child_display = $this->get_att('child_display') != '' ? $this->get_att('child_display') : 'dropdown';

        // child terms limit
        $child_limit = -1;

        if( $child_display == 'checkbox' ) {
            $child_limit_def = $this->get_att('child_limit_def');

            if( $child_limit_def != '' ) {
                $child_limit = $child_limit_def;

                if( defined( 'TD_SUBSCRIPTION' ) && method_exists( 'tds_util', 'is_user_subscribed_to_plan' ) && $child_limit > -1 ) {
                    for ($i = 0; $i < 5; $i++) {
                        $plan_ids = explode(',', $this->get_att('child_limit_plans_' . $i . '_id'));
                        $plan_limit = $this->get_att('child_limit_plans_' . $i . '_limit') != '' ? $this->get_att('child_limit_plans_' . $i . '_limit') : -1;

                        foreach ( $plan_ids as $plan_id ) {
                            if( tds_util::is_user_subscribed_to_plan( $current_user->ID, $plan_id ) ) {
                                $child_limit = $plan_limit == -1 ? $plan_limit : max($child_limit, $plan_limit);
                            }
                        }
                    }
                }
            }
        }

        // sub-child terms
        $sub_child_display = $this->get_att('sub_child_display') != '' ? $this->get_att('sub_child_display') : 'dropdown';

        // sub-child terms limit
        $sub_child_limit = -1;

        if( $sub_child_display == 'checkbox' ) {
            $sub_child_limit_def = $this->get_att('sub_child_limit_def');

            if( $sub_child_limit_def != '' ) {
                $sub_child_limit = $sub_child_limit_def;

                if( defined( 'TD_SUBSCRIPTION' ) && method_exists( 'tds_util', 'is_user_subscribed_to_plan' ) && $sub_child_limit > -1 ) {
                    for ($i = 0; $i < 5; $i++) {
                        $plan_ids = explode(',', $this->get_att('sub_child_limit_plans_' . $i . '_id'));
                        $plan_limit = $this->get_att('sub_child_limit_plans_' . $i . '_limit') != '' ? $this->get_att('sub_child_limit_plans_' . $i . '_limit') : -1;

                        foreach ( $plan_ids as $plan_id ) {
                            if( tds_util::is_user_subscribed_to_plan( $current_user->ID, $plan_id ) ) {
                                $sub_child_limit = $plan_limit == -1 ? $plan_limit : max($sub_child_limit, $plan_limit);
                            }
                        }
                    }
                }
            }
        }

        $buffy = ''; //output buffer

        $buffy .= '<div class="' . $this->get_block_classes() . ' ' . ( $this->disable_for_guests ? 'tdb-disabled' : '' ) . '" ' . $this->get_block_html_atts() . ' data-tax-type="' . $this->term_type . '" data-required="' . $required . '">';

            //get the block css
            $buffy .= $this->get_block_css();

            //get the js for this block
            $buffy .= $this->get_block_js();


            if( empty( $parent_terms_final ) ) {
                $buffy .= td_util::get_block_error('Posts Form Taxonomies', 'The selected taxonomy types do not have any items.');
            } else {
                $term_labels = get_taxonomy_labels( get_taxonomy($this->term_type) );
                $term_plural_name = $term_labels->name;
                $term_singular_name = $term_labels->singular_name;
                $term_is_hierarchical = is_taxonomy_hierarchical($this->term_type);

                // label text
                $label_txt = $this->get_att('label_txt');
                if( $label_txt == '' ) {
                    $label_txt = $term_plural_name;
                }

                // Label description
                $label_descr_txt = rawurldecode( base64_decode( strip_tags( $this->get_att('descr_txt') ) ) );

                $buffy .= '<div class="tdb-block-inner td-fix-index tdb-s-form tdb-s-content">';
                    $buffy .= '<div class="tdb-s-form-content">';
                        $buffy .= '<div class="tdb-s-fc-inner">';
                            if( $parent_limit_notif != '' || $child_limit_notif != '' || $sub_child_limit_notif != '' ) {
                                $buffy .= '<div class="tdb-s-notif tdb-s-notif-warning tdb-ft-limit-warning">';
                                    $buffy .= '<ul class="tdb-s-notif-list">';
                                        if( $parent_limit_notif != '' ) {
                                            $buffy .= '<li class="tdb-ft-limit-parent-notif">' . str_replace('%tax_name%', lcfirst($term_plural_name), $parent_limit_notif) . '</li>';
                                        }
                                        if( $child_limit_notif != '' ) {
                                            $buffy .= '<li class="tdb-ft-limit-child-notif">' . str_replace('%tax_name%', lcfirst($term_plural_name), $child_limit_notif) . '</li>';
                                        }
                                        if( $sub_child_limit_notif != '' ) {
                                            $buffy .= '<li class="tdb-ft-limit-sub-child-notif">' . str_replace('%tax_name%', lcfirst($term_plural_name), $sub_child_limit_notif) . '</li>';
                                        }
                                    $buffy .= '</ul>';
                                $buffy .= '</div>';
                            }

                            $buffy .= '<div class="tdb-s-form-group ' . ( $show_all_terms ? 'tdb-ft-all-terms' : '' ) . '" data-term-hierarchy="parent" data-display="' . $parent_display . '">';
                                $buffy .= '<label class="tdb-s-form-label" for="tdb-t-parent-' . $this->block_uid . '">';
                                    $buffy .= '<span class="tdb-s-form-label-inner">';
                                        $buffy .= $label_txt;

                                        if( $required ) {
                                            $buffy .= '<span class="tdb-s-form-label-required"> *</span>';
                                        }

                                        if( $label_descr_txt != '' ) {
                                            $buffy .= '<span class="tdb-s-form-label-descr">' . $label_descr_txt . '</span>';
                                        }
                                    $buffy .= '</span>';

                                    if( $enable_term_create ) {
                                        $buffy .= '<a class="tdb-s-btn tdb-s-btn-simple tdb-ft-create-term" href="#">Add new</a>';
                                    }

                                    if( ( $parent_display == 'checkbox' || $parent_display == 'radio' ) && !$disable_search ) {
                                        $buffy .= '<input class="tdb-s-form-input tdb-ft-checkbox-search" type="text" placeholder="Search by keyword..." ' . ( $this->disable_for_guests ? 'disabled' : '' ) . '>';
                                    }

                                $buffy .= '</label>';

                                if( $parent_display == 'dropdown' ) {
                                    $selected_parent_term_id = '';
                                    foreach ( $parent_terms as $parent_term ) {
                                        $parent_term_object_ids = get_objects_in_term($parent_term->term_id, $this->term_type);

                                        if( in_array( $this->curr_post_id, $parent_term_object_ids ) ) {
                                            $selected_parent_term_id = $parent_term->term_id;
                                        }
                                    }

                                    $buffy .= '<div class="tdb-s-form-select-wrap">';
                                        $buffy .= '<select class="tdb-s-form-input" name="tdb-posts-form-taxonomies' . $this->block_uid . '[]" data-old-term-id="' . $selected_parent_term_id . '" ' . ( $this->disable_for_guests ? 'disabled' : '' ) . '>';
                                            $buffy .= '<option value="">-- Select ' . ( !$show_all_terms ? 'parent ' : '' ) . lcfirst($term_singular_name) . ' --</option>';
                                            $buffy .= $this->display_terms_dropdown($parent_terms_final, 0, $selected_parent_term_id);
                                        $buffy .= '</select>';

                                        $buffy .= '<svg class="tdb-s-form-select-icon" xmlns="http://www.w3.org/2000/svg" width="8.947" height="12.578" viewBox="0 0 8.947 12.578"><g transform="translate(7.947 1) rotate(90)"><path d="M0,7.947A1,1,0,0,1-.58,7.761,1,1,0,0,1-.815,6.366l2.06-2.893L-.815.58A1,1,0,0,1-.58-.815,1,1,0,0,1,.815-.58L3.288,2.893a1,1,0,0,1,0,1.16L.815,7.527A1,1,0,0,1,0,7.947Z" transform="translate(8.104 0)"/><path d="M2.474,7.947a1,1,0,0,1-.815-.42L-.815,4.053a1,1,0,0,1,0-1.16L1.659-.58A1,1,0,0,1,3.053-.815,1,1,0,0,1,3.288.58L1.228,3.473l2.06,2.893a1,1,0,0,1-.814,1.58Z" transform="translate(0 0)"/></g></svg>';
                                    $buffy .= '</div>';
                                } else if( $parent_display == 'checkbox' ) {
                                    $selected_parent_terms_ids = array();
                                    foreach ( $parent_terms as $parent_term ) {
                                        $parent_term_object_ids = get_objects_in_term($parent_term->term_id, $this->term_type);

                                        if( in_array( $this->curr_post_id, $parent_term_object_ids ) ) {
                                            $selected_parent_terms_ids[] = $parent_term->term_id;
                                        }
                                    }

                                    $buffy .= '<div class="tdb-ft-checkboxes">';
                                        $buffy .= '<div class="tdb-s-form-checkboxes-wrap">';
                                            $buffy .= $this->display_terms_checkbox($parent_terms_final, 0, $parent_limit, $selected_parent_terms_ids);
                                        $buffy .= '</div>';
                                    $buffy .= '</div>';
                                } else if( $parent_display == 'radio' ) {
                                    $checked_parent_term_id = '';
                                    foreach ( $parent_terms as $parent_term ) {
                                        $parent_term_object_ids = get_objects_in_term($parent_term->term_id, $this->term_type);

                                        if( in_array( $this->curr_post_id, $parent_term_object_ids ) ) {
                                            $checked_parent_term_id = $parent_term->term_id;
                                        }
                                    }

                                    $buffy .= '<div class="tdb-ft-checkboxes" data-old-term-id="' . $checked_parent_term_id . '">';
                                        $buffy .= '<div class="tdb-s-form-checkboxes-wrap">';
                                            $buffy .= $this->display_terms_radio($parent_terms_final, 0, $checked_parent_term_id);
                                        $buffy .= '</div>';
                                    $buffy .= '</div>';
                                }

                            $buffy .= '</div>';

                            if( $this->curr_post_id != '' && !$show_all_terms ) {
                                foreach ( $parent_terms as $parent_term ) {
                                    $parent_term_object_ids = get_objects_in_term($parent_term->term_id, $this->term_type);

                                    if( in_array( $this->curr_post_id, $parent_term_object_ids ) ) {
                                        $child_terms = get_terms(array(
                                            'taxonomy' => $this->term_type,
                                            'parent' => $parent_term->term_id,
                                            'hide_empty' => 0
                                        ));
                                        $child_terms_final = $this->build_terms_array($child_terms, $parent_term->term_id);

                                        if( !empty( $child_terms_final ) ) {
                                            $buffy .= '<div class="tdb-s-form-group" data-term-hierarchy="child" data-parent-id="' . $parent_term->term_id . '" data-display="' . $child_display . '">';
                                                $buffy .= '<label class="tdb-s-form-label" for="tdb-t-parent-' . $this->block_uid . '">';
                                                    $buffy .= '<span class="tdb-s-form-label-inner">';
                                                        $buffy .= ucfirst($parent_term->name) . ' child ' . lcfirst($term_plural_name);
                                                    $buffy .= '</span>';

                                                    if( $child_display == 'checkbox' && !$disable_search ) {
                                                        $buffy .= '<input class="tdb-s-form-input tdb-ft-checkbox-search" type="text" placeholder="Search by keyword..." ' . ( $this->disable_for_guests ? 'disabled' : '' ) . '>';
                                                    }
                                                $buffy .= '</label>';

                                                if( $child_display == 'dropdown' ) {
                                                    $selected_child_term_id = '';
                                                    foreach ( $child_terms as $child_term ) {
                                                        $child_term_object_ids = get_objects_in_term($child_term->term_id, $this->term_type);

                                                        if( in_array( $this->curr_post_id, $child_term_object_ids ) ) {
                                                            $selected_child_term_id = $child_term->term_id;
                                                        }
                                                    }

                                                    $buffy .= '<div class="tdb-s-form-select-wrap">';
                                                        $buffy .= '<select class="tdb-s-form-input" name="tdb-posts-form-taxonomies' . $this->block_uid . '[]" ' . ( $this->disable_for_guests ? 'disabled' : '' ) . '>';
                                                            $buffy .= '<option value="">-- Select child ' . lcfirst($term_singular_name) . ' --</option>';
                                                            $buffy .= $this->display_terms_dropdown($child_terms_final, 0, $selected_child_term_id);
                                                        $buffy .= '</select>';

                                                        $buffy .= '<svg class="tdb-s-form-select-icon" xmlns="http://www.w3.org/2000/svg" width="8.947" height="12.578" viewBox="0 0 8.947 12.578"><g transform="translate(7.947 1) rotate(90)"><path d="M0,7.947A1,1,0,0,1-.58,7.761,1,1,0,0,1-.815,6.366l2.06-2.893L-.815.58A1,1,0,0,1-.58-.815,1,1,0,0,1,.815-.58L3.288,2.893a1,1,0,0,1,0,1.16L.815,7.527A1,1,0,0,1,0,7.947Z" transform="translate(8.104 0)"/><path d="M2.474,7.947a1,1,0,0,1-.815-.42L-.815,4.053a1,1,0,0,1,0-1.16L1.659-.58A1,1,0,0,1,3.053-.815,1,1,0,0,1,3.288.58L1.228,3.473l2.06,2.893a1,1,0,0,1-.814,1.58Z" transform="translate(0 0)"/></g></svg>';
                                                    $buffy .= '</div>';
                                                } else if( $child_display == 'checkbox' ) {
                                                    $selected_child_term_ids = array();
                                                    foreach ( $child_terms as $child_term ) {
                                                        $child_term_object_ids = get_objects_in_term($child_term->term_id, $this->term_type);

                                                        if( in_array( $this->curr_post_id, $child_term_object_ids ) ) {
                                                            $selected_child_term_ids[] = $child_term->term_id;
                                                        }
                                                    }

                                                    $buffy .= '<div class="tdb-ft-checkboxes">';
                                                        $buffy .= '<div class="tdb-s-form-checkboxes-wrap">';
                                                            $buffy .= $this->display_terms_checkbox($child_terms_final, 0, $child_limit, $selected_child_term_ids);
                                                        $buffy .= '</div>';
                                                    $buffy .= '</div>';
                                                } else if( $child_display == 'radio' ) {
                                                    $checked_child_term_id = '';
                                                    foreach ( $child_terms as $child_term ) {
                                                        $child_term_object_ids = get_objects_in_term($child_term->term_id, $this->term_type);

                                                        if( in_array( $this->curr_post_id, $child_term_object_ids ) ) {
                                                            $checked_child_term_id = $child_term->term_id;
                                                        }
                                                    }

                                                    $buffy .= '<div class="tdb-ft-checkboxes" data-old-term-id="' . $checked_child_term_id . '">';
                                                        $buffy .= '<div class="tdb-s-form-checkboxes-wrap">';
                                                            $buffy .= $this->display_terms_radio($child_terms_final, 0, $checked_child_term_id);
                                                        $buffy .= '</div>';
                                                    $buffy .= '</div>';
                                                }
                                            $buffy .= '</div>';

                                            foreach ( $child_terms as $child_term ) {
                                                $child_term_object_ids = get_objects_in_term($child_term->term_id, $this->term_type);

                                                if( in_array( $this->curr_post_id, $child_term_object_ids ) ) {
                                                    $sub_child_terms = get_terms(array(
                                                        'taxonomy' => $this->term_type,
                                                        'parent' => $child_term->term_id,
                                                        'hide_empty' => 0
                                                    ));
                                                    $sub_child_terms_final = $this->build_terms_array($sub_child_terms, $child_term->term_id);

                                                    if( !empty( $sub_child_terms_final ) ) {
                                                        $buffy .= '<div class="tdb-s-form-group" data-term-hierarchy="sub-child" data-parent-id="' . $child_term->term_id . '" data-display="' . $sub_child_display . '">';
                                                            $buffy .= '<label class="tdb-s-form-label" for="tdb-t-parent-' . $this->block_uid . '">';
                                                                $buffy .= '<span class="tdb-s-form-label-inner">';
                                                                    $buffy .= ucfirst($child_term->name) . ' sub-child ' . lcfirst($term_plural_name);
                                                                $buffy .= '</span>';

                                                                if( $sub_child_display == 'checkbox' && !$disable_search ) {
                                                                    $buffy .= '<input class="tdb-s-form-input tdb-ft-checkbox-search" type="text" placeholder="Search by keyword..." ' . ( $this->disable_for_guests ? 'disabled' : '' ) . '>';
                                                                }
                                                            $buffy .= '</label>';

                                                        if( $sub_child_display == 'dropdown' ) {
                                                            $selected_sub_child_term_id = '';
                                                            foreach ( $sub_child_terms as $sub_child_term ) {
                                                                $sub_child_term_object_ids = get_objects_in_term($sub_child_term->term_id, $this->term_type);

                                                                if( in_array( $this->curr_post_id, $sub_child_term_object_ids ) ) {
                                                                    $selected_sub_child_term_id = $sub_child_term->term_id;
                                                                }
                                                            }

                                                            $buffy .= '<div class="tdb-s-form-select-wrap">';
                                                                $buffy .= '<select class="tdb-s-form-input" name="tdb-posts-form-taxonomies' . $this->block_uid . '[]" ' . ( $this->disable_for_guests ? 'disabled' : '' ) . '>';
                                                                    $buffy .= '<option value="">-- Select sub-child ' . lcfirst($term_singular_name) . ' --</option>';
                                                                    $buffy .= $this->display_terms_dropdown($sub_child_terms_final, 0, $selected_sub_child_term_id);
                                                                $buffy .= '</select>';

                                                                $buffy .= '<svg class="tdb-s-form-select-icon" xmlns="http://www.w3.org/2000/svg" width="8.947" height="12.578" viewBox="0 0 8.947 12.578"><g transform="translate(7.947 1) rotate(90)"><path d="M0,7.947A1,1,0,0,1-.58,7.761,1,1,0,0,1-.815,6.366l2.06-2.893L-.815.58A1,1,0,0,1-.58-.815,1,1,0,0,1,.815-.58L3.288,2.893a1,1,0,0,1,0,1.16L.815,7.527A1,1,0,0,1,0,7.947Z" transform="translate(8.104 0)"/><path d="M2.474,7.947a1,1,0,0,1-.815-.42L-.815,4.053a1,1,0,0,1,0-1.16L1.659-.58A1,1,0,0,1,3.053-.815,1,1,0,0,1,3.288.58L1.228,3.473l2.06,2.893a1,1,0,0,1-.814,1.58Z" transform="translate(0 0)"/></g></svg>';
                                                            $buffy .= '</div>';
                                                        } else if( $sub_child_display == 'checkbox' ) {
                                                            $selected_sub_child_terms_ids = array();
                                                            foreach ( $sub_child_terms as $sub_child_term ) {
                                                                $sub_child_term_object_ids = get_objects_in_term($sub_child_term->term_id, $this->term_type);

                                                                if( in_array( $this->curr_post_id, $sub_child_term_object_ids ) ) {
                                                                    $selected_sub_child_terms_ids[] = $sub_child_term->term_id;
                                                                }
                                                            }

                                                            $buffy .= '<div class="tdb-ft-checkboxes">';
                                                                $buffy .= '<div class="tdb-s-form-checkboxes-wrap">';
                                                                    $buffy .= $this->display_terms_checkbox($sub_child_terms, 0, $sub_child_limit, $selected_sub_child_terms_ids);
                                                                $buffy .= '</div>';
                                                            $buffy .= '</div>';
                                                        } else if( $sub_child_display == 'radio' ) {
                                                            $checked_sub_child_term_id = '';
                                                            foreach ( $sub_child_terms as $sub_child_term ) {
                                                                $sub_child_term_object_ids = get_objects_in_term($sub_child_term->term_id, $this->term_type);

                                                                if( in_array( $this->curr_post_id, $sub_child_term_object_ids ) ) {
                                                                    $checked_sub_child_term_id = $sub_child_term->term_id;
                                                                }
                                                            }

                                                            $buffy .= '<div class="tdb-ft-checkboxes" data-old-term-id="' . $checked_sub_child_term_id . '">';
                                                                $buffy .= '<div class="tdb-s-form-checkboxes-wrap">';
                                                                    $buffy .= $this->display_terms_radio($sub_child_terms_final, 0, $checked_sub_child_term_id);
                                                                $buffy .= '</div>';
                                                            $buffy .= '</div>';
                                                        }
                                                        $buffy .= '</div>';
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                        $buffy .= '</div>';
                    $buffy .= '</div>';
                $buffy .= '</div>';

                if( !( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) ) {
                    ob_start();
                    ?>
                    <script>
                        /* global jQuery:{} */
                        jQuery().ready(function () {

                            let uid = '<?php echo $this->block_uid ?>',
                                $blockObj = jQuery('.<?php echo $this->block_uid ?>');

                            let tdbFormTaxonomiesItem = new tdbFormTaxonomies.item();
                            // block uid
                            tdbFormTaxonomiesItem.uid = uid;
                            // block object
                            tdbFormTaxonomiesItem.blockObj = $blockObj;
                            // required
                            tdbFormTaxonomiesItem.required = '<?php echo $required ?>';
                            // show all terms
                            tdbFormTaxonomiesItem.showAllTerms = '<?php echo $show_all_terms ?>';
                            // disable terms search
                            tdbFormTaxonomiesItem.disableSearch = '<?php echo $disable_search ?>';
                            // enable create term
                            tdbFormTaxonomiesItem.enableTermCreate = '<?php echo $enable_term_create ?>';
                            // terms order
                            tdbFormTaxonomiesItem.orderBy = '<?php echo $order_by ?>';
                            tdbFormTaxonomiesItem.order = '<?php echo $order ?>';
                            // term info
                            tdbFormTaxonomiesItem.termType = '<?php echo $this->term_type ?>';
                            tdbFormTaxonomiesItem.termIsHerarchical = '<?php echo $term_is_hierarchical ?>';
                            // term plural name
                            tdbFormTaxonomiesItem.termName = '<?php echo $term_plural_name ?>';
                            // terms display
                            tdbFormTaxonomiesItem.parentDisplay = '<?php echo $parent_display ?>';
                            tdbFormTaxonomiesItem.childDisplay = '<?php echo $child_display ?>';
                            tdbFormTaxonomiesItem.subChildDisplay = '<?php echo $sub_child_display ?>';
                            // terms limits
                            tdbFormTaxonomiesItem.parentLimit = '<?php echo ( ( $is_current_user_admin && $disable_limits != '' ) ? -1 : $parent_limit ) ?>';
                            tdbFormTaxonomiesItem.childLimit = '<?php echo ( ( $is_current_user_admin && $disable_limits != '' ) ? -1 : $child_limit ) ?>';
                            tdbFormTaxonomiesItem.subChildLimit = '<?php echo ( ( $is_current_user_admin && $disable_limits != '' ) ? -1 : $sub_child_limit ) ?>';
                            // disabled for guests
                            tdbFormTaxonomiesItem.disabledForGuests = '<?php echo $this->disable_for_guests ?>';

                            // add nonce to authorize ajax requests
                            tdbFormTaxonomiesItem._nonce = '<?php echo wp_create_nonce(__CLASS__); ?>';

                            tdbFormTaxonomies.addItem(tdbFormTaxonomiesItem);

                        });
                    </script>
                    <?php
                    td_js_buffer::add_to_footer( "\n" . td_util::remove_script_tag( ob_get_clean() ) );
                }
            }
        $buffy .= '</div> <!-- ./block -->';

        return $buffy;
    }


    function build_terms_array($terms, $parent_id) {

        $terms_array = array();

        foreach ( $terms as $term ) {
            if( $term->parent == $parent_id ) {
                $terms_array[$term->term_id] = array(
                    'name' => $term->name,
                    'children' => $this->build_terms_array($terms, $term->term_id),
                );
            }
        }

        return $terms_array;

    }

    function display_terms_dropdown($terms, $terms_level, $selected_term_id) {

        $buffy = '';

        $terms_name_prefix = str_repeat('-', $terms_level);
        if( $terms_name_prefix != '' ) {
            $terms_name_prefix .= ' ';
        }
        $terms_level++;

        foreach ( $terms as $term_id => $term_data ) {
            $selected = '';
            if( $selected_term_id == $term_id ) {
                $selected = 'selected';
            }

            $buffy .= '<option value="' . $term_id . '" ' . $selected . '>' . $terms_name_prefix . $term_data['name'] . '</option>';

            if( !empty( $term_data['children'] ) ) {
                $buffy .= $this->display_terms_dropdown($term_data['children'], $terms_level, $selected_term_id);
            }
        }

        return $buffy;

    }

    function display_terms_checkbox($terms, $terms_level, $terms_limit, $selected_terms) {

        $buffy = '';

        $terms_name_prefix = str_repeat('-', $terms_level);
        if( $terms_name_prefix != '' ) {
            $terms_name_prefix .= ' ';
        }
        $terms_level++;

        foreach ( $terms as $term_id => $term_data ) {
            $checked = '';
            if( in_array( $term_id, $selected_terms ) ) {
                $checked = 'checked';
            }

            $disabled = '';
            if(
                $this->disable_for_guests ||
                (
                    $checked == '' &&
                    $terms_limit > -1 &&
                    $terms_limit <= count( $selected_terms )
                )
            ) {
                $disabled = 'disabled';
            }

            $buffy .= '<div class="tdb-s-form-check">';
                $buffy .= '<label class="tdb-s-fc-label">';
                    $buffy .= '<input type="checkbox" name="tdb-posts-form-taxonomies-' . $this->block_uid . '[]" value="' . $term_id . '" ' . $disabled . ' ' . $checked . '>';
                    $buffy .= '<span class="tdb-s-fc-check"></span>';
                    $buffy .= '<span class="tdb-s-fc-title">' . $terms_name_prefix . $term_data['name'] . '</span>';
                $buffy .= '</label>';
            $buffy .= '</div>';

            if( !empty( $term_data['children'] ) ) {
                $buffy .= $this->display_terms_checkbox($term_data['children'], $terms_level, $terms_limit, $selected_terms);
            }
        }

        return $buffy;

    }

    function display_terms_radio($terms, $terms_level, $checked_term_id, $group_id = '') {

        $buffy = '';

        $terms_name_prefix = str_repeat('-', $terms_level);
        if( $terms_name_prefix != '' ) {
            $terms_name_prefix .= ' ';
        }
        $terms_level++;

        if( $group_id == '' ) {
            $group_id = substr( base_convert( strval(floor(1 + rand() * 0x10000 )), 10, 16 ), 0, 4 );
        }

        foreach ( $terms as $term_id => $term_data ) {
            $checked = '';
            if( $term_id == $checked_term_id ) {
                $checked = 'checked';
            }

            $buffy .= '<div class="tdb-s-form-check">';
                $buffy .= '<label class="tdb-s-fc-label">';
                    $buffy .= '<input type="radio" name="tdb-posts-form-taxonomies-' . $this->block_uid . '-' . $group_id . '[]" value="' . $term_id . '" ' . $checked . '>';
                    $buffy .= '<span class="tdb-s-fc-check"></span>';
                    $buffy .= '<span class="tdb-s-fc-title">' . $terms_name_prefix . $term_data['name'] . '</span>';
                $buffy .= '</label>';
            $buffy .= '</div>';

            if( !empty( $term_data['children'] ) ) {
                $buffy .= $this->display_terms_radio($term_data['children'], $terms_level, $checked_term_id, $group_id);
            }
        }

        return $buffy;

    }

}