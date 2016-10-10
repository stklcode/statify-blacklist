<?php
/*
Plugin Name: Statify Blacklist
Description: Extension for the statify plugin to add a customizable blacklists.
Text Domain: statify-blacklist
Domain Path: /lang
Author:      Stefan Kalscheuer
Author URI:  https://stklcode.de
Plugin URI:  https://wordpress.org/plugins/statify-blacklist
License:     GPLv3 or later
Version:     1.2.1
*/

/* Quit */
defined( 'ABSPATH' ) OR exit;

/*  Constants */
define( 'STATIFYBLACKLIST_FILE', __FILE__ );
define( 'STATIFYBLACKLIST_DIR', dirname( __FILE__ ) );
define( 'STATIFYBLACKLIST_BASE', plugin_basename( __FILE__ ) );

/* System Hooks */
add_action( 'plugins_loaded', array( 'StatifyBlacklist', 'instance' ) );

register_activation_hook( STATIFYBLACKLIST_FILE, array( 'StatifyBlacklist_System', 'install' ) );

register_uninstall_hook( STATIFYBLACKLIST_FILE, array( 'StatifyBlacklist_System', 'uninstall' ) );

/* Upgrade hook to v1.2.0 */
register_activation_hook( STATIFYBLACKLIST_FILE, array( 'StatifyBlacklist_System', 'upgrade' ) );

/* Autoload */
spl_autoload_register( 'statifyBlacklist_autoload' );

/**
 * Autoloader for StatifyBlacklist classes.
 *
 * @param $class
 *
 * @since 1.0.0
 */
function statifyBlacklist_autoload( $class ) {
	$plugin_classes = array(
		'StatifyBlacklist',
		'StatifyBlacklist_Admin',
		'StatifyBlacklist_System'
	);

	if ( in_array( $class, $plugin_classes ) ) {
		require_once( sprintf( '%s/inc/%s.class.php', STATIFYBLACKLIST_DIR, strtolower( $class ) ) );
	}
}
