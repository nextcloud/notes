<?php
/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */

namespace OCA\Notes\Service;

use \OCA\Notes\Db\Note;


class NotesService {

	private $fileSystem;

	public function __construct($fileSystem) {
		$this->fileSystem = $fileSystem;
	}


	public function getAll(){
		$files = $this->fileSystem->getDirectoryContent('/');
		$notes = array();
		
		foreach($files as $file) {
			if($file['type'] === 'file') {
				$file['content'] = ''; // no content because to make it faster
				$note = new Note();
				$note->fromFile($file);
				array_push($notes, $note);
			}
		}

		return $notes;
	}


	public function get($id) {
		$path = $this->fileSystem->getPath($id);
		$fileInfo = $this->fileSystem->getFileInfo($path);

		$note = new Note();

		$note->fromFile(array(
			'fileid' => $fileInfo['fileid'],
			'name' => basename($path),
			'content' => $this->fileSystem->file_get_contents($path),
			'mtime' => $fileInfo['mtime']
		));

		return $note;
	}


	/**
	 * If the file exists, rename the file, otherwise create a new file
	 */
	public function update($id, $content){

		// prevent directory traversal
		/*
		$oldTitle = str_replace(array('/', '\\'), '',  $oldTitle);
		$newTitle = str_replace(array('/', '\\'), '',  $newTitle);

		 update the file name
		if($this->fileSystem->file_exists('/' . $oldTitle . '.txt')){
			$this->fileSystem->rename('/' . $oldTitle . '.txt', '/' . 
				$newTitle . '.txt');
		}

		// in any case save the content
		// in case there is no file, this function creates one
		//	$this->fileSystem->file_put_contents('/' . $newTitle . '.txt', 
		/	                                 $content);*/
	}


	public function create() {

	}


	public function delete($title) {

		// prevent directory traversal
		$title = str_replace(array('/', '\\'), '',  $title);

		$this->fileSystem->unlink('/' . $title . '.txt');
	}


}
