<?php
/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */

namespace OCA\Notes\Service;

use \OCA\AppFramework\Core\API;

use \OCA\Notes\Db\Note;
use \OCA\Notes\Utility\FileSystemUtility;


class NotesService {

	private $fileSystem;
	private $fileSystemUtility;
	private $api;

	public function __construct($fileSystem,
	                            FileSystemUtility $fileSystemUtility,
	                            API $api) {
		$this->fileSystem = $fileSystem;
		$this->fileSystemUtility = $fileSystemUtility;
		$this->api = $api;
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


	/**
	 * @throws \OCA\Notes\Service\NoteDoesNotExistExcpetion if note does not exist
	 */
	public function get($id) {
		$path = $this->fileSystem->getPath($id);
		if($path === null) {
			throw new NoteDoesNotExistException();
		}

		$fileInfo = $this->fileSystem->getFileInfo($path);

		return Note::fromFile(array(
			'fileid' => $fileInfo['fileid'],
			'name' => basename($path),
			'content' => $this->fileSystem->file_get_contents($path),
			'mtime' => $fileInfo['mtime']
		));
	}


	public function create() {
		$title = $this->api->getTrans()->t('New note');

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


	/**
	 * @throws \OCA\Notes\Service\NoteDoesNotExistExcpetion if note does not exist
	 */
	public function update($id, $title, $content){
		$title = str_replace(array('/', '\\'), '',  $title);

		$currentFilePath = $this->fileSystem->getPath($id);
		if($currentFilePath === null) {
			throw new NoteDoesNotExistException();
		}

		$newFilePath = '/' . $this->fileSystemUtility
			->generateFileName($title, $id);

		// if the current path is not the new path, the file has to be renamed
		if($currentFilePath !== $newFilePath) {
			$this->fileSystem->rename($currentFilePath, $newFilePath);
		}

		// now update the content
		$this->fileSystem->file_put_contents($newFilePath, $content);
		$mtime = $this->fileSystem->filemtime($newFilePath);

		return Note::fromFile(array(
			'fileid' => $id,
			'name' => basename($newFilePath),
			'content' => $content,
			'mtime' => $mtime
		));
	}


	/**
	 * @throws \OCA\Notes\Service\NoteDoesNotExistExcpetion if note does not exist
	 */
	public function delete($id) {
		$path = $this->fileSystem->getPath($id);
		if($path === null) {
			throw new NoteDoesNotExistException();
		}
		$this->fileSystem->unlink($path);
	}


}
