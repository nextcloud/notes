<?php
/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */

namespace OCA\Notes\BusinessLayer;

use \OCA\AppFramework\Http\Request;
use \OCA\AppFramework\Http\JSONResponse;
use \OCA\AppFramework\Utility\TestUtility;

use \OCA\Notes\Db\Note;


require_once(__DIR__ . "/../classloader.php");


class NotesBusinessLayerTest extends TestUtility {

	private $fileSystem;
	private $filesystemNotes;
	private $notes;

	public function setUp(){
		$this->fileSystem = $this->getMock('Filesystem', 
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
		$this->bizLayer = new NotesBusinessLayer($this->fileSystem);

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

	public function testTest() {
		
	}

/*
	public function testGetAllNotes(){
		$this->fileSystem->expects($this->once())
			->method('getDirectoryContent')
			->with($this->equalTo('/'))
			->will($this->returnValue($this->filesystemNotes));

		$result = $this->bizLayer->getAllNotes();

		$this->assertEquals($this->notes[0], $result[0]);
		$this->assertEquals($this->notes[1], $result[1]);
		$this->assertCount(2, $result);
	}


	public function testRemove(){
		$title = 'hi';
		$this->fileSystem->expects($this->once())
			->method('unlink')
			->with($this->equalTo('/' . $title . '.txt' ))
			->will($this->returnValue($this->filesystemNotes));
		$this->bizLayer->deleteNote($title);
	}


	public function testGetNote(){
		$expected = new Note();
		$expected->fromFile(
			$this->filesystemNotes[0]
		);

		$this->fileSystem->expects($this->once())
			->method('file_get_contents')
			->with($this->equalTo($expected->getTitle() . '.txt'))
			->will($this->returnValue($this->filesystemNotes[0]['content']));
		$this->fileSystem->expects($this->once())
			->method('getFileInfo')
			->with($this->equalTo($expected->getTitle() . '.txt'))
			->will($this->returnValue($this->filesystemNotes[0]));
		$this->fileSystem->expects($this->once())
			->method('getPath')
			->with($this->equalTo(2))
			->will($this->returnValue($expected->getTitle() . '.txt'));

		$result = $this->bizLayer->getNote(2);

		$this->assertEquals($expected, $result);
	}


	public function SaveNoteRenamesNoteWhenTitleChanged(){
		$newTitle = 'heho';
		$title = $this->filesystemNotes[0]['name'];
		$content = 'content';
		$this->fileSystem->expects($this->once())
			->method('file_exists')
			->with($this->equalTo('/' . $title . '.txt'))
			->will($this->returnValue(true));
		$this->fileSystem->expects($this->once())
			->method('rename')
			->with($this->equalTo('/' . $title . '.txt'),
				$this->equalTo('/' . $newTitle . '.txt'));

		$result = $this->bizLayer->saveNote($title, $newTitle, $content);
	}


	public function SaveNoteCreatesAndDoesNotRenameWhenTitleSametleChanged(){
		$newTitle = 'heho';
		$title = $this->filesystemNotes[0]['name'];
		$content = 'content';
		$this->fileSystem->expects($this->once())
			->method('file_exists')
			->with($this->equalTo('/' . $title . '.txt'))
			->will($this->returnValue(false));
		$this->fileSystem->expects($this->never())
			->method('rename');

		$this->fileSystem->expects($this->once())
			->method('file_put_contents')
			->with($this->equalTo('/' . $newTitle . '.txt'),
				$this->equalTo($content));

		$result = $this->bizLayer->saveNote($title, $newTitle, $content);
	}
*/
}