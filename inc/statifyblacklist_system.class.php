<?php

/* Quit */
defined( 'ABSPATH' ) OR exit;

/**
 * Statify Blacklist system configuration
 *
 * @since 1.0.0
 */
class StatifyBlacklist_System extends StatifyBlacklist {

	const VERSION_MAIN = 1.3;

	/**
	 * Plugin install handler.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $network_wide Whether the plugin was activated network-wide or not.
	 */
	public static function install( $network_wide = false ) {
		global $wpdb;

		// Create tables for each site in a network.
		if ( is_multisite() && $network_wide ) {
			// Todo: Use get_sites() in WordPress 4.6+
			$ids = $wpdb->get_col( "SELECT blog_id FROM `$wpdb->blogs`" );

			foreach ( $ids as $site_id ) {
				switch_to_blog( $site_id );
				add_option(
					'statify-blacklist',
					array(
						'activate-referer' => 0,
						'referer'          => array()
					)
				);
			}

			restore_current_blog();
		} else {
			add_option(
				'statify-blacklist',
				array(
					'activate-referer' => 0,
					'referer'          => array()
				)
			);
		}
	}


	/**
	 * Plugin uninstall handler.
	 *
	 * @since 1.0.0
	 */
	public static function uninstall() {
		global $wpdb;

		if ( is_multisite() ) {
			$old = get_current_blog_id();

			// Todo: Use get_sites() in WordPress 4.6+
			$ids = $wpdb->get_col( "SELECT blog_id FROM `$wpdb->blogs`" );

			foreach ( $ids as $id ) {
				switch_to_blog( $id );
				delete_option( 'statify-blacklist' );
			}

			switch_to_blog( $old );
		}

		delete_option( 'statify-blacklist' );
	}


	/**
	 * Upgrade plugin options.
	 *
	 * @since   1.2.0
	 * @changed 1.3.0
	 */
	public static function upgrade() {
		self::update_options();
		/* Check if config array is not associative (pre 1.2.0) */
		if ( array_keys( self::$_options['referer'] ) === range( 0, count( self::$_options['referer'] ) - 1 ) ) {
			/* Flip referer array to make domains keys */
			$options            = self::$_options;
			$options['referer'] = array_flip( self::$_options['referer'] );
			if ( ( is_multisite() && array_key_exists( STATIFYBLACKLIST_BASE, (array) get_site_option( 'active_sitewide_plugins' ) ) ) ) {
				update_site_option( 'statify-blacklist', $options );
			} else {
				update_option( 'statify-blacklist', $options );
			}
		}

		/* Check if version is set (not before 1.3.0) */
		if ( ! isset( self::$_options['version'] ) ) {
			$options = self::$_options;
			/* Set version */
			$options['version'] = self::VERSION_MAIN;
			/* Add regular expression option (as of 1.3) */
			$options['referer_regexp'] = 0;
			if ( ( is_multisite() && array_key_exists( STATIFYBLACKLIST_BASE, (array) get_site_option( 'active_sitewide_plugins' ) ) ) ) {
				update_site_option( 'statify-blacklist', $options );
			} else {
				update_option( 'statify-blacklist', $options );
			}
		}
	}
}
