<?php
/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */

namespace OCA\Notes\Db;

use \OCA\AppFramework\Http\Request;
use \OCA\AppFramework\Http\JSONResponse;
use \OCA\AppFramework\Utility\TestUtility;


class NoteTest extends TestUtility {


	public function testFromFile(){
		$file = array(
			'fileid' => 1,
			'type' => 'file',
			'mtime' => 50,
			'name' => 'hi.txt',
			'content' => 'hehe'
		);

		$note = Note::fromFile($file);

		$this->assertEquals($file['fileid'], $note->getId());
		$this->assertEquals($file['mtime'], $note->getModified());
		$this->assertEquals('hi', $note->getTitle());
		$this->assertEquals($file['content'], $note->getContent());
	}


}
