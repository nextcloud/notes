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

	protected function setUp() {
		$notes = new Application();
		$this->container = $notes->getContainer();
		$this->user = 'john';

		$test = $this;

		$this->container->registerService('L10N', function ($c) use ($test) {
			return $test->getMockBuilder(
				'\OCP\IL10N')
				->disableOriginalConstructor()
				->getMock();
		});

		$this->container->registerService('Filesystem', function ($c) use ($test) {
			return $test->getMock('Filesystem',
				array(
					'getDirectoryContent',
					'unlink',
					'file_get_contents',
					'getFileInfo',
					'file_exists',
					'rename',
					'file_put_contents',
					'getPath'
				)
			);
		});

		$this->container->registerService('CoreConfig', function ($c) use ($test) {
			return $test->getMockBuilder(
				'\OCP\IConfig')
				->disableOriginalConstructor()
				->getMock();
		});

		$this->container->registerService('Request', function ($c) use ($test) {
			return $test->getRequest();
		});

		$this->container->registerParameter('UserId', $this->user);
	}


	/**
	 * @param array $params a hashmap with the parameters for request
	 */
	protected function setRequest(array $params=array()) {
		$mock = $this->getMockBuilder('\OCP\IRequest')
			->getMock();
		$merged = array();
		foreach ($params as $key => $value) {
			$merged = array_merge($value, $merged);
		}
		$mock->expects($this->any())
			->method('getParam')
			->will($this->returnCallback(function($index, $default) use ($merged) {
				if (array_key_exists($index, $merged)) {
					return $merged[$index];
				} else {
					return $default;
				}
			}));
		// attribute access
		if(array_key_exists('server', $params)) {
			$mock->server = $params['server'];
		}

		$test = $this;
		$this->container->registerService('Request', function ($c) use ($test) {
			return $mock;
		});
	}


}