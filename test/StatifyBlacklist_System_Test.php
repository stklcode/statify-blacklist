<?php
/**
 * Statify Filter: Unit Test
 *
 * This is a PHPunit test class for the plugin's functionality
 *
 * @package Statify_Blacklist
 */

/**
 * Class StatifyBlacklist_System_Test.
 *
 * PHPUnit test class for StatifyBlacklist_System.
 */
class StatifyBlacklist_System_Test extends PHPUnit\Framework\TestCase {

	/**
	 * Test the upgrade methodology for configuration options.
	 *
	 * @return void
	 */
	public function test_upgrade() {
		// Create configuration of version 1.3.
		$options13 = array(
			'active_referer' => 1,
			'cron_referer'   => 0,
			'referer'        => array(
				'example.net' => 0,
				'example.com' => 1,
			),
			'referer_regexp' => 0,
			'version'        => 1.3,
		);

		// Set options in mock.
		update_option( 'statify-blacklist', $options13 );

		// Execute upgrade.
		StatifyBlacklist_System::upgrade();

		// Retrieve updated options.
		$options_updated = get_option( 'statify-blacklist' );

		// Verify size against default options (no junk left).
		$this->assertEquals( 5, count( $options_updated ) );
		$this->assertEquals( 4, count( $options_updated['referer'] ) );
		$this->assertEquals( 4, count( $options_updated['target'] ) );
		$this->assertEquals( 2, count( $options_updated['ip'] ) );
		$this->assertEquals( 3, count( $options_updated['ua'] ) );
		$this->assertEquals( 1.6, $options_updated['version'] );

		// Verify that original attributes are unchanged.
		$this->assertEquals( $options13['active_referer'], $options_updated['referer']['active'] );
		$this->assertEquals( $options13['cron_referer'], $options_updated['referer']['cron'] );
		$this->assertEquals( $options13['referer'], $options_updated['referer']['blacklist'] );
		$this->assertEquals( $options13['referer_regexp'], $options_updated['referer']['regexp'] );

		// Verify that new attributes are present in config and filled with default values (disabled, empty).
		$this->assertEquals( 0, $options_updated['target']['active'] );
		$this->assertEquals( 0, $options_updated['target']['cron'] );
		$this->assertEquals( 0, $options_updated['target']['regexp'] );
		$this->assertEquals( array(), $options_updated['target']['blacklist'] );
		$this->assertEquals( 0, $options_updated['ip']['active'] );
		$this->assertEquals( array(), $options_updated['ip']['blacklist'] );
		$this->assertEquals( 0, $options_updated['ua']['active'] );
		$this->assertEquals( 0, $options_updated['ua']['regexp'] );
		$this->assertEquals( array(), $options_updated['ua']['blacklist'] );

		// Verify that version number has changed to current release.
		$this->assertEquals( StatifyBlacklist::VERSION_MAIN, $options_updated['version'] );

		// Test upgrade of incorrectly stored user agent list in 1.6.
		$options_updated['version']         = 1.4;
		$options_updated['ua']['blacklist'] = array( 'user agent 1', 'user agent 2' );
		update_option( 'statify-blacklist', $options_updated );

		// Execute upgrade.
		StatifyBlacklist_System::upgrade();

		// Retrieve updated options.
		$options_updated = get_option( 'statify-blacklist' );
		$this->assertEquals(
			array(
				'user agent 1' => 0,
				'user agent 2' => 1,
			),
			$options_updated['ua']['blacklist']
		);
		$this->assertEquals( 1.6, $options_updated['version'] );
		$this->assertEquals( StatifyBlacklist::VERSION_MAIN, $options_updated['version'] );
	}
}
