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

	private function createNode($name, $type, $mime, $mtime=0, $content='') {
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
		$nodes[] = $this->createNode('file2.txt', 'file', 'text/xml');
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
	public function testGetDoesNotExistWrongMime(){
			$nodes = [];
		$nodes[] = $this->createNode('file1.txt', 'file', 'text/xml');

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
	public function testDeleteDoesNotExistWrongMime(){
		$nodes = [];
		$nodes[] = $this->createNode('file1.txt', 'file', 'text/xml');

		$this->expectUserFolder();
		$this->userFolder->expects($this->once())
			->method('getById')
			->with($this->equalTo(2))
			->will($this->returnValue($nodes));

		$this->service->delete(2, $this->userId);
	}


}
