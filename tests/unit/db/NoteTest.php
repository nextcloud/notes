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

use PHPUnit_Framework_TestCase;


class NoteTest extends PHPUnit_Framework_TestCase {


    public function testFromFile(){
        $file = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(3));
        $file->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue('content'));
        $file->expects($this->any())
            ->method('getMTime')
            ->will($this->returnValue(323));
        $file->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('file.txt'));

        $note = Note::fromFile($file);

        $this->assertEquals(3, $note->getId());
        $this->assertEquals(323, $note->getModified());
        $this->assertEquals('file', $note->getTitle());
        $this->assertEquals('content', $note->getContent());
    }


}
