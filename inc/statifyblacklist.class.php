<?php

/* Quit */
defined( 'ABSPATH' ) OR exit;

/**
 * Statify Blacklist
 *
 * @since 1.0.0
 */
class StatifyBlacklist {
	/**
	 * Plugin options
	 *
	 * @var array
	 * @since   1.0.0
	 */
	public static $_options;

	/**
	 * Multisite Status
	 *
	 * @var bool
	 * @since   1.0.0
	 */
	public static $multisite;

	/**
	 * Class self initialize
	 *
	 * @since   1.0.0
	 */
	public static function instance() {
		new self();
	}

	/**
	 * Class constructor
	 *
	 * @since   1.0.0
	 * @changed 1.1.2
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
		if ( self::$_options['active_referer'] != 1 ) {
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
			if ( self::$_options['cron_referer'] == 1 ) {
				add_action( 'statify_cleanup', array( 'StatifyBlacklist_Admin', 'cleanup_database' ) );
			}
		}
	}

	/**
	 * Update options
	 *
	 * @param  $options array  New options to save
	 *
	 * @since   1.0.0
	 * @changed 1.1.1
	 */
	public static function update_options( $options = null ) {
		self::$_options = wp_parse_args(
			get_option( 'statify-blacklist' ),
			array(
				'active_referer' => 0,
				'cron_referer'   => 0,
				'referer'        => array()
			)
		);
	}

	/**
	 * Apply the blacklist filter if active
	 *
	 * @return  TRUE if referer matches blacklist.
	 *
	 * @since   1.0.0
	 * @changed 1.2.0
	 */
	public static function apply_blacklist_filter() {
		/* Skip if blacklist is inactive */
		if ( self::$_options['active_referer'] != 1 ) {
			return false;
		}

		/* Extract relevant domain parts */
		$referer = strtolower( ( isset( $_SERVER['HTTP_REFERER'] ) ? parse_url( $_SERVER['HTTP_REFERER'], PHP_URL_HOST ) : '' ) );
		$referer = explode( '.', $referer );
//		if ( count( $referer ) > 1 ) {
//			$referer = implode( '.', array_slice( $referer, - 2 ) );
//		} else {
			$referer = implode( '.', $referer );
//		}

		/* Get blacklist */
		$blacklist = self::$_options['referer'];

		/* Check blacklist */

		return isset( $blacklist[ $referer ] );
	}
}
