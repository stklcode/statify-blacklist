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

	/**
	 * Test sanitization of IP addresses.
	 *
	 * @return void
	 */
	public function test_sanitize_ips() {
		// IPv4 tests.
		$valid   = array( '192.0.2.123', '192.0.2.123/32', '192.0.2.0/24', '192.0.2.128/25' );
		$invalid = array( '12.34.56.789', '192.0.2.123/33', '192.0.2.123/-1' );
		$result  = invoke_static( StatifyBlacklist_Settings::class, 'sanitize_ips', array( array_merge( $valid, $invalid ) ) );
		$this->assertNotFalse( $result );

		/*
		 * Unfortunately this is necessary as long as we run PHP 5 tests, because "assertInternalType" is deprecated
		 * as of PHPUnit 8, but "assertIsArray" has been introduces in PHPUnit 7.5 which requires PHP >= 7.1.
		 */
		if ( method_exists( $this, 'assertIsArray' ) ) {
			$this->assertIsArray( $result );
		} else {
			$this->assertInternalType( 'array', $result );
		}
		$this->assertEquals( $valid, $result );

		// IPv6 tests.
		$valid   = array(
			'2001:db8:a0b:12f0::',
			'2001:db8:a0b:12f0::1',
			'2001:db8:a0b:12f0::1/128',
			'2001:DB8:A0B:12F0::/64',
			'fe80::7645:6de2:ff:1',
			'2001:db8:a0b:12f0::',
			'::ffff:192.0.2.123',
		);
		$invalid = array(
			'2001:db8:a0b:12f0::x',
			'2001:db8:a0b:12f0:::',
			'2001:fffff:a0b:12f0::1',
			'2001:DB8:A0B:12F0::/129',
			'1:2:3:4:5:6:7:8:9',
			'::ffff:12.34.56.789',
		);
		$result  = invoke_static( StatifyBlacklist_Settings::class, 'sanitize_ips', array( array_merge( $valid, $invalid ) ) );
		$this->assertNotFalse( $result );
		if ( method_exists( $this, 'assertIsArray' ) ) {
			$this->assertIsArray( $result );
		} else {
			$this->assertInternalType( 'array', $result );
		}
		$this->assertEquals(
			array(
				'2001:db8:a0b:12f0::',
				'2001:db8:a0b:12f0::1',
				'2001:db8:a0b:12f0::1/128',
				'2001:db8:a0b:12f0::/64',
				'fe80::7645:6de2:ff:1',
				'::ffff:192.0.2.123',
			),
			$result
		);
	}

	/**
	 * Test settings registration.
	 *
	 * @return void
	 */
	public function test_register_settings() {
		global $settings;
		$settings = array();

		StatifyBlacklist_Settings::register_settings();
		$this->assertEquals( array( 'statify-blacklist' ), array_keys( $settings ), 'unexpected settings pages' );
		$this->assertEquals(
			array(
				'statifyblacklist-referer',
				'statifyblacklist-target',
				'statifyblacklist-ip',
				'statifyblacklist-ua',
			),
			array_keys( $settings['statify-blacklist']['sections'] ),
			'unexpected settings sections'
		);
		$this->assertEquals(
			array(
				'statifyblacklist-referer-active',
				'statifyblacklist-referer-cron',
				'statifyblacklist-referer-regexp',
				'statifyblacklist-referer-blacklist',
			),
			array_keys( $settings['statify-blacklist']['sections']['statifyblacklist-referer']['fields'] ),
			'unexpected fields in referrer section'
		);
		$this->assertEquals(
			array(
				'statifyblacklist-target-active',
				'statifyblacklist-target-cron',
				'statifyblacklist-target-regexp',
				'statifyblacklist-target-blacklist',
			),
			array_keys( $settings['statify-blacklist']['sections']['statifyblacklist-target']['fields'] ),
			'unexpected fields in target section'
		);
		$this->assertEquals(
			array( 'statifyblacklist-ip-active', 'statifyblacklist-ip-blacklist' ),
			array_keys( $settings['statify-blacklist']['sections']['statifyblacklist-ip']['fields'] ),
			'unexpected fields in ip section'
		);
		$this->assertEquals(
			array(
				'statifyblacklist-ua-active',
				'statifyblacklist-ua-regexp',
				'statifyblacklist-ua-blacklist',
			),
			array_keys( $settings['statify-blacklist']['sections']['statifyblacklist-ua']['fields'] ),
			'unexpected fields in user agent section'
		);
	}
}
