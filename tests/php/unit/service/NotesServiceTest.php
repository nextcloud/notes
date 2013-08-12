<?php
/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */

namespace OCA\Notes\Service;

use \OCA\AppFramework\Http\Request;
use \OCA\AppFramework\Http\JSONResponse;
use \OCA\AppFramework\Utility\TestUtility;

use \OCA\Notes\DependencyInjection\DIContainer;
use \OCA\Notes\Db\Note;


class NotesServiceTest extends TestUtility {

	private $container;

	public function setUp(){
		// use the container to test to check if its wired up correctly and
		// replace needed components with mocks
		$this->container = new DIContainer();
		$this->container['API'] = $this->getMockBuilder(
			'\OCA\AppFramework\Core\API')
			->disableOriginalConstructor()
			->getMock();
		$this->container['Request'] = new Request();

		$this->container['FileSystem'] = $this->getMock('Filesystem', 
			array(
				'getDirectoryContent',
				'unlink',
				'file_get_contents',
				'getFileInfo',
				'file_exists',
				'rename',
				'file_put_contents',
				'getPath'
			));


		// reusable test data
		$this->filesystemNotes = array(
			array(
				'fileid' => 2,
				'type' => 'file',
				'mtime' => 50,
				'name' => 'hi.txt',
				'content' => ''
			),
			array(
				'fileid' => 1,
				'type' => 'directory',
				'mtime' => 50,
				'name' => ''
			),
			array(
				'fileid' => 3,
				'type' => 'file',
				'mtime' => 502,
				'name' => 'yo.txt',
				'content' => ''
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


	public function testGetNote(){
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


	public function testDelete(){
		$title = 'hi';
		$this->container['FileSystem']->expects($this->once())
			->method('unlink')
			->with($this->equalTo('/' . $title . '.txt' ))
			->will($this->returnValue($this->filesystemNotes));
		$this->container['NotesService']->delete($title);
	}


	public function testNoCollision() {
		$title = 'test';
		$id = 2;
		$this->container['FileSystem']->expects($this->any())
			->method('file_exists')
			->will($this->returnValue(true));
		$this->container['FileSystem']->expects($this->once())
			->method('getFileInfo')
			->with($this->equalTo('/' . $title . '.txt'))
			->will($this->returnValue(array('fileid' => $id)));
		$fileName = $this->container['NotesService']
			->generateFileName($title, $id);
		$this->assertEquals($title . '.txt', $fileName);
	}


	public function testNoCollisionFileDoesNotExist() {
		$title = 'test';
		$id = 2;
		$this->container['FileSystem']->expects($this->any())
			->method('file_exists')
			->will($this->returnValue(false));
		$fileName = $this->container['NotesService']
			->generateFileName($title, $id);
		$this->assertEquals($title . '.txt', $fileName);
	}


	public function testCollisionAddParenthesis() {
		$title = 'test';
		$id = 3;
		$this->container['FileSystem']->expects($this->at(0))
			->method('file_exists')
			->will($this->returnValue(true));
		$this->container['FileSystem']->expects($this->at(1))
			->method('getFileInfo')
			->with($this->equalTo('/' . $title . '.txt'))
			->will($this->returnValue(array('fileid' => $id+1)));
		$this->container['FileSystem']->expects($this->at(2))
			->method('file_exists')
			->will($this->returnValue(true));
		$this->container['FileSystem']->expects($this->at(3))
			->method('getFileInfo')
			->with($this->equalTo('/' . $title . ' (2).txt'))
			->will($this->returnValue(array('fileid' => $id)));

		$fileName = $this->container['NotesService']
			->generateFileName($title, $id);
		$this->assertEquals($title . ' (2).txt', $fileName);
	}


	public function testCollisionIncrementParenthesis() {
		$title = 'test';
		$id = 3;
		$this->container['FileSystem']->expects($this->at(0))
			->method('file_exists')
			->will($this->returnValue(true));
		$this->container['FileSystem']->expects($this->at(1))
			->method('getFileInfo')
			->with($this->equalTo('/' . $title . '.txt'))
			->will($this->returnValue(array('fileid' => $id+1)));
		$this->container['FileSystem']->expects($this->at(2))
			->method('file_exists')
			->will($this->returnValue(true));
		$this->container['FileSystem']->expects($this->at(3))
			->method('getFileInfo')
			->with($this->equalTo('/' . $title . ' (2).txt'))
			->will($this->returnValue(array('fileid' => $id+2)));
		$this->container['FileSystem']->expects($this->at(4))
			->method('file_exists')
			->will($this->returnValue(true));
		$this->container['FileSystem']->expects($this->at(5))
			->method('getFileInfo')
			->with($this->equalTo('/' . $title . ' (3).txt'))
			->will($this->returnValue(array('fileid' => $id)));


		$fileName = $this->container['NotesService']
			->generateFileName($title, $id);
		$this->assertEquals($title . ' (3).txt', $fileName);
	}


	public function testCreate() {
		$this->notes[0]->setTitle('New note');
		$this->container['FileSystem']->expects($this->once())
			->method('file_exists')
			->will($this->returnValue(false));
		$this->container['FileSystem']->expects($this->once())
			->method('file_put_contents')
			->with($this->equalTo('/New note.txt'));
		$this->container['FileSystem']->expects($this->once())
			->method('getFileInfo')
			->will($this->returnValue($this->filesystemNotes[0]));

		$note = $this->container['NotesService']->create();
		$this->assertEquals($this->notes[0], $note);
	}	


	public function testUpdate() {
		$id = 3;
		$content = 'yo';
		$title = 'title';
		$this->container['FileSystem']->expects($this->at(0))
			->method('getPath')
			->with($this->equalTo($id))
			->will($this->returnValue('/' . $title . 'txt'));
		$this->container['FileSystem']->expects($this->at(1))
			->method('file_exists')
			->will($this->returnValue(false));
		$this->container['FileSystem']->expects($this->at(2))
			->method('getFileInfo')
			->with($this->equalTo('/' . $title . '.txt'))
			->will($this->returnValue(array('fileid' => $id)));


		$note = $this->container['NotesService']->update($id, $title, $content);
	}


	public function testUpdateRenames() {

	}


}