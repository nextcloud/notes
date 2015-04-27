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
use OCP\AppFramework\Http\TemplateResponse;

use OCA\Notes\Service\NoteDoesNotExistException;


class PageControllerTest extends PHPUnit_Framework_TestCase {


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
        $this->service = $this->getMockBuilder('OCA\Notes\Service\NotesService')
            ->disableOriginalConstructor()
            ->getMock();
        $this->config = $this->getMockBuilder('OCP\IConfig')
            ->disableOriginalConstructor()
            ->getMock();
        $this->userId = 'john';
        $this->appName = 'notes';
        $this->controller = new PageController(
            $this->appName, $this->request, $this->userId,
            $this->service, $this->config
        );
    }


    public function testIndexReturnsTemplate(){
        $result = $this->controller->index();
        $this->assertTrue($result instanceof TemplateResponse);
    }


    public function testIndexShouldSendTheCorrectTemplate(){
        $this->config->expects($this->once())
            ->method('getUserValue')
            ->with($this->equalTo($this->userId),
                $this->equalTo($this->appName),
                $this->equalTo('notesLastViewedNote'))
            ->will($this->returnValue('3'));
        $result = $this->controller->index();

        $this->assertEquals('main', $result->getTemplateName());
        $this->assertEquals(['lastViewedNote' => 3], $result->getParams());
    }


    public function testIndexShouldSendZeroWhenNoLastViewedNote(){
        $this->config->expects($this->once())
            ->method('getUserValue')
            ->with($this->equalTo($this->userId),
                $this->equalTo($this->appName),
                $this->equalTo('notesLastViewedNote'))
            ->will($this->returnValue(''));
        $result = $this->controller->index();

        $this->assertEquals(['lastViewedNote' => 0], $result->getParams());
    }


    public function testIndexShouldSetZeroWhenLastViewedNotDoesNotExist(){
        $this->config->expects($this->once())
            ->method('getUserValue')
            ->with($this->equalTo($this->userId),
                $this->equalTo($this->appName),
                $this->equalTo('notesLastViewedNote'))
            ->will($this->returnValue('3'));
        $this->service->expects($this->once())
            ->method('get')
            ->with($this->equalTo(3),
                   $this->equalTo($this->userId))
            ->will($this->throwException(new NoteDoesNotExistException()));
        $result = $this->controller->index();

        $this->assertEquals(['lastViewedNote' => 0], $result->getParams());
    }


}