<?php
/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */

namespace OCA\Notes\Db;


class Note {

	public $modified;
	public $title;
	public $content;

	public function fromFile($file){
		$this->modified = (int) $file['mtime'];
		$this->title = $file['name'];
		$this->content = $file['content'];
	}


	public function getModified(){ return $this->modified; }
	public function getTitle(){ return $this->title; }
	public function getContent(){ return $this->content; }
}