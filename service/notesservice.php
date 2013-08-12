<?php
/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */

namespace OCA\Notes\Service;

use \OCA\Notes\Db\Note;
use \OCA\Notes\Utility\FileSystemUtility;


class NotesService {

	private $fileSystem;
	private $fileSystemUtility;

	public function __construct($fileSystem, 
	                            FileSystemUtility $fileSystemUtility) {
		$this->fileSystem = $fileSystem;
		$this->fileSystemUtility = $fileSystemUtility;
	}


	public function getAll(){
		$files = $this->fileSystem->getDirectoryContent('/');
		$notes = array();
		
		foreach($files as $file) {
			if($file['type'] === 'file') {
				$file['content'] = ''; // no content because to make it faster
				$note = Note::fromFile($file);
				array_push($notes, $note);
			}
		}

		return $notes;
	}


	public function get($id) {
		$path = $this->fileSystem->getPath($id);
		$fileInfo = $this->fileSystem->getFileInfo($path);

		return Note::fromFile(array(
			'fileid' => $fileInfo['fileid'],
			'name' => basename($path),
			'content' => $this->fileSystem->file_get_contents($path),
			'mtime' => $fileInfo['mtime']
		));
	}


	/**
	 * If the file exists, rename the file. In both cases update the content
	 */
	public function update($id, $title, $content){
		$title = str_replace(array('/', '\\'), '',  $title);

		$currentFilePath = $this->fileSystem->getPath($id);
		$newFilePath = '/' . $this->fileSystemUtility
			->generateFileName($title, $id);

		// if the current path is not the new path, the file has to be renamed
		if($currentFilePath !== $newFilePath) {
			$this->fileSystem->rename($currentFilePath, $newFilePath);
		}

		// now update the content
		$this->fileSystem->file_put_contents($newFilePath, $content);
		$fileInfo = $this->fileSystem->getFileInfo($newFilePath);

		return Note::fromFile(array(
			'fileid' => $id,
			'name' => basename($newFilePath),
			'content' => $content,
			'mtime' => $fileInfo['mtime']
		));
	}


	public function create() {
		$title = 'New note';

		// check new note exists already and we need to number it
		// pass -1 because no file has id -1 and that will ensure
		// to only return filenames that dont yet exist
		$filePath = $this->fileSystemUtility
			->generateFileName($title, -1);
		$this->fileSystem->file_put_contents('/' . $filePath, '');
		$fileInfo = $this->fileSystem->getFileInfo($filePath);

		return Note::fromFile(array(
			'fileid' => $fileInfo['fileid'],
			'name' => basename($filePath),
			'content' => '',
			'mtime' => $fileInfo['mtime']
		));
	}


	public function delete($title) {
		// prevent directory traversal
		$title = str_replace(array('/', '\\'), '',  $title);
		$this->fileSystem->unlink('/' . $title . '.txt');
	}





}
