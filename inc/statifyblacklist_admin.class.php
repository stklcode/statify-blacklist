<?php

/**
 * Statify Blacklist admin configuration
 *
 * @since 1.0.0
 */
class StatifyBlacklist_Admin extends StatifyBlacklist
{
  /**
   * Add configuration page to admin menu
   *
   * @since   1.0.0
   */
  public function _add_menu_page() {
    $title = __( 'Statify Blacklist', 'statify-blacklist' );
    if (self::$multisite)
      add_submenu_page( 'settings.php', $title, $title, 'manage_network_plugins', 'statify-blacklist-settings', array('StatifyBlacklist_Admin', 'settings_page') );
    else
      add_submenu_page( 'options-general.php', $title, $title, 'manage_options', 'statify-blacklist', array('StatifyBlacklist_Admin', 'settings_page') );

  }

  public static function settings_page() {
    include STATIFYBLACKLIST_DIR . '/views/settings_page.php';
  }

  /**
   * Add plugin meta links
   *
   * @param $links
   * @param $file
   * @return array
   *
   * @since   1.0.0
   */
  public static function plugin_meta_link($links, $file) {
    if ($file == STATIFYBLACKLIST_BASE) {
      $links[] = '<a href="https://github.com/stklcode/statify-blacklist">GitHub</a>';
    }
    return $links;
  }

  /**
   * Add plugin action links
   *
   * @param   array   $input  Registered links
   * @return  array           Merged links
   *
   * @since   1.0.0
   */
  public static function plugin_actions_links($links, $file)
  {
    $base = self::$multisite ? network_admin_url( 'settings.php' ) : admin_url( 'options-general.php' );

    if( $file == STATIFYBLACKLIST_BASE && current_user_can('manage_options') ) {
      array_unshift(
        $links,
        sprintf( '<a href="%s">%s</a>', esc_attr(add_query_arg( 'page', 'statify-blacklist', $base )), __('Settings'))
      );
    }
    return $links;
  }
}