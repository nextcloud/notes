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

		$note1 = new Note();
		$note1->fromFile($this->filesystemNotes[0]);

		$note2 = new Note();
		$note2->fromFile($this->filesystemNotes[2]);

		$this->notes = array(
			$note1, 
			$note2
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
		$expected = new Note();
		$expected->fromFile(
			$this->filesystemNotes[0]
		);

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




/*
	public function SaveNoteRenamesNoteWhenTitleChanged(){
		$newTitle = 'heho';
		$title = $this->filesystemNotes[0]['name'];
		$content = 'content';
		$this->container['FileSystem']->expects($this->once())
			->method('file_exists')
			->with($this->equalTo('/' . $title . '.txt'))
			->will($this->returnValue(true));
		$this->container['FileSystem']->expects($this->once())
			->method('rename')
			->with($this->equalTo('/' . $title . '.txt'),
				$this->equalTo('/' . $newTitle . '.txt'));

		$result = $this->container['NotesService']->saveNote($title, $newTitle, $content);
	}


	public function SaveNoteCreatesAndDoesNotRenameWhenTitleSametleChanged(){
		$newTitle = 'heho';
		$title = $this->filesystemNotes[0]['name'];
		$content = 'content';
		$this->container['FileSystem']->expects($this->once())
			->method('file_exists')
			->with($this->equalTo('/' . $title . '.txt'))
			->will($this->returnValue(false));
		$this->container['FileSystem']->expects($this->never())
			->method('rename');

		$this->container['FileSystem']->expects($this->once())
			->method('file_put_contents')
			->with($this->equalTo('/' . $newTitle . '.txt'),
				$this->equalTo($content));

		$result = $this->container['NotesService']->saveNote($title, $newTitle, $content);
	}
*/
}