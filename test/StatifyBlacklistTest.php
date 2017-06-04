<?php

const ABSPATH = false;
require_once( '../inc/statifyblacklist.class.php' );
require_once( '../inc/statifyblacklist_system.class.php' );
require_once( '../inc/statifyblacklist_admin.class.php' );

/**
 * Class StatifyBlacklistTest
 *
 * PHPUnit test class for StatifyBlacklist
 *
 * @version 1.4.0~dev
 */
class StatifyBlacklistTest extends PHPUnit_Framework_TestCase {

	/**
	 * Test simple referer filter.
	 */
	public function testRefererFilter() {
		/* Prepare Options: 2 blacklisted domains, disabled */
		StatifyBlacklist::$_options = array(
			'active_referer' => 0,
			'cron_referer'   => 0,
			'referer'        => array(
				'example.com' => 0,
				'example.net' => 1
			),
			'active_ip'      => 0,
			'ip'             => array(),
			'version'        => StatifyBlacklist::VERSION_MAIN
		);

		/* No multisite */
		StatifyBlacklist::$multisite = false;

		/* No referer */
		unset( $_SERVER['HTTP_REFERER'] );
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		/* Non-blacklisted referer */
		$_SERVER['HTTP_REFERER'] = 'http://example.org';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		/* Blacklisted referer */
		$_SERVER['HTTP_REFERER'] = 'http://example.com';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		/* Blacklisted referer with path */
		$_SERVER['HTTP_REFERER'] = 'http://example.net/foo/bar.html';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );

		/* Activate filter and run tests again */
		StatifyBlacklist::$_options['active_referer'] = 1;

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
	 */
	public function testRefererRegexFilter() {
		/* Prepare Options: 2 regular expressions */
		StatifyBlacklist::$_options = array(
			'active_referer' => 1,
			'cron_referer'   => 0,
			'referer'        => array(
				'example.[a-z]+' => 0,
				'test'           => 1
			),
			'referer_regexp' => 1,
			'version'        => 1.3
		);

		/* No multisite */
		StatifyBlacklist::$multisite = false;

		/* No referer */
		unset( $_SERVER['HTTP_REFERER'] );
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		/* Non-blacklisted referer */
		$_SERVER['HTTP_REFERER'] = 'http://not.evil';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		/* Blacklisted referer */
		$_SERVER['HTTP_REFERER'] = 'http://example.com';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
		/* Blacklisted referer with path */
		$_SERVER['HTTP_REFERER'] = 'http://foobar.net/test/me';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
		/* Matching both */
		$_SERVER['HTTP_REFERER'] = 'http://example.net/test/me';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
		/* Mathinc with wrong case */
		$_SERVER['HTTP_REFERER'] = 'http://eXaMpLe.NeT/tEsT/mE';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );

