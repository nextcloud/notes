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

use \OCA\Notes\Service\NoteDoesNotExistException;
use \OCA\Notes\Utility\ControllerTestUtility;

class NotesControllerTest extends \OCA\Notes\Tests\Unit\NotesUnitTest {


	/**
	 * Gets run before each test
	 */
	public function setUp(){
		parent::setUp();
		$test = &$this;
		$this->container->registerService('NotesService', function ($c) use ($test) {
			return $test->getMockBuilder(
				'\OCA\Notes\Service\NotesService')
				->disableOriginalConstructor()
				->getMock();
		});
	}


	/**
	 * GET /notes/
	 */
	public function testGetAll(){
		$expected = array(
			'hi'
		);

		$this->container->query('NotesService')
			->expects($this->once())
			->method('getAll')
			->will($this->returnValue($expected));

		$response = $this->container->query('NotesController')->index();

		$this->assertEquals($expected, $response->getData());
		$this->assertTrue($response instanceof JSONResponse);
	}


	/**
	 * GET /notes/1
	 */
	public function testGet(){
		$id = 1;
		$expected = array(
			'hi'
		);

		$this->container->query('CoreConfig')->expects($this->once())
			->method('setUserValue')
			->with($this->equalTo($this->container->query('UserId')),
				$this->equalTo($this->container->query('AppName')),
				$this->equalTo('notesLastViewedNote'),
				$this->equalTo($id));

		$this->container->query('NotesService')
			->expects($this->once())
			->method('get')
			->with($this->equalTo($id))
			->will($this->returnValue($expected));

		$response = $this->container->query('NotesController')->get($id);

		$this->assertEquals($expected, $response->getData());
		$this->assertTrue($response instanceof JSONResponse);
	}


	public function testGetDoesNotExist(){
		$id = 1;
		$expected = array(
			'hi'
		);

		$this->container->query('CoreConfig')->expects($this->once())
			->method('setUserValue')
			->with($this->equalTo($this->container->query('UserId')),
				$this->equalTo($this->container->query('AppName')),
				$this->equalTo('notesLastViewedNote'),
				$this->equalTo($id));

		$this->container->query('NotesService')
			->expects($this->once())
			->method('get')
			->with($this->equalTo($id))
			->will($this->throwException(new NoteDoesNotExistException()));

		$response = $this->container->query('NotesController')->get($id);

		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
		$this->assertTrue($response instanceof JSONResponse);
	}


	/**
	 * GET /config
	 */
	public function testGetConfig(){
		$expected = array(
			'markdown' => false
		);

		$this->container->query('CoreConfig')->expects($this->once())
			->method('getUserValue')
			->with($this->equalTo($this->container->query('UserId')),
				$this->equalTo($this->container->query('AppName')),
				$this->equalTo('notesMarkdown'))
			->will($this->returnValue('0'));

		$response = $this->container->query('NotesController')->getConfig();

		$this->assertEquals($expected, $response->getData());
		$this->assertTrue($response instanceof JSONResponse);
	}


	/**
	 * POST /config
	 */
	public function testSetConfig(){
		$this->container->query('CoreConfig')->expects($this->once())
			->method('setUserValue')
			->with($this->equalTo($this->container->query('UserId')),
				$this->equalTo($this->container->query('AppName')),
				$this->equalTo('notesMarkdown'), $this->equalTo(true));

		$response = $this->container->query('NotesController')->setConfig(true);

		$this->assertTrue($response instanceof JSONResponse);
	}


	/**
	 * POST /notes
	 */
	public function testCreate(){
		$expected = array(
			'hi'
		);

		$this->container->query('NotesService')
			->expects($this->once())
			->method('create')
			->will($this->returnValue($expected));

		$response = $this->container->query('NotesController')->create();

		$this->assertEquals($expected, $response->getData());
		$this->assertTrue($response instanceof JSONResponse);
	}


	/**
	 * PUT /notes/
	 */
	public function testUpdate(){
		$id = 1;
		$content = 'yo';
		$expected = array(
			'hi'
		);

		$this->container->query('NotesService')
			->expects($this->once())
			->method('update')
			->with($this->equalTo($id),	$this->equalTo($content))
			->will($this->returnValue($expected));

		$response = $this->container->query('NotesController')->update($id, $content);

		$this->assertEquals($expected, $response->getData());
		$this->assertTrue($response instanceof JSONResponse);
	}


	public function testUpdateDoesNotExist(){
		$id = 1;
		$content = 'yo';

		$this->container->query('NotesService')
			->expects($this->once())
			->method('update')
			->with($this->equalTo($id),
				$this->equalTo($content))
			->will($this->throwException(new NoteDoesNotExistException()));

		$response = $this->container->query('NotesController')->update($id, $content);

		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
		$this->assertTrue($response instanceof JSONResponse);
	}


	/**
	 * DELETE /notes/
	 */
	public function testDelete(){
		$id = 1;

		$this->container->query('NotesService')
			->expects($this->once())
			->method('delete');

		$response = $this->container->query('NotesController')->destroy($id);

		$this->assertTrue($response instanceof JSONResponse);
	}


	public function testDeleteDoesNotExist(){
		$id = 1;

		$this->container->query('NotesService')
			->expects($this->once())
			->method('delete')
			->will($this->throwException(new NoteDoesNotExistException()));

		$response = $this->container->query('NotesController')->destroy($id);

		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
		$this->assertTrue($response instanceof JSONResponse);
	}


}
