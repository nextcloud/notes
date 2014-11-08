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

namespace OCA\Notes\Controller;

use \OCP\IRequest;
use \OCP\AppFramework\Http\TemplateResponse;

use \OCA\Notes\Service\NoteDoesNotExistException;

class PageControllerTest extends \OCA\Notes\Tests\Unit\NotesUnitTest {


	/**
	 * Gets run before each test
	 */
	public function setUp(){
		// use the container to test to check if its wired up correctly and
		// replace needed components with mocks
		parent::setUp();
		$test = &$this;
		$this->container->registerService('NotesService', function ($c) use ($test) {
			return $test->getMockBuilder(
				'\OCA\Notes\Service\NotesService')
				->disableOriginalConstructor()
				->getMock();
		});
	}


	public function testIndexReturnsTemplate(){
		$result = $this->container->query('PageController')->index();
		$this->assertTrue($result instanceof TemplateResponse);
	}


	public function testIndexShouldSendTheCorrectTemplate(){
		$this->container->query('CoreConfig')->expects($this->once())
			->method('getUserValue')
			->with($this->equalTo($this->container->query('UserId')),
				$this->equalTo($this->container->query('AppName')),
				$this->equalTo('notesLastViewedNote'))
			->will($this->returnValue('3'));
		$result = $this->container->query('PageController')->index();

		$this->assertEquals('main', $result->getTemplateName());
		$this->assertEquals(array('lastViewedNote' => 3), $result->getParams());
	}


	public function testIndexShouldSendZeroWhenNoLastViewedNote(){
		$this->container->query('CoreConfig')->expects($this->once())
			->method('getUserValue')
			->with($this->equalTo($this->container->query('UserId')),
				$this->equalTo($this->container->query('AppName')),
				$this->equalTo('notesLastViewedNote'))
			->will($this->returnValue(''));
		$result = $this->container->query('PageController')->index();

		$this->assertEquals(array('lastViewedNote' => 0), $result->getParams());
	}


	public function testIndexShouldSetZeroWhenLastViewedNotDoesNotExist(){
		$this->container->query('CoreConfig')->expects($this->once())
			->method('getUserValue')
			->with($this->equalTo($this->container->query('UserId')),
				$this->equalTo($this->container->query('AppName')),
				$this->equalTo('notesLastViewedNote'))
			->will($this->returnValue('3'));
		$this->container->query('NotesService')->expects($this->once())
			->method('get')
			->with($this->equalTo(3))
			->will($this->throwException(new NoteDoesNotExistException('hi')));
		$result = $this->container->query('PageController')->index();

		$this->assertEquals(array('lastViewedNote' => 0), $result->getParams());
	}


}