		/* Set RegExp filter to case insensitive */
		StatifyBlacklist::$_options['referer_regexp'] = 2;
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
	}

	/**
	 * Test the upgrade methodology for configuration options.
	 */
	public function testUpgrade() {
		/* Create configuration of version 1.3 */
		$options13 = array(
			'active_referer' => 1,
			'cron_referer'   => 0,
			'referer'        => array(
				'example.net' => 0,
				'example.com' => 1
			),
			'referer_regexp' => 0,
			'version'        => 1.3
		);

		/* Set options in mock */
		update_option( 'statify-blacklist', $options13 );

		/* Execute upgrade */
		StatifyBlacklist_System::upgrade();

		/* Retrieve updated options */
		$optionsUpdated = get_option( 'statify-blacklist' );

		/* Verify size against default options (no junk left) */
		$this->assertEquals( 11, sizeof( $optionsUpdated ) );

		/* Verify that original attributes are unchanged */
		$this->assertEquals( $options13['active_referer'], $optionsUpdated['active_referer'] );
		$this->assertEquals( $options13['cron_referer'], $optionsUpdated['cron_referer'] );
		$this->assertEquals( $options13['referer'], $optionsUpdated['referer'] );
		$this->assertEquals( $options13['referer_regexp'], $optionsUpdated['referer_regexp'] );

		/* Verify that new attributes are present in config */
		$this->assertEquals( 0, $optionsUpdated['active_ip'] );
		$this->assertEquals( array(), $optionsUpdated['ip'] );

		/* Verify that version number has changed to current release */
		$this->assertEquals( StatifyBlacklist::VERSION_MAIN, $optionsUpdated['version'] );
	}

	/**
	 * Test CIDR address matching for IP filter (#7)
	 */
	public function testCidrMatch() {
		/* IPv4 tests */
		$this->assertTrue( invokeStatic( StatifyBlacklist::class, 'cidrMatch', array( '127.0.0.1', '127.0.0.1' ) ) );
		$this->assertTrue( invokeStatic( StatifyBlacklist::class, 'cidrMatch', array( '127.0.0.1', '127.0.0.1/32' ) ) );
		$this->assertFalse( invokeStatic( StatifyBlacklist::class, 'cidrMatch', array(
			'127.0.0.1',
			'127.0.0.1/33'
		) ) );
		$this->assertFalse( invokeStatic( StatifyBlacklist::class, 'cidrMatch', array(
			'127.0.0.1',
			'127.0.0.1/-1'
		) ) );
		$this->assertTrue( invokeStatic( StatifyBlacklist::class, 'cidrMatch', array(
			'192.0.2.123',
			'192.0.2.0/24'
		) ) );
		$this->assertFalse( invokeStatic( StatifyBlacklist::class, 'cidrMatch', array(
			'192.0.3.123',
			'192.0.2.0/24'
		) ) );
		$this->assertTrue( invokeStatic( StatifyBlacklist::class, 'cidrMatch', array(
			'192.0.2.123',
			'192.0.2.120/29'
		) ) );
		$this->assertFalse( invokeStatic( StatifyBlacklist::class, 'cidrMatch', array(
			'192.0.2.128',
			'192.0.2.120/29'
		) ) );
		$this->assertTrue( invokeStatic( StatifyBlacklist::class, 'cidrMatch', array( '10.11.12.13', '10.0.0.0/8' ) ) );
		$this->assertFalse( invokeStatic( StatifyBlacklist::class, 'cidrMatch', array(
			'10.11.12.345',
			'10.0.0.0/8'
		) ) );

		/* IPv6 tests */
		$this->assertTrue( invokeStatic( StatifyBlacklist::class, 'cidrMatch', array( '::1', '::1' ) ) );
		$this->assertTrue( invokeStatic( StatifyBlacklist::class, 'cidrMatch', array( '::1', '::1/128' ) ) );
		$this->assertFalse( invokeStatic( StatifyBlacklist::class, 'cidrMatch', array( '::1', '::1/129' ) ) );
		$this->assertFalse( invokeStatic( StatifyBlacklist::class, 'cidrMatch', array( '::1', '::1/-1' ) ) );
		$this->assertTrue( invokeStatic( StatifyBlacklist::class, 'cidrMatch', array(
			'2001:db8:a0b:12f0:1:2:3:4',
			'2001:db8:a0b:12f0::1/64 '
		) ) );
		$this->assertTrue( invokeStatic( StatifyBlacklist::class, 'cidrMatch', array(
			'2001:db8:a0b:12f0::123:456',
			'2001:db8:a0b:12f0::1/96 '
		) ) );
		$this->assertFalse( invokeStatic( StatifyBlacklist::class, 'cidrMatch', array(
			'2001:db8:a0b:12f0::1:132:465',
			'2001:db8:a0b:12f0::1/96 '
		) ) );
	}

	/**
	 * Test sanitization of IP addresses
	 */
	public function testSanitizeIPs() {
		/* IPv4 tests */
		$valid   = array( '192.0.2.123', '192.0.2.123/32', '192.0.2.0/24', '192.0.2.128/25' );
		$invalid = array( '12.34.56.789', '192.0.2.123/33', '192.0.2.123/-1' );
		$result  = invokeStatic( StatifyBlacklist_Admin::class, 'sanitizeIPs', array( array_merge( $valid, $invalid ) ) );
		$this->assertNotFalse( $result );
		$this->assertInternalType( 'array', $result );
		$this->assertEquals( $valid, $result );

		/* IPv6 tests */
		$valid   = array(
			'2001:db8:a0b:12f0::',
			'2001:db8:a0b:12f0::1',
			'2001:db8:a0b:12f0::1/128',
			'2001:db8:a0b:12f0::/64'
		);
		$invalid = array(
			'2001:db8:a0b:12f0::x',
			'2001:db8:a0b:12f0:::',
			'2001:fffff:a0b:12f0::1',
			'2001:db8:a0b:12f0::/129',
			'1:2:3:4:5:6:7:8:9'
		);
		$result  = invokeStatic( StatifyBlacklist_Admin::class, 'sanitizeIPs', array( array_merge( $valid, $invalid ) ) );
		$this->assertNotFalse( $result );
		$this->assertInternalType( 'array', $result );
		$this->assertEquals( $valid, $result );
	}

	/**
	 * Test IP filter (#7).
	 */
	public function testIPFilter() {
		/* Prepare Options: 2 blacklisted IPs, disabled */
		StatifyBlacklist::$_options = array(
			'active_referer' => 0,
			'cron_referer'   => 0,
			'referer'        => array(),
			'active_ip'      => 0,
			'ip'             => array(
				'192.0.2.123',
				'2001:db8:a0b:12f0::1'
			),
			'version'        => StatifyBlacklist::VERSION_MAIN
		);

		/* No multisite */
		StatifyBlacklist::$multisite = false;

		/* Set matching IP */
		$_SERVER['REMOTE_ADDR'] = '192.0.2.123';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		/* Activate filter */
		StatifyBlacklist::$_options['active_ip'] = 1;
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
		/* Try matching v6 address */
		$_SERVER['REMOTE_ADDR'] = '2001:db8:a0b:12f0::1';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
		/* Non-matching addresses */
		$_SERVER['REMOTE_ADDR'] = '192.0.2.234';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		$_SERVER['REMOTE_ADDR'] = '2001:db8:a0b:12f0::2';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		/* Subnet matching */
		StatifyBlacklist::$_options['ip'] = array(
			'192.0.2.0/25',
			'2001:db8:a0b:12f0::/96'
		);
		$_SERVER['REMOTE_ADDR']           = '192.0.2.123';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
		$_SERVER['REMOTE_ADDR'] = '192.0.2.234';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		$_SERVER['REMOTE_ADDR'] = '2001:db8:a0b:12f0::5';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
		$_SERVER['REMOTE_ADDR'] = '2001:db8:a0b:12f0:0:1111::1';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );

		/* Filter using proxy header */
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
	 */
	public function testTargetFilter() {
		/* Prepare Options: 2 blacklisted domains, disabled */
		StatifyBlacklist::$_options = array(
			'active_referer' => 0,
			'cron_referer'   => 0,
			'referer'        => array(
				'example.com' => 0,
				'example.net' => 1
			),
			'referer_regexp' => 0,
			'active_target'  => 0,
			'cron_target'    => 0,
			'target'         => array(
				'/excluded/page/' => 0,
				'/?page_id=3'     => 1
			),
			'target_regexp'  => 0,
			'active_ip'      => 0,
			'ip'             => array(),
			'version'        => StatifyBlacklist::VERSION_MAIN
		);

		/* No multisite */
		StatifyBlacklist::$multisite = false;

		/* Empty target */
		unset( $_SERVER['REQUEST_URI'] );
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		/* Non-blacklisted targets */
		$_SERVER['REQUEST_URI'] = '';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		$_SERVER['REQUEST_URI'] = '/';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		$_SERVER['REQUEST_URI'] = '/?page_id=1';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		/* Blacklisted referer */
		$_SERVER['REQUEST_URI'] = '/excluded/page/';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		$_SERVER['REQUEST_URI'] = '/?page_id=3';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );

		/* Activate filter and run tests again */
		StatifyBlacklist::$_options['active_target'] = 1;

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

	/**
	 * Test target filter using regular expressions.
	 */
	public function testTargetRegexFilter() {
		/* Prepare Options: 2 regular expressions */
		StatifyBlacklist::$_options = array(
			'active_referer' => 1,
			'cron_referer'   => 0,
			'referer'        => array(
				'example.[a-z]+' => 0,
				'test'           => 1
			),
			'referer_regexp' => 1,
			'version'        => 1.3
		);

		/* No multisite */
		StatifyBlacklist::$multisite = false;

		/* No referer */
		unset( $_SERVER['HTTP_REFERER'] );
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		/* Non-blacklisted referer */
		$_SERVER['HTTP_REFERER'] = 'http://not.evil';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );
		/* Blacklisted referer */
		$_SERVER['HTTP_REFERER'] = 'http://example.com';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
		/* Blacklisted referer with path */
		$_SERVER['HTTP_REFERER'] = 'http://foobar.net/test/me';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
		/* Matching both */
		$_SERVER['HTTP_REFERER'] = 'http://example.net/test/me';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
		/* Mathinc with wrong case */
		$_SERVER['HTTP_REFERER'] = 'http://eXaMpLe.NeT/tEsT/mE';
		$this->assertNull( StatifyBlacklist::apply_blacklist_filter() );

		/* Set RegExp filter to case insensitive */
		StatifyBlacklist::$_options['referer_regexp'] = 2;
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
	}
}

/**
 * Helper for testing inaccessible static methods
 */
function invokeStatic( $class, $methodName, $parameters = array() ) {
	$reflection = new \ReflectionClass( $class );
	$method     = $reflection->getMethod( $methodName );
	$method->setAccessible( true );

	return $method->invokeArgs( null, $parameters );
}


/* Some mocked functions */

$mock_options   = array();
$mock_multisite = false;


function is_multisite() {
	global $mock_multisite;

	return $mock_multisite;
}

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

function get_option( $option, $default = false ) {
	global $mock_options;

	return isset( $mock_options[ $option ] ) ? $mock_options[ $option ] : $default;
}

function update_option( $option, $value, $autoload = null ) {
	global $mock_options;
	$mock_options[ $option ] = $value;
}

function wp_unslash ( $value ) {
	return is_string( $value ) ? stripslashes( $value ) : $value;
}