<?php
/**
 * Statify Filter: StatifyBlacklist_Settings class
 *
 * This file contains the plugin's settings capabilities.
 *
 * @package Statify_Blacklist
 * @since 1.7.0
 */

// Quit if accessed directly..
defined( 'ABSPATH' ) || exit;

/**
 * Statify Filter settings handling.
 */
class StatifyBlacklist_Settings extends StatifyBlacklist {
	/**
	 * Registers all options using the WP Settings API.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			'statify-blacklist',
			'statify-blacklist',
			array(
				'sanitize_callback' =>
					array( __CLASS__, 'sanitize_options' ),
			)
		);

		// Referer filter.
		add_settings_section(
			'statifyblacklist-referer',
			__( 'Referer filter', 'statify-blacklist' ),
			null,
			'statify-blacklist'
		);
		add_settings_field(
			'statifyblacklist-referer-active',
			__( 'Activate live filter', 'statify-blacklist' ),
			array( __CLASS__, 'option_referer_active' ),
			'statify-blacklist',
			'statifyblacklist-referer'
		);
		add_settings_field(
			'statifyblacklist-referer-cron',
			__( 'CronJob execution', 'statify-blacklist' ),
			array( __CLASS__, 'option_referer_cron' ),
			'statify-blacklist',
			'statifyblacklist-referer'
		);
		add_settings_field(
			'statifyblacklist-referer-regexp',
			__( 'Matching method', 'statify-blacklist' ),
			array( __CLASS__, 'option_referer_regexp' ),
			'statify-blacklist',
			'statifyblacklist-referer',
			array( 'label_for' => 'statifyblacklist-referer-regexp' )
		);
		add_settings_field(
			'statifyblacklist-referer-blacklist',
			__( 'Referer filter', 'statify-blacklist' ),
			array( __CLASS__, 'option_referer_blacklist' ),
			'statify-blacklist',
			'statifyblacklist-referer',
			array( 'label_for' => 'statifyblacklist-referer-blacklist' )
		);

		// Target filter.
		add_settings_section(
			'statifyblacklist-target',
			__( 'Target filter', 'statify-blacklist' ),
			null,
			'statify-blacklist'
		);
		add_settings_field(
			'statifyblacklist-target-active',
			__( 'Activate live filter', 'statify-blacklist' ),
			array( __CLASS__, 'option_target_active' ),
			'statify-blacklist',
			'statifyblacklist-target'
		);
		add_settings_field(
			'statifyblacklist-target-cron',
			__( 'CronJob execution', 'statify-blacklist' ),
			array( __CLASS__, 'option_target_cron' ),
			'statify-blacklist',
			'statifyblacklist-target'
		);
		add_settings_field(
			'statifyblacklist-target-regexp',
			__( 'Matching method', 'statify-blacklist' ),
			array( __CLASS__, 'option_target_regexp' ),
			'statify-blacklist',
			'statifyblacklist-target',
			array( 'label_for' => 'statifyblacklist-target-regexp' )
		);
		add_settings_field(
			'statifyblacklist-target-blacklist',
			__( 'Target filter', 'statify-blacklist' ),
			array( __CLASS__, 'option_target_blacklist' ),
			'statify-blacklist',
			'statifyblacklist-target',
			array( 'label_for' => 'statifyblacklist-target-blacklist' )
		);

		// IP filter.
		add_settings_section(
			'statifyblacklist-ip',
			__( 'IP filter', 'statify-blacklist' ),
			null,
			'statify-blacklist'
		);
		add_settings_field(
			'statifyblacklist-ip-active',
			__( 'Activate live filter', 'statify-blacklist' ),
			array( __CLASS__, 'option_ip_active' ),
			'statify-blacklist',
			'statifyblacklist-ip'
		);
		add_settings_field(
			'statifyblacklist-ip-blacklist',
			__( 'IP filter', 'statify-blacklist' ),
			array( __CLASS__, 'option_ip_blacklist' ),
			'statify-blacklist',
			'statifyblacklist-ip',
			array( 'label_for' => 'statifyblacklist-ip-blacklist' )
		);

		// User agent filter.
		add_settings_section(
			'statifyblacklist-ua',
			__( 'User agent filter', 'statify-blacklist' ),
			null,
			'statify-blacklist'
		);
		add_settings_field(
			'statifyblacklist-ua-active',
			__( 'Activate live filter', 'statify-blacklist' ),
			array( __CLASS__, 'option_ua_active' ),
			'statify-blacklist',
			'statifyblacklist-ua'
		);
		add_settings_field(
			'statifyblacklist-ua-regexp',
			__( 'Matching method', 'statify-blacklist' ),
			array( __CLASS__, 'option_ua_regexp' ),
			'statify-blacklist',
			'statifyblacklist-ua',
			array( 'label_for' => 'statifyblacklist-ua-regexp' )
		);
		add_settings_field(
			'statifyblacklist-ua-blacklist',
			__( 'User agent filter', 'statify-blacklist' ),
			array( __CLASS__, 'option_ua_blacklist' ),
			'statify-blacklist',
			'statifyblacklist-ua',
			array( 'label_for' => 'statifyblacklist-ua-blacklist' )
		);
	}

	/**
	 * Creates the settings pages.
	 *
	 * @return void
	 */
	public static function create_settings_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Statify Filter', 'statify-blacklist' ); ?></h1>

