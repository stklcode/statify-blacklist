<?php
/**
 * Statify Blacklist: Settings View
 *
 * This file contains the dynamic HTML skeleton for the plugin's settings page.
 *
 * @package    Statify_Blacklist
 * @subpackage Admin
 * @since      1.0.0
 */

// phpcs:disable WordPress.WhiteSpace.PrecisionAlignment.Found

// Quit.
defined( 'ABSPATH' ) || exit;

// Update plugin options.
if ( ! empty( $_POST['statifyblacklist'] ) ) {
	// Verify nonce.
	check_admin_referer( 'statify-blacklist-settings' );

	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		die( esc_html__( 'Are you sure you want to do this?', 'statify-blacklist' ) );
	}

	if ( ! empty( $_POST['cleanUp'] ) ) {
		// CleanUp DB.
		StatifyBlacklist_Admin::cleanup_database();
	} else {
		// Extract referer array.
		if ( isset( $_POST['statifyblacklist']['referer']['blacklist'] ) ) {
			$referer_str = sanitize_textarea_field( wp_unslash( $_POST['statifyblacklist']['referer']['blacklist'] ) );
		}
		if ( empty( trim( $referer_str ) ) ) {
			$referer = array();
		} else {
			$referer = array_filter(
				array_map(
					function ( $a ) {
						return trim( $a );
					},
					explode( "\r\n", $referer_str )
				),
				function ( $a ) {
					return ! empty( $a );
				}
			);
		}

		// Extract target array.
		if ( isset( $_POST['statifyblacklist']['target']['blacklist'] ) ) {
			$target_str = sanitize_textarea_field( wp_unslash( $_POST['statifyblacklist']['target']['blacklist'] ) );
		}
		if ( empty( trim( $target_str ) ) ) {
			$target = array();
		} else {
			$target = array_filter(
				array_map(
					function ( $a ) {
						return trim( $a );
					},
					explode( "\r\n", str_replace( '\\\\', '\\', $target_str ) )
				),
				function ( $a ) {
					return ! empty( $a );
				}
			);
		}

		// Extract IP array.
		if ( isset( $_POST['statifyblacklist']['ip']['blacklist'] ) ) {
			$ip_str = sanitize_textarea_field( wp_unslash( $_POST['statifyblacklist']['ip']['blacklist'] ) );
		}
		if ( empty( trim( $ip_str ) ) ) {
			$ip = array();
		} else {
			$ip = array_filter(
				array_map(
					function ( $a ) {
						return trim( $a );
					},
					explode( "\r\n", $ip_str )
				),
				function ( $a ) {
					return ! empty( $a );
				}
			);
		}

		// Update options (data will be sanitized).
		$statifyblacklist_update_result = StatifyBlacklist_Admin::update_options(
			array(
				'referer' => array(
					'active'    => isset( $_POST['statifyblacklist']['referer']['active'] )
						? (int) $_POST['statifyblacklist']['referer']['active'] : 0,
					'cron'      => isset( $_POST['statifyblacklist']['referer']['cron'] )
						? (int) $_POST['statifyblacklist']['referer']['cron'] : 0,
					'regexp'    => isset( $_POST['statifyblacklist']['referer']['regexp'] )
						? (int) $_POST['statifyblacklist']['referer']['regexp'] : 0,
					'blacklist' => array_flip( $referer ),
				),
				'target'  => array(
					'active'    => isset( $_POST['statifyblacklist']['target']['active'] )
						? (int) $_POST['statifyblacklist']['target']['active'] : 0,
					'cron'      => isset( $_POST['statifyblacklist']['target']['cron'] )
						? (int) $_POST['statifyblacklist']['target']['cron'] : 0,
					'regexp'    => isset( $_POST['statifyblacklist']['target']['regexp'] )
						? (int) $_POST['statifyblacklist']['target']['regexp'] : 0,
					'blacklist' => array_flip( $target ),
				),
				'ip'      => array(
					'active'    => isset( $_POST['statifyblacklist']['ip']['active'] )
						? (int) $_POST['statifyblacklist']['ip']['active'] : 0,
					'blacklist' => $ip,
				),
				'version' => StatifyBlacklist::VERSION_MAIN,
			)
		);

		// Generate messages.
		if ( false !== $statifyblacklist_update_result ) {
			$statifyblacklist_post_warning = array();
			if ( ! empty( $statifyblacklist_update_result['referer']['diff'] ) ) {
				$statifyblacklist_post_warning[] = __( 'Some URLs are invalid and have been sanitized.', 'statify-blacklist' );
			}
			if ( ! empty( $statifyblacklist_update_result['referer']['invalid'] ) ) {
				$statifyblacklist_post_warning[] = __( 'Some regular expressions are invalid:', 'statify-blacklist' ) . '<br>' . implode( '<br>', $statifyblacklist_update_result['referer']['invalid'] );
			}
			if ( ! empty( $statifyblacklist_update_result['ip']['diff'] ) ) {
				// translators: List of invalid IP addresses (comma separated).
				$statifyblacklist_post_warning[] = sprintf( __( 'Some IPs are invalid: %s', 'statify-blacklist' ), implode( ', ', $statifyblacklist_update_result['ip']['diff'] ) );
			}
		} else {
			$statifyblacklist_post_success = __( 'Settings updated successfully.', 'statify-blacklist' );
		}
	}
}

