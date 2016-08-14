<?php

/* Quit */
defined('ABSPATH') OR exit;

/**
 * Statify Blacklist
 *
 * @since 1.0.0
 */
class StatifyBlacklist
{
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
  public static function instance()
  {
    new self();
  }

  /**
   * Class constructor
   *
   * @since   1.0.0
   */
  public function __construct()
  {
    /* Skip on autosave or AJAX */
    if ( (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) OR (defined('DOING_AJAX') && DOING_AJAX) ) {
      return;
    }

    /* Plugin options */
    self::update_options();

    /* Get multisite status */
    self::$multisite = (is_multisite() && array_key_exists(STATIFYBLACKLIST_BASE, (array)get_site_option('active_sitewide_plugins')));

    /* Add Filter to statify hook */
    add_filter('statify_skip_tracking', array('StatifyBlacklist', 'apply_blacklist_filter'));

    /* Admin only filters */
    if ( is_admin() ) {
      add_action('wpmu_new_blog', array('StatifyBlacklist_Install', 'init_site'));

      add_action('delete_blog', array('StatifyBlacklist_System', 'init_site'));

      add_filter('plugin_row_meta', array('StatifyBlacklist_Admin', 'plugin_meta_link'), 10, 2);

      if (is_multisite()) {
        add_action('network_admin_menu', array('StatifyBlacklist_Admin', '_add_menu_page'));
        add_filter('network_admin_plugin_action_links', array('StatifyBlacklist_Admin', 'plugin_actions_links'), 10, 2);
      } else {
        add_action('admin_menu', array('StatifyBlacklist_Admin', '_add_menu_page'));
        add_filter('plugin_action_links', array('StatifyBlacklist_Admin', 'plugin_actions_links'), 10, 2 );
      }
    }
  }

  /**
   * Update options
   *
   * @since   1.0.0
   */
  public static function update_options($options = null) {
    if (isset($options)) {
      if ((is_multisite() && array_key_exists(STATIFYBLACKLIST_BASE, (array)get_site_option('active_sitewide_plugins'))))
        update_site_option('statify-blacklist', $options);
      else
        update_option('statify-blacklist', $options);
    }

    self::$_options = wp_parse_args(
      get_option('statify-blacklist'),
      array(
        'active_referer' => 0,
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
   */
  public static function apply_blacklist_filter() {
    /* Skip if blacklist is inactive */
    if (self::$_options['active_referer'] != 1) {
      return false;
    }

    /* Extract relevant domain parts */
    $referer = strtolower( ( isset($_SERVER['HTTP_REFERER']) ? parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) : '' ) );
    $referer = explode('.', $referer);
    if( count($referer) >1 )
      $referer = implode('.', array_slice($referer, -2));
    else
      $referer = implode('.', $referer);

    /* Get blacklist */
    $blacklist = self::$_options['referer'];

    /* Check blacklist */
    return in_array($referer, $blacklist);
  }
}
