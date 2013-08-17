<?php
/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */

namespace OCA\Notes\Controller;

use \OCA\AppFramework\Http\Request;
use \OCA\AppFramework\Http\TemplateResponse;
use \OCA\AppFramework\Utility\ControllerTestUtility;

use \OCA\Notes\DependencyInjection\DIContainer;


class PageControllerTest extends ControllerTestUtility {

	private $container;

	/**
	 * Gets run before each test
	 */
	public function setUp(){
		// use the container to test to check if its wired up correctly and
		// replace needed components with mocks
		$this->container = new DIContainer();
		$this->container['API'] = $this->getMockBuilder(
			'\OCA\AppFramework\Core\API')
			->disableOriginalConstructor()
			->getMock();
		$this->container['Request'] = new Request();
	}


	public function testIndexAnnotations(){
		$annotations = array('IsAdminExemption', 'IsSubAdminExemption', 'CSRFExemption');
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


}