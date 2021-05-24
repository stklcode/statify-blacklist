<?php
/**
 * Statify Filter: Unit Test
 *
 * This is a PHPunit test class for the plugin's functionality
 *
 * @package    Statify_Blacklist
 * @subpackage Admin
 * @since      1.3.0
 */

/**
 * Simulating the ABSPATH constant.
 *
 * @since 1.3.0
 * @var bool ABSPATH
 */
const ABSPATH = false;

/**
 * The StatifyBlacklist base class.
 */
require_once __DIR__ . '/../inc/class-statifyblacklist.php';

/**
 * The StatifyBlacklist system class.
 */
require_once __DIR__ . '/../inc/class-statifyblacklist-system.php';

/**
 * The StatifyBlacklist admin class.
 */
require_once __DIR__ . '/../inc/class-statifyblacklist-admin.php';

/**
 * Class StatifyBlacklistTest.
 *
 * PHPUnit test class for StatifyBlacklist.
 *
 * @since 1.3.0
 */
class StatifyBlacklist_Test extends PHPUnit\Framework\TestCase {

	/**
	 * Test simple referer filter.
	 *
	 * @return void
	 */
	public function test_referer_filter() {
		// Prepare Options: 2 filtered domains, disabled.
		StatifyBlacklist::$options = array(
			'referer' => array(
				'active'    => 0,
				'cron'      => 0,
				'regexp'    => 0,
				'blacklist' => array(
					'example.com' => 0,
					'example.net' => 1,
				),
			),
			'target'  => array(
				'active'    => 0,
				'cron'      => 0,
				'regexp'    => 0,
				'blacklist' => array(),
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
		);

		// No multisite.
		StatifyBlacklist::$multisite = false;

		// No referer.
		unset( $_SERVER['HTTP_REFERER'] );
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		// Non-filtered referer.
		$_SERVER['HTTP_REFERER'] = 'http://example.org';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		// Filtered referer.
		$_SERVER['HTTP_REFERER'] = 'http://example.com';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		// Filtered referer with path.
		$_SERVER['HTTP_REFERER'] = 'http://example.net/foo/bar.html';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );

		// Activate filter and run tests again.
		StatifyBlacklist::$options['referer']['active'] = 1;

		unset( $_SERVER['HTTP_REFERER'] );
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );

