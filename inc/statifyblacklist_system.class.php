<?php

/* Quit */
defined('ABSPATH') OR exit;

/**
 * Statify system configuration
 *
 * @since 1.0.0
 */
class StatifyBlacklist_System extends StatifyBlacklist
{
  /**
   * Plugin install handler.
   *
   * @since 1.0.0
   *
   * @param bool $network_wide Whether the plugin was activated network-wide or not.
   */
  public static function install( $network_wide = false ) {
    global $wpdb;

    // Create tables for each site in a network.
    if ( is_multisite() && $network_wide ) {
      // Todo: Use get_sites() in WordPress 4.6+
      $ids = $wpdb->get_col( "SELECT blog_id FROM `$wpdb->blogs`" );

      foreach ( $ids as $site_id ) {
        switch_to_blog( $site_id );
        add_option(
          'statify-blacklist',
          array()
        );
      }

      restore_current_blog();
    } else {
      add_option(
        'statify-blacklist',
        array()
      );
    }
  }


  /**
   * Plugin uninstall handler.
   *
   * @since 1.0.0
   */
  public static function uninstall() {
    global $wpdb;

    if ( is_multisite() ) {
      $old = get_current_blog_id();

      // Todo: Use get_sites() in WordPress 4.6+
      $ids = $wpdb->get_col( "SELECT blog_id FROM `$wpdb->blogs`" );

      foreach ( $ids as $id ) {
        switch_to_blog( $id );
        delete_option('statify-blacklist');
      }

      switch_to_blog( $old );
    }

    delete_option('statify-blacklist');
  }
}