			<form id="statify-settings" method="post" action="options.php">
				<?php
				settings_fields( 'statify-blacklist' );
				do_settings_sections( 'statify-blacklist' );
				submit_button();
				?>
				<hr>
				<input class="button-secondary" type="submit" name="cleanUp"
					value="<?php esc_html_e( 'CleanUp Database', 'statify-blacklist' ); ?>"
					onclick="return confirm('<?php echo esc_js( __( 'Do you really want to apply filters to database? This cannot be undone.', 'statify-blacklist' ) ); ?>');">
				<p class="description">
					<?php esc_html_e( 'Applies referer and target filter (even if disabled) to data stored in database.', 'statify-blacklist' ); ?>
					<em><?php esc_html_e( 'This cannot be undone!', 'statify-blacklist' ); ?></em>
				</p>
			</form>
		</div>

		<?php
	}

	/*
	 * Disable some code style rules that are impractical for textarea content:
	 *
	 * phpcs:disable Squiz.PHP.EmbeddedPhp.ContentBeforeOpen
	 * phpcs:disable Squiz.PHP.EmbeddedPhp.ContentAfterEnd
	 */

	/**
	 * Option for activating the live referer filter.
	 *
	 * @return void
	 */
	public static function option_referer_active() {
		?>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Activate live filter', 'statify-blacklist' ); ?></legend>
			<label for="statifyblacklist-referer-active">
				<input id="statifyblacklist-referer-active" name="statify-blacklist[referer][active]" type="checkbox" value="1" <?php checked( StatifyBlacklist::$options['referer']['active'], 1 ); ?>>
				<?php esc_html_e( 'Activate', 'statify-blacklist' ); ?>
			</label>
			<p class="description">
				<?php esc_html_e( 'Filter at time of tracking, before anything is stored', 'statify-blacklist' ); ?>
			</p>
		</fieldset>
		<?php
	}

	/**
	 * Option for activating cron the referer filter.
	 *
	 * @return void
	 */
	public static function option_referer_cron() {
		?>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'CronJob execution', 'statify-blacklist' ); ?></legend>
			<label for="statifyblacklist-referer-cron">
				<input id="statifyblacklist-referer-cron" name="statify-blacklist[referer][cron]" type="checkbox" value="1" <?php checked( StatifyBlacklist::$options['referer']['cron'], 1 ); ?>>
				<?php esc_html_e( 'Activate', 'statify-blacklist' ); ?>
			</label>
			<p class="description">
				<?php esc_html_e( 'Periodically clean up database in background', 'statify-blacklist' ); ?>
			</p>
		</fieldset>
		<?php
	}

	/**
	 * Option for referer matching method.
	 *
	 * @return void
	 */
	public static function option_referer_regexp() {
		?>
		<select id="statifyblacklist-referer-regexp" name="statify-blacklist[referer][regexp]">
			<option value="<?php print esc_attr( StatifyBlacklist::MODE_NORMAL ); ?>" <?php selected( StatifyBlacklist::$options['referer']['regexp'], StatifyBlacklist::MODE_NORMAL ); ?>>
				<?php esc_html_e( 'Domain', 'statify-blacklist' ); ?>
			</option>
			<option value="<?php print esc_attr( StatifyBlacklist::MODE_KEYWORD ); ?>" <?php selected( StatifyBlacklist::$options['referer']['regexp'], StatifyBlacklist::MODE_KEYWORD ); ?>>
				<?php esc_html_e( 'Keyword', 'statify-blacklist' ); ?>
			</option>
			<option value="<?php print esc_attr( StatifyBlacklist::MODE_REGEX ); ?>" <?php selected( StatifyBlacklist::$options['referer']['regexp'], StatifyBlacklist::MODE_REGEX ); ?>>
				<?php esc_html_e( 'RegEx case-sensitive', 'statify-blacklist' ); ?>
			</option>
			<option value="<?php print esc_attr( StatifyBlacklist::MODE_REGEX_CI ); ?>" <?php selected( StatifyBlacklist::$options['referer']['regexp'], StatifyBlacklist::MODE_REGEX_CI ); ?>>
				<?php esc_html_e( 'RegEx case-insensitive', 'statify-blacklist' ); ?>
			</option>
		</select>
		<p class="description">
			<?php esc_html_e( 'Domain', 'statify-blacklist' ); ?> - <?php esc_html_e( 'Match given domain including subdomains', 'statify-blacklist' ); ?>
			<br>
			<?php esc_html_e( 'Keyword', 'statify-blacklist' ); ?> - <?php esc_html_e( 'Match every referer that contains one of the keywords', 'statify-blacklist' ); ?>
			<br>
			<?php esc_html_e( 'RegEx', 'statify-blacklist' ); ?> - <?php esc_html_e( 'Match referer by regular expression', 'statify-blacklist' ); ?>
		</p>
		<?php
	}

	/**
	 * Option for the referer filter list.
	 *
	 * @return void
	 */
	public static function option_referer_blacklist() {
		?>
		<textarea id="statifyblacklist-referer-blacklist" name="statify-blacklist[referer][blacklist]" cols="40" rows="5"><?php
		print esc_html( implode( "\r\n", array_keys( StatifyBlacklist::$options['referer']['blacklist'] ) ) );
		?></textarea>
		<p class="description">
			<?php esc_html_e( 'Add one domain (without subdomains) each line, e.g. example.com', 'statify-blacklist' ); ?>
		</p>
		<?php
	}

	/**
	 * Option for activating the live target filter.
	 *
	 * @return void
	 */
	public static function option_target_active() {
		?>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Activate live filter', 'statify-blacklist' ); ?></legend>
			<label for="statifyblacklist-target-active">
				<input id="statifyblacklist-target-active" name="statify-blacklist[target][active]" type="checkbox" value="1" <?php checked( StatifyBlacklist::$options['target']['active'], 1 ); ?>>
				<?php esc_html_e( 'Activate', 'statify-blacklist' ); ?>
			</label>
			<p class="description">
				<?php esc_html_e( 'Filter at time of tracking, before anything is stored', 'statify-blacklist' ); ?>
			</p>
		</fieldset>
		<?php
	}

	/**
	 * Option for activating cron the target filter.
	 *
	 * @return void
	 */
	public static function option_target_cron() {
		?>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'CronJob execution', 'statify-blacklist' ); ?></legend>
			<label for="statifyblacklist-target-cron">
				<input id="statifyblacklist-target-cron" name="statify-blacklist[target][cron]" type="checkbox" value="1" <?php checked( StatifyBlacklist::$options['target']['cron'], 1 ); ?>>
				<?php esc_html_e( 'Activate', 'statify-blacklist' ); ?>
			</label>
			<p class="description">
				<?php esc_html_e( 'Clean database periodically in background', 'statify-blacklist' ); ?>
			</p>
		</fieldset>
		<?php
	}

	/**
	 * Option for target matching method.
	 *
	 * @return void
	 */
	public static function option_target_regexp() {
		?>
		<select id="statifyblacklist-target-regexp" name="statify-blacklist[target][regexp]">
			<option value="<?php print esc_attr( StatifyBlacklist::MODE_NORMAL ); ?>" <?php selected( StatifyBlacklist::$options['target']['regexp'], StatifyBlacklist::MODE_NORMAL ); ?>>
				<?php esc_html_e( 'Exact', 'statify-blacklist' ); ?>
			</option>
			<option value="<?php print esc_attr( StatifyBlacklist::MODE_REGEX ); ?>" <?php selected( StatifyBlacklist::$options['target']['regexp'], StatifyBlacklist::MODE_REGEX ); ?>>
				<?php esc_html_e( 'RegEx case-sensitive', 'statify-blacklist' ); ?>
			</option>
			<option value="<?php print esc_attr( StatifyBlacklist::MODE_REGEX_CI ); ?>" <?php selected( StatifyBlacklist::$options['target']['regexp'], StatifyBlacklist::MODE_REGEX_CI ); ?>>
				<?php esc_html_e( 'RegEx case-insensitive', 'statify-blacklist' ); ?>
			</option>
		</select>
		<p class="description">
			<?php esc_html_e( 'Exact', 'statify-blacklist' ); ?> - <?php esc_html_e( 'Match only given targets', 'statify-blacklist' ); ?>
			<br>
			<?php esc_html_e( 'RegEx', 'statify-blacklist' ); ?> - <?php esc_html_e( 'Match target by regular expression', 'statify-blacklist' ); ?>
		</p>
		<?php
	}

	/**
	 * Option for the target filter list.
	 *
	 * @return void
	 */
	public static function option_target_blacklist() {
		?>
		<textarea id="statifyblacklist-target-blacklist" name="statify-blacklist[target][blacklist]" cols="40" rows="5"><?php
		print esc_html( implode( "\r\n", array_keys( StatifyBlacklist::$options['target']['blacklist'] ) ) );
		?></textarea>
		<p class="description">
			<?php esc_html_e( 'Add one target URL each line, e.g.', 'statify-blacklist' ); ?> /, /test/page/, /?page_id=123
		</p>
		<?php
	}

	/**
	 * Option for activating the live IP filter.
	 *
	 * @return void
	 */
	public static function option_ip_active() {
		?>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Activate live filter', 'statify-blacklist' ); ?></legend>
			<label for="statifyblacklist-ip-active">
				<input id="statifyblacklist-ip-active" name="statify-blacklist[ip][active]" type="checkbox" value="1" <?php checked( StatifyBlacklist::$options['ip']['active'], 1 ); ?>>
				<?php esc_html_e( 'Activate', 'statify-blacklist' ); ?>
			</label>
			<p class="description">
				<?php esc_html_e( 'Filter at time of tracking, before anything is stored', 'statify-blacklist' ); ?>
				<br>
				<?php esc_html_e( 'Cron execution is not possible for IP filter, because IP addresses are not stored.', 'statify-blacklist' ); ?>
			</p>
		</fieldset>
		<?php
	}

	/**
	 * Option for the IP filter list.
	 *
	 * @return void
	 */
	public static function option_ip_blacklist() {
		?>
		<textarea id="statifyblacklist-ip-blacklist" name="statify-blacklist[ip][blacklist]" cols="40" rows="5"><?php
		print esc_html( implode( "\r\n", StatifyBlacklist::$options['ip']['blacklist'] ) );
		?></textarea>
		<p class="description">
			<?php esc_html_e( 'Add one IP address or range per line, e.g.', 'statify-blacklist' ); ?>
			127.0.0.1, 192.168.123.0/24, 2001:db8:a0b:12f0::1/64
		</p>
		<?php
	}

	/**
	 * Option for activating the live user agent filter.
	 *
	 * @return void
	 */
	public static function option_ua_active() {
		?>
		<label for="statifyblacklist-ua-active">
			<input id="statifyblacklist-ua-active" name="statify-blacklist[ua][active]" type="checkbox" value="1" <?php checked( StatifyBlacklist::$options['ua']['active'], 1 ); ?>>
			<?php esc_html_e( 'Activate', 'statify-blacklist' ); ?>
		</label>

		<p class="description">
			<?php esc_html_e( 'Filter at time of tracking, before anything is stored', 'statify-blacklist' ); ?>
			<br>
			<?php esc_html_e( 'Cron execution is not possible for user agent filter, because the user agent is stored.', 'statify-blacklist' ); ?>
		</p>
		<?php
	}

	/**
	 * Option for user agent matching method.
	 *
	 * @return void
	 */
	public static function option_ua_regexp() {
		?>
		<select id="statifyblacklist-ua-regexp" name="statify-blacklist[ua][regexp]">
			<option value="<?php print esc_attr( StatifyBlacklist::MODE_NORMAL ); ?>" <?php selected( StatifyBlacklist::$options['ua']['regexp'], StatifyBlacklist::MODE_NORMAL ); ?>>
				<?php esc_html_e( 'Exact', 'statify-blacklist' ); ?>
			</option>
			<option value="<?php print esc_attr( StatifyBlacklist::MODE_KEYWORD ); ?>" <?php selected( StatifyBlacklist::$options['ua']['regexp'], StatifyBlacklist::MODE_KEYWORD ); ?>>
				<?php esc_html_e( 'Keyword', 'statify-blacklist' ); ?>
			</option>
			<option value="<?php print esc_attr( StatifyBlacklist::MODE_REGEX ); ?>" <?php selected( StatifyBlacklist::$options['ua']['regexp'], StatifyBlacklist::MODE_REGEX ); ?>>
				<?php esc_html_e( 'RegEx case-sensitive', 'statify-blacklist' ); ?>
			</option>
			<option value="<?php print esc_attr( StatifyBlacklist::MODE_REGEX_CI ); ?>" <?php selected( StatifyBlacklist::$options['ua']['regexp'], StatifyBlacklist::MODE_REGEX_CI ); ?>>
				<?php esc_html_e( 'RegEx case-insensitive', 'statify-blacklist' ); ?>
			</option>
		</select>
		<p class="description">
			<?php esc_html_e( 'Exact', 'statify-blacklist' ); ?> - <?php esc_html_e( 'Match only given user agents', 'statify-blacklist' ); ?>
			<br>
			<?php esc_html_e( 'Keyword', 'statify-blacklist' ); ?> - <?php esc_html_e( 'Match every referer that contains one of the keywords', 'statify-blacklist' ); ?>
			<br>
			<?php esc_html_e( 'RegEx', 'statify-blacklist' ); ?> - <?php esc_html_e( 'Match user agent by regular expression', 'statify-blacklist' ); ?>
		</p>
		<?php
	}

	/**
	 * Option for the user agent filter list.
	 *
	 * @return void
	 */
	public static function option_ua_blacklist() {
		?>
		<textarea name="statify-blacklist[ua][blacklist]" id="statifyblacklist-ua-blacklist" cols="40" rows="5"><?php
		print esc_html( implode( "\r\n", StatifyBlacklist::$options['ua']['blacklist'] ) );
		?></textarea>
		<p class="description">
			<?php esc_html_e( 'Add one user agent string per line, e.g.', 'statify-blacklist' ); ?>
			MyBot/1.23
		</p>
		<?php
	}

	/**
	 * Validate and sanitize submitted options.
	 *
	 * @param array $options Original options.
	 *
	 * @return array Validated and sanitized options.
	 */
	public static function sanitize_options( $options ) {
		// Extract filter lists from multi-line inputs.
		$referer = self::parse_multiline_option( $options['referer']['blacklist'] );
		$target  = self::parse_multiline_option( $options['target']['blacklist'] );
		$ip      = self::parse_multiline_option( $options['ip']['blacklist'] );
		$ua      = self::parse_multiline_option( $options['ua']['blacklist'] );

		// Generate options.
		$res = array(
			'referer' => array(
				'active'    => isset( $options['referer']['active'] ) ? (int) $options['referer']['active'] : 0,
				'cron'      => isset( $options['referer']['cron'] ) ? (int) $options['referer']['cron'] : 0,
				'regexp'    => isset( $options['referer']['regexp'] ) ? (int) $options['referer']['regexp'] : 0,
				'blacklist' => array_flip( $referer ),
			),
			'target'  => array(
				'active'    => isset( $options['target']['active'] ) ? (int) $options['target']['active'] : 0,
				'cron'      => isset( $options['target']['cron'] ) ? (int) $options['target']['cron'] : 0,
				'regexp'    => isset( $options['target']['regexp'] ) ? (int) $options['target']['regexp'] : 0,
				'blacklist' => array_flip( $target ),
			),
			'ip'      => array(
				'active'    => isset( $options['ip']['active'] ) ? (int) $options['ip']['active'] : 0,
				'blacklist' => $ip,
			),
			'ua'      => array(
				'active'    => isset( $options['ua']['active'] ) ? (int) $options['ua']['active'] : 0,
				'regexp'    => isset( $options['ua']['regexp'] ) ? (int) $options['ua']['regexp'] : 0,
				'blacklist' => array_flip( $ua ),
			),
			'version' => StatifyBlacklist::VERSION_MAIN,
		);

		// Apply sanitizations.
		self::sanitize_referer_options( $res['referer'] );
		self::sanitize_target_options( $res['target'] );
		self::sanitize_ip_options( $res['ip'] );

		return $res;
	}

	/**
	 * Sanitize referer options.
	 *
	 * @param array $options Original referer options.
	 *
	 * @return void
	 *
	 * @since 1.7.0
	 */
	private static function sanitize_referer_options( &$options ) {
		$referer_given   = $options['blacklist'];
		$referer_invalid = array();
		if ( StatifyBlacklist::MODE_NORMAL === $options['regexp'] ) {
			// Sanitize URLs and remove empty inputs.
			$referer_sanitized = self::sanitize_urls( $referer_given );
		} elseif ( StatifyBlacklist::MODE_REGEX === $options['regexp'] || StatifyBlacklist::MODE_REGEX_CI === $options['regexp'] ) {
			$referer_sanitized = $referer_given;
			// Check regular expressions.
			$referer_invalid = self::sanitize_regex( $referer_given );
		} else {
			$referer_sanitized = $referer_given;
		}
		$referer_diff         = array_diff_key( $referer_given, $referer_sanitized );
		$options['blacklist'] = $referer_sanitized;

		// Generate messages.
		if ( ! empty( $referer_diff ) ) {
			add_settings_error(
				'statify-blacklist',
				'referer-diff',
				__( 'Some URLs are invalid and have been sanitized.', 'statify-blacklist' ),
				'warning'
			);
		}
		if ( ! empty( $referer_invalid ) ) {
			add_settings_error(
				'statify-blacklist',
				'referer-invalid',
				__( 'Some regular expressions for referrers are invalid:', 'statify-blacklist' ) . '<br>' . implode( '<br>', $referer_invalid )
			);
		}
	}

	/**
	 * Sanitize target options.
	 *
	 * @param array $options Original target options.
	 *
	 * @return void
	 *
	 * @since 1.7.0
	 */
	private static function sanitize_target_options( &$options ) {
		$target_given   = $options['blacklist'];
		$target_invalid = array();
		if ( StatifyBlacklist::MODE_REGEX === $options['regexp'] || StatifyBlacklist::MODE_REGEX_CI === $options['regexp'] ) {
			$target_sanitized = $target_given;
			// Check regular expressions.
			$target_invalid = self::sanitize_regex( $target_given );
		} else {
			$target_sanitized = $target_given;
		}
		$options['blacklist'] = $target_sanitized;

		// Generate messages.
		if ( ! empty( $target_invalid ) ) {
			add_settings_error(
				'statify-blacklist',
				'target-invalid',
				__( 'Some regular expressions for targets are invalid:', 'statify-blacklist' ) . '<br>' . implode( '<br>', $target_invalid )
			);
		}
	}

	/**
	 * Sanitize IPs and subnets and remove empty inputs.
	 *
	 * @param array $options Original IP options.
	 *
	 * @return void
	 *
	 * @since 1.7.0
	 */
	private static function sanitize_ip_options( &$options ) {
		$given_ip             = $options['blacklist'];
		$sanitized_ip         = self::sanitize_ips( $given_ip );
		$ip_diff              = array_diff( $given_ip, $sanitized_ip );
		$options['blacklist'] = $sanitized_ip;

		// Generate messages.
		if ( ! empty( $ip_diff ) ) {
			add_settings_error(
				'statify-blacklist',
				'ip-diff',
				// translators: List of invalid IP addresses (comma separated).
				sprintf( __( 'Some IPs are invalid: %s', 'statify-blacklist' ), implode( ', ', $ip_diff ) ),
				'warning'
			);
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
	 * @since 1.7.0 moved from StatifyBlacklist_Admin to StatifyBlacklist_Settings.
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
	 * @param array $ips given array of URLs.
	 *
	 * @return array  sanitized array.
	 *
	 * @since 1.4.0
	 * @since 1.7.0 moved from StatifyBlacklist_Admin to StatifyBlacklist_Settings.
	 */
	private static function sanitize_ips( $ips ) {
		return array_values(
			array_unique(
				array_filter(
					array_map( 'strtolower', $ips ),
					function ( $ip ) {
						return preg_match(
							'/^((25[0-5]|(2[0-4]|1?[0-9])?[0-9])\.){3}(25[0-5]|(2[0-4]|1?[0-9])?[0-9])(\/([0-9]|[1-2][0-9]|3[0-2]))?$/',
							$ip
						) ||
						preg_match(
							'/^(([0-9a-f]{1,4}:){7}[0-9a-f]{1,4}|([0-9a-f]{1,4}:){1,7}:|([0-9a-f]{1,4}:){1,6}:[0-9a-f]{1,4}' .
							'|([0-9a-f]{1,4}:){1,5}(:[0-9a-f]{1,4}){1,2}|([0-9a-f]{1,4}:){1,4}(:[0-9a-f]{1,4}){1,3}' .
							'|([0-9a-f]{1,4}:){1,3}(:[0-9a-f]{1,4}){1,4}|([0-9a-f]{1,4}:){1,2}(:[0-9a-f]{1,4}){1,5}' .
							'|[0-9a-f]{1,4}:((:[0-9a-f]{1,4}){1,6})|:((:[0-9a-f]{1,4}){1,7}|:)' .
							'|fe80:(:[0-9a-f]{0,4}){0,4}%[0-9a-zA-Z]+|::(ffff(:0{1,4})?:)?((25[0-5]|(2[0-4]|1?[0-9])?[0-9])\.){3}(25[0-5]|(2[0-4]' .
							'|1?[0-9])?[0-9])|([0-9a-f]{1,4}:){1,4}:((25[0-5]|(2[0-4]|1?[0-9])?[0-9])\.){3}(25[0-5]|(2[0-4]|1?[0-9])?[0-9]))' .
							'(\/([0-9]|[1-9][0-9]|1[0-1][0-9]|12[0-8]))?$/',
							$ip
						);
					}
				)
			)
		);
	}

	/**
	 * Validate regular expressions, i.e. remove duplicates and empty values and validate others.
	 *
	 * @param array $expressions Given pre-sanitized array of regular expressions.
	 *
	 * @return array Array of invalid expressions.
	 *
	 * @since 1.5.0 #13
	 * @since 1.7.0 moved from StatifyBlacklist_Admin to StatifyBlacklist_Settings.
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

	/**
	 * Parse multi-line option string.
	 *
	 * @param string $raw Input string.
	 *
	 * @return array Parsed options.
	 */
	private static function parse_multiline_option( $raw ) {
		if ( empty( trim( $raw ) ) ) {
			return array();
		} else {
			return array_filter(
				array_map(
					function ( $a ) {
						return trim( $a );
					},
					explode( "\r\n", str_replace( '\\\\', '\\', $raw ) )
				),
				function ( $a ) {
					return ! empty( $a );
				}
			);
		}
	}
}