/*
 * Disable some code style rules that are impractical for textarea content:
 *
 * phpcs:disable Squiz.PHP.EmbeddedPhp.ContentBeforeOpen
 * phpcs:disable Squiz.PHP.EmbeddedPhp.ContentAfterEnd
 */
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Statify Blacklist', 'statify-blacklist' ); ?></h1>
	<?php
	if ( is_plugin_inactive( 'statify/statify.php' ) ) {
		print '<div class="notice notice-warning"><p>';
		esc_html_e( 'Statify plugin is not active.', 'statify-blacklist' );
		print '</p></div>';
	}
	if ( isset( $statifyblacklist_post_warning ) ) {
		foreach ( $statifyblacklist_post_warning as $w ) {
			print '<div class="notice notice-warning"><p>' .
				wp_kses( $w, array( 'br' => array() ) ) .
				'</p></div>';
		}
		print '<div class="notice notice-warning"><p>' . esc_html__( 'Settings have not been saved yet.', 'statify-blacklist' ) . '</p></div>';
	}
	if ( isset( $statifyblacklist_post_success ) ) {
		print '<div class="notice notice-success"><p>' .
			esc_html( $statifyblacklist_post_success ) .
			'</p></div>';
	}
	?>
	<form action="" method="post" id="statify-blacklist-settings">
		<?php wp_nonce_field( 'statify-blacklist-settings' ); ?>

		<h2><?php esc_html_e( 'Referer blacklist', 'statify-blacklist' ); ?></h2>

		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row">
					<label for="statify-blacklist_active_referer">
						<?php esc_html_e( 'Activate live filter', 'statify-blacklist' ); ?>
					</label>
				</th>
				<td>
					<input type="checkbox" name="statifyblacklist[referer][active]"
						   id="statify-blacklist_active_referer"
						   value="1" <?php checked( StatifyBlacklist::$options['referer']['active'], 1 ); ?>>
					<p class="description">
						<?php esc_html_e( 'Filter at time of tracking, before anything is stored', 'statify-blacklist' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="statify-blacklist_cron_referer">
						<?php esc_html_e( 'CronJob execution', 'statify-blacklist' ); ?>
					</label>
				</th>
				<td>
					<input type="checkbox" name="statifyblacklist[referer][cron]" id="statify-blacklist_cron_referer"
						   value="1" <?php checked( StatifyBlacklist::$options['referer']['cron'], 1 ); ?>>
					<p class="description"><?php esc_html_e( 'Periodically clean up database in background', 'statify-blacklist' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="statify-blacklist_referer_regexp"><?php esc_html_e( 'Matching method', 'statify-blacklist' ); ?></label>
				</th>
				<td>
					<select name="statifyblacklist[referer][regexp]" id="statify-blacklist_referer_regexp">
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
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="statify-blacklist_referer"><?php esc_html_e( 'Referer blacklist', 'statify-blacklist' ); ?></label>
				</th>
				<td>
					<textarea cols="40" rows="5" name="statifyblacklist[referer][blacklist]" id="statify-blacklist_referer"><?php
					if ( empty( $statifyblacklist_update_result['referer'] ) ) {
						print esc_html( implode( "\r\n", array_keys( StatifyBlacklist::$options['referer']['blacklist'] ) ) );
					} else {
						print esc_html( implode( "\r\n", array_keys( $statifyblacklist_update_result['referer']['sanitized'] ) ) );
					}
					?></textarea>
					<p class="description">
						<?php esc_html_e( 'Add one domain (without subdomains) each line, e.g. example.com', 'statify-blacklist' ); ?>
					</p>
				</td>
			</tr>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'Target blacklist', 'statify-blacklist' ); ?></h2>

		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row">
					<label for="statify-blacklist_active_target">
						<?php esc_html_e( 'Activate live filter', 'statify-blacklist' ); ?>
					</label>
				</th>
				<td>
					<input type="checkbox" name="statifyblacklist[target][active]"
						   id="statify-blacklist_active_target"
						   value="1" <?php checked( StatifyBlacklist::$options['target']['active'], 1 ); ?>>
					<p class="description">
						<?php esc_html_e( 'Filter at time of tracking, before anything is stored', 'statify-blacklist' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="statify-blacklist_cron_target">
						<?php esc_html_e( 'CronJob execution', 'statify-blacklist' ); ?>
					</label>
				</th>
				<td>
					<input type="checkbox" name="statifyblacklist[target][cron]" id="statify-blacklist_cron_target"
						   value="1" <?php checked( StatifyBlacklist::$options['target']['cron'], 1 ); ?>>
					<p class="description">
						<?php esc_html_e( 'Clean database periodically in background', 'statify-blacklist' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="statify-blacklist_target_regexp">
						<?php esc_html_e( 'Matching method', 'statify-blacklist' ); ?>
					</label>
				</th>
				<td>
					<select name="statifyblacklist[target][regexp]" id="statify-blacklist_referer_regexp">
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
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="statify-blacklist_target">
						<?php esc_html_e( 'Target blacklist', 'statify-blacklist' ); ?>
					</label>
				</th>
				<td>
					<textarea cols="40" rows="5" name="statifyblacklist[target][blacklist]" id="statify-blacklist_target"><?php
					if ( empty( $statifyblacklist_update_result['target'] ) ) {
						print esc_html( implode( "\r\n", array_keys( StatifyBlacklist::$options['target']['blacklist'] ) ) );
					} else {
						print esc_html( implode( "\r\n", array_keys( $statifyblacklist_update_result['target']['sanitized'] ) ) );
					}
					?></textarea>

					<p class="description">
						(<?php esc_html_e( 'Add one target URL each line, e.g.', 'statify-blacklist' ); ?> /, /test/page/, /?page_id=123)
					</p>
				</td>
			</tr>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'IP blacklist', 'statify-blacklist' ); ?></h2>

		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row">
					<label for="statify-blacklist_active_ip">
						<?php esc_html_e( 'Activate live filter', 'statify-blacklist' ); ?>
					</label>
				</th>
				<td>
					<input type="checkbox" name="statifyblacklist[ip][active]" id="statify-blacklist_active_ip"
						   value="1" <?php checked( StatifyBlacklist::$options['ip']['active'], 1 ); ?>>
					<p class="description">
						<?php esc_html_e( 'Filter at time of tracking, before anything is stored', 'statify-blacklist' ); ?>
						<br>
						<?php esc_html_e( 'Cron execution is not possible for IP filter, because IP addresses are not stored.', 'statify-blacklist' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="statify-blacklist_ip"><?php esc_html_e( 'IP blacklist', 'statify-blacklist' ); ?></label>:
				</th>
				<td>
					<textarea cols="40" rows="5" name="statifyblacklist[ip][blacklist]" id="statify-blacklist_ip"><?php
					if ( empty( $statifyblacklist_update_result['ip'] ) ) {
						print esc_html( implode( "\r\n", StatifyBlacklist::$options['ip']['blacklist'] ) );
					} else {
						print esc_html( implode( "\r\n", $statifyblacklist_update_result['ip']['sanitized'] ) );
					}
					?></textarea>

					<p class="description">
						<?php esc_html_e( 'Add one IP address or range per line, e.g.', 'statify-blacklist' ); ?>
						127.0.0.1, 192.168.123.0/24, 2001:db8:a0b:12f0::1/64
					</p>
				</td>
			</tr>
			</tbody>
		</table>

		<p class="submit">
			<input class="button-primary" type="submit" name="submit" value="<?php esc_html_e( 'Save Changes', 'statify-blacklist' ); ?>">
			<hr>
			<input class="button-secondary" type="submit" name="cleanUp"
				   value="<?php esc_html_e( 'CleanUp Database', 'statify-blacklist' ); ?>"
				   onclick="return confirm('Do you really want to apply filters to database? This cannot be undone.');">
			<br>
			<p class="description">
				<?php esc_html_e( 'Applies referer and target filter (even if disabled) to data stored in database.', 'statify-blacklist' ); ?>
				<em><?php esc_html_e( 'This cannot be undone!', 'statify-blacklist' ); ?></em>
			</p>
		</p>
	</form>
</div>
