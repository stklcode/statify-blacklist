<?php

/* Quit */
defined( 'ABSPATH' ) OR exit;

/**
 * Statify Blacklist admin configuration
 *
 * @since 1.0.0
 */
class StatifyBlacklist_Admin extends StatifyBlacklist {
	/**
	 * Update options
	 *
	 * @param  $options array  New options to save
	 * @return mixed  array of sanitized array on errors, FALSE if there were none
	 * @since   1.1.1
	 */
	public static function update_options( $options = null ) {
		if ( isset( $options ) && current_user_can( 'manage_options' ) ) {
			/* Sanitize URLs and remove empty inputs */
			$givenReferer = $options['referer'];
			$sanitizedReferer = self::sanitizeURLs( $givenReferer );

			/* Abort on errors */
			if ( ! empty( array_diff( $givenReferer, $sanitizedReferer ) ) ) {
				return $sanitizedReferer;
			}

			/* Update database on success */
			if ( ( is_multisite() && array_key_exists( STATIFYBLACKLIST_BASE, (array) get_site_option( 'active_sitewide_plugins' ) ) ) ) {
				update_site_option( 'statify-blacklist', $options );
			} else {
				update_option( 'statify-blacklist', $options );
			}
		}

		/* Refresh options */
		parent::update_options( $options );

		return false;
	}

	/**
	 * Add configuration page to admin menu
	 *
	 * @since   1.0.0
	 */
	public function _add_menu_page() {
		$title = __( 'Statify Blacklist', 'statify-blacklist' );
		if ( self::$multisite ) {
			add_submenu_page( 'settings.php', $title, $title, 'manage_network_plugins', 'statify-blacklist-settings', array(
				'StatifyBlacklist_Admin',
				'settings_page'
			) );
		} else {
			add_submenu_page( 'options-general.php', $title, $title, 'manage_options', 'statify-blacklist', array(
				'StatifyBlacklist_Admin',
				'settings_page'
			) );
		}

	}

	public static function settings_page() {
		include STATIFYBLACKLIST_DIR . '/views/settings_page.php';
	}

	/**
	 * Add plugin meta links
	 *
	 * @param $links
	 * @param $file
	 *
	 * @return array
	 *
	 * @since   1.0.0
	 */
	public static function plugin_meta_link( $links, $file ) {
		if ( $file == STATIFYBLACKLIST_BASE ) {
			$links[] = '<a href="https://github.com/stklcode/statify-blacklist">GitHub</a>';
		}

		return $links;
	}

	/**
	 * Add plugin action links
	 *
	 * @param   array $input Registered links
	 *
	 * @return  array           Merged links
	 *
	 * @since   1.0.0
	 */
	public static function plugin_actions_links( $links, $file ) {
		$base = self::$multisite ? network_admin_url( 'settings.php' ) : admin_url( 'options-general.php' );

		if ( $file == STATIFYBLACKLIST_BASE && current_user_can( 'manage_options' ) ) {
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
	 * @since   1.1.0
	 * @changed 1.2.0
	 */
	public static function cleanup_database() {
		/* Check user permissions */
		if ( ! current_user_can( 'manage_options' ) && ! ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
			die( _e( 'Are you sure you want to do this?' ) );
		}

		global $wpdb;

		/* Sanitize URLs */
		$referer = self::sanitizeURLs( self::$_options['referer'] );

		/* Build filter regexp */
		$refererRegexp = str_replace( '.', '\.', implode( '|', array_flip( $referer ) ) );
		if ( ! empty( $refererRegexp ) ) {
			/* Execute filter on database */
			$wpdb->query(
				$wpdb->prepare( "DELETE FROM `$wpdb->statify` WHERE referrer REGEXP %s", $refererRegexp )
			);

			/* Optimize DB */
			$wpdb->query( "OPTIMIZE TABLE `$wpdb->statify`" );

			/* Delete transient statify data */
			delete_transient('statify_data');
		}
	}


	/**
	 * Sanitize URLs and remove empty results
	 *
	 * @param $urls array   given array of URLs
	 *
	 * @return array  sanitized array
	 *
	 * @since    1.1.1
	 * @changed  1.2.0
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
}
