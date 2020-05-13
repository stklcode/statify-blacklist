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
 * @version   1.5.0
 */

use Robo\Exception\TaskException;
use Robo\Tasks;

/**
 * Class RoboFile
 */
class RoboFile extends Tasks {
	const PROJECT_NAME = 'statify-blacklist';
	const SVN_URL      = 'https://plugins.svn.wordpress.org/statify-blacklist';

	const OPT_TARGET    = 'target';
	const OPT_SKIPTEST  = 'skipTests';
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
	public function clean( $opts = [ self::OPT_TARGET => 'dist' ] ) {
		$this->say( 'Cleaning target directory...' );
		if ( is_dir( $this->target_dir . '/' . $this->final_name ) ) {
			$this->_deleteDir( [ $this->target_dir . '/' . $this->final_name ] );
		}
		if ( is_file( $this->target_dir . '/' . $this->final_name . '.zip' ) ) {
			$this->_remove( $this->target_dir . '/' . $this->final_name . '.zip' );
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
		$this->clean( $opts );
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
		$this->taskCopyDir(
			[
				'inc'   => $this->target_dir . '/' . $this->final_name . '/inc',
				'views' => $this->target_dir . '/' . $this->final_name . '/views',
			]
		)->run();
		$this->_copy( 'statify-blacklist.php', $this->target_dir . '/' . $this->final_name . '/statify-blacklist.php' );
		$this->_copy( 'LICENSE.md', $this->target_dir . '/' . $this->final_name . '/LICENSE.md' );
		$this->_copy( 'README.md', $this->target_dir . '/' . $this->final_name . '/README.md' );

		// Remove content before title (e.g. badges) from README file.
		$this->taskReplaceInFile( $this->target_dir . '/' . $this->final_name . '/README.md' )
			->regex( '/^[^\\#]*/' )
			->to( '' )
			->run();
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
		$this->build( $opts );
		$this->say( 'Packaging...' );
		$this->taskPack( $this->target_dir . '/' . $this->final_name . '.zip' )
			->addDir( '', $this->target_dir . '/' . $this->final_name )
			->run();
	}

	/**
	 * Deploy development version (trunk).
	 *
	 * @param array $opts Options.
	 *
	 * @return void
	 * @throws TaskException On errors.
	 */
	public function deployTrunk(
		$opts = [
			self::OPT_TARGET    => 'dist',
			self::OPT_SKIPTEST  => false,
			self::OPT_SKIPSTYLE => false,
		]
	) {
		// First execute build job.
		$this->build( $opts );

		// Prepare VCS, either checkout or update local copy.
		$this->prepareVCS();

		$this->say( 'Preparing deployment directory...' );
		$this->updateVCStrunk();

		// Update remote repository.
		$this->say( 'Deploying...' );
		$this->commitVCS(
			'--force trunk/*',
			'Updated ' . self::PROJECT_NAME . ' trunk'
		);
	}

	/**
	 * Deploy current version tag.
	 *
	 * @param array $opts Options.
	 *
	 * @return void
	 * @throws TaskException On errors.
	 */
	public function deployTag(
		$opts = [
			self::OPT_TARGET    => 'dist',
			self::OPT_SKIPTEST  => false,
			self::OPT_SKIPSTYLE => false,
		]
	) {
		// First execute build job.
		$this->build( $opts );

		// Prepare VCS, either checkout or update local copy.
		$this->prepareVCS();

		$this->say( 'Preparing deployment directory...' );
		$this->updateVCStag();

		// Update remote repository.
		$this->say( 'Deploying...' );
		$this->commitVCS(
			'tags/' . $this->version,
			'Updated ' . self::PROJECT_NAME . ' v' . $this->version
		);
	}

	/**
	 * Deploy current version tag.
	 *
	 * @param array $opts Options.
	 *
	 * @return void
	 * @throws TaskException On errors.
	 */
	public function deployReadme(
		$opts = [
			self::OPT_TARGET    => 'dist',
			self::OPT_SKIPTEST  => false,
			self::OPT_SKIPSTYLE => false,
		]
	) {
		// First execute build job.
		$this->build( $opts );

		// Prepare VCS, either checkout or update local copy.
		$this->prepareVCS();

		$this->updateVCSreadme();

		// Update remote repository.
		$this->say( 'Deploying...' );
		$this->commitVCS(
			'--force trunk/README.md',
			'Updated ' . self::PROJECT_NAME . ' ReadMe'
		);
	}

	/**
	 * Deploy current version tag and trunk.
	 *
	 * @param array $opts Options.
	 *
	 * @return void
	 * @throws TaskException On errors.
	 */
	public function deployAll(
		$opts = [
			self::OPT_TARGET    => 'dist',
			self::OPT_SKIPTEST  => false,
			self::OPT_SKIPSTYLE => false,
		]
	) {
		// First execute build job.
		$this->build( $opts );

		// Prepare VCS, either checkout or update local copy.
		$this->prepareVCS();

		$this->say( 'Preparing deployment directory...' );
		$this->updateVCStrunk();
		$this->updateVCStag();

		// Update remote repository.
		$this->say( 'Deploying...' );
		$this->commitVCS(
			[
				'--force trunk/*',
				'--force tags/' . $this->version,
			],
			'Updated ' . self::PROJECT_NAME . ' v' . $this->version
		);
	}

	/**
	 * Prepare VCS direcory.
	 *
	 * Checkout or update local copy of SVN repository.
	 *
	 * @return void
	 * @throws TaskException On errors.
	 */
	private function prepareVCS() {
		if ( is_dir( $this->target_dir . '/svn' ) ) {
			$this->taskSvnStack()
				->stopOnFail()
				->dir( $this->target_dir . '/svn/statify-blacklist' )
				->update()
				->run();
		} else {
			$this->_mkdir( $this->target_dir . '/svn' );
			$this->taskSvnStack()
				->dir( $this->target_dir . '/svn' )
				->checkout( self::SVN_URL )
				->run();
		}
	}

	/**
	 * Commit VCS changes
	 *
	 * @param string|array $to_add Files to add.
	 * @param string       $msg    Commit message.
	 *
	 * @return void
	 * @throws TaskException On errors.
	 */
	private function commitVCS( $to_add, $msg ) {
		$task = $this->taskSvnStack()
					->stopOnFail()
					->dir( $this->target_dir . '/svn/statify-blacklist' );

		if ( is_array( $to_add ) ) {
			foreach ( $to_add as $ta ) {
				$task = $task->add( $ta );
			}
		} else {
			$task = $task->add( $to_add );
		}

		$task->commit( $msg )->run();
	}

	/**
	 * Update SVN readme file.
	 *
	 * @return void
	 */
	private function updateVCSreadme() {
		$trunk_dir = $this->target_dir . '/svn/statify-blacklist/trunk';
		$this->_copy( $this->target_dir . '/' . $this->final_name . '/README.md', $trunk_dir . '/README.md' );
	}

	/**
	 * Update SVN development version (trunk).
	 *
	 * @return void
	 */
	private function updateVCStrunk() {
		// Clean trunk directory.
		$trunk_dir = $this->target_dir . '/svn/statify-blacklist/trunk';
		$this->taskCleanDir( $trunk_dir )->run();

		// Copy built bundle to trunk.
		$this->taskCopyDir( [ $this->target_dir . '/' . $this->final_name => $trunk_dir ] )->run();
	}

	/**
	 * Update current SVN version tag.
	 *
	 * @return void
	 */
	private function updateVCStag() {
		// Clean tag directory if it exists.
		$tag_dir = $this->target_dir . '/svn/statify-blacklist/tags/' . $this->version;
		if ( is_dir( $tag_dir ) ) {
			$this->taskCleanDir( $this->target_dir . '/svn/statify-blacklist/tags/' . $this->version )->run();
		} else {
			$this->_mkdir( $tag_dir );
		}

		// Copy built bundle to trunk.
		$this->taskCopyDir( [ $this->target_dir . '/' . $this->final_name => $tag_dir ] )->run();
	}
}
