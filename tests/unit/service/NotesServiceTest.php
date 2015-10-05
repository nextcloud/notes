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

namespace OCA\Notes\Service;

use PHPUnit_Framework_TestCase;

use OCA\Notes\Db\Note;


class NotesServiceTest extends PHPUnit_Framework_TestCase {

    private $root;
    private $service;
    private $userId;
    private $l10n;
    private $userFolder;

    public function setUp(){
        $this->root = $this->getMockBuilder('OCP\Files\IRootFolder')
            ->getMock();
        $this->userFolder = $this->getMockBuilder('OCP\Files\Folder')
            ->getMock();
        $this->l10n = $this->getMockBuilder('OCP\IL10N')
            ->getMock();
        $this->userId = 'john';
        $this->service = new NotesService($this->root, $this->l10n);
    }

    private function createNode($name, $type, $mime, $mtime=0, $content='', $id=0, $path='/') {
        if ($type === 'folder') {
            $iface = 'OCP\Files\Folder';
        } else {
            $iface = 'OCP\Files\File';
        }
        $node = $this->getMockBuilder($iface)
            ->getMock();
        $node->expects($this->any())
            ->method('getType')
            ->will($this->returnValue($type));
        $node->expects($this->any())
            ->method('getMimeType')
            ->will($this->returnValue($mime));
        $node->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));
        $node->expects($this->any())
            ->method('getMTime')
            ->will($this->returnValue($mtime));
        $node->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));
        $node->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($path));
        if ($type === 'file') {
            $node->expects($this->any())
                ->method('getContent')
                ->will($this->returnValue($content));
        }
        return $node;
    }

    private function expectUserFolder($at=0) {
        $path = '/' . $this->userId . '/files/Notes';
        $this->root->expects($this->at($at))
            ->method('nodeExists')
            ->with($this->equalTo($path))
            ->will($this->returnValue(true));
        $this->root->expects($this->any($at + 1))
            ->method('get')
            ->with($this->equalTo($path))
            ->will($this->returnValue($this->userFolder));
    }

    public function testGetAll(){
        $nodes = [];
        $nodes[] = $this->createNode('file1.txt', 'file', 'text/plain');
        $nodes[] = $this->createNode('file1.jpg', 'file', 'image/jpeg');
        $nodes[] = $this->createNode('file3.txt', 'folder', 'text/plain');

        $this->expectUserFolder();
        $this->userFolder->expects($this->once())
            ->method('getDirectoryListing')
            ->will($this->returnValue($nodes));

        $result = $this->service->getAll($this->userId);

        $this->assertEquals('file1', $result[0]->getTitle());
        $this->assertCount(1, $result);
    }


    public function testGet(){
        $nodes = [];
        $nodes[] = $this->createNode('file1.txt', 'file', 'text/plain');

        $this->expectUserFolder();
        $this->userFolder->expects($this->once())
            ->method('getById')
            ->with($this->equalTo(2))
            ->will($this->returnValue($nodes));
        $result = $this->service->get(2, $this->userId);

        $this->assertEquals('file1', $result->getTitle());
    }



    /**
     * @expectedException OCA\Notes\Service\NoteDoesNotExistException
     */
    public function testGetDoesNotExist(){
        $nodes = [];

        $this->expectUserFolder();
        $this->userFolder->expects($this->once())
            ->method('getById')
            ->with($this->equalTo(2))
            ->will($this->returnValue($nodes));
        $this->service->get(2, $this->userId);
    }


    /**
     * @expectedException OCA\Notes\Service\NoteDoesNotExistException
     */
    public function testGetDoesNotExistWrongExtension(){
            $nodes = [];
        $nodes[] = $this->createNode('file1.jpg', 'file', 'image/jpeg');

        $this->expectUserFolder();
        $this->userFolder->expects($this->once())
            ->method('getById')
            ->with($this->equalTo(2))
            ->will($this->returnValue($nodes));

        $this->service->get(2, $this->userId);
    }



    public function testDelete(){
        $nodes = [];
        $nodes[] = $this->createNode('file1.txt', 'file', 'text/plain');

        $this->expectUserFolder();
        $this->userFolder->expects($this->once())
            ->method('getById')
            ->with($this->equalTo(2))
            ->will($this->returnValue($nodes));
        $nodes[0]->expects($this->once())
            ->method('delete');

        $this->service->delete(2, $this->userId);
    }



    /**
     * @expectedException OCA\Notes\Service\NoteDoesNotExistException
     */
    public function testDeleteDoesNotExist(){
        $nodes = [];

        $this->expectUserFolder();
        $this->userFolder->expects($this->once())
            ->method('getById')
            ->with($this->equalTo(2))
            ->will($this->returnValue($nodes));
        $this->service->delete(2, $this->userId);
    }


    /**
     * @expectedException OCA\Notes\Service\NoteDoesNotExistException
     */
    public function testDeleteDoesNotExistWrongExtension(){
        $nodes = [];
        $nodes[] = $this->createNode('file1.jpg', 'file', 'image/jpeg');

        $this->expectUserFolder();
        $this->userFolder->expects($this->once())
            ->method('getById')
            ->with($this->equalTo(2))
            ->will($this->returnValue($nodes));

        $this->service->delete(2, $this->userId);
    }


    private function expectGenerateFileName($at=0, $title, $id=0, $branch=0) {
        if ($branch === 0) {
            $this->userFolder->expects($this->at($at))
                ->method('nodeExists')
                ->with($this->equalTo($title . '.txt'))
                ->will($this->returnValue(false));
        } else if ($branch === 1) {
            $this->userFolder->expects($this->at($at))
                ->method('nodeExists')
                ->with($this->equalTo($title . '.txt'))
                ->will($this->returnValue(true));
            $file = $this->createNode('file1.txt', 'file', 'text/plain', 0, '', 0);
            $this->userFolder->expects($this->at($at+1))
                ->method('get')
                ->with($this->equalTo($title . '.txt'))
                ->will($this->returnValue($file));
        } else if ($branch === 2) {
            $this->userFolder->expects($this->at($at))
                ->method('nodeExists')
                ->with($this->equalTo($title . '.txt'))
                ->will($this->returnValue(true));
            $file = $this->createNode('file1.txt', 'file', 'text/plain', 0, '', 0);
            $this->userFolder->expects($this->at($at+1))
                ->method('get')
                ->with($this->equalTo($title . '.txt'))
                ->will($this->returnValue($file));
            $this->userFolder->expects($this->at($at+2))
                ->method('nodeExists')
                ->with($this->equalTo($title . ' (2).txt'))
                ->will($this->returnValue(true));
            $this->userFolder->expects($this->at($at+3))
                ->method('get')
                ->with($this->equalTo($title . ' (2).txt'))
                ->will($this->returnValue($file));
            $this->userFolder->expects($this->at($at+4))
                ->method('nodeExists')
                ->with($this->equalTo($title . ' (3).txt'))
                ->will($this->returnValue(false));
        }
    }


    public function testCreate() {
        $this->l10n->expects($this->once())
            ->method('t')
            ->with($this->equalTo('New note'))
            ->will($this->returnValue('New note'));
        $this->expectUserFolder();

        $this->expectGenerateFileName(0, 'New note');

        $file = $this->createNode('file1.txt', 'file', 'text/plain');
        $this->userFolder->expects($this->once())
            ->method('newFile')
            ->with($this->equalTo('New note.txt'))
            ->will($this->returnValue($file));

        $note = $this->service->create($this->userId);

        $this->assertEquals('file1', $note->getTitle());
    }


    public function testCreateExists() {
        $this->l10n->expects($this->once())
            ->method('t')
            ->with($this->equalTo('New note'))
            ->will($this->returnValue('New note'));
        $this->expectUserFolder();

        $this->expectGenerateFileName(0, 'New note', 0, 2);

        $file = $this->createNode('file1.txt', 'file', 'text/plain');
        $this->userFolder->expects($this->once())
            ->method('newFile')
            ->with($this->equalTo('New note (3).txt'))
            ->will($this->returnValue($file));

        $note = $this->service->create($this->userId);

        $this->assertEquals('file1', $note->getTitle());
    }


    public function testUpdate() {
        $nodes = [];
        $nodes[] = $this->createNode('file1.txt', 'file', 'text/plain');

        $this->expectUserFolder();
        $this->userFolder->expects($this->at(0))
            ->method('getById')
            ->with($this->equalTo(3))
            ->will($this->returnValue($nodes));

        $this->l10n->expects($this->once())
            ->method('t')
            ->with($this->equalTo('New note'))
            ->will($this->returnValue('New note'));
        $this->expectUserFolder();

        $this->expectGenerateFileName(1, 'New note', 0, 2);

        $path = '/' . $this->userId . '/files/Notes/New note (3).txt';
        $nodes[0]->expects($this->once())
            ->method('move')
            ->with($this->equalTo($path));

        $note = $this->service->update(3, '', $this->userId);

        $this->assertEquals('file1', $note->getTitle());
    }


    public function testUpdateWithContent() {
        $nodes = [];
        $nodes[] = $this->createNode('file1.txt', 'file', 'text/plain');

        $this->expectUserFolder();
        $this->userFolder->expects($this->at(0))
            ->method('getById')
            ->with($this->equalTo(3))
            ->will($this->returnValue($nodes));

        $this->l10n->expects($this->never())
            ->method('t');
        $this->expectUserFolder();

        $this->expectGenerateFileName(1, 'some', 0, 2);

        $path = '/' . $this->userId . '/files/Notes/some (3).txt';
        $nodes[0]->expects($this->once())
            ->method('move')
            ->with($this->equalTo($path));

        $note = $this->service->update(3, "some\nnice", $this->userId);

        $this->assertEquals('file1', $note->getTitle());
    }
}
