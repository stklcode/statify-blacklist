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
 */
class StatifyBlacklist_Admin extends StatifyBlacklist {

	/**
	 * Initialize admin-only components of the plugin.
	 *
	 * @return void
	 *
	 * @since 1.5.0
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
			add_action( 'admin_init', array( 'StatifyBlacklist_Settings', 'register_settings' ) );
			add_action( 'admin_menu', array( 'StatifyBlacklist_Admin', 'add_menu_page' ) );
			add_filter( 'plugin_action_links', array( 'StatifyBlacklist_Admin', 'plugin_actions_links' ), 10, 2 );
		}
	}

	/**
	 * Add configuration page to admin menu.
	 *
	 * @since 1.0.0
	 */
	public static function add_menu_page() {
		$title = __( 'Statify Filter', 'statify-blacklist' );
		if ( self::$multisite ) {
			add_options_page(
				$title,
				$title,
				'manage_network_plugins',
				'statify-blacklist',
				array( 'StatifyBlacklist_Settings', 'create_settings_page' )
			);
		} else {
			add_options_page(
				$title,
				$title,
				'manage_options',
				'statify-blacklist',
				array( 'StatifyBlacklist_Settings', 'create_settings_page' )
			);
		}
	}

	/**
	 * Add plugin meta links
	 *
	 * @param array  $links Registered links.
	 * @param string $file  The filename.
	 *
	 * @return array  Merged links.
	 *
	 * @since 1.0.0
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
	 * @param array  $links Registered links.
	 * @param string $file  The filename.
	 *
	 * @return array  Merged links.
	 *
	 * @since 1.0.0
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
	 * @param array $urls given array of URLs.
	 *
	 * @return array  sanitized array.
	 *
	 * @since 1.1.1
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
}
