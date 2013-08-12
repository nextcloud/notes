<?php
/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */

namespace OCA\Notes\Db;

use \OCA\AppFramework\Db\Entity;


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