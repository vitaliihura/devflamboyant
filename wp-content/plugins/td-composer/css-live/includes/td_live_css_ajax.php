<?php
/**
 * Created by ra.
 * Date: 7/18/2016
 * Handle the ajax requests, for now only save
 */

add_action( 'rest_api_init', 'td_live_css_on_rest_api_init');
function td_live_css_on_rest_api_init() {
	$namespace = 'tdw';
	register_rest_route( $namespace, '/save_css/', array(
		'methods'  => 'POST',
		'callback' => 'td_live_css_on_ajax_save_css',
		'permission_callback' => function() {
			return is_user_logged_in();
		}
	));
}


/**
 * the save_css endpoint
 * @param WP_REST_Request $request
 */
function td_live_css_on_ajax_save_css( WP_REST_Request $request ) {

	$result = [];

	// verify if the user trying to update live css has admin permissions
	if( !current_user_can( 'switch_themes' ) ) {
		$result['error'] = __td( 'You do not hold the required privileges to execute this request.' );
		die( json_encode( $result ) );
	}

	$compiled_css_items = $request->get_param('compiled_css');
	if ( !empty($compiled_css_items) ) {

		// 'empty' string - to clear the custom css
		if ( 'empty' === $compiled_css_items ) {
			$compiled_css_items = '';
		}

		td_live_css_css_storage::update( 'css', $compiled_css_items );

	}

	$compiled_less_items = $request->get_param('less_input');
	if ( !empty($compiled_less_items) ) {

		// 'empty' string - to clear the custom css
		if ( 'empty' === $compiled_less_items ) {
			$compiled_less_items = '';
		}

		td_live_css_css_storage::update( 'less', $compiled_less_items );

	}
}
