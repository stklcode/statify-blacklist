<?php

/* Update plugin options */
if ( ! empty($_POST['statifyblacklist']) ) {
    StatifyBlacklist::update_options(
      array(
        'active_referer' => (int)@$_POST['statifyblacklist']['active_referer'],
        'referer'        => explode("\r\n", $_POST['statifyblacklist']['referer'])
      )
    );
}

?>

<div class="wrap">
    <h1><?php _e( 'Statify Blacklist', 'statify-blacklist') ?></h1>
    <form action="" method="post" id="statify-blacklist-settings">
        <ul style="list-style: none;">
            <li>
                <label for="statify-blacklist_active_referer">
                    <input type="checkbox" name="statifyblacklist[active_referer]" id="statifyblacklist_active_referer" value="1" <?php checked(StatifyBlacklist::$_options['active_referer'], 1); ?> />
                    <?php esc_html_e('Activate referer blacklist', 'statify-blacklist'); ?>
                </label>
            </li>
            <li>
                <label for="statify-blacklist_referer">
                    <?php  esc_html_e('Referer blacklist:', 'statify-blacklist'); ?><br />
                    <textarea cols="40" rows="5" name="statifyblacklist[referer]" id="statify-blacklist_referer"><?php print implode("\r\n", StatifyBlacklist::$_options['referer']); ?></textarea><br />
                    <small>(<?php esc_html_e('Add one domain (without subdomains) each line, e.g. example.com', 'statify-blacklist'); ?>)</small>
                </label>
            </li>
        </ul>
        <?php wp_nonce_field('statify-blacklist-settings'); ?>

        <p class="submit"><input class="button-primary" type="submit" name="submit" value="<?php _e('Save Changes') ?>"></p>
    </form>
</div>
