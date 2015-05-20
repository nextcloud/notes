<?php
/**
 * ownCloud - Notes
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @copyright Bernhard Posselt 2015
 */

namespace OCA\Notes\Controller;

use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\App;
use Test\TestCase;


class NotesApiControllerTest extends TestCase {

    private $controller;
    private $mapper;
    private $userId = 'test';

    public function setUp() {
        parent::setUp();

        $app = new App('notes');
        $container = $app->getContainer();
        $container->registerService('UserId', function($c) {
            return $this->userId;
        });
        $this->controller = $container->query(
            'OCA\Notes\Controller\NotesApiController'
        );
    }


    public function testUpdate() {
        $note = $this->controller->create('test')->getData();
        $this->assertEquals('test', $note->getContent());

        $note2 = $this->controller->update($note->getId(), 'test2')->getData();
        $this->assertEquals('test2', $note2->getContent());
        $this->assertEquals($note->getId(), $note2->getId());

        $notes = $this->controller->index()->getData();

        $this->assertCount(1, $notes);
        $this->assertEquals('test2', $notes[0]->getContent());
    }


}