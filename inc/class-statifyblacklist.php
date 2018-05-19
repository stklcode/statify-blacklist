<?php
/**
 * Statify Blacklist: StatifyBlacklist class
 *
 * This file contains the plugin's base class.
 *
 * @package Statify_Blacklist
 * @since   1.0.0
 */

// Quit.
defined( 'ABSPATH' ) || exit;

/**
 * Statify Blacklist.
 *
 * @since   1.0.0
 */
class StatifyBlacklist {

	/**
	 * Plugin major version.
	 *
	 * @since 1.4.0
	 * @var int VERSION_MAIN
	 */
	const VERSION_MAIN = 1.4;

	/**
	 * Plugin options.
	 *
	 * @since 1.0.0
	 * @var array $_options
	 */
	public static $_options;

	/**
	 * Multisite Status.
	 *
	 * @since 1.0.0
	 * @var bool $multisite
	 */
	public static $multisite;

	/**
	 * Class self initialize.
	 *
	 * @since 1.0.0
	 * @deprecated 1.4.2 Replaced by init().
	 */
	public static function instance() {
		self::init();
	}

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 * @deprecated 1.4.2 Replaced by init().
	 */
	public function __construct() {
		self::init();
	}

	/**
	 * Plugin initialization.
	 *
	 * @since 1.4.2
	 */
	public static function init() {
		// Skip on autosave or AJAX.
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}

		// Get multisite status.
		self::$multisite = ( is_multisite() && array_key_exists( STATIFYBLACKLIST_BASE, (array) get_site_option( 'active_sitewide_plugins' ) ) );

		// Plugin options.
		self::update_options();

		// Add Filter to statify hook if enabled.
		if ( 0 !== self::$_options['referer']['active'] || 0 !== self::$_options['target']['active'] || 0 !== self::$_options['ip']['active'] ) {
			add_filter( 'statify__skip_tracking', array( 'StatifyBlacklist', 'apply_blacklist_filter' ) );
		}

		// Admin only filters.
		if ( is_admin() ) {
			// Load Textdomain (only needed for backend.
			load_plugin_textdomain( 'statifyblacklist', false, STATIFYBLACKLIST_DIR . '/lang/' );

			// Add actions.
			add_action( 'wpmu_new_blog', array( 'StatifyBlacklist_System', 'install_site' ) );
			add_action( 'delete_blog', array( 'StatifyBlacklist_System', 'uninstall_site' ) );
			add_filter( 'plugin_row_meta', array( 'StatifyBlacklist_Admin', 'plugin_meta_link' ), 10, 2 );

			if ( self::$multisite ) {
				add_action( 'network_admin_menu', array( 'StatifyBlacklist_Admin', 'add_menu_page' ) );
				add_filter(
					'network_admin_plugin_action_links', array(
						'StatifyBlacklist_Admin',
						'plugin_actions_links',
					),
					10,
					2
				);
			} else {
				add_action( 'admin_menu', array( 'StatifyBlacklist_Admin', 'add_menu_page' ) );
				add_filter( 'plugin_action_links', array( 'StatifyBlacklist_Admin', 'plugin_actions_links' ), 10, 2 );
			}
		}

