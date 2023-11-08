<?php

/**
 *
 * @link              https://notix.co/
 * @since             1.2.0
 * @package           Notix
 *
 * @wordpress-plugin
 * Plugin Name:       Notix Push Notifications
 * Description:       Bring more repeat traffic to your WordPress site with Notix. Best engine for web push subscribers collection and notifications delivery.
 * Version:           1.2.0
 * Author:            Notix
 * Author URI:        https://notix.co/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       notix
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'NOTIX_VERSION', '1.2.0' );

function activate_notix() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-notix-activator.php';
	Notix_Activator::activate();
}

function deactivate_notix() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-notix-deactivator.php';
	Notix_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_notix' );
register_deactivation_hook( __FILE__, 'deactivate_notix' );

require plugin_dir_path( __FILE__ ) . 'includes/class-notix.php';

function run_notix() {

	$plugin = new Notix();
	$plugin->run();

}
run_notix();
