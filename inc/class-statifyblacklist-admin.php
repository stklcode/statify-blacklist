<?php
/**
 * Statify Filter: StatifyBlacklist_Admin class
 *
 * This file contains the derived class for the plugin's administration features.
 *
 * @package   Statify_Blacklist
 * @subpackge Admin
 * @since     1.0.0
 */

// Quit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Statify Filter admin configuration.
 *
 * @since   1.0.0
 */
class StatifyBlacklist_Admin extends StatifyBlacklist {

	/**
	 * Initialize admin-only components of the plugin.
	 *
	 * @since 1.5.0
	 *
	 * @return void
	 */
	public static function init() {
		// Add actions.
		add_action( 'wpmu_new_blog', array( 'StatifyBlacklist_System', 'install_site' ) );
		add_action( 'delete_blog', array( 'StatifyBlacklist_System', 'uninstall_site' ) );
		add_filter( 'plugin_row_meta', array( 'StatifyBlacklist_Admin', 'plugin_meta_link' ), 10, 2 );

		if ( self::$multisite ) {
			add_action( 'network_admin_menu', array( 'StatifyBlacklist_Admin', 'add_menu_page' ) );
			add_filter(
				'network_admin_plugin_action_links',
				array(
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

	/**
	 * Update options.
	 *
	 * @since 1.1.1
	 *
	 * @param  array $options Optional. New options to save.
	 *
	 * @return array|bool  array of sanitized array on errors, FALSE if there were none.
	 */
	public static function update_options( $options = null ) {
		if ( isset( $options ) && current_user_can( 'manage_options' ) ) {

			// Sanitize referer list.
			$given_referer   = $options['referer']['blacklist'];
			$invalid_referer = array();
			if ( self::MODE_NORMAL === $options['referer']['regexp'] ) {
				// Sanitize URLs and remove empty inputs.
				$sanitized_referer = self::sanitize_urls( $given_referer );
			} elseif ( self::MODE_REGEX === $options['referer']['regexp'] || self::MODE_REGEX_CI === $options['referer']['regexp'] ) {
				$sanitized_referer = $given_referer;
				// Check regular expressions.
				$invalid_referer = self::sanitize_regex( $given_referer );
			} else {
				$sanitized_referer = $given_referer;
			}

			// Sanitize target list.
			$given_target   = $options['target']['blacklist'];
			$invalid_target = array();
			if ( self::MODE_REGEX === $options['target']['regexp'] || self::MODE_REGEX_CI === $options['target']['regexp'] ) {
				$sanitized_target = $given_target;
				// Check regular expressions.
				$invalid_target = self::sanitize_regex( $given_target );
			} else {
				$sanitized_target = $given_target;
			}

			// Sanitize IPs and subnets and remove empty inputs.
			$given_ip     = $options['ip']['blacklist'];
			$sanitized_ip = self::sanitize_ips( $given_ip );

			// Abort on errors.
			$errors = array(
				'referer' => array(
					'sanitized' => $sanitized_referer,
					'diff'      => array_diff( $given_referer, $sanitized_referer ),
					'invalid'   => $invalid_referer,
				),
				'target'  => array(
					'sanitized' => $sanitized_target,
					'diff'      => array_diff( $given_target, $sanitized_target ),
					'invalid'   => $invalid_target,
				),
				'ip'      => array(
					'sanitized' => $sanitized_ip,
					'diff'      => array_diff( $given_ip, $sanitized_ip ),
				),
			);
			if ( ! empty( $errors['referer']['diff'] )
				|| ! empty( $errors['referer']['invalid'] )
				|| ! empty( $errors['target']['diff'] )
				|| ! empty( $errors['target']['invalid'] )
				|| ! empty( $errors['ip']['diff'] ) ) {
				return $errors;
			}

			// Update database on success.
			if ( self::$multisite ) {
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
	public static function add_menu_page() {
		$title = __( 'Statify Filter', 'statify-blacklist' );
		if ( self::$multisite ) {
			add_submenu_page(
				'settings.php',
				$title,
				$title,
				'manage_network_plugins',
				'statify-blacklist-settings',
				array(
					'StatifyBlacklist_Admin',
					'settings_page',
				)
			);
		} else {
			add_submenu_page(
				'options-general.php',
				$title,
				$title,
				'manage_options',
				'statify-blacklist',
				array(
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
				sprintf( '<a href="%s">%s</a>', esc_attr( add_query_arg( 'page', 'statify-blacklist', $base ) ), __( 'Settings', 'statify-blacklist' ) )
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
			die( esc_html__( 'Are you sure you want to do this?', 'statify-blacklist' ) );
		}

		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			$clean_ref = ( 1 === self::$options['referer']['cron'] );
			$clean_trg = ( 1 === self::$options['target']['cron'] );
		} else {
			$clean_ref = true;
			$clean_trg = true;
		}

		if ( $clean_ref ) {
			if ( isset( self::$options['referer']['regexp'] ) && self::$options['referer']['regexp'] > 0 ) {
				// Merge given regular expressions into one.
				$referer_regexp = implode( '|', array_keys( self::$options['referer']['blacklist'] ) );
			} else {
				// Sanitize URLs.
				$referer = self::sanitize_urls( self::$options['referer']['blacklist'] );

				// Build filter regexp.
				$referer_regexp = str_replace( '.', '\.', implode( '|', array_flip( $referer ) ) );
			}
		}

		if ( $clean_trg ) {
			if ( isset( self::$options['target']['regexp'] ) && self::$options['target']['regexp'] > 0 ) {
				// Merge given regular expressions into one.
				$target_regexp = implode( '|', array_keys( self::$options['target']['blacklist'] ) );
			} else {
				// Build filter regexp.
				$target_regexp = str_replace( '.', '\.', implode( '|', array_flip( self::$options['target']['blacklist'] ) ) );
			}
		}

		if ( ! empty( $referer_regexp ) || ! empty( $target_regexp ) ) {
			global $wpdb;

			// Execute filter on database.
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- These statements produce warnings, rework in future release (TODO).
			if ( ! empty( $referer_regexp ) ) {
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM `$wpdb->statify` WHERE "
						. ( ( 1 === self::$options['referer']['regexp'] ) ? ' BINARY ' : '' )
						. 'referrer REGEXP %s',
						$referer_regexp
					)
				);
			}
			if ( ! empty( $target_regexp ) ) {
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM `$wpdb->statify` WHERE "
						. ( ( 1 === self::$options['target']['regexp'] ) ? ' BINARY ' : '' )
						. 'target REGEXP %s',
						$target_regexp
					)
				);
			}
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

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
	private static function sanitize_urls( $urls ) {
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
	private static function sanitize_ips( $ips ) {
		return array_filter(
			$ips,
			function ( $ip ) {
				return preg_match(
					'/^((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])(\/([0-9]|[1-2][0-9]|3[0-2]))?$/',
					$ip
				) ||
				preg_match(
					'/^(([0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}|([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}|([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,1}:){0,1}((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])|([0-9a-fA-F]{1,4}:){1,4}:((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9]))(\/([0-9]|[1-9][0-9]|1[0-1][0-9]|12[0-8]))?$/',
					$ip
				);
			}
		);
	}

	/**
	 * Validate regular expressions, i.e. remove duplicates and empty values and validate others.
	 *
	 * @since 1.5.0 #13
	 *
	 * @param array $expressions Given pre-sanitized array of regular expressions.
	 *
	 * @return array Array of invalid expressions.
	 */
	private static function sanitize_regex( $expressions ) {
		return array_filter(
			array_flip( $expressions ),
			function ( $re ) {
				// Check of preg_match() fails (warnings suppressed).

				// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				return false === @preg_match( StatifyBlacklist::regex( $re, false ), null );
			}
		);
	}
}
