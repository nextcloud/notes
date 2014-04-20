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
use \OCA\Notes\Db\Note;

class NotesApiControllerTest extends ControllerTestUtility {

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
		$this->container['Request'] = $this->getRequest();
		$this->container['NotesService'] = $this->getMockBuilder(
			'\OCA\Notes\Service\NotesService')
			->disableOriginalConstructor()
			->getMock();
	}


	private function assertDefaultAJAXAnnotations ($method) {
		$annotations = array('NoAdminRequired', 'API', 'NoCSRFRequired');
		$this->assertAnnotations($this->container['NotesApiController'],
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

		$response = $this->container['NotesApiController']->index();

		$this->assertEquals($expected, $response->getData());
		$this->assertTrue($response instanceof JSONResponse);
	}


	public function testGetAllHide(){
		$note1 = Note::fromFile(array(
			'fileid' => 3,
			'mtime' => 123,
			'name' => 'test',
			'content' => 'yo'
		));
		$note2 = Note::fromFile(array(
			'fileid' => 4,
			'mtime' => 111,
			'name' => 'abc',
			'content' => 'deee'
		));
		$notes = array(
			$note1, $note2	
		);

		$this->container['Request'] = $this->getRequest(array(
			'get' => array('exclude' => 'title,content')
		));

		$this->container['NotesService']
			->expects($this->once())
			->method('getAll')
			->will($this->returnValue($notes));

		$response = $this->container['NotesApiController']->index();

		$this->assertEquals(json_encode(array(
			array(
				'modified' => 123,
				'id' => 3,
			),
			array(
				'modified' => 111,
				'id' => 4,
			))
		), json_encode($response->getData()));
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

		$this->container['NotesService']
			->expects($this->once())
			->method('get')
			->with($this->equalTo($id))
			->will($this->returnValue($expected));

		$response = $this->container['NotesApiController']->get();

		$this->assertEquals($expected, $response->getData());
		$this->assertTrue($response instanceof JSONResponse);
	}

	public function testGetHide(){
		$note = Note::fromFile(array(
			'fileid' => 3,
			'mtime' => 123,
			'name' => 'test',
			'content' => 'yo'
		));

		$this->container['Request'] = $this->getRequest(array(
			'get' => array('exclude' => 'title,content')
		));

		$this->container['NotesService']
			->expects($this->once())
			->method('get')
			->will($this->returnValue($note));

		$response = $this->container['NotesApiController']->get();

		$this->assertEquals(json_encode(array(
			'modified' => 123,
			'id' => 3,
		)), json_encode($response->getData()));
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

		$this->container['NotesService']
			->expects($this->once())
			->method('get')
			->with($this->equalTo($id))
			->will($this->throwException(new NoteDoesNotExistException()));

		$response = $this->container['NotesApiController']->get();

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
		$content = 'yii';
		$note = new Note();
		$note->setId(4);

		$this->container['Request'] = $this->getRequest(array(
			'params' => array('content' => $content)
		));

		$this->container['NotesService']
			->expects($this->once())
			->method('create')
			->will($this->returnValue($note));

		$this->container['NotesService']
			->expects($this->once())
			->method('update')
			->with($this->equalTo($note->getId()),
				$this->equalTo($content))
			->will($this->returnValue($note));

		$response = $this->container['NotesApiController']->create();

		$this->assertEquals($note, $response->getData());
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
			->with($this->equalTo($id),
				$this->equalTo($content))
			->will($this->returnValue($expected));

		$response = $this->container['NotesApiController']->update();

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

		$response = $this->container['NotesApiController']->update();

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

		$response = $this->container['NotesApiController']->destroy();

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

		$response = $this->container['NotesApiController']->destroy();

		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
		$this->assertTrue($response instanceof JSONResponse);
	}


	// cors
	public function testCORSAnnotations() {
		$annotations = array('NoAdminRequired', 'PublicPage', 'NoCSRFRequired');
		$this->assertAnnotations($this->container['NotesApiController'],
			'cors', $annotations);
	}

	public function testCors() {
		$this->container['Request'] = $this->getRequest(array(
			'server' => array()
		));
		$response = $this->container['NotesApiController']->cors();

		$headers = $response->getHeaders();

		$this->assertEquals('*', $headers['Access-Control-Allow-Origin']);
		$this->assertEquals('PUT, POST, GET, DELETE', $headers['Access-Control-Allow-Methods']);
		$this->assertEquals('true', $headers['Access-Control-Allow-Credentials']);
		$this->assertEquals('Authorization, Content-Type', $headers['Access-Control-Allow-Headers']);
		$this->assertEquals('1728000', $headers['Access-Control-Max-Age']);
	}


	public function testCorsUsesOriginIfGiven() {
		$this->container['Request'] = $this->getRequest(array(
			'server' => array('HTTP_ORIGIN' => 'test')
		));
		$response = $this->container['NotesApiController']->cors();

		$headers = $response->getHeaders();

		$this->assertEquals('test', $headers['Access-Control-Allow-Origin']);
	}


}
