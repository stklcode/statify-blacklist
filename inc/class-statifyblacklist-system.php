<?php
/**
 * Statify Filter: StatifyBlacklist_System class
 *
 * This file contains the derived class for the plugin's system operations.
 *
 * @package   Statify_Blacklist
 * @subpackge System
 * @since     1.0.0
 */

// Quit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Statify Filter system configuration.
 *
 * @since   1.0.0
 */
class StatifyBlacklist_System extends StatifyBlacklist {

	/**
	 * Plugin install handler.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $network_wide Whether the plugin was activated network-wide or not.
	 *
	 * @return void
	 */
	public static function install( $network_wide = false ) {
		// Create tables for each site in a network.
		if ( $network_wide && is_multisite() ) {
			if ( function_exists( 'get_sites' ) ) {
				$sites = get_sites();
			} else {
				return;
			}

			foreach ( $sites as $site ) {
				if ( is_array( $site ) ) {
					$site_id = $site['blog_id'];
				} else {
					$site_id = $site->blog_id;
				}
				self::install_site( $site_id );
			}

			restore_current_blog();
		} else {
			add_option(
				'statify-blacklist',
				self::default_options()
			);
		}
	}

	/**
	 * Set up the plugin for a single site on Multisite.
	 *
	 * @since 1.4.3
	 *
	 * @param integer $site_id Site ID.
	 *
	 * @return void
	 */
	public static function install_site( $site_id ) {
		switch_to_blog( (int) $site_id );
		add_option(
			'statify-blacklist',
			self::default_options()
		);
		restore_current_blog();
	}


	/**
	 * Plugin uninstall handler.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function uninstall() {
		if ( is_multisite() ) {
			$old = get_current_blog_id();

			if ( function_exists( 'get_sites' ) ) {
				$sites = get_sites();
			} else {
				return;
			}

			foreach ( $sites as $site ) {
				if ( is_array( $site ) ) {
					$site_id = $site['blog_id'];
				} else {
					$site_id = $site->blog_id;
				}
				self::uninstall_site( $site_id );
			}

			switch_to_blog( $old );
		}

		delete_option( 'statify-blacklist' );
	}

	/**
	 * Remove the plugin for a single site on Multisite.
	 *
	 * @since 1.4.3
	 *
	 * @param integer $site_id Site ID.
	 *
	 * @return void
	 */
	public static function uninstall_site( $site_id ) {
		$old = get_current_blog_id();
		switch_to_blog( (int) $site_id );
		delete_option( 'statify-blacklist' );
		switch_to_blog( $old );
	}

	/**
	 * Upgrade plugin options.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public static function upgrade() {
		self::update_options();
		// Check if config array is not associative (pre 1.2.0).
		if ( array_keys( self::$options['referer'] ) === range( 0, count( self::$options['referer'] ) - 1 ) ) {
			// Flip referer array to make domains keys.
			$options            = self::$options;
			$options['referer'] = array_flip( self::$options['referer'] );
			if ( self::$multisite ) {
				update_site_option( 'statify-blacklist', $options );
			} else {
				update_option( 'statify-blacklist', $options );
			}
		}

		// Version not set (pre 1.3.0) or older than 1.4.
		if ( ! isset( self::$options['version'] ) || self::$options['version'] < 1.4 ) {
			// Upgrade options to new schema.
			$options = array(
				'referer' => array(
					'active'    => self::$options['active_referer'],
					'cron'      => self::$options['cron_referer'],
					'regexp'    => self::$options['referer_regexp'],
					'blacklist' => self::$options['referer'],
				),
				'target'  => array(
					'active'    => 0,
					'cron'      => 0,
					'regexp'    => 0,
					'blacklist' => array(),
				),
				'ip'      => array(
					'active'    => 0,
					'blacklist' => array(),
				),
				'version' => 1.4,
			);
			if ( self::$multisite ) {
				update_site_option( 'statify-blacklist', $options );
			} else {
				update_option( 'statify-blacklist', $options );
			}
			self::update_options();
		}

		// Version older than 1.6.
		if ( self::$options['version'] < 1.6 ) {
			$options = self::$options;
			if ( ! isset( $options['ua'] ) ) {
				$options['ua'] = array(
					'active'    => 0,
					'regexp'    => 0,
					'blacklist' => array(),
				);
			} elseif ( ! isset( $options['ua']['blacklist'] ) ) {
				$options['ua']['blacklist'] = array();
			} else {
				// User agent strings got stored incorrectly in 1.6.0 - luckily the version was not updated, either.
				$options['ua']['blacklist'] = array_flip( $options['ua']['blacklist'] );
			}
			$options['version'] = 1.6;
			if ( self::$multisite ) {
				update_site_option( 'statify-blacklist', $options );
			} else {
				update_option( 'statify-blacklist', $options );
			}
			self::update_options();
		}

		// Version older than current major release.
		if ( self::VERSION_MAIN > self::$options['version'] ) {
			// Merge default options with current config, assuming only additive changes.
			$options            = array_replace_recursive( self::default_options(), self::$options );
			$options['version'] = self::VERSION_MAIN;
			if ( self::$multisite ) {
				update_site_option( 'statify-blacklist', $options );
			} else {
				update_option( 'statify-blacklist', $options );
			}
		}
	}
}
