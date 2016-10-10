<?php

/* Quit */
defined( 'ABSPATH' ) OR exit;

/* Update plugin options */
if ( ! empty( $_POST['statifyblacklist'] ) ) {
	/* Verify nonce */
	check_admin_referer( 'statify-blacklist-settings' );

	/* Check user capabilities */
	if ( ! current_user_can( 'manage_options' ) ) {
		die( _e( 'Are you sure you want to do this?' ) );
	}

	if ( ! empty( $_POST['cleanUp'] ) ) {
		/* CleanUp DB */
		StatifyBlacklist_Admin::cleanup_database();
	} else {
		/* Extract referer array */
		if ( empty( trim( $_POST['statifyblacklist']['referer'] ) ) ) {
			$referer = array();
		} else {
			$referer = explode( "\r\n", $_POST['statifyblacklist']['referer'] );
		}

		/* Update options (data will be sanitized) */
		$statifyBlacklistUpdateResult = StatifyBlacklist_Admin::update_options(
			array(
				'active_referer' => (int) @$_POST['statifyblacklist']['active_referer'],
				'cron_referer'   => (int) @$_POST['statifyblacklist']['cron_referer'],
				'referer'        => array_flip( $referer ),
				'referer_regexp' => (int) @$_POST['statifyblacklist']['referer_regexp']
			)
		);

		/* Generate messages */
		if ( $statifyBlacklistUpdateResult !== false ) {
			$statifyBlacklistPostWarning = 'Some URLs are invalid and have been sanitized. Settings have not been saved yet.';
		} else {
			$statifyBlacklistPostSuccess = 'Settings updated successfully.';
		}
	}
}
?>

<div class="wrap">
	<h1><?php _e( 'Statify Blacklist', 'statify-blacklist' ) ?></h1>
	<?php
	if ( is_plugin_inactive( 'statify/statify.php' ) ) {
		print '<div class="notice notice-warning"><p>';
		esc_html_e( 'Statify plugin is not active.', 'statify-blacklist' );
		print '</p></div>';
	}
	if ( isset( $statifyBlacklistPostWarning ) ) {
		print '<div class="notice notice-warning"><p>';
		esc_html_e( $statifyBlacklistPostWarning );
		print '</p></div>';
	}
	if ( isset( $statifyBlacklistPostSuccess ) ) {
		print '<div class="notice notice-success"><p>';
		esc_html_e( $statifyBlacklistPostSuccess );
		print '</p></div>';
	}
	?>
	<form action="" method="post" id="statify-blacklist-settings">
		<ul style="list-style: none;">
			<li>
				<label for="statify-blacklist_active_referer">
					<input type="checkbox" name="statifyblacklist[active_referer]" id="statifyblacklist_active_referer"
					       value="1" <?php checked( StatifyBlacklist::$_options['active_referer'], 1 ); ?> />
					<?php esc_html_e( 'Activate referer blacklist', 'statify-blacklist' ); ?>
				</label>
			</li>
			<li>
				<label for="statify-blacklist_cron_referer">
					<input type="checkbox" name="statifyblacklist[cron_referer]" id="statifyblacklist_cron_referer"
					       value="1" <?php checked( StatifyBlacklist::$_options['cron_referer'], 1 ); ?> />
					<?php esc_html_e( 'CronJob execution', 'statify-blacklist' ); ?>
					<small>(<?php esc_html_e( 'Clean database periodically in background', 'statify-blacklist' ); ?>)</small>
				</label>
			</li>
			<li>
				<label for="statify-blacklist_referer_regexp">
					<?php esc_html_e( 'Use regular expressions', 'statify-blacklist' ); ?>:
					<br />
					<select name="statifyblacklist[referer_regexp]" id="statifyblacklist_referer_regexp">
						<option value="0" <?php selected( StatifyBlacklist::$_options['referer_regexp'], 0 ); ?>>
							<?php esc_html_e( 'Disabled', 'statify-blacklist' ); ?>
						</option>
						<option value="1" <?php selected( StatifyBlacklist::$_options['referer_regexp'], 1 ); ?>>
							<?php esc_html_e( 'Case-sensitive', 'statify-blacklist' ); ?>
						</option>
						<option value="2" <?php selected( StatifyBlacklist::$_options['referer_regexp'], 2 ); ?>>
							<?php esc_html_e( 'Case-insensitive', 'statify-blacklist' ); ?>
						</option>
					</select>
					<small>(<?php esc_html_e( 'Performance slower than standard domain filter. Recommended for cron or manual execition only.', 'statify-blacklist' ); ?>)</small>
				</label>
			</li>
			<li>
				<label for="statify-blacklist_referer">
					<?php esc_html_e( 'Referer blacklist:', 'statify-blacklist' ); ?><br/>
					<textarea cols="40" rows="5" name="statifyblacklist[referer]" id="statify-blacklist_referer"><?php
						if ( isset( $statifyBlacklistUpdateResult ) && $statifyBlacklistUpdateResult !== false ) {
							print esc_html( implode( "\r\n", array_keys( $statifyBlacklistUpdateResult ) ) );
						} else {
							print esc_html( implode( "\r\n", array_keys( StatifyBlacklist::$_options['referer'] ) ) );
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
		<?php wp_nonce_field( 'statify-blacklist-settings' ); ?>

		<p class="submit">
			<input class="button-primary" type="submit" name="submit" value="<?php _e( 'Save Changes' ) ?>">
			<hr />
			<input class="button-secondary" type="submit" name="cleanUp"
			       value="<?php esc_html_e( 'CleanUp Database', 'statify-blacklist' ) ?>"
			       onclick="return confirm('Do you really want to apply filters to database? This cannot be undone.');">
			<br />
			<small><?php esc_html_e( 'Applies filter (even if disabled) to data stored in database. This cannot be undone!', 'statify-blacklist' ); ?></small>
		</p>
	</form>
</div>
