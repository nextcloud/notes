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

use \OCA\Notes\Utility\ControllerTestUtility;
use \OCA\Notes\App\Notes;
use \OCA\Notes\Db\Note;

class NotesServiceTest extends ControllerTestUtility {

	private $container;

	public function setUp(){
		// use the container to test to check if its wired up correctly and
		// replace needed components with mocks
		$notes = new Notes();
		$this->container = $notes->getContainer();
		$this->container['L10N'] = $this->getMockBuilder(
			'\OCP\IL10N')
			->disableOriginalConstructor()
			->getMock();
		$this->container['FileSystemUtility'] = $this->getMockBuilder(
			'\OCA\Notes\Utility\FileSystemUtility')
			->disableOriginalConstructor()
			->getMock();
		$this->container['Request'] = $this->getRequest();

		$this->container['FileSystem'] = $this->getMock('Filesystem',
			array(
				'getDirectoryContent',
				'unlink',
				'file_get_contents',
				'getFileInfo',
				'file_exists',
				'rename',
				'file_put_contents',
				'getPath',
				'filemtime'
			));


		// reusable test data
		$this->filesystemNotes = array(
			array(
				'fileid' => 2,
				'type' => 'file',
				'mtime' => 50,
				'name' => 'hi.txt',
				'content' => '',
				'path' => '',
				'mimetype' => 'text/plain'
			),
			array(
				'fileid' => 1,
				'type' => 'directory',
				'mtime' => 50,
				'name' => '',
				'path' => '',
				'mimetype' => 'text/plain'
			),
			array(
				'fileid' => 3,
				'type' => 'file',
				'mtime' => 502,
				'name' => 'yo.txt',
				'content' => '',
				'path' => '',
				'mimetype' => 'text/plain'
			),
			array(
				'fileid' => 5,
				'type' => 'file',
				'mtime' => 502,
				'name' => 'yo.png',
				'content' => '',
				'path' => '',
				'mimetype' => 'image/png'
			)
		);


		$this->notes = array(
			Note::fromFile($this->filesystemNotes[0]),
			Note::fromFile($this->filesystemNotes[2])
		);
	}


	public function testGetAll(){
		$this->container['FileSystem']->expects($this->once())
			->method('getDirectoryContent')
			->with($this->equalTo('/'))
			->will($this->returnValue($this->filesystemNotes));

		$result = $this->container['NotesService']->getAll();

		$this->assertEquals($this->notes[0], $result[0]);
		$this->assertEquals($this->notes[1], $result[1]);
		$this->assertCount(2, $result);
	}


	public function testGet(){
		$expected = Note::fromFile($this->filesystemNotes[0]);

		$this->container['FileSystem']->expects($this->once())
			->method('file_get_contents')
			->with($this->equalTo($expected->getTitle() . '.txt'))
			->will($this->returnValue($this->filesystemNotes[0]['content']));
		$this->container['FileSystem']->expects($this->once())
			->method('getFileInfo')
			->with($this->equalTo($expected->getTitle() . '.txt'))
			->will($this->returnValue($this->filesystemNotes[0]));
		$this->container['FileSystem']->expects($this->once())
			->method('getPath')
			->with($this->equalTo(2))
			->will($this->returnValue($expected->getTitle() . '.txt'));

		$result = $this->container['NotesService']->get(2);

		$this->assertEquals($expected, $result);
	}


	public function testGetDoesNotExist(){
		$this->container['FileSystem']->expects($this->once())
			->method('getPath')
			->will($this->returnValue(null));

		$this->setExpectedException('\OCA\Notes\Service\NoteDoesNotExistException');
		$result = $this->container['NotesService']->get(2);

	}


	public function testGetImageDoesNotExist(){
		$this->container['FileSystem']->expects($this->once())
			->method('getPath')
			->will($this->returnValue(null));

		$this->setExpectedException('\OCA\Notes\Service\NoteDoesNotExistException');
		$result = $this->container['NotesService']->get(5);

	}


	public function testDelete(){
		$id = 3;
		$this->container['FileSystem']->expects($this->once())
			->method('getPath')
			->with($this->equalTo($id))
			->will($this->returnValue('hi'));
		$this->container['FileSystem']->expects($this->once())
			->method('unlink')
			->with($this->equalTo('hi'));
		$this->container['NotesService']->delete($id);
	}


	public function testDeleteDoesNotExist(){
		$id = 3;
		$this->container['FileSystem']->expects($this->once())
			->method('getPath')
			->with($this->equalTo($id))
			->will($this->returnValue(null));
		$this->setExpectedException('\OCA\Notes\Service\NoteDoesNotExistException');
		$this->container['NotesService']->delete($id);
	}