		$_SERVER['HTTP_REFERER'] = 'http://example.org';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );

		$_SERVER['HTTP_REFERER'] = 'http://example.com';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );

		$_SERVER['HTTP_REFERER'] = 'http://example.net/foo/bar.html';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
	}

	/**
	 * Test referer filter using regular expressions.
	 *
	 * @return void
	 */
	public function test_referer_regex_filter() {
		// Prepare Options: 2 regular expressions.
		StatifyBlacklist::$options = array(
			'referer' => array(
				'active'    => 1,
				'cron'      => 0,
				'regexp'    => 1,
				'blacklist' => array(
					'example.[a-z]+' => 0,
					'test'           => 1,
				),
			),
			'target'  => array(
				'active'    => 0,
				'cron'      => 0,
				'regexp'    => 0,
				'blacklist' => array(),
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
		);

		// No multisite.
		StatifyBlacklist::$multisite = false;

		// No referer.
		unset( $_SERVER['HTTP_REFERER'] );
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		// Non-filtered referer.
		$_SERVER['HTTP_REFERER'] = 'http://not.evil';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		// Filtered referer.
		$_SERVER['HTTP_REFERER'] = 'http://example.com';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
		// Filtered referer with path.
		$_SERVER['HTTP_REFERER'] = 'http://foobar.net/test/me';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
		// Matching both.
		$_SERVER['HTTP_REFERER'] = 'http://example.net/test/me';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
		// Matching with wrong case.
		$_SERVER['HTTP_REFERER'] = 'http://eXaMpLe.NeT/tEsT/mE';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );

		// Set RegExp filter to case insensitive.
		StatifyBlacklist::$options['referer']['regexp'] = 2;
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
	}

	/**
	 * Test referer filter using keywords.
	 *
	 * @return void
	 */
	public function test_referer_keyword_filter() {
		// Prepare Options: 2 regular expressions.
		StatifyBlacklist::$options = array(
			'referer' => array(
				'active'    => 1,
				'cron'      => 0,
				'regexp'    => StatifyBlacklist::MODE_KEYWORD,
				'blacklist' => array(
					'example' => 0,
					'test'    => 1,
				),
			),
			'target'  => array(
				'active'    => 0,
				'cron'      => 0,
				'regexp'    => StatifyBlacklist::MODE_NORMAL,
				'blacklist' => array(),
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
		);

		// No multisite.
		StatifyBlacklist::$multisite = false;

		// No referer.
		unset( $_SERVER['HTTP_REFERER'] );
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		// Non-filtered referer.
		$_SERVER['HTTP_REFERER'] = 'http://not.evil';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		// Filtered referer.
		$_SERVER['HTTP_REFERER'] = 'http://example.com';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
		// Filtered referer with path.
		$_SERVER['HTTP_REFERER'] = 'http://foobar.net/test/me';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
		// Matching both.
		$_SERVER['HTTP_REFERER'] = 'http://example.net/test/me';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
		// Matching with wrong case.
		$_SERVER['HTTP_REFERER'] = 'http://eXaMpLe.NeT/tEsT/mE';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
	}

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


		// Test upgrade of incorrectly stored user agent list in 1.6
		$options_updated['version'] = 1.4;
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

	/**
	 * Test CIDR address matching for IP filter (#7).
	 *
	 * @return void
	 */
	public function test_cidr_match() {
		// IPv4 tests.
		$this->assertTrue( invoke_static( StatifyBlacklist::class, 'cidr_match', array( '127.0.0.1', '127.0.0.1' ) ) );
		$this->assertTrue( invoke_static( StatifyBlacklist::class, 'cidr_match', array( '127.0.0.1', '127.0.0.1/32' ) ) );
		$this->assertFalse(
			invoke_static(
				StatifyBlacklist::class,
				'cidr_match',
				array( '127.0.0.1', '127.0.0.1/33' )
			)
		);
		$this->assertFalse(
			invoke_static(
				StatifyBlacklist::class,
				'cidr_match',
				array( '127.0.0.1', '127.0.0.1/-1' )
			)
		);
		$this->assertTrue(
			invoke_static(
				StatifyBlacklist::class,
				'cidr_match',
				array( '192.0.2.123', '192.0.2.0/24' )
			)
		);
		$this->assertFalse(
			invoke_static(
				StatifyBlacklist::class,
				'cidr_match',
				array( '192.0.3.123', '192.0.2.0/24' )
			)
		);
		$this->assertTrue(
			invoke_static(
				StatifyBlacklist::class,
				'cidr_match',
				array( '192.0.2.123', '192.0.2.120/29' )
			)
		);
		$this->assertFalse(
			invoke_static(
				StatifyBlacklist::class,
				'cidr_match',
				array( '192.0.2.128', '192.0.2.120/29' )
			)
		);
		$this->assertTrue( invoke_static( StatifyBlacklist::class, 'cidr_match', array( '10.11.12.13', '10.0.0.0/8' ) ) );
		$this->assertFalse(
			invoke_static(
				StatifyBlacklist::class,
				'cidr_match',
				array( '10.11.12.345', '10.0.0.0/8' )
			)
		);

		// IPv6 tests.
		$this->assertTrue( invoke_static( StatifyBlacklist::class, 'cidr_match', array( '::1', '::1' ) ) );
		$this->assertTrue( invoke_static( StatifyBlacklist::class, 'cidr_match', array( '::1', '::1/128' ) ) );
		$this->assertFalse( invoke_static( StatifyBlacklist::class, 'cidr_match', array( '::1', '::1/129' ) ) );
		$this->assertFalse( invoke_static( StatifyBlacklist::class, 'cidr_match', array( '::1', '::1/-1' ) ) );
		$this->assertTrue(
			invoke_static(
				StatifyBlacklist::class,
				'cidr_match',
				array( '2001:db8:a0b:12f0:1:2:3:4', '2001:db8:a0b:12f0::1/64 ' )
			)
		);
		$this->assertTrue(
			invoke_static(
				StatifyBlacklist::class,
				'cidr_match',
				array( '2001:db8:a0b:12f0::123:456', '2001:db8:a0b:12f0::1/96 ' )
			)
		);
		$this->assertFalse(
			invoke_static(
				StatifyBlacklist::class,
				'cidr_match',
				array( '2001:db8:a0b:12f0::1:132:465', '2001:db8:a0b:12f0::1/96 ' )
			)
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
		$result  = invoke_static( StatifyBlacklist_Admin::class, 'sanitize_ips', array( array_merge( $valid, $invalid ) ) );
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
			'2001:db8:a0b:12f0::/64',
		);
		$invalid = array(
			'2001:db8:a0b:12f0::x',
			'2001:db8:a0b:12f0:::',
			'2001:fffff:a0b:12f0::1',
			'2001:db8:a0b:12f0::/129',
			'1:2:3:4:5:6:7:8:9',
		);
		$result  = invoke_static( StatifyBlacklist_Admin::class, 'sanitize_ips', array( array_merge( $valid, $invalid ) ) );
		$this->assertNotFalse( $result );
		if ( method_exists( $this, 'assertIsArray' ) ) {
			$this->assertIsArray( $result );
		} else {
			$this->assertInternalType( 'array', $result );
		}
		$this->assertEquals( $valid, $result );
	}

	/**
	 * Test IP filter (#7).
	 *
	 * @return void
	 */
	public function test_ip_filter() {
		// Prepare Options: 2 filtered IPs, disabled.
		StatifyBlacklist::$options = array(
			'referer' => array(
				'active'    => 0,
				'cron'      => 0,
				'regexp'    => StatifyBlacklist::MODE_NORMAL,
				'blacklist' => array(),
			),
			'target'  => array(
				'active'    => 0,
				'cron'      => 0,
				'regexp'    => StatifyBlacklist::MODE_NORMAL,
				'blacklist' => array(),
			),
			'ip'      => array(
				'active'    => 0,
				'blacklist' => array(
					'192.0.2.123',
					'2001:db8:a0b:12f0::1',
				),
			),
			'ua'      => array(
				'active'    => 0,
				'regexp'    => StatifyBlacklist::MODE_NORMAL,
				'blacklist' => array(),
			),
			'version' => StatifyBlacklist::VERSION_MAIN,
		);

		// No multisite.
		StatifyBlacklist::$multisite = false;

		// Set matching IP.
		$_SERVER['REMOTE_ADDR'] = '192.0.2.123';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		// Activate filter.
		StatifyBlacklist::$options['ip']['active'] = 1;
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
		// Try matching v6 address.
		$_SERVER['REMOTE_ADDR'] = '2001:db8:a0b:12f0::1';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
		// Non-matching addresses.
		$_SERVER['REMOTE_ADDR'] = '192.0.2.234';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		$_SERVER['REMOTE_ADDR'] = '2001:db8:a0b:12f0::2';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		// Subnet matching.
		StatifyBlacklist::$options['ip']['blacklist'] = array(
			'192.0.2.0/25',
			'2001:db8:a0b:12f0::/96',
		);
		$_SERVER['REMOTE_ADDR']                       = '192.0.2.123';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
		$_SERVER['REMOTE_ADDR'] = '192.0.2.234';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		$_SERVER['REMOTE_ADDR'] = '2001:db8:a0b:12f0::5';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
		$_SERVER['REMOTE_ADDR'] = '2001:db8:a0b:12f0:0:1111::1';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );

		// Filter using proxy header.
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '192.0.2.123';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
		$_SERVER['HTTP_X_REAL_IP'] = '2001:db8:a0b:12f0:0:1111::1';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		$_SERVER['HTTP_X_REAL_IP'] = '2001:db8:a0b:12f0:0::1';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
	}

	/**
	 * Test simple target filter.
	 *
	 * @return void
	 */
	public function test_target_filter() {
		// Prepare Options: 2 filtered domains, disabled.
		StatifyBlacklist::$options = array(
			'referer' => array(
				'active'    => 0,
				'cron'      => 0,
				'regexp'    => StatifyBlacklist::MODE_NORMAL,
				'blacklist' => array(),
			),
			'target'  => array(
				'active'    => 0,
				'cron'      => 0,
				'regexp'    => StatifyBlacklist::MODE_NORMAL,
				'blacklist' => array(
					'/excluded/page/' => 0,
					'/?page_id=3'     => 1,
				),
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
		);

		// No multisite.
		StatifyBlacklist::$multisite = false;

		// Empty target.
		unset( $_SERVER['REQUEST_URI'] );
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		// Non-filtered targets.
		$_SERVER['REQUEST_URI'] = '';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		$_SERVER['REQUEST_URI'] = '/';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		$_SERVER['REQUEST_URI'] = '/?page_id=1';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		// Filtered referer.
		$_SERVER['REQUEST_URI'] = '/excluded/page/';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		$_SERVER['REQUEST_URI'] = '/?page_id=3';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );

		// Activate filter and run tests again.
		StatifyBlacklist::$options['target']['active'] = 1;

		unset( $_SERVER['REQUEST_URI'] );
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );

		$_SERVER['REQUEST_URI'] = '';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		$_SERVER['REQUEST_URI'] = '/';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		$_SERVER['REQUEST_URI'] = '/?page_id=1';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );

		$_SERVER['REQUEST_URI'] = '/excluded/page/';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
		$_SERVER['REQUEST_URI'] = '/?page_id=3';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
		$_SERVER['REQUEST_URI'] = '/?page_id=3';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
	}

	// TODO: Test target regex filter.


	/**
	 * Test user agent filter (#20).
	 *
	 * @return void
	 */
	public function test_ua_filter() {
		// Prepare Options: 2 filtered IPs, disabled.
		StatifyBlacklist::$options = array(
			'referer' => array(
				'active'    => 0,
				'cron'      => 0,
				'regexp'    => StatifyBlacklist::MODE_NORMAL,
				'blacklist' => array(),
			),
			'target'  => array(
				'active'    => 0,
				'cron'      => 0,
				'regexp'    => StatifyBlacklist::MODE_NORMAL,
				'blacklist' => array(),
			),
			'ip'      => array(
				'active'    => 0,
				'blacklist' => array(),
			),
			'ua'      => array(
				'active'    => 0,
				'regexp'    => StatifyBlacklist::MODE_NORMAL,
				'blacklist' => array(
					'TestBot/1.23' => 0,
				),
			),
			'version' => StatifyBlacklist::VERSION_MAIN,
		);

		// No multisite.
		StatifyBlacklist::$multisite = false;

		// Set matching user agent.
		$_SERVER['HTTP_USER_AGENT'] = 'TestBot/1.23';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		// Activate filter.
		StatifyBlacklist::$options['ua']['active'] = 1;
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
		// Non-matching addresses.
		$_SERVER['HTTP_USER_AGENT'] = 'Another Browser 4.5.6 (Linux)';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		$_SERVER['HTTP_USER_AGENT'] = 'TestBot/2.34';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		// Keyword matching.
		StatifyBlacklist::$options['ua']['blacklist'] = array( 'TestBot' => 0 );
		StatifyBlacklist::$options['ua']['regexp'] = StatifyBlacklist::MODE_KEYWORD;
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
		// RegEx.
		StatifyBlacklist::$options['ua']['blacklist'] = array( 'T[a-z]+B[a-z]+' => 0 );
		StatifyBlacklist::$options['ua']['regexp'] = StatifyBlacklist::MODE_REGEX;
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
		StatifyBlacklist::$options['ua']['blacklist'] = array( 't[a-z]+' => 0 );
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		StatifyBlacklist::$options['ua']['regexp'] = StatifyBlacklist::MODE_REGEX_CI;
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
	}


	/**
	 * Test combined filters.
	 *
	 * @since 1.4.4
	 *
	 * @return void
	 */
	public function test_combined_filters() {
		// Prepare Options: simple referer + simple target + ip.
		StatifyBlacklist::$options = array(
			'referer' => array(
				'active'    => 1,
				'cron'      => 0,
				'regexp'    => StatifyBlacklist::MODE_NORMAL,
				'blacklist' => array(
					'example.com' => 0,
				),
			),
			'target'  => array(
				'active'    => 1,
				'cron'      => 0,
				'regexp'    => StatifyBlacklist::MODE_NORMAL,
				'blacklist' => array(
					'/excluded/page/' => 0,
				),
			),
			'ip'      => array(
				'active'    => 1,
				'blacklist' => array(
					'192.0.2.123',
				),
			),
			'ua'      => array(
				'active'    => 0,
				'regexp'    => StatifyBlacklist::MODE_NORMAL,
				'blacklist' => array(),
			),
			'version' => StatifyBlacklist::VERSION_MAIN,
		);

		// No multisite.
		StatifyBlacklist::$multisite = false;

		// No match.
		$_SERVER['HTTP_REFERER'] = 'https://example.net';
		$_SERVER['REQUEST_URI']  = '/normal/page/';
		$_SERVER['REMOTE_ADDR']  = '192.0.2.234';
		unset( $_SERVER['HTTP_X_FORWARDED_FOR'] );
		unset( $_SERVER['HTTP_X_REAL_IP'] );

		// Matching Referer.
		$_SERVER['HTTP_REFERER'] = 'https://example.com';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );

		// Matching target.
		$_SERVER['HTTP_REFERER'] = 'https://example.net';
		$_SERVER['REQUEST_URI']  = '/excluded/page/';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );

		// Matching IP.
		$_SERVER['REQUEST_URI'] = '/normal/page/';
		$_SERVER['REMOTE_ADDR'] = '192.0.2.123';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
		$_SERVER['REMOTE_ADDR'] = '192.0.2.234';

		// Same for RegExp filters.
		StatifyBlacklist::$options['referer']['regexp']    = StatifyBlacklist::MODE_REGEX;
		StatifyBlacklist::$options['referer']['blacklist'] = array( 'example\.com' => 0 );
		StatifyBlacklist::$options['target']['regexp']     = StatifyBlacklist::MODE_REGEX;
		StatifyBlacklist::$options['target']['blacklist']  = array( '/excluded/.*' => 0 );

		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		$_SERVER['HTTP_REFERER'] = 'https://example.com';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
		// Check case-insensitive match.
		$_SERVER['HTTP_REFERER'] = 'https://eXaMpLe.com';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		StatifyBlacklist::$options['referer']['regexp'] = StatifyBlacklist::MODE_REGEX_CI;
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
		$_SERVER['HTTP_REFERER'] = 'https://example.net';
		$_SERVER['REQUEST_URI']  = '/excluded/page/';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
		$_SERVER['REQUEST_URI'] = '/normal/page/';
		$_SERVER['REMOTE_ADDR'] = '192.0.2.123';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
		$_SERVER['REMOTE_ADDR'] = '192.0.2.234';

	}
}


