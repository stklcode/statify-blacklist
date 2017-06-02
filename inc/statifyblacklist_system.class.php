<?php

/* Quit */
defined( 'ABSPATH' ) OR exit;

/**
 * Statify Blacklist system configuration
 *
 * @since   1.0.0
 * @version 1.4.0~dev
 */
class StatifyBlacklist_System extends StatifyBlacklist {

	const VERSION_MAIN = 1.3;

	/**
	 * Plugin install handler.
	 *
	 * @since   1.0.0
	 * @changed 1.4.0
	 *
	 * @param bool $network_wide Whether the plugin was activated network-wide or not.
	 */
	public static function install( $network_wide = false ) {
		// Create tables for each site in a network.
		if ( is_multisite() && $network_wide ) {
			if ( function_exists( 'get_sites' ) ) {
				$sites = get_sites();
			} elseif ( function_exists( 'wp_get_sites' ) ) {
				$sites = wp_get_sites();    /* legacy support for WP < 4.6 */
			} else {
				return;
			}

			foreach ( $sites as $site ) {
				switch_to_blog( $site['blog_id'] );
				add_option(
					'statify-blacklist',
					self::defaultOptions()
				);
			}

			restore_current_blog();
		} else {
			add_option(
				'statify-blacklist',
				self::defaultOptions()
			);
		}
	}

	/**
	 * Create default plugin configuration.
	 *
	 * @since 1.4.0
	 *
	 * @return array the options array
	 */
	private static function defaultOptions() {
		return array(
			'activate-referer' => 0,
			'cron_referer'     => 0,
			'referer'          => array(),
			'referer_regexp'   => 0,
			'version'          => self::VERSION_MAIN
		);
	}


	/**
	 * Plugin uninstall handler.
	 *
	 * @since 1.0.0
	 */
	public static function uninstall() {
		if ( is_multisite() ) {
			$old = get_current_blog_id();

			if ( function_exists( 'get_sites' ) ) {
				$sites = get_sites();
			} elseif ( function_exists( 'wp_get_sites' ) ) {
				$sites = wp_get_sites();    /* legacy support for WP < 4.6 */
			} else {
				return;
			}

			foreach ( $sites as $site ) {
				switch_to_blog( $site['blog_id'] );
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
