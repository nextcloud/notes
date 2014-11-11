<?php
/**
 * ownCloud - Notes
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @copyright Bernhard Posselt 2012, 2014
 */

namespace OCA\Notes\Tests\Unit;

use \OCA\Notes\AppInfo\Application;


class NotesUnitTest extends \PHPUnit_Framework_TestCase {

	protected $container;
	protected $user;

	public function setUp() {
		$notes = new Application();
		$this->container = $notes->getContainer();
		$this->user = 'john';

		$test = &$this;

		$this->container->registerService('L10N', function ($c) use ($test) {
			return $test->getMockBuilder(
				'\OCP\IL10N')
				->disableOriginalConstructor()
				->getMock();
		});

		$this->container->registerService('CoreConfig', function ($c) use ($test) {
			return $test->getMockBuilder(
				'\OCP\IConfig')
				->disableOriginalConstructor()
				->getMock();
		});

		$this->container->registerService('Request', function ($c) use ($test) {
			return $test->getMockBuilder(
				'\OCP\IRequest')
				->disableOriginalConstructor()
				->getMock();
		});

		$this->container->registerParameter('UserId', $this->user);

		$this->container->registerService('FileSystem', function ($c) use ($test) {
			return $test->getMock('Filesystem',
				array(
					'getDirectoryContent',
					'unlink',
					'file_get_contents',
					'getFileInfo',
					'file_exists',
					'rename',
					'file_put_contents',
					'getPath',
					'filemtime'
				)
			);
		});
	}


}