/** @ignore */
function invoke_static( $class, $method_name, $parameters = array() ) {
	$reflection = new \ReflectionClass( $class );
	$method     = $reflection->getMethod( $method_name );
	$method->setAccessible( true );

	return $method->invokeArgs( null, $parameters );
}


// Some mocked WP functions.
$mock_options   = array();
$mock_multisite = false;

/** @ignore */
function is_multisite() {
	global $mock_multisite;

	return $mock_multisite;
}

/** @ignore */
function wp_parse_args( $args, $defaults = '' ) {
	if ( is_object( $args ) ) {
		$r = get_object_vars( $args );
	} elseif ( is_array( $args ) ) {
		$r =& $args;
	} else {
		parse_str( $args, $r );
	}

	if ( is_array( $defaults ) ) {
		return array_merge( $defaults, $r );
	}

	return $r;
}

/** @ignore */
function get_option( $option, $default = false ) {
	global $mock_options;

	return isset( $mock_options[ $option ] ) ? $mock_options[ $option ] : $default;
}

/** @ignore */
function update_option( $option, $value, $autoload = null ) {
	global $mock_options;
	$mock_options[ $option ] = $value;
}

/** @ignore */
function wp_get_raw_referer() {
	return isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '';
}

function wp_parse_url( $value ) {
	return parse_url( $value );
}

/** @ignore */
function wp_unslash( $value ) {
	return is_string( $value ) ? stripslashes( $value ) : $value;
}
