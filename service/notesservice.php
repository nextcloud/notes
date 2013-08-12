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
				$note = Note::fromFile($file);
				array_push($notes, $note);
			}
		}

		return $notes;
	}


	public function get($id) {
		$path = $this->fileSystem->getPath($id);
		$fileInfo = $this->fileSystem->getFileInfo($path);

		$note = Note::fromFile(array(
			'fileid' => $fileInfo['fileid'],
			'name' => basename($path),
			'content' => $this->fileSystem->file_get_contents($path),
			'mtime' => $fileInfo['mtime']
		));

		return $note;
	}


	/**
	 * If the file exists, rename the file. In both cases update the content
	 */
	public function update($id, $title, $content){
		$title = str_replace(array('/', '\\'), '',  $title);

		$currentFilePath = $this->fileSystem->getPath($id);
		$newFilePath = '/' . $this->generateFileName($title, $id);

		// if the current path is not the new path, the file has to be renamed
		if($currentFilePath !== $newFilePath) {
			$this->fileSystem->rename($currentFilePath, $newFilePath);
		}

		// now update the content
		$this->fileSystem->file_put_contents($newFilePath, $content);

		return $this->get($id);
	}


	public function create() {
		$title = 'New note';

		// check new note exists already and we need to number it
		// pass -1 because no file has id -1 and that will ensure
		// to only return filenames that dont yet exist
		$filePath = $this->generateFileName($title, -1);
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


	/**
	 * get path of file and the title.txt and check if they are the same
	 * file. If not the title needs to be renamed
	 */
	public function generateFileName($title, $id) {
		$path = '/' . $title . '.txt';

		// if file does not exist, that name has not been taken
		if (!$this->fileSystem->file_exists($path)) {
			return $title . '.txt';
		}

		$fileInfo = $this->fileSystem->getFileInfo($path);

		if($fileInfo['fileid'] === $id) {
			return $title . '.txt';
		} else {
			// increments name (2) to name (3)
			$match = preg_match('/\((?P<id>\d+)\)$/', $title, $matches);
			if($match) {
				$newId = ((int) $matches['id']) + 1;
				$newTitle = preg_replace('/(.*)\s\((\d+)\)$/', 
					'$1 (' . $newId . ')', $title);
			} else {
				$newTitle = $title . ' (2)'; 
			}

			return $this->generateFileName($newTitle, $id);
		}

	}


}
