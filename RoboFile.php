<?php
/**
 * Statify Blacklist Robo build script.
 *
 * This file contains the Robo tasks for building a distributable plugin package.
 * Should not be included in final package.
 *
 * @author    Stefan Kalscheuer <stefan@stklcode.de>
 *
 * @package   Statify_Blacklist
 * @version   1.4.2
 */

use Robo\Tasks;

/**
 * Class RoboFile
 */
class RoboFile extends Tasks {
	const PROJECT_NAME = 'statify-blacklist';

	const OPT_TARGET = 'target';
	const OPT_SKIPTEST = 'skipTests';
	const OPT_SKIPSTYLE = 'skipStyle';

	/**
	 * Version tag (read from composer.json).
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Target directory path.
	 *
	 * @var string
	 */
	private $target_dir;

	/**
	 * Final package name.
	 *
	 * @var string
	 */
	private $final_name;

	/**
	 * RoboFile constructor
	 *
	 * @param array $opts Options.
	 *
	 * @return void
	 */
	public function __construct( $opts = [ self::OPT_TARGET => 'dist' ] ) {
		// Read composer configuration and extract version number..
		$composer = json_decode( file_get_contents( __DIR__ . '/composer.json' ) );
		// Extract parameter from options.
		$this->version    = $composer->version;
		$this->target_dir = $opts[ self::OPT_TARGET ];
		$this->final_name = self::PROJECT_NAME . '.' . $this->version;
	}

	/**
	 * Clean up target directory
	 *
	 * @param array $opts Options.
	 *
	 * @return void
	 */
	public function clean(
		$opts = [
			self::OPT_TARGET => 'dist'
		]
	) {
		$this->say( 'Cleaning target directory...' );
		if ( is_dir( $this->target_dir ) ) {
			$this->taskCleanDir( [ $this->target_dir ] )->run();
		}
	}

	/**
	 * Run PHPUnit tests
	 *
	 * @return void
	 */
	public function test() {
		$this->say( 'Executing PHPUnit tests...' );
		$this->taskPhpUnit()->configFile( __DIR__ . '/phpunit.xml' )->run();
	}

	/**
	 * Run code style tests
	 *
	 * @return void
	 */
	public function testCS() {
		$this->say( 'Executing PHPCS tests...' );
		$this->_exec( __DIR__ . '/vendor/bin/phpcs --standard=phpcs.xml -s' );
	}

	/**
	 * Build a distributable bundle.
	 *
	 * @param array $opts Options.
	 *
	 * @return void
	 */
	public function build(
		$opts = [
			self::OPT_TARGET    => 'dist',
			self::OPT_SKIPTEST  => false,
			self::OPT_SKIPSTYLE => false,
		]
	) {
		$this->clean($opts);
		if ( isset( $opts[ self::OPT_SKIPTEST ] ) && true === $opts[ self::OPT_SKIPTEST ] ) {
			$this->say( 'Tests skipped' );
		} else {
			$this->test();
		}
		if ( isset( $opts[ self::OPT_SKIPSTYLE ] ) && true === $opts[ self::OPT_SKIPSTYLE ] ) {
			$this->say( 'Style checks skipped' );
		} else {
			$this->testCS();
		}
		$this->bundle();
	}

	/**
	 * Bundle global resources.
	 *
	 * @return void
	 */
	private function bundle() {
		$this->say( 'Bundling resources...' );
		$this->taskCopyDir( [
			'inc'   => $this->target_dir . '/' . $this->final_name . '/inc',
			'views' => $this->target_dir . '/' . $this->final_name . '/views',
		] )->run();
		$this->_copy( 'statify-blacklist.php', $this->target_dir . '/' . $this->final_name . '/statify-blacklist.php' );
		$this->_copy( 'README.md', $this->target_dir . '/' . $this->final_name . '/README.md' );
		$this->_copy( 'LICENSE.md', $this->target_dir . '/' . $this->final_name . '/LICENSE.md' );
	}

	/**
	 * Create ZIP package from distribution bundle.
	 *
	 * @param array $opts Options.
	 *
	 * @return void
	 */
	public function package(
		$opts = [
			self::OPT_TARGET    => 'dist',
			self::OPT_SKIPTEST  => false,
			self::OPT_SKIPSTYLE => false,
		]
	) {
		$this->build($opts);
		$this->say( 'Packaging...' );
		$this->taskPack( $this->target_dir . '/' . $this->final_name . '.zip' )
			->addDir( '', $this->target_dir . '/' . $this->final_name )
			->run();
	}
}
