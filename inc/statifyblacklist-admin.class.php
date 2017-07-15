<?php
/**
 * Statify Blacklist: StatifyBlacklist_Admin class
 *
 * This file contains the derived class for the plugin's administration features.
 *
 * @package   Statify_Blacklist
 * @subpackge Admin
 * @since     1.0.0
 */

// Quit.
defined( 'ABSPATH' ) or exit;

/**
 * Statify Blacklist admin configuration.
 *
 * @since   1.0.0
 */
class StatifyBlacklist_Admin extends StatifyBlacklist {
	/**
	 * Update options.
	 *
	 * @param  array $options Optional. New options to save.
	 *
	 * @return array|bool  array of sanitized array on errors, FALSE if there were none.
	 * @since 1.1.1
	 */
	public static function update_options( $options = null ) {
		if ( isset( $options ) && current_user_can( 'manage_options' ) ) {
			// Sanitize URLs and remove empty inputs.
			$givenReferer = $options['referer']['blacklist'];
			if ( 0 === $options['referer']['regexp'] ) {
				$sanitizedReferer = self::sanitizeURLs( $givenReferer );
			} else {
				$sanitizedReferer = $givenReferer;
			}

			// Sanitize IPs and Subnets and remove empty inputs.
			$givenIP     = $options['ip']['blacklist'];
			$sanitizedIP = self::sanitizeIPs( $givenIP );

			// Abort on errors.
			if ( ! empty( array_diff( array_keys( $givenReferer ), array_keys( $sanitizedReferer ) ) ) ) {
				return array( 'referer' => $sanitizedReferer );
			} elseif ( ! empty( array_diff( $givenIP, $sanitizedIP ) ) ) {
				return array( 'ip' => array_diff( $givenIP, $sanitizedIP ) );
			}

			// Update database on success.
			if ( ( is_multisite() && array_key_exists( STATIFYBLACKLIST_BASE, (array) get_site_option( 'active_sitewide_plugins' ) ) ) ) {
				update_site_option( 'statify-blacklist', $options );
			} else {
				update_option( 'statify-blacklist', $options );
			}
		}

		// Refresh options.
		parent::update_options( $options );

		return false;
	}

	/**
	 * Add configuration page to admin menu.
	 *
	 * @since 1.0.0
	 */
	public function _add_menu_page() {
		$title = __( 'Statify Blacklist', 'statify-blacklist' );
		if ( self::$multisite ) {
			add_submenu_page(
				'settings.php', $title, $title, 'manage_network_plugins', 'statify-blacklist-settings', array(
					'StatifyBlacklist_Admin',
					'settings_page',
				)
			);
		} else {
			add_submenu_page(
				'options-general.php', $title, $title, 'manage_options', 'statify-blacklist', array(
					'StatifyBlacklist_Admin',
					'settings_page',
				)
			);
		}

	}

	/**
	 * Include the Statify-Blacklist settings page.
	 *
	 * @since 1.0.0
	 */
	public static function settings_page() {
		include STATIFYBLACKLIST_DIR . '/views/settings-page.php';
	}

	/**
	 * Add plugin meta links
	 *
	 * @since 1.0.0
	 *
	 * @param array  $links Registered links.
	 * @param string $file  The filename.
	 *
	 * @return array  Merged links.
	 */
	public static function plugin_meta_link( $links, $file ) {
		if ( STATIFYBLACKLIST_BASE === $file ) {
			$links[] = '<a href="https://github.com/stklcode/statify-blacklist">GitHub</a>';
		}

		return $links;
	}

	/**
	 * Add plugin action links.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $links Registered links.
	 * @param string $file  The filename.
	 *
	 * @return array  Merged links.
	 */
	public static function plugin_actions_links( $links, $file ) {
		$base = self::$multisite ? network_admin_url( 'settings.php' ) : admin_url( 'options-general.php' );

		if ( STATIFYBLACKLIST_BASE === $file && current_user_can( 'manage_options' ) ) {
			array_unshift(
				$links,
				sprintf( '<a href="%s">%s</a>', esc_attr( add_query_arg( 'page', 'statify-blacklist', $base ) ), __( 'Settings' ) )
			);
		}

		return $links;
	}

