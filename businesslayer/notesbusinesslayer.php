<?php
/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */

namespace OCA\Notes\BusinessLayer;

use \OCA\Notes\Db\Note;


class NotesBusinessLayer {

	private $fileSystem;

	public function __construct($fileSystem) {
		$this->fileSystem = $fileSystem;
	}


	public function getAllNotes(){
		$files = $this->fileSystem->getDirectoryContent('/');
		$notes = array();
		
		foreach($files as $file) {
			if($file['type'] === 'file') {
				$file['content'] = ''; // no content because this is fast ;)
				$note = new Note();
				$note->fromFile($file);
				array_push($notes, $note);
			}
		}

		return $notes;
	}


	public function getNote($title) {

		// prevent directory traversal
		$title = str_replace(array('/', '\\'), '',  $title);

		$note = new Note();

		$note->fromFile(array(
			'name' => $title,
			'content' => $this->fileSystem->file_get_contents('/' . $title . '.txt'),
			'mtime' => $this->fileSystem->filemtime('/' . $title . '.txt')
		));

		return $note;
	}


	/**
	 * If the file exists, rename the file, otherwise create a new file
	 */
	public function saveNote($oldTitle, $newTitle, $content){

		// prevent directory traversal
		$oldTitle = str_replace(array('/', '\\'), '',  $oldTitle);
		$newTitle= str_replace(array('/', '\\'), '',  $newTitle);

		// rename if the old file exists
		if($this->fileSystem->file_exists('/' . $oldTitle . '.txt')){
			$this->fileSystem->rename('/' . $oldTitle . '.txt', '/' . 
				$newTitle . '.txt');
		}

		// in any case save the content
		$this->fileSystem->file_put_contents('/' . $newTitle . '.txt', 
			                                 $content);

	}


	public function deleteNote($title) {

		// prevent directory traversal
		$title = str_replace(array('/', '\\'), '',  $title);

		$this->fileSystem->unlink('/' . $title . '.txt');
	}


}
