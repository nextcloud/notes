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

use PHPUnit_Framework_TestCase;

use OCP\IRequest;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http;

use OCA\Notes\Service\NoteDoesNotExistException;
use OCA\Notes\Db\Note;


class NotesControllerTest extends PHPUnit_Framework_TestCase {


    private $request;
    private $service;
    private $userId;
    private $appName;
    private $controller;
    private $config;

    public function setUp (){
        $this->request = $this->getMockBuilder('OCP\IRequest')
            ->disableOriginalConstructor()
            ->getMock();
        $this->config = $this->getMockBuilder('OCP\IConfig')
            ->disableOriginalConstructor()
            ->getMock();
        $this->service = $this->getMockBuilder('OCA\Notes\Service\NotesService')
            ->disableOriginalConstructor()
            ->getMock();
        $this->userId = 'john';
        $this->appName = 'notes';
        $this->controller = new NotesController(
            $this->appName, $this->request, $this->service, $this->config,
            $this->userId
        );
    }


    /**
     * GET /notes/
     */
    public function testGetAll(){
        $expected = ['hi'];

        $this->service->expects($this->once())
            ->method('getAll')
            ->will($this->returnValue($expected));

        $response = $this->controller->index();

        $this->assertEquals($expected, $response->getData());
        $this->assertTrue($response instanceof DataResponse);
    }


    /**
     * GET /notes/1
     */
    public function testGet(){
        $id = 1;
        $expected = ['hi'];

        $this->config->expects($this->once())
            ->method('setUserValue')
            ->with($this->equalTo($this->userId),
                $this->equalTo($this->appName),
                $this->equalTo('notesLastViewedNote'),
                $this->equalTo($id));

        $this->service->expects($this->once())
            ->method('get')
            ->with($this->equalTo($id),
                   $this->equalTo($this->userId))
            ->will($this->returnValue($expected));

        $response = $this->controller->get($id);

        $this->assertEquals($expected, $response->getData());
        $this->assertTrue($response instanceof DataResponse);
    }


    public function testGetDoesNotExist(){
        $id = 1;
        $expected = ['hi'];

        $this->config->expects($this->once())
            ->method('setUserValue')
            ->with($this->equalTo($this->userId),
                $this->equalTo($this->appName),
                $this->equalTo('notesLastViewedNote'),
                $this->equalTo($id));

        $this->service->expects($this->once())
            ->method('get')
            ->with($this->equalTo($id),
                   $this->equalTo($this->userId))
            ->will($this->throwException(new NoteDoesNotExistException()));

        $response = $this->controller->get($id);

        $this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
        $this->assertTrue($response instanceof DataResponse);
    }


    /**
     * POST /notes
     */
    public function testCreate(){
        $created = new Note();
        $created->setId(3);

        $expected = new Note();

        $this->service->expects($this->once())
            ->method('create')
            ->with($this->equalTo($this->userId))
            ->will($this->returnValue($created));
        $this->service->expects($this->once())
            ->method('update')
            ->with(3, 'hi', $this->userId)
            ->will($this->returnValue($expected));

        $response = $this->controller->create('hi');

        $this->assertEquals($expected, $response->getData());
        $this->assertTrue($response instanceof DataResponse);
    }


    /**
     * PUT /notes/
     */
    public function testUpdate(){
        $id = 1;
        $content = 'yo';
        $expected = ['hi'];

        $this->service->expects($this->once())
            ->method('update')
            ->with($this->equalTo($id),
                   $this->equalTo($content),
                   $this->equalTo($this->userId))
            ->will($this->returnValue($expected));

        $response = $this->controller->update($id, $content);

        $this->assertEquals($expected, $response->getData());
        $this->assertTrue($response instanceof DataResponse);
    }


    public function testUpdateDoesNotExist(){
        $id = 1;
        $content = 'yo';

        $this->service->expects($this->once())
            ->method('update')
            ->with($this->equalTo($id),
                   $this->equalTo($content),
                   $this->equalTo($this->userId))
            ->will($this->throwException(new NoteDoesNotExistException()));

        $response = $this->controller->update($id, $content);

        $this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
        $this->assertTrue($response instanceof DataResponse);
    }


    /**
     * DELETE /notes/
     */
    public function testDelete(){
        $id = 1;

        $this->service->expects($this->once())
            ->method('delete')
            ->with($this->equalTo($id),
                   $this->equalTo($this->userId));

        $response = $this->controller->destroy($id);

        $this->assertTrue($response instanceof DataResponse);
    }


    public function testDeleteDoesNotExist(){
        $id = 1;

        $this->service->expects($this->once())
            ->method('delete')
            ->with($this->equalTo($id),
                   $this->equalTo($this->userId))
            ->will($this->throwException(new NoteDoesNotExistException()));

        $response = $this->controller->destroy($id);

        $this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
        $this->assertTrue($response instanceof DataResponse);
    }


}