	public function testCreate() {
		$this->notes[0]->setTitle('Na nute');
		$this->container['L10N']->expects($this->once())
			->method('t')
			->with('New note')
			->will($this->returnValue('Na nute'));
		$this->container['FileSystemUtility']->expects($this->once())
			->method('generateFileName')
			->with($this->equalTo('Na nute'), $this->equalTo(-1))
			->will($this->returnValue('Na nute.txt'));
		$this->container['FileSystem']->expects($this->once())
			->method('file_put_contents')
			->with($this->equalTo('/Na nute.txt'));
		$this->container['FileSystem']->expects($this->once())
			->method('getFileInfo')
			->will($this->returnValue($this->filesystemNotes[0]));

		$note = $this->container['NotesService']->create();
		$this->assertEquals($this->notes[0], $note);
	}


	public function testUpdate() {
		$id = 3;
		$content = "title\nman";
		$title = 'title';
		$this->container['FileSystem']->expects($this->once())
			->method('getPath')
			->with($this->equalTo($id))
			->will($this->returnValue('/title.txt'));

		$this->container['FileSystemUtility']->expects($this->once())
			->method('generateFileName')
			->with($this->equalTo($title), $this->equalTo($id))
			->will($this->returnValue('title.txt'));

		$this->container['FileSystem']->expects($this->once())
			->method('file_put_contents')
			->with($this->equalTo('/' . $title . '.txt'))
			->will($this->returnValue(array('fileid' => $id)));
		$this->container['FileSystem']->expects($this->once())
			->method('filemtime')
			->will($this->returnValue($this->filesystemNotes[0]['mtime']));

		$note = $this->container['NotesService']->update($id, $content);
		$this->assertEquals(Note::fromFile(array(
			'fileid' => $id,
			'content' => $content,
			'name' => $title . '.txt',
			'mtime' => 50
		)), $note);
	}


	public function testUpdateWithEmptyTitleUsesNewNote() {
		$id = 3;
		$content = "\nman";
		$title = 'Na nute';
		$this->container['L10N']->expects($this->once())
			->method('t')
			->with('New note')
			->will($this->returnValue($title));

		$this->container['FileSystem']->expects($this->once())
			->method('getPath')
			->with($this->equalTo($id))
			->will($this->returnValue('/' . $title . '.txt'));

		$this->container['FileSystemUtility']->expects($this->once())
			->method('generateFileName')
			->with($this->equalTo($title), $this->equalTo($id))
			->will($this->returnValue($title . '.txt'));

		$this->container['FileSystem']->expects($this->once())
			->method('file_put_contents')
			->with($this->equalTo('/' . $title . '.txt'))
			->will($this->returnValue(array('fileid' => $id)));
		$this->container['FileSystem']->expects($this->once())
			->method('filemtime')
			->will($this->returnValue($this->filesystemNotes[0]['mtime']));

		$note = $this->container['NotesService']->update($id, $content);
		$this->assertEquals(Note::fromFile(array(
			'fileid' => $id,
			'content' => $content,
			'name' => $title . '.txt',
			'mtime' => 50
		)), $note);
	}


	public function testUpdateDoesNotExist(){
		$id = 3;
		$this->container['FileSystem']->expects($this->once())
			->method('getPath')
			->with($this->equalTo($id))
			->will($this->returnValue(null));
		$this->setExpectedException('\OCA\Notes\Service\NoteDoesNotExistException');
		$this->container['NotesService']->update($id, '');
	}


	public function testUpdateRenames() {
		$id = 3;
		$content = "title\nyo";
		$title = 'title';
		$this->container['FileSystem']->expects($this->once())
			->method('getPath')
			->with($this->equalTo($id))
			->will($this->returnValue('/title.txt'));

		$this->container['FileSystemUtility']->expects($this->once())
			->method('generateFileName')
			->with($this->equalTo($title), $this->equalTo($id))
			->will($this->returnValue('title (3).txt'));

		$this->container['FileSystem']->expects($this->once())
			->method('rename')
			->with($this->equalTo('/' . $title . '.txt'),
				$this->equalTo('/' . $title . ' (3).txt'));
		$this->container['FileSystem']->expects($this->once())
			->method('file_put_contents')
			->with($this->equalTo('/' . $title . ' (3).txt'))
			->will($this->returnValue(array('fileid' => $id)));
		$this->container['FileSystem']->expects($this->once())
			->method('filemtime')
			->will($this->returnValue($this->filesystemNotes[0]['mtime']));

		$note = $this->container['NotesService']->update($id, $content);
		$this->assertEquals(Note::fromFile(array(
			'fileid' => $id,
			'content' => $content,
			'name' => $title . ' (3).txt',
			'mtime' => 50
		)), $note);
	}


}
