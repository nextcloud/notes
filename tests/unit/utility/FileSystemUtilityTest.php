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

namespace OCA\Notes\Utility;


class FileSystemUtilityTest extends \OCA\Notes\Tests\Unit\NotesUnitTest {


	public function testNoCollision() {
		$title = 'test';
		$id = 2;
		$this->container->query('FileSystem')->expects($this->any())
			->method('file_exists')
			->will($this->returnValue(true));
		$this->container->query('FileSystem')->expects($this->once())
			->method('getFileInfo')
			->with($this->equalTo('/' . $title . '.txt'))
			->will($this->returnValue(array('fileid' => $id)));
		$fileName = $this->container->query('FileSystemUtility')
			->generateFileName($title, $id);
		$this->assertEquals($title . '.txt', $fileName);
	}


	public function testNoCollisionFileDoesNotExist() {
		$title = 'test';
		$id = 2;
		$this->container->query('FileSystem')->expects($this->any())
			->method('file_exists')
			->will($this->returnValue(false));
		$fileName = $this->container->query('FileSystemUtility')
			->generateFileName($title, $id);
		$this->assertEquals($title . '.txt', $fileName);
	}


	public function testCollisionAddParenthesis() {
		$title = 'test';
		$id = 3;
		$this->container->query('FileSystem')->expects($this->at(0))
			->method('file_exists')
			->will($this->returnValue(true));
		$this->container->query('FileSystem')->expects($this->at(1))
			->method('getFileInfo')
			->with($this->equalTo('/' . $title . '.txt'))
			->will($this->returnValue(array('fileid' => $id+1)));
		$this->container->query('FileSystem')->expects($this->at(2))
			->method('file_exists')
			->will($this->returnValue(true));
		$this->container->query('FileSystem')->expects($this->at(3))
			->method('getFileInfo')
			->with($this->equalTo('/' . $title . ' (2).txt'))
			->will($this->returnValue(array('fileid' => $id)));

		$fileName = $this->container->query('FileSystemUtility')
			->generateFileName($title, $id);
		$this->assertEquals($title . ' (2).txt', $fileName);
	}


	public function testCollisionIncrementParenthesis() {
		$title = 'test';
		$id = 3;
		$this->container->query('FileSystem')->expects($this->at(0))
			->method('file_exists')
			->will($this->returnValue(true));
		$this->container->query('FileSystem')->expects($this->at(1))
			->method('getFileInfo')
			->with($this->equalTo('/' . $title . '.txt'))
			->will($this->returnValue(array('fileid' => $id+1)));
		$this->container->query('FileSystem')->expects($this->at(2))
			->method('file_exists')
			->will($this->returnValue(true));
		$this->container->query('FileSystem')->expects($this->at(3))
			->method('getFileInfo')
			->with($this->equalTo('/' . $title . ' (2).txt'))
			->will($this->returnValue(array('fileid' => $id+2)));
		$this->container->query('FileSystem')->expects($this->at(4))
			->method('file_exists')
			->will($this->returnValue(true));
		$this->container->query('FileSystem')->expects($this->at(5))
			->method('getFileInfo')
			->with($this->equalTo('/' . $title . ' (3).txt'))
			->will($this->returnValue(array('fileid' => $id)));


		$fileName = $this->container->query('FileSystemUtility')
			->generateFileName($title, $id);
		$this->assertEquals($title . ' (3).txt', $fileName);
	}



}