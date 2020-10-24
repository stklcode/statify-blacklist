<?php
/**
 * Statify Filter: StatifyBlacklist class
 *
 * This file contains the plugin's base class.
 *
 * @package Statify_Blacklist
 * @since   1.0.0
 */

// Quit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Statify Filter.
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
	 * Operation mode "normal".
	 *
	 * @var integer MODE_NORMAL
	 */
	const MODE_NORMAL = 0;

	/**
	 * Operation mode "regular expression".
	 *
	 * @var integer MODE_REGEX
	 */
	const MODE_REGEX = 1;

	/**
	 * Operation mode "regular expression case insensitive".
	 *
	 * @var integer MODE_REGEX_CI
	 */
	const MODE_REGEX_CI = 2;

	/**
	 * Operation mode "keyword".
	 *
	 * @since 1.5.0
	 * @var integer MODE_KEYWORD
	 */
	const MODE_KEYWORD = 3;

	/**
	 * Plugin options.
	 *
	 * @since 1.0.0
	 * @var array $options
	 */
	public static $options;

	/**
	 * Multisite Status.
	 *
	 * @since 1.0.0
	 * @var bool $multisite
	 */
	public static $multisite;

	/**
	 * Plugin initialization.
	 *
	 * @since 1.4.2
	 *
	 * @return void
	 */
	public static function init() {
		// Skip on autosave.
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}

		// Get multisite status.
		self::$multisite = ( is_multisite() && array_key_exists( STATIFYBLACKLIST_BASE, (array) get_site_option( 'active_sitewide_plugins' ) ) );

		// Plugin options.
		self::update_options();

		// Add Filter to statify hook if enabled.
		if ( 0 !== self::$options['referer']['active'] ||
			0 !== self::$options['target']['active'] ||
			0 !== self::$options['ip']['active'] ||
			0 !== self::$options['ua']['active'] ) {
			add_filter( 'statify__skip_tracking', array( 'StatifyBlacklist', 'apply_blacklist_filter' ) );
		}

		// Statify uses WP AJAX as of 1.7, so we need to reach this point. But there are no further admin/cron actions.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		// Admin only filters.
		if ( is_admin() ) {
			StatifyBlacklist_Admin::init();
		}

		// CronJob to clean up database.
		if ( defined( 'DOING_CRON' ) && DOING_CRON &&
			( 1 === self::$options['referer']['cron'] || 1 === self::$options['target']['cron'] ) ) {
			add_action( 'statify_cleanup', array( 'StatifyBlacklist_Admin', 'cleanup_database' ) );
		}
	}

	/**
	 * Update options.
	 *
	 * @since 1.0.0
	 * @since 1.2.1 update_options($options = null) Parameter with default value introduced.
	 *
	 * @param array $options Optional. New options to save.
	 *
	 * @return void
	 */
	public static function update_options( $options = null ) {
		if ( self::$multisite ) {
			$o = get_site_option( 'statify-blacklist' );
		} else {
			$o = get_option( 'statify-blacklist' );
		}
		self::$options = wp_parse_args( $o, self::default_options() );
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
			'ua'      => array(
				'active'    => 0,
				'regexp'    => 0,
				'blacklist' => array(),
			),
			'version' => self::VERSION_MAIN,
		);
	}

	/**
	 * Apply the filter if active
	 *
	 * @since 1.0.0
	 *
	 * @return bool TRUE if referer matches filter.
	 */
	public static function apply_blacklist_filter() {
		// Referer filter.
		if ( isset( self::$options['referer']['active'] ) && 0 !== self::$options['referer']['active'] ) {
			// Determine filter mode.
			$mode = isset( self::$options['referer']['regexp'] ) ? intval( self::$options['referer']['regexp'] ) : 0;

			// Get full referer string.
			$referer = wp_get_raw_referer();
			if ( ! $referer ) {
				$referer = '';
			}

			switch ( $mode ) {

				// Regular Expression filtering since 1.3.0.
				case self::MODE_REGEX:
				case self::MODE_REGEX_CI:
					// Merge given regular expressions into one.
					$regexp = self::regex(
						array_keys( self::$options['referer']['blacklist'] ),
						self::MODE_REGEX_CI === self::$options['referer']['regexp']
					);

					// Check filter (no return to continue filtering #12).
					if ( 1 === preg_match( $regexp, $referer ) ) {
						return true;
					}
					break;

				// Keyword filter since 1.5.0 (#15).
				case self::MODE_KEYWORD:
					// Get filter.
					$blacklist = self::$options['referer']['blacklist'];

					foreach ( array_keys( $blacklist ) as $keyword ) {
						if ( false !== strpos( strtolower( $referer ), strtolower( $keyword ) ) ) {
							return true;
						}
					}
					break;

				// Standard domain filter.
				default:
					// Extract relevant domain parts.
					$referer = wp_parse_url( $referer );
					$referer = strtolower( ( isset( $referer['host'] ) ? $referer['host'] : '' ) );

					// Get filter.
					$blacklist = self::$options['referer']['blacklist'];

					// Check filter.
					if ( isset( $blacklist[ $referer ] ) ) {
						return true;
					}
			}
		}

		// Target filter (since 1.4.0).
		if ( isset( self::$options['target']['active'] ) && 0 !== self::$options['target']['active'] ) {
			// Regular Expression filtering since 1.3.0.
			if ( isset( self::$options['target']['regexp'] ) && 0 < self::$options['target']['regexp'] ) {
				// Get full referer string.
				$target = ( isset( $_SERVER['REQUEST_URI'] ) ? filter_var( wp_unslash( $_SERVER['REQUEST_URI'] ), FILTER_SANITIZE_URL ) : '/' );
				// Merge given regular expressions into one.
				$regexp = self::regex(
					array_keys( self::$options['target']['blacklist'] ),
					self::MODE_REGEX_CI === self::$options['target']['regexp']
				);

				// Check filter (no return to continue filtering #12).
				if ( 1 === preg_match( $regexp, $target ) ) {
					return true;
				}
			} else {
				// Extract target page.
				$target = ( isset( $_SERVER['REQUEST_URI'] ) ? filter_var( wp_unslash( $_SERVER['REQUEST_URI'] ), FILTER_SANITIZE_URL ) : '/' );
				// Get filter.
				$blacklist = self::$options['target']['blacklist'];
				// Check filter.
				if ( isset( $blacklist[ $target ] ) ) {
					return true;
				}
			}
		}

		// IP filter (since 1.4.0).
		if ( isset( self::$options['ip']['active'] ) && 0 !== self::$options['ip']['active'] ) {
			$ip = self::get_ip();
			if ( false !== ( $ip ) ) {
				foreach ( self::$options['ip']['blacklist'] as $net ) {
					if ( self::cidr_match( $ip, $net ) ) {
						return true;
					}
				}
			}
		}

		// User agent filter (since 1.6).
		if ( isset( self::$options['ua']['active'] ) && 0 !== self::$options['ua']['active'] ) {
			// Determine filter mode.
			$mode = isset( self::$options['ua']['regexp'] ) ? intval( self::$options['ua']['regexp'] ) : 0;

			// Get full user agent string.
			if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
				$user_agent = filter_var( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ), FILTER_SANITIZE_STRING );

				if ( $user_agent ) {
					switch ( $mode ) {

						// Regular Expression filtering since 1.3.0.
						case self::MODE_REGEX:
						case self::MODE_REGEX_CI:
							// Merge given regular expressions into one.
							$regexp = self::regex(
								array_keys( self::$options['ua']['blacklist'] ),
								self::MODE_REGEX_CI === self::$options['ua']['regexp']
							);

							// Check filter (no return to continue filtering #12).
							if ( 1 === preg_match( $regexp, $user_agent ) ) {
								return true;
							}
							break;

						// Keyword filter since 1.5.0 (#15).
						case self::MODE_KEYWORD:
							// Get filter.
							$blacklist = self::$options['ua']['blacklist'];

							foreach ( array_keys( $blacklist ) as $keyword ) {
								if ( false !== strpos( strtolower( $user_agent ), strtolower( $keyword ) ) ) {
									return true;
								}
							}
							break;

						// Standard exact filter.
						default:
							// Get filter.
							$blacklist = self::$options['ua']['blacklist'];

							// Check filter.
							if ( isset( $blacklist[ $user_agent ] ) ) {
								return true;
							}
					}
				}
			}
		}

		// Skip and continue (return NULL), if all filters are inactive.
		return null;
	}

	/**
	 * Preprocess regular expression provided by the user, i.e. add delimiters and optional ci flag.
	 *
	 * @param string|array $expression       Original expression string or array of expressions.
	 * @param string|array $case_insensitive Make expression match case-insensitive.
	 *
	 * @return string Preprocessed expression ready for preg_match().
	 */
	protected static function regex( $expression, $case_insensitive ) {
		$res = '/';
		if ( is_string( $expression ) ) {
			$res .= str_replace( '/', '\/', $expression );
		} elseif ( is_array( $expression ) ) {
			$res .= implode(
				'|',
				array_map(
					function ( $e ) {
						return str_replace( '/', '\/', $e );
					},
					$expression
				)
			);
		}
		$res .= '/';
		if ( $case_insensitive ) {
			$res .= 'i';
		}

		return $res;
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
			if ( isset( $_SERVER[ $k ] ) ) {
				// phpcs:ignore
				foreach ( explode( ',', $_SERVER[ $k ] ) as $ip ) {
					if ( false !== filter_var( $ip, FILTER_VALIDATE_IP ) ) {
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
		}
	}
}
