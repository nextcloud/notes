<?php
/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */

namespace OCA\Notes\Controller;

use \OCA\AppFramework\Http\Request;
use \OCA\AppFramework\Http\JSONResponse;
use \OCA\AppFramework\Utility\ControllerTestUtility;


require_once(__DIR__ . "/../classloader.php");


class NotesControllerTest extends ControllerTestUtility {

	private $api;
	private $request;
	private $controller;
	private $bizLayer;


	/**
	 * Gets run before each test
	 */
	public function setUp(){
		$this->api = $this->getAPIMock();
		$this->bizLayer = $this->getMockBuilder(
			'\OCA\Notes\BusinessLayer\NotesBusinessLayer')
			->disableOriginalConstructor()
			->getMock();
		$this->request = new Request();
		$this->controller = 
			new NotesController($this->api, $this->request, $this->bizLayer);
	}


	public function testGetAllAnnotations(){
		$annotations = array('IsAdminExemption', 'IsSubAdminExemption', 'Ajax');
		$this->assertAnnotations($this->controller, 'getAll', $annotations);
	}


	public function testGetAllReturnsJSON(){
		$result = $this->controller->getAll();
		$this->assertTrue($result instanceof JSONResponse);
	}


	public function testCallsNotesBizLayer(){
		$expected = array(
			'notes' => array('hi')
		);
		$this->bizLayer->expects($this->once())
			->method('getAllNotes')
			->will($this->returnValue($expected['notes']));

		$result = $this->controller->getAll();
		$params = $result->getParams();

		$this->assertEquals($expected, $params);
	}


	public function testGetAnnotations(){
		$annotations = array('IsAdminExemption', 'IsSubAdminExemption', 'Ajax');
		$this->assertAnnotations($this->controller, 'get', $annotations);
	}


	public function testGetReturnsJSON(){
		$result = $this->controller->get();
		$this->assertTrue($result instanceof JSONResponse);			
	}


	public function testGetCallsBizLayer(){
		$expected = array(
			'notes' => array('hi')
		);
		$getParams = array('id' => '3');
		$request = new Request(array('get' => $getParams));
		$this->controller = new NotesController($this->api, $request, 
			                                    $this->bizLayer);
		$this->bizLayer->expects($this->once())
			->method('getNote')
			->with($this->equalTo(3))
			->will($this->returnValue($expected['notes'][0]));

		$result = $this->controller->get();
		$params = $result->getParams();

		$this->assertEquals($expected, $params);
	}


	public function testSaveAnnotations(){
		$annotations = array('IsAdminExemption', 'IsSubAdminExemption', 'Ajax');
		$this->assertAnnotations($this->controller, 'save', $annotations);
	}


	public function testSaveReturnsJSON(){
		$result = $this->controller->save();
		$this->assertTrue($result instanceof JSONResponse);			
	}


	public function testSaveCallsBizLayer(){
		$postParams = array(
			'oldTitle' => 'tests',
			'newTitle' => 'tests2',
			'content' => 'cont'
		);
		$request = new Request(array('post' => $postParams));
		$this->controller = new NotesController($this->api, $request, 
			                                    $this->bizLayer);

		$this->bizLayer->expects($this->once())
			->method('saveNote')
			->with($this->equalTo($postParams['oldTitle']),
				$this->equalTo($postParams['newTitle']),
				$this->equalTo($postParams['content']));
		$result = $this->controller->save();
	}


	public function testDeleteAnnotations(){
		$annotations = array('IsAdminExemption', 'IsSubAdminExemption', 'Ajax');
		$this->assertAnnotations($this->controller, 'delete', $annotations);
	}


	public function testDeleteReturnsJSON(){
		$result = $this->controller->delete();
		$this->assertTrue($result instanceof JSONResponse);			
	}


	public function testDeleteCallsBizLayer(){
		$postParams = array(
			'id' => '3'
		);
		$request = new Request(array('post' => $postParams));
		$this->controller = new NotesController($this->api, $request, 
			                                    $this->bizLayer);

		$this->bizLayer->expects($this->once())
			->method('deleteNote')
			->with($this->equalTo(3));
		$result = $this->controller->delete();
	}

}