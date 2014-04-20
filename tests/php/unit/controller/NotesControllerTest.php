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
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\AppFramework\Http;

use \OCA\Notes\App\Notes;
use \OCA\Notes\Service\NoteDoesNotExistException;
use \OCA\Notes\Utility\ControllerTestUtility;

class NotesControllerTest extends ControllerTestUtility {

	private $container;


	/**
	 * Gets run before each test
	 */
	public function setUp(){
		// use the container to test to check if its wired up correctly and
		// replace needed components with mocks
		$notes = new Notes();
		$this->container = $notes->getContainer();
		$this->container['UserId'] = 'john';
		$this->container['CoreConfig'] = $this->getMockBuilder(
			'\OCP\IConfig')
			->disableOriginalConstructor()
			->getMock();
		$this->container['Request'] = $this->getRequest();
		$this->container['NotesService'] = $this->getMockBuilder(
			'\OCA\Notes\Service\NotesService')
			->disableOriginalConstructor()
			->getMock();
	}


	private function assertDefaultAJAXAnnotations ($method) {
		$annotations = array('NoAdminRequired');
		$this->assertAnnotations($this->container['NotesController'],
			$method, $annotations);
	}


	/**
	 * GET /notes/
	 */
	public function testGetAllAnnotations(){
		$this->assertDefaultAJAXAnnotations('index');
	}


	public function testGetAll(){
		$expected = array(
			'hi'
		);

		$this->container['NotesService']
			->expects($this->once())
			->method('getAll')
			->will($this->returnValue($expected));

		$response = $this->container['NotesController']->index();

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

		$this->container['Request'] = $this->getRequest(array(
			'urlParams' => array('id' => $id)
		));
		$this->container['CoreConfig']->expects($this->once())
			->method('setUserValue')
			->with($this->equalTo($this->container['UserId']),
				$this->equalTo($this->container['AppName']),
				$this->equalTo('notesLastViewedNote'),
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

		$this->container['Request'] = $this->getRequest(array(
			'urlParams' => array('id' => $id)
		));
		$this->container['CoreConfig']->expects($this->once())
			->method('setUserValue')
			->with($this->equalTo($this->container['UserId']),
				$this->equalTo($this->container['AppName']),
				$this->equalTo('notesLastViewedNote'),
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
	 * GET /config
	 */
	public function testGetConfigAnnotations(){
		$this->assertDefaultAJAXAnnotations('getConfig');
	}

	public function testGetConfig(){
		$expected = array(
			'markdown' => false
		);

		$this->container['CoreConfig']->expects($this->once())
			->method('getUserValue')
			->with($this->equalTo($this->container['UserId']),
				$this->equalTo($this->container['AppName']),
				$this->equalTo('notesMarkdown'))
			->will($this->returnValue('0'));

		$response = $this->container['NotesController']->getConfig();

		$this->assertEquals($expected, $response->getData());
		$this->assertTrue($response instanceof JSONResponse);
	}


	/**
	 * POST /config
	 */
	public function testSetConfigAnnotations(){
		$this->assertDefaultAJAXAnnotations('setConfig');
	}

	public function testSetConfig(){
		$this->container['Request'] = $this->getRequest(array(
			'post' => array('markdown' => true)
		));

		$this->container['CoreConfig']->expects($this->once())
			->method('setUserValue')
			->with($this->equalTo($this->container['UserId']),
				$this->equalTo($this->container['AppName']),
				$this->equalTo('notesMarkdown'), $this->equalTo(true));

		$response = $this->container['NotesController']->setConfig();

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

		$this->container['Request'] = $this->getRequest(array(
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

		$this->container['Request'] = $this->getRequest(array(
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
		$this->assertDefaultAJAXAnnotations('destroy');
	}


	public function testDelete(){
		$id = 1;

		$this->container['Request'] = $this->getRequest(array(
			'urlParams' => array('id' => $id)
		));
		$this->container['NotesService']
			->expects($this->once())
			->method('delete');

		$response = $this->container['NotesController']->destroy();

		$this->assertTrue($response instanceof JSONResponse);
	}


	public function testDeleteDoesNotExist(){
		$id = 1;

		$this->container['Request'] = $this->getRequest(array(
			'urlParams' => array('id' => $id)
		));
		$this->container['NotesService']
			->expects($this->once())
			->method('delete')
			->will($this->throwException(new NoteDoesNotExistException()));

		$response = $this->container['NotesController']->destroy();

		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
		$this->assertTrue($response instanceof JSONResponse);
	}


}
