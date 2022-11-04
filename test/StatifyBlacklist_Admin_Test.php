<?php
/**
 * Statify Filter: Unit Test
 *
 * This is a PHPunit test class for the plugin's functionality
 *
 * @package Statify_Blacklist
 */

/**
 * Class StatifyBlacklist_Admin_Test.
 *
 * PHPUnit test class for StatifyBlacklist_Admin.
 */
class StatifyBlacklist_Admin_Test extends PHPUnit\Framework\TestCase {


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
			'2001:DB8:A0B:12F0::/64',
			'fe80::7645:6de2:ff:1',
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
		$result  = invoke_static( StatifyBlacklist_Admin::class, 'sanitize_ips', array( array_merge( $valid, $invalid ) ) );
		$this->assertNotFalse( $result );
		if ( method_exists( $this, 'assertIsArray' ) ) {
			$this->assertIsArray( $result );
		} else {
			$this->assertInternalType( 'array', $result );
		}
		$this->assertEquals( array_map( 'strtolower', $valid ), $result );
	}
}
