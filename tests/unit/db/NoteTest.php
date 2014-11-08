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

namespace OCA\Notes\Db;

class NoteTest extends \PHPUnit_Framework_TestCase {


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
