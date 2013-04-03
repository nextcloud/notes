<?php
/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */

namespace OCA\Notes\Db;

use \OCA\AppFramework\Http\Request;
use \OCA\AppFramework\Http\JSONResponse;
use \OCA\AppFramework\Utility\TestUtility;


require_once(__DIR__ . "/../classloader.php");


class NoteTest extends TestUtility {


	public function testFromFile(){
		$file = array(
			'type' => 'file',
			'mtime' => 50,
			'name' => 'hi.txt',
			'content' => 'hehe'
		);

		$note = new Note();
		$note->fromFile($file);

		$this->assertEquals($file['mtime'], $note->getModified());
		$this->assertEquals('hi', $note->getTitle());
		$this->assertEquals($file['content'], $note->getContent());
	}


}
