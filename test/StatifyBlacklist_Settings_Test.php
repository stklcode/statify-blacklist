<?php
/**
 * Statify Filter: Unit Test
 *
 * This is a PHPunit test class for the plugin's functionality
 *
 * @package Statify_Blacklist
 */

/**
 * Class StatifyBlacklist_Settings_Test.
 *
 * PHPUnit test class for StatifyBlacklist_Settings.
 */
class StatifyBlacklist_Settings_Test extends PHPUnit\Framework\TestCase {

	/**
	 * Test options sanitization.
	 *
	 * @return void
	 */
	public function test_sanitize_options() {
		global $settings_error;

		// Emulate default submission: nothing checked, all textareas empty.
		$raw = array(
			'referer' => array(
				'blacklist' => '',
				'regexp'    => '0',
			),
			'target'  => array(
				'blacklist' => '',
				'regexp'    => '0',
			),
			'ip'      => array( 'blacklist' => '' ),
			'ua'      => array(
				'blacklist' => '',
				'regexp'    => '0',
			),
		);

		$sanitized = StatifyBlacklist_Settings::sanitize_options( $raw );

		self::assertEmpty( $settings_error );
		self::assertEquals(
			array(
				'referer' => array(
					'active'    => 0,
					'cron'      => 0,
					'blacklist' => array(),
					'regexp'    => StatifyBlacklist::MODE_NORMAL,
				),
				'target'  => array(
					'active'    => 0,
					'cron'      => 0,
					'blacklist' => array(),
					'regexp'    => StatifyBlacklist::MODE_NORMAL,
				),
				'ip'      => array(
					'active'    => 0,
					'blacklist' => array(),
				),
				'ua'      => array(
					'active'    => 0,
					'regexp'    => StatifyBlacklist::MODE_NORMAL,
					'blacklist' => array(),
				),
				'version' => StatifyBlacklist::VERSION_MAIN,
			),
			$sanitized
		);

		// Some checked options and some valid entries.
		$raw = array(
			'referer' => array(
				'cron'      => '1',
				'blacklist' => "example.com\r\nexample.net\r\nexample.org",
				'regexp'    => '0',
			),
			'target'  => array(
				'active'    => '1',
				'blacklist' => "foo\r\nbar\r\ntest",
				'regexp'    => '3',
			),
			'ip'      => array(
				'active'    => '1',
				'blacklist' => "127.0.0.1/8\r\n::1",
			),
			'ua'      => array(
				'blacklist' => 'MyBot/1.23',
				'regexp'    => '1',
			),
		);

		$sanitized = StatifyBlacklist_Settings::sanitize_options( $raw );

		self::assertEmpty( $settings_error );
		self::assertEquals(
			array(
				'referer' => array(
					'active'    => 0,
					'cron'      => 1,
					'blacklist' => array(
						'example.com' => 0,
						'example.net' => 1,
						'example.org' => 2,
					),
					'regexp'    => StatifyBlacklist::MODE_NORMAL,
				),
				'target'  => array(
					'active'    => 1,
					'cron'      => 0,
					'blacklist' => array(
						'foo'  => 0,
						'bar'  => 1,
						'test' => 2,
					),
					'regexp'    => StatifyBlacklist::MODE_KEYWORD,
				),
				'ip'      => array(
					'active'    => 1,
					'blacklist' => array(
						'127.0.0.1/8',
						'::1',
					),
				),
				'ua'      => array(
					'active'    => 0,
					'regexp'    => StatifyBlacklist::MODE_REGEX,
					'blacklist' => array(
						'MyBot/1.23' => 0,
					),
				),
				'version' => StatifyBlacklist::VERSION_MAIN,
			),
			$sanitized
		);

		// Now we have some additional nonsense fields and invalid entries.
		$raw = array(
			'testme ' => 'whatever',
			'referer' => array(
				'cron'      => '1',
				'blacklist' => "  example\\.com   \r\nexample(\\.net\r\nexample\\.com",
				'regexp'    => '1',
			),
			'target'  => array(
				'active'    => '1',
				'blacklist' => "fo.\r\n[bar\r\n*test",
				'regexp'    => '2',
			),
			'ip'      => array(
				'active'    => '1',
				'blacklist' => "127.0.0.1/8\r\nthisisnotanip\r\n127.0.0.1/8",
			),
			'ua'      => array(
				'blacklist' => 'MyBot/1.23',
				'regexp'    => '1',
			),
		);

		$sanitized = StatifyBlacklist_Settings::sanitize_options( $raw );

		self::assertEquals(
			array(
				'referer' => array(
					'active'    => 0,
					'cron'      => 1,
					'blacklist' => array(
						'example\.com'  => 2,
						'example(\.net' => 1,
					),
					'regexp'    => StatifyBlacklist::MODE_REGEX,
				),
				'target'  => array(
					'active'    => 1,
					'cron'      => 0,
					'blacklist' => array(
						'fo.'   => 0,
						'[bar'  => 1,
						'*test' => 2,
					),
					'regexp'    => StatifyBlacklist::MODE_REGEX_CI,
				),
				'ip'      => array(
					'active'    => 1,
					'blacklist' => array(
						'127.0.0.1/8',
					),
				),
				'ua'      => array(
					'active'    => 0,
					'regexp'    => StatifyBlacklist::MODE_REGEX,
					'blacklist' => array(
						'MyBot/1.23' => 0,
					),
				),
				'version' => StatifyBlacklist::VERSION_MAIN,
			),
			$sanitized
		);

		self::assertEquals(
			array(
				array( 'statify-blacklist', 'referer-invalid', 'Some regular expressions for referrers are invalid:<br>example(\.net', 'error' ),
				array( 'statify-blacklist', 'target-invalid', 'Some regular expressions for targets are invalid:<br>[bar<br>*test', 'error' ),
				array( 'statify-blacklist', 'ip-diff', 'Some IPs are invalid: thisisnotanip', 'warning' ),
			),
			$settings_error
		);
	}
}
