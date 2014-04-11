<?php
/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */

namespace OCA\Notes\Controller;

use \OCP\IRequest;
use \OCP\AppFramework\Http\TemplateResponse;

use \OCA\Notes\App\Notes;
use \OCA\Notes\Service\NoteDoesNotExistException;
use \OCA\Notes\Utility\ControllerTestUtility;


class PageControllerTest extends ControllerTestUtility {

	private $container;

	/**
	 * Gets run before each test
	 */
	public function setUp(){
		// use the container to test to check if its wired up correctly and
		// replace needed components with mocks
		$notes = new Notes();
		$this->container = $notes->getContainer();
		$this->container['API'] = $this->getMockBuilder(
			'\OCA\Notes\Core\API')
			->disableOriginalConstructor()
			->getMock();
		$this->container['NotesService'] = $this->getMockBuilder(
			'\OCA\Notes\Service\NotesService')
			->disableOriginalConstructor()
			->getMock();
		$this->container['Request'] = new Request();
	}


	public function testIndexAnnotations(){
		$annotations = array('NoAdminRequired', 'NoCSRFRequired');
		$this->assertAnnotations($this->container['PageController'], 'index', $annotations);
	}


	public function testIndexReturnsTemplate(){
		$result = $this->container['PageController']->index();
		$this->assertTrue($result instanceof TemplateResponse);
	}


	public function testIndexShouldSendTheCorrectTemplate(){
		$this->container['API']->expects($this->once())
			->method('getUserValue')
			->with($this->equalTo('notesLastViewedNote'))
			->will($this->returnValue('3'));
		$result = $this->container['PageController']->index();

		$this->assertEquals('main', $result->getTemplateName());
		$this->assertEquals(array('lastViewedNote' => 3), $result->getParams());
	}


	public function testIndexShouldSendZeroWhenNoLastViewedNote(){
		$this->container['API']->expects($this->once())
			->method('getUserValue')
			->with($this->equalTo('notesLastViewedNote'))
			->will($this->returnValue(''));
		$result = $this->container['PageController']->index();

		$this->assertEquals(array('lastViewedNote' => 0), $result->getParams());
	}


	public function testIndexShouldSetZeroWhenLastViewedNotDoesNotExist(){
		$this->container['API']->expects($this->once())
			->method('getUserValue')
			->with($this->equalTo('notesLastViewedNote'))
			->will($this->returnValue('3'));
		$this->container['NotesService']->expects($this->once())
			->method('get')
			->with($this->equalTo(3))
			->will($this->throwException(new NoteDoesNotExistException('hi')));
		$result = $this->container['PageController']->index();

		$this->assertEquals(array('lastViewedNote' => 0), $result->getParams());
	}


}