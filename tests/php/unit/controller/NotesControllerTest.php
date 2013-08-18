<?php
/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */

namespace OCA\Notes\Controller;

use \OCA\AppFramework\Http\Request;
use \OCA\AppFramework\Http\JSONResponse;
use \OCA\AppFramework\Http\Http;
use \OCA\AppFramework\Utility\ControllerTestUtility;

use \OCA\Notes\DependencyInjection\DIContainer;
use \OCA\Notes\Service\NoteDoesNotExistException;


class NotesControllerTest extends ControllerTestUtility {

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
		$this->container['NotesService'] = $this->getMockBuilder(
			'\OCA\Notes\Service\NotesService')
			->disableOriginalConstructor()
			->getMock();
	}


	private function assertDefaultAJAXAnnotations ($method) {
		$annotations = array('IsAdminExemption', 'IsSubAdminExemption', 'Ajax');
		$this->assertAnnotations($this->container['NotesController'],
			$method, $annotations);
	}


	/**
	 * GET /notes/
	 */
	public function testGetAllAnnotations(){
		$this->assertDefaultAJAXAnnotations('getAll');
	}


	public function testGetAll(){
		$expected = array(
			'hi'
		);

		$this->container['NotesService']
			->expects($this->once())
			->method('getAll')
			->will($this->returnValue($expected));

		$response = $this->container['NotesController']->getAll();

		$this->assertEquals($expected, $response->getData());
		$this->assertTrue($response instanceof JSONResponse);
	}


	/**
	 * GET /notes/1
	 */
	public function testGetAnnotations(){
		$this->assertDefaultAJAXAnnotations('get');
	}


	public function testGet(){
		$id = 1;
		$expected = array(
			'hi'
		);

		$this->container['Request'] = new Request(array(
			'urlParams' => array('id' => $id)
		));
		$this->container['API']->expects($this->once())
			->method('setUserValue')
			->with($this->equalTo('notesLastViewedNote'),
				$this->equalTo($id));

		$this->container['NotesService']
			->expects($this->once())
			->method('get')
			->with($this->equalTo($id))
			->will($this->returnValue($expected));

		$response = $this->container['NotesController']->get();

		$this->assertEquals($expected, $response->getData());
		$this->assertTrue($response instanceof JSONResponse);
	}


	public function testGetDoesNotExist(){
		$id = 1;
		$expected = array(
			'hi'
		);

		$this->container['Request'] = new Request(array(
			'urlParams' => array('id' => $id)
		));
		$this->container['API']->expects($this->once())
			->method('setUserValue')
			->with($this->equalTo('notesLastViewedNote'),
				$this->equalTo($id));

		$this->container['NotesService']
			->expects($this->once())
			->method('get')
			->with($this->equalTo($id))
			->will($this->throwException(new NoteDoesNotExistException()));

		$response = $this->container['NotesController']->get();

		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
		$this->assertTrue($response instanceof JSONResponse);
	}


	/**
	 * POST /notes
	 */
	public function testCreateAnnotations(){
		$this->assertDefaultAJAXAnnotations('create');
	}


	public function testCreate(){
		$expected = array(
			'hi'
		);

		$this->container['NotesService']
			->expects($this->once())
			->method('create')
			->will($this->returnValue($expected));

		$response = $this->container['NotesController']->create();

		$this->assertEquals($expected, $response->getData());
		$this->assertTrue($response instanceof JSONResponse);
	}


	/**
	 * PUT /notes/
	 */
	public function testUpdateAnnotations(){
		$this->assertDefaultAJAXAnnotations('update');
	}


	public function testUpdate(){
		$id = 1;
		$content = 'yo';
		$expected = array(
			'hi'
		);

		$this->container['Request'] = new Request(array(
			'urlParams' => array('id' => $id),
			'params' => array('content' => $content)
		));
		$this->container['NotesService']
			->expects($this->once())
			->method('update')
			->with($this->equalTo($id),	$this->equalTo($content))
			->will($this->returnValue($expected));

		$response = $this->container['NotesController']->update();

		$this->assertEquals($expected, $response->getData());
		$this->assertTrue($response instanceof JSONResponse);
	}


	public function testUpdateDoesNotExist(){
		$id = 1;
		$content = 'yo';

		$this->container['Request'] = new Request(array(
			'urlParams' => array('id' => $id),
			'params' => array('content' => $content)
		));
		$this->container['NotesService']
			->expects($this->once())
			->method('update')
			->with($this->equalTo($id),
				$this->equalTo($content))
			->will($this->throwException(new NoteDoesNotExistException()));

		$response = $this->container['NotesController']->update();

		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
		$this->assertTrue($response instanceof JSONResponse);
	}


	/**
	 * DELETE /notes/
	 */
	public function testDeleteAnnotations(){
		$this->assertDefaultAJAXAnnotations('delete');
	}


	public function testDelete(){
		$id = 1;

		$this->container['Request'] = new Request(array(
			'urlParams' => array('id' => $id)
		));
		$this->container['NotesService']
			->expects($this->once())
			->method('delete');

		$response = $this->container['NotesController']->delete();

		$this->assertTrue($response instanceof JSONResponse);
	}


		public function testDeleteDoesNotExist(){
		$id = 1;

		$this->container['Request'] = new Request(array(
			'urlParams' => array('id' => $id)
		));
		$this->container['NotesService']
			->expects($this->once())
			->method('delete')
			->will($this->throwException(new NoteDoesNotExistException()));

		$response = $this->container['NotesController']->delete();

		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
		$this->assertTrue($response instanceof JSONResponse);
	}


}
