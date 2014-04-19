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

class Note extends Entity {

	public $modified;
	public $title;
	public $content;

	public static function fromFile($file){
		$note = new static();
		$note->setId((int) $file['fileid']);
		$note->setModified((int) $file['mtime']);
		$note->setTitle(substr($file['name'], 0, -4)); // remove trailing .txt
		$note->setContent($file['content']);
		return $note;
	}


}