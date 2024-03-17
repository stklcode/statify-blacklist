<?php
/**
 * Statify Filter
 *
 * @package     PluginPackage
 * @author      Stefan Kalscheuer <stefan@stklcode.de>
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name:       Statify Filter
 * Plugin URI:        https://wordpress.org/plugins/statify-blacklist/
 * Description:       Extension for the Statify plugin to add customizable filters. (formerly "Statify Blacklist)
 * Version:           1.7.0
 * Requires at least: 4.7
 * Requires PHP:      5.5
 * Requires Plugins:  statify
 * Author:            Stefan Kalscheuer (@stklcode)
 * Author URI:        https://www.stklcode.de
 * Text Domain:       statify-blacklist
 * License:           GPLv2 or later
 *
 * Statify Filter is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Statify Filter is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Statify Filter. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
 */

// Quit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Constants.
define( 'STATIFYBLACKLIST_FILE', __FILE__ );
define( 'STATIFYBLACKLIST_DIR', __DIR__ );
define( 'STATIFYBLACKLIST_BASE', plugin_basename( __FILE__ ) );

// Check for compatibility.
if ( statify_blacklist_compatibility_check() ) {
	// System Hooks.
	add_action( 'plugins_loaded', array( 'StatifyBlacklist', 'init' ) );

	register_activation_hook( STATIFYBLACKLIST_FILE, array( 'StatifyBlacklist_System', 'install' ) );

	register_uninstall_hook( STATIFYBLACKLIST_FILE, array( 'StatifyBlacklist_System', 'uninstall' ) );

	// Upgrade hook.
	register_activation_hook( STATIFYBLACKLIST_FILE, array( 'StatifyBlacklist_System', 'upgrade' ) );

	// Autoload.
	spl_autoload_register( 'statify_blacklist_autoload' );
} else {
	// Disable plugin, if active.
	add_action( 'admin_init', 'statify_blacklist_disable' );
}

/**
 * Autoloader for StatifyBlacklist classes.
 *
 * @param string $class_name Name of the class to load.
 *
 * @since 1.0.0
 */
function statify_blacklist_autoload( $class_name ) {
	$plugin_classes = array(
		'StatifyBlacklist',
		'StatifyBlacklist_Admin',
		'StatifyBlacklist_Settings',
		'StatifyBlacklist_System',
	);

	if ( in_array( $class_name, $plugin_classes, true ) ) {
		require_once sprintf(
			'%s/inc/class-%s.php',
			STATIFYBLACKLIST_DIR,
			strtolower( str_replace( '_', '-', $class_name ) )
		);
	}
}

/**
 * Check for compatibility with PHP and WP version.
 *
 * @since 1.5.0
 *
 * @return boolean Whether minimum WP and PHP versions are met.
 */
function statify_blacklist_compatibility_check() {
	return version_compare( $GLOBALS['wp_version'], '4.7', '>=' ) &&
		version_compare( phpversion(), '5.5', '>=' );
}

/**
 * Disable plugin if active and incompatible.
 *
 * @since 1.5.0
 *
 * @return void
 */
function statify_blacklist_disable() {
	if ( is_plugin_active( STATIFYBLACKLIST_BASE ) ) {
		deactivate_plugins( STATIFYBLACKLIST_BASE );
		add_action( 'admin_notices', 'statify_blacklist_disabled_notice' );
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
		// phpcs:enable
	}
}

/**
 * Admin notification for unmet requirements.
 *
 * @since 1.5.0
 *
 * @return void
 */
function statify_blacklist_disabled_notice() {
	echo '<div class="notice notice-error is-dismissible"><p><strong>';
	printf(
		/* translators: minimum version numbers for WordPress and PHP inserted at placeholders */
		esc_html__( 'Statify Filter requires at least WordPress %1$s and PHP %2$s.', 'statify-blacklist' ),
		'4.7',
		'5.5'
	);
	echo '<br>';
	printf(
		/* translators: current version numbers for WordPress and PHP inserted at placeholders */
		esc_html__( 'Your site is running WordPress %1$s on PHP %2$s, thus the plugin has been disabled.', 'statify-blacklist' ),
		esc_html( $GLOBALS['wp_version'] ),
		esc_html( phpversion() )
	);
	echo '</strong></p></div>';
}
