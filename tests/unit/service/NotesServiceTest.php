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

	public function setUp(){
		$this->root = $this->getMockBuilder('OCP\Files\IRootFolder')
			->disableOriginalConstructor()
			->getMock();
		$this->l10n = $this->getMockBuilder('OCP\IL10N')
			->disableOriginalConstructor()
			->getMock();
		$this->userId = 'john';
		$this->service = new NotesService($this->root, $this->l10n);
	}


	public function testGetAll(){
		$this->root->expects($this->once())
			->method('getDirectoryContent')
			->with($this->equalTo('/'))
			->will($this->returnValue($this->filesystemNotes));

		$result = $this->service->getAll();

		$this->assertEquals($this->notes[0], $result[0]);
		$this->assertEquals($this->notes[1], $result[1]);
		$this->assertCount(2, $result);
	}


	public function testGet(){
		$expected = Note::fromFile($this->filesystemNotes[0]);

		$this->root->expects($this->once())
			->method('file_get_contents')
			->with($this->equalTo($expected->getTitle() . '.txt'))
			->will($this->returnValue($this->filesystemNotes[0]['content']));
		$this->root->expects($this->once())
			->method('getFileInfo')
			->with($this->equalTo($expected->getTitle() . '.txt'))
			->will($this->returnValue($this->filesystemNotes[0]));
		$this->root->expects($this->once())
			->method('getPath')
			->with($this->equalTo(2))
			->will($this->returnValue($expected->getTitle() . '.txt'));

		$result = $this->service->get(2);

		$this->assertEquals($expected, $result);
	}


	public function testGetDoesNotExist(){
		$this->root->expects($this->once())
			->method('getPath')
			->will($this->returnValue(null));

		$this->setExpectedException('\OCA\Notes\Service\NoteDoesNotExistException');
		$result = $this->service->get(2);

	}


	public function testGetImageDoesNotExist(){
		$this->root->expects($this->once())
			->method('getPath')
			->will($this->returnValue(null));

		$this->setExpectedException('\OCA\Notes\Service\NoteDoesNotExistException');
		$result = $this->service->get(5);

	}


	public function testDelete(){
		$id = 3;
		$this->root->expects($this->once())
			->method('getPath')
			->with($this->equalTo($id))
			->will($this->returnValue('hi'));
		$this->root->expects($this->once())
			->method('unlink')
			->with($this->equalTo('hi'));
		$this->service->delete($id);
	}


	public function testDeleteDoesNotExist(){
		$id = 3;
		$this->root->expects($this->once())
			->method('getPath')
			->with($this->equalTo($id))
			->will($this->returnValue(null));
		$this->setExpectedException('\OCA\Notes\Service\NoteDoesNotExistException');
		$this->service->delete($id);
	}


	public function testCreate() {
		$this->notes[0]->setTitle('Na nute');
		$this->container->query('L10N')->expects($this->once())
			->method('t')
			->with('New note')
			->will($this->returnValue('Na nute'));
		$this->container->query('FileSystemUtility')->expects($this->once())
			->method('generateFileName')
			->with($this->equalTo('Na nute'), $this->equalTo(-1))
			->will($this->returnValue('Na nute.txt'));
		$this->root->expects($this->once())
			->method('file_put_contents')
			->with($this->equalTo('/Na nute.txt'));
		$this->root->expects($this->once())
			->method('getFileInfo')
			->will($this->returnValue($this->filesystemNotes[0]));

		$note = $this->service->create();
		$this->assertEquals($this->notes[0], $note);
	}


	public function testUpdate() {
		$id = 3;
		$content = "title\nman";
		$title = 'title';
		$this->root->expects($this->once())
			->method('getPath')
			->with($this->equalTo($id))
			->will($this->returnValue('/title.txt'));

		$this->container->query('FileSystemUtility')->expects($this->once())
			->method('generateFileName')
			->with($this->equalTo($title), $this->equalTo($id))
			->will($this->returnValue('title.txt'));

		$this->root->expects($this->once())
			->method('file_put_contents')
			->with($this->equalTo('/' . $title . '.txt'))
			->will($this->returnValue(array('id' => $id)));
		$this->root->expects($this->once())
			->method('filemtime')
			->will($this->returnValue($this->filesystemNotes[0]['mtime']));

		$note = $this->service->update($id, $content);
		$this->assertEquals(Note::fromFile(array(
			'id' => $id,
			'content' => $content,
			'name' => $title . '.txt',
			'mtime' => 50
		)), $note);
	}


	public function testUpdateWithEmptyTitleUsesNewNote() {
		$id = 3;
		$content = "\nman";
		$title = 'Na nute';
		$this->container->query('L10N')->expects($this->once())
			->method('t')
			->with('New note')
			->will($this->returnValue($title));

		$this->root->expects($this->once())
			->method('getPath')
			->with($this->equalTo($id))
			->will($this->returnValue('/' . $title . '.txt'));

		$this->container->query('FileSystemUtility')->expects($this->once())
			->method('generateFileName')
			->with($this->equalTo($title), $this->equalTo($id))
			->will($this->returnValue($title . '.txt'));

		$this->root->expects($this->once())
			->method('file_put_contents')
			->with($this->equalTo('/' . $title . '.txt'))
			->will($this->returnValue(array('id' => $id)));
		$this->root->expects($this->once())
			->method('filemtime')
			->will($this->returnValue($this->filesystemNotes[0]['mtime']));

		$note = $this->service->update($id, $content);
		$this->assertEquals(Note::fromFile(array(
			'id' => $id,
			'content' => $content,
			'name' => $title . '.txt',
			'mtime' => 50
		)), $note);
	}


	public function testUpdateDoesNotExist(){
		$id = 3;
		$this->root->expects($this->once())
			->method('getPath')
			->with($this->equalTo($id))
			->will($this->returnValue(null));
		$this->setExpectedException('\OCA\Notes\Service\NoteDoesNotExistException');
		$this->service->update($id, '');
	}


	public function testUpdateRenames() {
		$id = 3;
		$content = "title\nyo";
		$title = 'title';
		$this->root->expects($this->once())
			->method('getPath')
			->with($this->equalTo($id))
			->will($this->returnValue('/title.txt'));

		$this->container->query('FileSystemUtility')->expects($this->once())
			->method('generateFileName')
			->with($this->equalTo($title), $this->equalTo($id))
			->will($this->returnValue('title (3).txt'));

		$this->root->expects($this->once())
			->method('rename')
			->with($this->equalTo('/' . $title . '.txt'),
				$this->equalTo('/' . $title . ' (3).txt'));
		$this->root->expects($this->once())
			->method('file_put_contents')
			->with($this->equalTo('/' . $title . ' (3).txt'))
			->will($this->returnValue(array('id' => $id)));
		$this->root->expects($this->once())
			->method('filemtime')
			->will($this->returnValue($this->filesystemNotes[0]['mtime']));

		$note = $this->service->update($id, $content);
		$this->assertEquals(Note::fromFile(array(
			'id' => $id,
			'content' => $content,
			'name' => $title . ' (3).txt',
			'mtime' => 50
		)), $note);
	}


}
