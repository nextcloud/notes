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


class PageControllerTest extends ControllerTestUtility {

	private $api;
	private $request;
	private $controller;


	/**
	 * Gets run before each test
	 */
	public function setUp(){
		$this->api = $this->getAPIMock();
		$this->request = new Request();
		$this->controller = 
			new PageController($this->api, $this->request);
	}


	public function testIndexAnnotations(){
		$annotations = array('IsAdminExemption', 'IsSubAdminExemption', 'CSRFExemption');
		$this->assertAnnotations($this->controller, 'index', $annotations);
	}


	public function testIndexReturnsTemplate(){
		$result = $this->controller->index();
		$this->assertTrue($result instanceof TemplateResponse);
	}


	public function testIndexShouldSendTheCorrectTemplate(){
		$result = $this->controller->index();
		$this->assertEquals('main', $result->getTemplateName());				
	}


}