		// CronJob to clean up database.
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			if ( 1 === self::$_options['referer']['cron'] || 1 === self::$_options['target']['cron'] ) {
				add_action( 'statify_cleanup', array( 'StatifyBlacklist_Admin', 'cleanup_database' ) );
			}
		}
	}

	/**
	 * Update options.
	 *
	 * @since 1.0.0
	 * @since 1.2.1 update_options($options = null) Parameter with default value introduced.
	 *
	 * @param array $options Optional. New options to save.
	 */
	public static function update_options( $options = null ) {
		if ( self::$multisite ) {
			$o = get_site_option( 'statify-blacklist' );
		} else {
			$o = get_option( 'statify-blacklist' );
		}
		self::$_options = wp_parse_args( $o, self::default_options() );
	}

	/**
	 * Create default plugin configuration.
	 *
	 * @since 1.4.0
	 *
	 * @return array The options array.
	 */
	protected static function default_options() {
		return array(
			'referer' => array(
				'active'    => 0,
				'cron'      => 0,
				'regexp'    => 0,
				'blacklist' => array(),
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
			'version' => self::VERSION_MAIN,
		);
	}

	/**
	 * Apply the blacklist filter if active
	 *
	 * @since 1.0.0
	 *
	 * @return bool TRUE if referer matches blacklist.
	 */
	public static function apply_blacklist_filter() {
		// Referer blacklist.
		if ( isset( self::$_options['referer']['active'] ) && 0 !== self::$_options['referer']['active'] ) {
			// Regular Expression filtering since 1.3.0.
			if ( isset( self::$_options['referer']['regexp'] ) && self::$_options['referer']['regexp'] > 0 ) {
				// Get full referer string.
				$referer = wp_get_raw_referer();
				if ( ! $referer ) {
					$referer = '';
				}
				// Merge given regular expressions into one.
				$regexp = '/' . implode( '|', array_keys( self::$_options['referer']['blacklist'] ) ) . '/';
				if ( 2 === self::$_options['referer']['regexp'] ) {
					$regexp .= 'i';
				}

				// Check blacklist (no return to continue filtering #12).
				if ( 1 === preg_match( $regexp, $referer ) ) {
					return true;
				}
			} else {
				// Extract relevant domain parts.
				$referer = wp_parse_url( wp_get_raw_referer() );
				$referer = strtolower( ( isset( $referer['host'] ) ? $referer['host'] : '' ) );

				// Get blacklist.
				$blacklist = self::$_options['referer']['blacklist'];

				// Check blacklist.
				if ( isset( $blacklist[ $referer ] ) ) {
					return true;
				}
			}
		}

		// Target blacklist (since 1.4.0).
		if ( isset( self::$_options['target']['active'] ) && 0 !== self::$_options['target']['active'] ) {
			// Regular Expression filtering since 1.3.0.
			if ( isset( self::$_options['target']['regexp'] ) && 0 < self::$_options['target']['regexp'] ) {
				// Get full referer string.
				// @codingStandardsIgnoreStart The globals are checked.
				$target = ( isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '/' );
				// @codingStandardsIgnoreEnd
				// Merge given regular expressions into one.
				$regexp = '/' . implode( '|', array_keys( self::$_options['target']['blacklist'] ) ) . '/';
				if ( 2 === self::$_options['target']['regexp'] ) {
					$regexp .= 'i';
				}

				// Check blacklist (no return to continue filtering #12).
				if ( 1 === preg_match( $regexp, $target ) ) {
					return true;
				}
			} else {
				// Extract target page.
				// @codingStandardsIgnoreStart The globals are checked.
				$target = ( isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '/' );
				// @codingStandardsIgnoreEnd
				// Get blacklist.
				$blacklist = self::$_options['target']['blacklist'];
				// Check blacklist.
				if ( isset( $blacklist[ $target ] ) ) {
					return true;
				}
			}
		}

		// IP blacklist (since 1.4.0).
		if ( isset( self::$_options['ip']['active'] ) && 0 !== self::$_options['ip']['active'] ) {
			$ip = self::get_ip();
			if ( false !== ( $ip ) ) {
				foreach ( self::$_options['ip']['blacklist'] as $net ) {
					if ( self::cidr_match( $ip, $net ) ) {
						return true;
					}
				}
			}
		}

		// Skip and continue (return NULL), if all blacklists are inactive.
		return null;
	}

	/**
	 * Helper method to determine the client's IP address.
	 *
	 * If a proxy is used, the X-Real-IP or X-Forwarded-For header is checked, otherwise the default remote address.
	 * For performance reasons only the most common flags are checked. This might be even reduce by user configuration.
	 * Maybe some community feedback will ease the decision on that.
	 *
	 * @return string|bool the client's IP address or FALSE, if none could be determined.
	 */
	private static function get_ip() {
		foreach (

			/*
			 * There are more fields, that could possibly be checked, but we only consider the most common for now:
			 * HTTP_CLIENT_IP, HTTP_X_REAL_IP, HTTP_X_FORWARDED_FOR, HTTP_X_FORWARDED,
			 * HTTP_X_CLUSTER_CLIENT_IP, HTTP_FORWARDED_FOR, HTTP_FORWARDED, REMOTE_ADDR
			 */
			array(
				'HTTP_X_REAL_IP',
				'HTTP_X_FORWARDED_FOR',
				'REMOTE_ADDR',
			) as $k
		) {
			// @codingStandardsIgnoreStart The globals are checked.
			if ( isset( $_SERVER[ $k ] ) ) {
				foreach ( explode( ',', $_SERVER[ $k ] ) as $ip ) {
					if ( false !== filter_var( $ip, FILTER_VALIDATE_IP ) ) {
						return $ip;
					}
				}
			}
			// @codingStandardsIgnoreEnd
		}

		return false;
	}

	/**
	 * Helper function to check if an IP address matches a given subnet.
	 *
	 * @param string $ip  IP address to check.
	 * @param string $net IP address or subnet in CIDR notation.
	 *
	 * @return bool TRUE, if the given IP addresses matches the given subnet.
	 */
	private static function cidr_match( $ip, $net ) {
		if ( substr_count( $net, ':' ) > 1 ) {  // Check for IPv6.
			if ( ! ( ( extension_loaded( 'sockets' ) && defined( 'AF_INET6' ) ) || inet_pton( '::1' ) ) ) {
				return false;
			}

			if ( false !== strpos( $net, '/' ) ) {   // Parse CIDR subnet.
				list( $base, $mask ) = explode( '/', trim( $net ), 2 );

				if ( ! is_numeric( $mask ) ) {
					return false;
				} else {
					$mask = (int) $mask;
				}

				if ( $mask < 1 || $mask > 128 ) {
					return false;
				}
			} else {
				$base = $net;
				$mask = 128;
			}

			$bytes_addr = unpack( 'n*', inet_pton( $base ) );
			$bytes_est  = unpack( 'n*', inet_pton( $ip ) );

			if ( ! $bytes_addr || ! $bytes_est ) {
				return false;
			}

			$ceil = ceil( $mask / 16 );
			for ( $i = 1; $i <= $ceil; ++ $i ) {
				$left   = $mask - 16 * ( $i - 1 );
				$left   = ( $left <= 16 ) ? $left : 16;
				$mask_b = ~( 0xffff >> $left ) & 0xffff;
				if ( ( $bytes_addr[ $i ] & $mask_b ) !== ( $bytes_est[ $i ] & $mask_b ) ) {
					return false;
				}
			}

			return true;
		} else {    // Check for IPv4.
			if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
				return false;
			}

			if ( false !== strpos( $net, '/' ) ) {  // Parse CIDR subnet.
				list( $base, $mask ) = explode( '/', $net, 2 );

				if ( '0' === $mask ) {
					return filter_var( $base, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
				}

				if ( $mask < 0 || $mask > 32 ) {
					return false;
				}
			} else {    // Use single address.
				$base = $net;
				$mask = 32;
			}

			return ( 0 === substr_compare( sprintf( '%032b', ip2long( $ip ) ), sprintf( '%032b', ip2long( $base ) ), 0, $mask ) );
		} // End if().
	}
}
