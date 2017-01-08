<?php
/**
 * Nextcloud - Notes
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @copyright Bernhard Posselt 2015
 */

namespace OCA\Notes\Controller;

use PHPUnit_Framework_TestCase;

use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\App;
use OCP\Files\File;


class NotesApiControllerTest extends PHPUnit_Framework_TestCase {

    private $controller;
    private $mapper;
    private $userId = 'test';
    private $notesFolder = '/test/files/Notes';
    private $fs;

    public function setUp() {
        $app = new App('notes');
        $container = $app->getContainer();
        $container->registerService('UserId', function($c) {
            return $this->userId;
        });
        $this->controller = $container->query(
            'OCA\Notes\Controller\NotesApiController'
        );

        $this->fs = $container->query(
            'OCP\Files\IRootFolder'
        );
        $this->fs->newFolder($this->notesFolder);
    }


    public function testUpdate() {
        $note = $this->controller->create('test')->getData();
        $this->assertEquals('test', $note->getContent());

	$t = 100000;

        $note2 = $this->controller->update($note->getId(), 'test2')->getData();
        $this->assertEquals('test2', $note2->getContent());
        $this->assertEquals($note->getId(), $note2->getId());
        $this->assertNotEquals($t, $note2->getModified());

        $note3 = $this->controller->update($note->getId(), 'test3', $t)->getData();
        $this->assertEquals('test3', $note3->getContent());
        $this->assertEquals($note->getId(), $note3->getId());
        $this->assertEquals($t, $note3->getModified());

        $notes = $this->controller->index()->getData();

        $this->assertCount(1, $notes);
        $this->assertEquals('test3', $notes[0]->getContent());

        $file = $this->fs->get($this->notesFolder . '/test3.txt');

        $this->assertTrue($file instanceof File);
    }


    public function tearDown() {
         $this->fs->get($this->notesFolder)->delete();
    }


}
