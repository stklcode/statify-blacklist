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

// Quit.
defined( 'ABSPATH' ) || exit;

// Update plugin options.
if ( ! empty( $_POST['statifyblacklist'] ) ) {
	// Verify nonce.
	check_admin_referer( 'statify-blacklist-settings' );

	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		die( __( 'Are you sure you want to do this?' ) );
	}

	if ( ! empty( $_POST['cleanUp'] ) ) {
		// CleanUp DB.
		StatifyBlacklist_Admin::cleanup_database();
	} else {
		// Extract referer array.
		if ( empty( trim( $_POST['statifyblacklist']['referer']['blacklist'] ) ) ) {
			$referer = array();
		} else {
			$referer = explode( "\r\n", $_POST['statifyblacklist']['referer']['blacklist'] );
		}

		// Extract target array.
		if ( empty( trim( $_POST['statifyblacklist']['target']['blacklist'] ) ) ) {
			$target = array();
		} else {
			$target = explode( "\r\n", str_replace( '\\\\', '\\', $_POST['statifyblacklist']['target']['blacklist'] ) );
		}

		// Extract IP array.
		if ( empty( trim( $_POST['statifyblacklist']['ip']['blacklist'] ) ) ) {
			$ip = array();
		} else {
			$ip = explode( "\r\n", $_POST['statifyblacklist']['ip']['blacklist'] );
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
			if ( array_key_exists( 'referer', $statifyblacklist_update_result ) ) {
				$statifyblacklist_post_warning = __( 'Some URLs are invalid and have been sanitized.', 'statify-blacklist' );
			} elseif ( array_key_exists( 'ip', $statifyblacklist_update_result ) ) {
				// translators: List of invalid IP addresses (comma separated).
				$statifyblacklist_post_warning = sprintf( __( 'Some IPs are invalid : %s', 'statify-blacklist' ), implode( ', ', $statifyblacklist_update_result['ip'] ) );
			}
		} else {
			$statifyblacklist_post_success = __( 'Settings updated successfully.', 'statify-blacklist' );
		}
	} // End if().
} // End if().
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Statify Blacklist', 'statify-blacklist' ) ?></h1>
	<?php
	if ( is_plugin_inactive( 'statify/statify.php' ) ) {
		print '<div class="notice notice-warning"><p>';
		esc_html_e( 'Statify plugin is not active.', 'statify-blacklist' );
		print '</p></div>';
	}
	if ( isset( $statifyblacklist_post_warning ) ) {
		print '<div class="notice notice-warning"><p>' .
			esc_html( $statifyblacklist_post_warning );
		print '<br/>';
		esc_html_e( 'Settings have not been saved yet.', 'statify-blacklist' );
		print '</p></div>';
	}
	if ( isset( $statifyblacklist_post_success ) ) {
		print '<div class="notice notice-success"><p>' .
			esc_html( $statifyblacklist_post_success ) .
			'</p></div>';
	}
	?>
	<form action="" method="post" id="statify-blacklist-settings">
		<fieldset>
			<h2><?php esc_html_e( 'Referer blacklist', 'statify-blacklist' ); ?></h2>
			<ul style="list-style: none;">
				<li>
					<label for="statify-blacklist_active_referer">
						<input type="checkbox" name="statifyblacklist[referer][active]"
							   id="statifyblacklist_active_referer"
							   value="1" <?php checked( StatifyBlacklist::$_options['referer']['active'], 1 ); ?> />
						<?php esc_html_e( 'Activate live fiter', 'statify-blacklist' ); ?>
					</label>
				</li>
				<li>
					<label for="statify-blacklist_cron_referer">
						<input type="checkbox" name="statifyblacklist[referer][cron]" id="statifyblacklist_cron_referer"
							   value="1" <?php checked( StatifyBlacklist::$_options['referer']['cron'], 1 ); ?> />
						<?php esc_html_e( 'CronJob execution', 'statify-blacklist' ); ?>
						<small>(<?php esc_html_e( 'Clean database periodically in background', 'statify-blacklist' ); ?>
							)
						</small>
					</label>
				</li>
				<li>
					<label for="statify-blacklist_referer_regexp">
						<?php esc_html_e( 'Use regular expressions', 'statify-blacklist' ); ?>:
						<br />
						<select name="statifyblacklist[referer][regexp]" id="statifyblacklist_referer_regexp">
							<option value="0" <?php selected( StatifyBlacklist::$_options['referer']['regexp'], 0 ); ?>>
								<?php esc_html_e( 'Disabled', 'statify-blacklist' ); ?>
							</option>
							<option value="1" <?php selected( StatifyBlacklist::$_options['referer']['regexp'], 1 ); ?>>
								<?php esc_html_e( 'Case-sensitive', 'statify-blacklist' ); ?>
							</option>
							<option value="2" <?php selected( StatifyBlacklist::$_options['referer']['regexp'], 2 ); ?>>
								<?php esc_html_e( 'Case-insensitive', 'statify-blacklist' ); ?>
							</option>
						</select>
						<small>
							(<?php esc_html_e( 'Performance slower than standard filter. Recommended for cron or manual execition only.', 'statify-blacklist' ); ?>
							)
						</small>
					</label>
				</li>
				<li>
					<label for="statify-blacklist_referer">
						<?php esc_html_e( 'Referer blacklist', 'statify-blacklist' ); ?>:<br />
						<textarea cols="40" rows="5" name="statifyblacklist[referer][blacklist]" id="statify-blacklist_referer"><?php
						if ( isset( $statifyblacklist_update_result['referer'] ) ) {
							print esc_html( implode( "\r\n", array_keys( $statifyblacklist_update_result['referer'] ) ) );
						} else {
							print esc_html( implode( "\r\n", array_keys( StatifyBlacklist::$_options['referer']['blacklist'] ) ) );
						}
							?></textarea>
						<br />
						<small>
							(<?php esc_html_e( 'Add one domain (without subdomains) each line, e.g. example.com', 'statify-blacklist' ); ?>
							)
						</small>
					</label>
				</li>
			</ul>
		</fieldset>

		<fieldset>
			<h2><?php esc_html_e( 'Target blacklist', 'statify-blacklist' ); ?></h2>
			<ul style="list-style: none;">
				<li>
					<label for="statify-blacklist_active_target">
						<input type="checkbox" name="statifyblacklist[target][active]"
							   id="statifyblacklist_active_target"
							   value="1" <?php checked( StatifyBlacklist::$_options['target']['active'], 1 ); ?> />
						<?php esc_html_e( 'Activate live fiter', 'statify-blacklist' ); ?>
					</label>
				</li>
				<li>
					<label for="statify-blacklist_cron_target">
						<input type="checkbox" name="statifyblacklist[target][cron]" id="statifyblacklist_cron_target"
							   value="1" <?php checked( StatifyBlacklist::$_options['target']['cron'], 1 ); ?> />
						<?php esc_html_e( 'CronJob execution', 'statify-blacklist' ); ?>
						<small>(<?php esc_html_e( 'Clean database periodically in background', 'statify-blacklist' ); ?>
							)
						</small>
					</label>
				</li>
				<li>
					<label for="statify-blacklist_target_regexp">
						<?php esc_html_e( 'Use regular expressions', 'statify-blacklist' ); ?>:
						<br />
						<select name="statifyblacklist[target][regexp]" id="statifyblacklist_target_regexp">
							<option value="0" <?php selected( StatifyBlacklist::$_options['target']['regexp'], 0 ); ?>>
								<?php esc_html_e( 'Disabled', 'statify-blacklist' ); ?>
							</option>
							<option value="1" <?php selected( StatifyBlacklist::$_options['target']['regexp'], 1 ); ?>>
								<?php esc_html_e( 'Case-sensitive', 'statify-blacklist' ); ?>
							</option>
							<option value="2" <?php selected( StatifyBlacklist::$_options['target']['regexp'], 2 ); ?>>
								<?php esc_html_e( 'Case-insensitive', 'statify-blacklist' ); ?>
							</option>
						</select>
						<small>
							(<?php esc_html_e( 'Performance slower than standard filter. Recommended for cron or manual execition only.', 'statify-blacklist' ); ?>
							)
						</small>
					</label>
				</li>
				<li>
					<label for="statify-blacklist_target">
						<?php esc_html_e( 'Target blacklist', 'statify-blacklist' ); ?>:<br />
						<textarea cols="40" rows="5" name="statifyblacklist[target][blacklist]" id="statify-blacklist_target"><?php
						if ( isset( $statifyblacklist_update_result['target'] ) ) {
							print esc_html( implode( "\r\n", array_keys( $statifyblacklist_update_result['target'] ) ) );
						} else {
							print esc_html( implode( "\r\n", array_keys( StatifyBlacklist::$_options['target']['blacklist'] ) ) );
						}
							?></textarea>
						<br />
						<small>
							(<?php esc_html_e( 'Add one target URL each line, e.g.', 'statify-blacklist' );
							print ' /, /test/page/, /?page_id=123' ?>
							)
						</small>
					</label>
				</li>
			</ul>
		</fieldset>

		<fieldset>
			<h2><?php esc_html_e( 'IP blacklist', 'statify-blacklist' ); ?></h2>
			<ul style="list-style: none;">
				<li>
					<label for="statify-blacklist_active_ip">
						<input type="checkbox" name="statifyblacklist[ip][active]" id="statifyblacklist_active_ip"
							   value="1" <?php checked( StatifyBlacklist::$_options['ip']['active'], 1 ); ?> />
						<?php esc_html_e( 'Activate live fiter', 'statify-blacklist' ); ?>
					</label>
				</li>
				<li>
					<small>
						(<?php esc_html_e( 'Cron execution is not possible for IP filter, because IP addresses are not stored.', 'statify-blacklist' ); ?>
						)
					</small>
				</li>
				<li>
					<label for="statify-blacklist_ip">
						<?php esc_html_e( 'IP blacklist', 'statify-blacklist' ); ?>:<br />
						<textarea cols="40" rows="5" name="statifyblacklist[ip][blacklist]" id="statify-blacklist_ip"><?php
						if ( isset( $statifyblacklist_update_result['ip'] ) ) {
							print esc_html( $_POST['statifyblacklist']['ip']['blacklist'] );
						} else {
							print esc_html( implode( "\r\n", StatifyBlacklist::$_options['ip']['blacklist'] ) );
						}
							?></textarea>
						<br />
						<small>
							(<?php esc_html_e( 'Add one IP address or range per line, e.g.', 'statify-blacklist' ) ?>
							127.0.0.1, 192.168.123.0/24, 2001:db8:a0b:12f0::1/64
							)
						</small>
					</label>
				</li>
			</ul>
		</fieldset>

		<?php wp_nonce_field( 'statify-blacklist-settings' ); ?>

		<p class="submit">
			<input class="button-primary" type="submit" name="submit" value="<?php esc_html_e( 'Save Changes' ) ?>">
		<hr />
		<input class="button-secondary" type="submit" name="cleanUp"
			   value="<?php esc_html_e( 'CleanUp Database', 'statify-blacklist' ) ?>"
			   onclick="return confirm('Do you really want to apply filters to database? This cannot be undone.');">
		<br />
		<small><?php esc_html_e( 'Applies referer and target filter (even if disabled) to data stored in database.', 'statify-blacklist' ); ?>
			<em><?php esc_html_e( 'This cannot be undone!', 'statify-blacklist' ); ?></em></small>
		</p>
	</form>
</div>
