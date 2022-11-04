<?php
/**
 * Statify Filter: Unit Test
 *
 * This is a PHPunit test class for the plugin's functionality
 *
 * @package Statify_Blacklist
 * @since   1.3.0
 */

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
		$this->assertTrue(
			invoke_static(
				StatifyBlacklist::class,
				'cidr_match',
				array( '2001:DB8:A0B:12F0::123:456', '2001:db8:a0b:12f0::1/96 ' )
			)
		);
		$this->assertTrue(
			invoke_static(
				StatifyBlacklist::class,
				'cidr_match',
				array( '2001:db8:a0b:12f0::123:456', '2001:DB8:A0B:12F0::1/96 ' )
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
		StatifyBlacklist::$options['ua']['regexp']    = StatifyBlacklist::MODE_KEYWORD;
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
		// RegEx.
		StatifyBlacklist::$options['ua']['blacklist'] = array( 'T[a-z]+B[a-z]+' => 0 );
		StatifyBlacklist::$options['ua']['regexp']    = StatifyBlacklist::MODE_REGEX;
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
