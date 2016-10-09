<?php

const ABSPATH = false;
require_once( '../inc/statifyblacklist.class.php' );

/**
 * Class StatifyBlacklistTest
 *
 * PHPUnit test class for StatifyBlacklist
 */
class StatifyBlacklistTest extends PHPUnit_Framework_TestCase {

	public function testFilter() {
		/* Prepare Options: 2 blacklisted domains, disabled */
		StatifyBlacklist::$_options = array(
			'active_referer' => 0,
			'cron_referer'   => 0,
			'referer'        => array(
				'example.com' => 0,
				'example.net' => 1
			)
		);

		/* No multisite */
		StatifyBlacklist::$multisite = false;

		/* No referer */
		unset( $_SERVER['HTTP_REFERER'] );
		$this->assertFalse( StatifyBlacklist::apply_blacklist_filter() );
		/* Non-blacklisted referer */
		$_SERVER['HTTP_REFERER'] = 'http://example.org';
		$this->assertFalse( StatifyBlacklist::apply_blacklist_filter() );
		/* Blacklisted referer */
		$_SERVER['HTTP_REFERER'] = 'http://example.com';
		$this->assertFalse( StatifyBlacklist::apply_blacklist_filter() );
		/* Blacklisted referer with path */
		$_SERVER['HTTP_REFERER'] = 'http://example.net/foo/bar.html';
		$this->assertFalse( StatifyBlacklist::apply_blacklist_filter() );

		/* Activate filter and run tests again */
		StatifyBlacklist::$_options['active_referer'] = 1;

		unset( $_SERVER['HTTP_REFERER'] );
		$this->assertFalse( StatifyBlacklist::apply_blacklist_filter() );

		$_SERVER['HTTP_REFERER'] = 'http://example.org';
		$this->assertFalse( StatifyBlacklist::apply_blacklist_filter() );

		$_SERVER['HTTP_REFERER'] = 'http://example.com';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );

		$_SERVER['HTTP_REFERER'] = 'http://example.net/foo/bar.html';
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
	}

	public function testRegexFilter() {
		/* Prepare Options: 2 regular expressions */
		StatifyBlacklist::$_options = array(
			'active_referer' => 1,
			'cron_referer'   => 0,
			'referer'        => array(
				'example.[a-z]+' => 0,
				'test' => 1
			),
			'referer_regexp' => 1
		);

		/* No multisite */
		StatifyBlacklist::$multisite = false;

		/* No referer */
		unset( $_SERVER['HTTP_REFERER'] );
		$this->assertFalse( StatifyBlacklist::apply_blacklist_filter() );
		/* Non-blacklisted referer */
		$_SERVER['HTTP_REFERER'] = 'http://not.evil';
		$this->assertFalse( StatifyBlacklist::apply_blacklist_filter() );
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
		$this->assertFalse( StatifyBlacklist::apply_blacklist_filter() );

		/* Set RegExp filter to case insensitive */
		StatifyBlacklist::$_options['referer_regexp'] = 2;
		$this->assertTrue( StatifyBlacklist::apply_blacklist_filter() );
	}

}