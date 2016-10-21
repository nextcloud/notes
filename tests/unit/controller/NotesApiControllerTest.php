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

use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http;

use OCA\Notes\Service\NoteDoesNotExistException;
use OCA\Notes\Db\Note;


class NotesApiControllerTest extends PHPUnit_Framework_TestCase {

    private $request;
    private $service;
    private $userId;
    private $appName;
    private $controller;

    public function setUp (){
        $this->request = $this->getMockBuilder('OCP\IRequest')
            ->disableOriginalConstructor()
            ->getMock();
        $this->service = $this->getMockBuilder('OCA\Notes\Service\NotesService')
            ->disableOriginalConstructor()
            ->getMock();
        $this->userId = 'john';
        $this->appName = 'notes';
        $this->controller = new NotesApiController(
            $this->appName, $this->request, $this->service, $this->userId
        );
    }


    /**
     * GET /notes/
     */
    public function testGetAll(){
        $expected = [new Note, new Note];

        $this->service->expects($this->once())
            ->method('getAll')
            ->with($this->equalTo($this->userId))
            ->will($this->returnValue($expected));

        $response = $this->controller->index();

        $this->assertEquals($expected, $response->getData());
        $this->assertTrue($response instanceof DataResponse);
    }


    public function testGetAllHide(){
        $note1 = Note::fromRow([
            'id' => 3,
            'modified' => 123,
            'title' => 'test',
            'content' => 'yo'
        ]);
        $note2 = Note::fromRow([
            'id' => 4,
            'modified' => 111,
            'title' => 'abc',
            'content' => 'deee'
        ]);
        $notes = [
            $note1, $note2
        ];

        $this->service->expects($this->once())
            ->method('getAll')
            ->with($this->equalTo($this->userId))
            ->will($this->returnValue($notes));

        $response = $this->controller->index('title,content');

        $this->assertEquals(json_encode([
            [
                'modified' => 123,
                'favorite' => false,
                'id' => 3,
            ],
            [
                'modified' => 111,
                'favorite' => false,
                'id' => 4,
            ]
        ]), json_encode($response->getData()));
        $this->assertTrue($response instanceof DataResponse);
    }


    /**
     * GET /notes/1
     */
    public function testGet(){
        $id = 1;
        $expected = new Note;

        $this->service->expects($this->once())
            ->method('get')
            ->with($this->equalTo($id),
                   $this->equalTo($this->userId))
            ->will($this->returnValue($expected));

        $response = $this->controller->get($id);

        $this->assertEquals($expected, $response->getData());
        $this->assertTrue($response instanceof DataResponse);
    }

    public function testGetHide(){
        $note = Note::fromRow([
            'id' => 3,
            'modified' => 123,
            'title' => 'test',
            'content' => 'yo'
        ]);

        $this->service->expects($this->once())
            ->method('get')
            ->with($this->equalTo(3),
                   $this->equalTo($this->userId))
            ->will($this->returnValue($note));

        $response = $this->controller->get(3, 'title,content');

        $this->assertEquals(json_encode([
            'modified' => 123,
            'favorite' => false,
            'id' => 3,
        ]), json_encode($response->getData()));
        $this->assertTrue($response instanceof DataResponse);
    }


    public function testGetDoesNotExist(){
        $id = 1;
        $expected = ['hi'];

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
        $content = 'yii';
        $note = new Note();
        $note->setId(4);

        $this->service->expects($this->once())
            ->method('create')
            ->with($this->equalTo($this->userId))
            ->will($this->returnValue($note));

        $this->service->expects($this->once())
            ->method('update')
            ->with($this->equalTo($note->getId()),
                $this->equalTo($content),
                $this->equalTo($this->userId))
            ->will($this->returnValue($note));

        $response = $this->controller->create($content);

        $this->assertEquals($note, $response->getData());
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
            ->with($this->equalTo(1),
                  $this->equalTo($this->userId));

        $response = $this->controller->destroy($id);

        $this->assertTrue($response instanceof DataResponse);
    }


    public function testDeleteDoesNotExist(){
        $id = 1;

        $this->service->expects($this->once())
            ->method('delete')
            ->with($this->equalTo(1),
                   $this->equalTo($this->userId))
            ->will($this->throwException(new NoteDoesNotExistException()));

        $response = $this->controller->destroy($id);

        $this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
        $this->assertTrue($response instanceof DataResponse);
    }


}
