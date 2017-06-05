<?php

/* Quit */
defined( 'ABSPATH' ) OR exit;

/**
 * Statify Blacklist
 *
 * @since   1.0.0
 * @version 1.4.0~dev
 */
class StatifyBlacklist {

	const VERSION_MAIN = 1.4;

	/**
	 * Plugin options
	 *
	 * @var array
	 * @since 1.0.0
	 */
	public static $_options;

	/**
	 * Multisite Status
	 *
	 * @var bool
	 * @since 1.0.0
	 */
	public static $multisite;

	/**
	 * Class self initialize
	 *
	 * @since 1.0.0
	 */
	public static function instance() {
		new self();
	}

	/**
	 * Class constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		/* Skip on autosave or AJAX */
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) OR ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}

		/* Plugin options */
		self::update_options();

		/* Get multisite status */
		self::$multisite = ( is_multisite() && array_key_exists( STATIFYBLACKLIST_BASE, (array) get_site_option( 'active_sitewide_plugins' ) ) );

		/* Add Filter to statify hook if enabled */
		if ( self::$_options['referer']['active'] != 0 ) {
			add_filter( 'statify_skip_tracking', array( 'StatifyBlacklist', 'apply_blacklist_filter' ) );
		}

		/* Admin only filters */
		if ( is_admin() ) {
			/* Load Textdomain (only needed for backend */
			load_plugin_textdomain( 'statifyblacklist', false, STATIFYBLACKLIST_DIR . '/lang/' );

			/* Add actions */
			add_action( 'wpmu_new_blog', array( 'StatifyBlacklist_Install', 'init_site' ) );
			add_action( 'delete_blog', array( 'StatifyBlacklist_System', 'init_site' ) );
			add_filter( 'plugin_row_meta', array( 'StatifyBlacklist_Admin', 'plugin_meta_link' ), 10, 2 );

			if ( is_multisite() ) {
				add_action( 'network_admin_menu', array( 'StatifyBlacklist_Admin', '_add_menu_page' ) );
				add_filter( 'network_admin_plugin_action_links', array(
					'StatifyBlacklist_Admin',
					'plugin_actions_links'
				), 10, 2 );
			} else {
				add_action( 'admin_menu', array( 'StatifyBlacklist_Admin', '_add_menu_page' ) );
				add_filter( 'plugin_action_links', array( 'StatifyBlacklist_Admin', 'plugin_actions_links' ), 10, 2 );
			}
		}

		/* CronJob to clean up database */
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			if ( self::$_options['referer']['cron'] == 1 || self::$_options['target']['cron'] == 1 ) {
				add_action( 'statify_cleanup', array( 'StatifyBlacklist_Admin', 'cleanup_database' ) );
			}
		}
	}

	/**
	 * Update options
	 *
	 * @param array $options New options to save
	 *
	 * @since 1.0.0
	 * @since 1.2.1 update_options($options = null) Parameter with default value introduced
	 */
	public static function update_options( $options = null ) {
		self::$_options = wp_parse_args(
			get_option( 'statify-blacklist' ),
			self::defaultOptions()
		);
	}

	/**
	 * Create default plugin configuration.
	 *
	 * @since 1.4.0
	 *
	 * @return array the options array
	 */
	protected static function defaultOptions() {
		return array(
			'referer' => array(
				'active'    => 0,
				'cron'      => 0,
				'regexp'    => 0,
				'blacklist' => array()
			),
			'target'  => array(
				'active'    => 0,
				'cron'      => 0,
				'regexp'    => 0,
				'blacklist' => array()
			),
			'ip'      => array(
				'active'    => 0,
				'blacklist' => array()
			),
			'version' => self::VERSION_MAIN
		);
	}

	/**
	 * Apply the blacklist filter if active
	 *
	 * @return bool TRUE if referer matches blacklist.
	 *
	 * @since 1.0.0
	 */
	public static function apply_blacklist_filter() {
		/* Referer blacklist */
		if ( isset( self::$_options['referer']['active'] ) && self::$_options['referer']['active'] != 0 ) {
			/* Regular Expression filtering since 1.3.0 */
			if ( isset( self::$_options['referer']['regexp'] ) && self::$_options['referer']['regexp'] > 0 ) {
				/* Get full referer string */
				$referer = ( isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '' );
				/* Merge given regular expressions into one */
				$regexp = '/' . implode( "|", array_keys( self::$_options['referer']['blacklist'] ) ) . '/';
				if ( self::$_options['referer']['regexp'] == 2 ) {
					$regexp .= 'i';
				}

				/* Check blacklist (return NULL to continue filtering) */

				return ( preg_match( $regexp, $referer ) === 1 ) ? true : null;
			} else {
				/* Extract relevant domain parts */
				$referer = strtolower( ( isset( $_SERVER['HTTP_REFERER'] ) ? parse_url( $_SERVER['HTTP_REFERER'], PHP_URL_HOST ) : '' ) );

				/* Get blacklist */
				$blacklist = self::$_options['referer']['blacklist'];

				/* Check blacklist */
				if ( isset( $blacklist[ $referer ] ) ) {
					return true;
				}
			}
		}

		/* Target blacklist (since 1.4.0) */
		if ( isset( self::$_options['target']['active'] ) && self::$_options['target']['active'] != 0 ) {
			/* Regular Expression filtering since 1.3.0 */
			if ( isset( self::$_options['target']['regexp'] ) && self::$_options['target']['regexp'] > 0 ) {
				/* Get full referer string */
				$target = ( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/' );
				/* Merge given regular expressions into one */
				$regexp = '/' . implode( "|", array_keys( self::$_options['target']['blacklist'] ) ) . '/';
				if ( self::$_options['target']['regexp'] == 2 ) {
					$regexp .= 'i';
				}

				/* Check blacklist (return NULL to continue filtering) */

				return ( preg_match( $regexp, $target ) === 1 ) ? true : null;
			} else {
				/* Extract target page */
				$target = ( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/' );
				/* Get blacklist */
				$blacklist = self::$_options['target']['blacklist'];
				/* Check blacklist */
				if ( isset( $blacklist[ $target ] ) ) {
					return true;
				}
			}
		}

		/* IP blacklist (since 1.4.0) */
		if ( isset ( self::$_options['ip']['active'] ) && self::$_options['ip']['active'] != 0 ) {
			if ( ( $ip = self::getIP() ) !== false ) {
				foreach ( self::$_options['ip']['blacklist'] as $net ) {
					if ( self::cidrMatch( $ip, $net ) ) {
						return true;
					}
				}
			}
		}

		/* Skip and continue (return NULL), if all blacklists are inactive */

		return null;
	}

	/**
	 * Helper method to determine the client's IP address.
	 * If a proxy is used, the X-Real-IP or X-Forwarded-For header is checked, otherwise the default remote address.
	 * For performance reasons only the most common flags are checked. This might be even reduce by user configuration.
	 * Maybe some community feedback will ease the decision on that.
	 *
	 * @return string|bool the client's IP address or FALSE, if none could be determined
	 */
	private static function getIP() {
		foreach (
			array(
//				'HTTP_CLIENT_IP',
				'HTTP_X_REAL_IP',
				'HTTP_X_FORWARDED_FOR',
//				'HTTP_X_FORWARDED',
//				'HTTP_X_CLUSTER_CLIENT_IP',
//				'HTTP_FORWARDED_FOR',
//				'HTTP_FORWARDED',
				'REMOTE_ADDR'
			) as $k
		) {
			if ( isset( $_SERVER[ $k ] ) ) {
				foreach ( explode( ',', $_SERVER[ $k ] ) as $ip ) {
					if ( filter_var( $ip, FILTER_VALIDATE_IP ) !== false ) {
						return $ip;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Helper function to check if an IP address matches a given subnet.
	 *
	 * @param string $ip IP address to check
	 * @param string $net IP address or subnet in CIDR notation
	 *
	 * @return bool TRUE, if the given IP addresses matches the given subnet
	 */
	private static function cidrMatch( $ip, $net ) {
		if ( substr_count( $net, ':' ) > 1 ) {  /* Check for IPv6 */
			if ( ! ( ( extension_loaded( 'sockets' ) && defined( 'AF_INET6' ) ) || @inet_pton( '::1' ) ) ) {
				return false;
			}

			if ( false !== strpos( $net, '/' ) ) {   /* Parse CIDR subnet */
				list( $base, $mask ) = explode( '/', $net, 2 );

				if ( $mask < 1 || $mask > 128 ) {
					return false;
				}
			} else {
				$base = $net;
				$mask = 128;
			}

			$bytesAddr = unpack( 'n*', @inet_pton( $base ) );
			$bytesTest = unpack( 'n*', @inet_pton( $ip ) );

			if ( ! $bytesAddr || ! $bytesTest ) {
				return false;
			}

			for ( $i = 1, $ceil = ceil( $mask / 16 ); $i <= $ceil; ++ $i ) {
				$left  = $mask - 16 * ( $i - 1 );
				$left  = ( $left <= 16 ) ? $left : 16;
				$maskB = ~( 0xffff >> $left ) & 0xffff;
				if ( ( $bytesAddr[ $i ] & $maskB ) != ( $bytesTest[ $i ] & $maskB ) ) {
					return false;
				}
			}

			return true;
		} else {    /* Check for IPv4 */
			if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
				return false;
			}

			if ( false !== strpos( $net, '/' ) ) {  /* Parse CIDR subnet */
				list( $base, $mask ) = explode( '/', $net, 2 );

				if ( $mask === '0' ) {
					return filter_var( $base, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
				}

				if ( $mask < 0 || $mask > 32 ) {
					return false;
				}
			} else {    /* Use single address */
				$base = $net;
				$mask = 32;
			}

			return 0 === substr_compare( sprintf( '%032b', ip2long( $ip ) ), sprintf( '%032b', ip2long( $base ) ), 0, $mask );
		}
	}
}