	/**
	 * Filter database for cleanup.
	 *
	 * @since 1.1.0
	 *
	 * @global wpdb $wpdb WordPress database.
	 */
	public static function cleanup_database() {
		// Check user permissions.
		if ( ! current_user_can( 'manage_options' ) && ! ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
			die( esc_html__( 'Are you sure you want to do this?' ) );
		}

		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			$cleanRef = ( 1 === self::$_options['referer']['cron'] );
			$cleanTrg = ( 1 === self::$_options['target']['cron'] );
		} else {
			$cleanRef = true;
			$cleanTrg = true;
		}

		if ( $cleanRef ) {
			if ( isset( self::$_options['referer']['regexp'] ) && self::$_options['referer']['regexp'] > 0 ) {
				// Merge given regular expressions into one.
				$refererRegexp = implode( '|', array_keys( self::$_options['referer']['blacklist'] ) );
			} else {
				// Sanitize URLs.
				$referer = self::sanitizeURLs( self::$_options['referer']['blacklist'] );

				// Build filter regexp.
				$refererRegexp = str_replace( '.', '\.', implode( '|', array_flip( $referer ) ) );
			}
		}

		if ( $cleanTrg ) {
			if ( isset( self::$_options['target']['regexp'] ) && self::$_options['target']['regexp'] > 0 ) {
				// Merge given regular expressions into one.
				$targetRegexp = implode( '|', array_keys( self::$_options['target']['blacklist'] ) );
			} else {
				// Build filter regexp.
				$targetRegexp = str_replace( '.', '\.', implode( '|', array_flip( self::$_options['target']['blacklist'] ) ) );
			}
		}

		if ( ! empty( $refererRegexp ) || ! empty( $targetRegexp ) ) {
			global $wpdb;

			// Execute filter on database.
			if ( ! empty( $refererRegexp ) ) {
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM `$wpdb->statify` WHERE "
						. ( ( 1 === self::$_options['referer']['regexp'] ) ? ' BINARY ' : '' )
						. 'referrer REGEXP %s', $refererRegexp
					)
				);
			}
			if ( ! empty( $targetRegexp ) ) {
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM `$wpdb->statify` WHERE "
						. ( ( 1 === self::$_options['target']['regexp'] ) ? ' BINARY ' : '' )
						. 'target REGEXP %s', $targetRegexp
					)
				);
			}

			// Optimize DB.
			$wpdb->query( "OPTIMIZE TABLE `$wpdb->statify`" );

			// Delete transient statify data.
			delete_transient( 'statify_data' );
		}
	}


	/**
	 * Sanitize URLs and remove empty results.
	 *
	 * @since 1.1.1
	 *
	 * @param array $urls given array of URLs.
	 *
	 * @return array  sanitized array.
	 */
	private static function sanitizeURLs( $urls ) {
		return array_flip(
			array_filter(
				array_map(
					function ( $r ) {
						return preg_replace( '/[^\da-z\.-]/i', '', filter_var( $r, FILTER_SANITIZE_URL ) );
					},
					array_flip( $urls )
				)
			)
		);
	}

	/**
	 * Sanitize IP addresses with optional CIDR notation and remove empty results.
	 *
	 * @since 1.4.0
	 *
	 * @param array $ips given array of URLs.
	 *
	 * @return array  sanitized array.
	 */
	private static function sanitizeIPs( $ips ) {
		return array_filter(
			$ips, function ( $ip ) {
				return preg_match(
					'/^((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])(\/([0-9]|[1-2][0-9]|3[0-2]))?$/', $ip
				) ||
					preg_match(
						'/^(([0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}|([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}|([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,1}:){0,1}((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])|([0-9a-fA-F]{1,4}:){1,4}:((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9]))(\/([0-9]|[1-9][0-9]|1[0-1][0-9]|12[0-8]))?$/', $ip
					);
			}
		);
	}
}