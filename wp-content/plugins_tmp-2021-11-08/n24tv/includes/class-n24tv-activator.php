<?php

/**
 * Fired during plugin activation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    N24TV
 * @subpackage N24TV/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    N24TV
 * @subpackage N24TV/includes
 * @author     Your Name <email@example.com>
 */
class N24TV_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

        /**
         * n24tv_hourly_event action/hook
         *
         * Register our cron to update page views from redis to WP
         */
        if (! wp_next_scheduled ( 'n24tv_hourly_event' )) {
            wp_schedule_event( time(), 'hourly', N24TV::$hourly_event );
        }

	}

}
