<?php

/* Quit */
defined( 'ABSPATH' ) OR exit;

/* Update plugin options */
if ( ! empty( $_POST['statifyblacklist'] ) ) {
	/* Verify nonce */
	check_admin_referer( 'statify-blacklist-settings' );

	/* Check user capabilities */
	if ( ! current_user_can( 'manage_options' ) ) {
		die( __( 'Are you sure you want to do this?' ) );
	}

	if ( ! empty( $_POST['cleanUp'] ) ) {
		/* CleanUp DB */
		StatifyBlacklist_Admin::cleanup_database();
	} else {
		/* Extract referer array */
		if ( empty( trim( $_POST['statifyblacklist']['referer']['blacklist'] ) ) ) {
			$referer = array();
		} else {
			$referer = explode( "\r\n", $_POST['statifyblacklist']['referer']['blacklist'] );
		}

		/* Extract target array */
		if ( empty( trim( $_POST['statifyblacklist']['target']['blacklist'] ) ) ) {
			$target = array();
		} else {
			$target = explode( "\r\n", str_replace( '\\\\', '\\', $_POST['statifyblacklist']['target']['blacklist'] ) );
		}

		/* Extract IP array */
		if ( empty( trim( $_POST['statifyblacklist']['ip']['blacklist'] ) ) ) {
			$ip = array();
		} else {
			$ip = explode( "\r\n", $_POST['statifyblacklist']['ip']['blacklist'] );
		}

		/* Update options (data will be sanitized) */
		$statifyBlacklistUpdateResult = StatifyBlacklist_Admin::update_options(
			array(
				'referer' => array(
					'active'    => (int) @$_POST['statifyblacklist']['referer']['active'],
					'cron'      => (int) @$_POST['statifyblacklist']['referer']['cron'],
					'regexp'    => (int) @$_POST['statifyblacklist']['referer']['regexp'],
					'blacklist' => array_flip( $referer )
				),
				'target'  => array(
					'active'    => (int) @$_POST['statifyblacklist']['target']['active'],
					'cron'      => (int) @$_POST['statifyblacklist']['target']['cron'],
					'regexp'    => (int) @$_POST['statifyblacklist']['target']['regexp'],
					'blacklist' => array_flip( $target )
				),
				'ip'      => array(
					'active'    => (int) @$_POST['statifyblacklist']['ip']['active'],
					'blacklist' => $ip
				),
				'version' => StatifyBlacklist::VERSION_MAIN
			)
		);

		/* Generate messages */
		if ( $statifyBlacklistUpdateResult !== false ) {
			if ( array_key_exists( 'referer', $statifyBlacklistUpdateResult ) ) {
				$statifyBlacklistPostWarning = __( 'Some URLs are invalid and have been sanitized.', 'statify-blacklist' );
			} elseif ( array_key_exists( 'ip', $statifyBlacklistUpdateResult ) ) {
				$statifyBlacklistPostWarning = sprintf( __( 'Some IPs are invalid : %s', 'statify-blacklist' ), implode( ', ', $statifyBlacklistUpdateResult['ip'] ) );
			}
		} else {
			$statifyBlacklistPostSuccess = __( 'Settings updated successfully.', 'statify-blacklist' );
		}
	}
}
?>

<div class="wrap">
    <h1><?php _e( 'Statify Blacklist', 'statify-blacklist' ) ?></h1>
	<?php
	if ( is_plugin_inactive( 'statify/statify.php' ) ) {
		print '<div class="notice notice-warning"><p>';
		esc_html( 'Statify plugin is not active.' );
		print '</p></div>';
	}
	if ( isset( $statifyBlacklistPostWarning ) ) {
		print '<div class="notice notice-warning"><p>' .
		      esc_html( $statifyBlacklistPostWarning );
		print '<br/>';
		esc_html_e( 'Settings have not been saved yet.', 'statify-blacklist' );
		print '</p></div>';
	}
	if ( isset( $statifyBlacklistPostSuccess ) ) {
		print '<div class="notice notice-success"><p>' .
		      esc_html( $statifyBlacklistPostSuccess ) .
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
                        <br/>
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
						<?php esc_html_e( 'Referer blacklist', 'statify-blacklist' ); ?>:<br/>
                        <textarea cols="40" rows="5" name="statifyblacklist[referer][blacklist]" id="statify-blacklist_referer"><?php
							if ( isset( $statifyBlacklistUpdateResult['referer'] ) ) {
								print esc_html( implode( "\r\n", array_keys( $statifyBlacklistUpdateResult['referer'] ) ) );
							} else {
								print esc_html( implode( "\r\n", array_keys( StatifyBlacklist::$_options['referer']['blacklist'] ) ) );
							}
							?></textarea>
                        <br/>
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
                        <br/>
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
						<?php esc_html_e( 'Target blacklist', 'statify-blacklist' ); ?>:<br/>
                        <textarea cols="40" rows="5" name="statifyblacklist[target][blacklist]" id="statify-blacklist_target"><?php
							if ( isset( $statifyBlacklistUpdateResult['target'] ) ) {
								print esc_html( implode( "\r\n", array_keys( $statifyBlacklistUpdateResult['target'] ) ) );
							} else {
								print esc_html( implode( "\r\n", array_keys( StatifyBlacklist::$_options['target']['blacklist'] ) ) );
							}
							?></textarea>
                        <br/>
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
						<?php esc_html_e( 'IP blacklist', 'statify-blacklist' ); ?>:<br/>
                        <textarea cols="40" rows="5" name="statifyblacklist[ip][blacklist]" id="statify-blacklist_ip"><?php
							if ( isset( $statifyBlacklistUpdateResult['ip'] ) ) {
								print esc_html( $_POST['statifyblacklist']['ip']['blacklist'] );
							} else {
								print esc_html( implode( "\r\n", StatifyBlacklist::$_options['ip']['blacklist'] ) );
							}
							?></textarea>
                        <br/>
                        <small>
                            (<?php esc_html_e( 'Add one IP address or range per line, e.g.', 'statify-blacklist' ) ?>
                            127.0.0.1,
                            192.168.123.0/24, 2001:db8:a0b:12f0::1/64
                            )
                        </small>
                    </label>
                </li>
            </ul>
        </fieldset>

		<?php wp_nonce_field( 'statify-blacklist-settings' ); ?>

        <p class="submit">
            <input class="button-primary" type="submit" name="submit" value="<?php _e( 'Save Changes' ) ?>">
        <hr/>
        <input class="button-secondary" type="submit" name="cleanUp"
               value="<?php esc_html_e( 'CleanUp Database', 'statify-blacklist' ) ?>"
               onclick="return confirm('Do you really want to apply filters to database? This cannot be undone.');">
        <br/>
        <small><?php esc_html_e( 'Applies referer and target filter (even if disabled) to data stored in database.', 'statify-blacklist' ); ?> <b><?php esc_html_e( 'This cannot be undone!', 'statify-blacklist' ); ?></b></small>
        </p>
    </form>
</div>
