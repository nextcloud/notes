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

	public function fromFile($file){
		$this->id = (int) $file['fileid'];
		$this->modified = (int) $file['mtime'];
		$this->title = substr($file['name'], 0, -4); // remove trailing .txt
		$this->content = $file['content'];
	}


}