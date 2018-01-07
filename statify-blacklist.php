<?php
/**
 * Statify Blacklist
 *
 * @package     PluginPackage
 * @author      Stefan Kalscheuer <stefan@stklcode.de>
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: Statify Blacklist
 * Plugin URI:  https://wordpress.org/plugins/statify-blacklist/
 * Description: Extension for the Statify plugin to add a customizable blacklists.
 * Version:     1.4.3-alpha
 * Author:      Stefan Kalscheuer (@stklcode)
 * Author URI:  https://www.stklcode.de
 * Text Domain: statify-blacklist
 * Domain Path: /lang
 * License:     GPLv2 or later
 *
 * Statify Blacklist is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Statify Blacklist is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Statify Blacklist. If not, see http://www.gnu.org/licenses/gpl-2.0.html.
 */

// Quit.
defined( 'ABSPATH' ) || exit;

// Constants.
define( 'STATIFYBLACKLIST_FILE', __FILE__ );
define( 'STATIFYBLACKLIST_DIR', dirname( __FILE__ ) );
define( 'STATIFYBLACKLIST_BASE', plugin_basename( __FILE__ ) );

// System Hooks.
add_action( 'plugins_loaded', array( 'StatifyBlacklist', 'init' ) );

register_activation_hook( STATIFYBLACKLIST_FILE, array( 'StatifyBlacklist_System', 'install' ) );

register_uninstall_hook( STATIFYBLACKLIST_FILE, array( 'StatifyBlacklist_System', 'uninstall' ) );

// Upgrade hook.
register_activation_hook( STATIFYBLACKLIST_FILE, array( 'StatifyBlacklist_System', 'upgrade' ) );

// Autoload.
spl_autoload_register( 'statify_blacklist_autoload' );

/**
 * Autoloader for StatifyBlacklist classes.
 *
 * @param string $class  Name of the class to load.
 *
 * @since 1.0.0
 */
function statify_blacklist_autoload( $class ) {
	$plugin_classes = array(
		'StatifyBlacklist',
		'StatifyBlacklist_Admin',
		'StatifyBlacklist_System',
	);

	if ( in_array( $class, $plugin_classes, true ) ) {
		require_once sprintf(
			'%s/inc/class-%s.php',
			STATIFYBLACKLIST_DIR,
			strtolower( str_replace( '_', '-', $class ) )
		);
	}
}
