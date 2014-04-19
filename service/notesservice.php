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

namespace OCA\Notes\Service;

use \OCP\IL10N;

use \OCA\Notes\Db\Note;
use \OCA\Notes\Utility\FileSystemUtility;

class NotesService {

	private $fileSystem;
	private $fileSystemUtility;
	private $l10n;

	/**
	 * @param \OC\Files\View $fileSystem a filesystem which points to the users
	 * notes directory
	 * @param \OCA\Notes\Utility\FileSystemUtility $fileSystemUtility utility
	 * for handling conflicting resolution for files with the same title
	 * @param \OCP\IL10N $l10n
	 */
	public function __construct($fileSystem,
	                            FileSystemUtility $fileSystemUtility,
	                            IL10N $l10n) {
		$this->fileSystem = $fileSystem;
		$this->fileSystemUtility = $fileSystemUtility;
		$this->l10n = $l10n;
	}


	/**
	 * @return array with all notes in the current directory
	 */
	public function getAll(){
		$files = $this->fileSystem->getDirectoryContent('/');
		$notes = array();

		foreach($files as $file) {
			if($file['type'] === 'file' && $file['mimetype'] === 'text/plain') {
				$path = $this->fileSystem->getPath($file['fileid']);
				$content = $this->fileSystem->file_get_contents($path);
				$file['content'] = $content;
				$note = Note::fromFile($file);
				array_push($notes, $note);
			}
		}

		return $notes;
	}


	/**
	 * Used to get a single note by id
	 * @param int $id the id of the note to get
	 * @throws \OCA\Notes\Service\NoteDoesNotExistExcpetion if note does not
	 * exist
	 * @return \OCA\Notes\Db\Note
	 */
	public function get($id) {
		$path = $this->fileSystem->getPath($id);
		if($path === null) {
			throw new NoteDoesNotExistException();
		}

		$fileInfo = $this->fileSystem->getFileInfo($path);

		if($fileInfo['mimetype'] !== 'text/plain') {
			throw new NoteDoesNotExistException();
		}

		return Note::fromFile(array(
			'fileid' => $fileInfo['fileid'],
			'name' => basename($path),
			'content' => $this->fileSystem->file_get_contents($path),
			'mtime' => $fileInfo['mtime']
		));
	}


	/**
	 * Creates a note and returns the empty note
	 * @see update for setting note content
	 * @return \OCA\Notes\Db\Note the newly created note
	 */
	public function create() {
		$title = $this->l10n->t('New note');

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
	 * Updates a note. Be sure to check the returned note since the title is
	 * dynamically generated and filename conflicts are resolved
	 * @param int $id the id of the note used to update
	 * @param string $content the content which will be written into the note
	 * the title is generated from the first line of the content
	 * @throws \OCA\Notes\Service\NoteDoesNotExistExcpetion if note does not
	 * exist
	 * @return \OCA\Notes\Db\Note the updated note
	 */
	public function update($id, $content){
		$currentFilePath = $this->fileSystem->getPath($id);
		if($currentFilePath === null) {
			throw new NoteDoesNotExistException();
		}

		// generate content from the first line of the title
		$splitContent = explode("\n", $content);
		$title = $splitContent[0];

		if(!$title) {
			$title = $this->l10n->t('New note');
		}

		// prevent directory traversal
		$title = str_replace(array('/', '\\'), '',  $title);

		// generate filename if there were collisions
		$newFilePath = '/' . $this->fileSystemUtility
			->generateFileName($title, $id);

		// if the current path is not the new path, the file has to be renamed
		if($currentFilePath !== $newFilePath) {
			$this->fileSystem->rename($currentFilePath, $newFilePath);
		}

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
	 * Deletes a note
	 * @param int $id the id of the note which should be deleted
	 * @throws \OCA\Notes\Service\NoteDoesNotExistExcpetion if note does not
	 * exist
	 */
	public function delete($id) {
		$path = $this->fileSystem->getPath($id);
		if($path === null) {
			throw new NoteDoesNotExistException();
		}
		$this->fileSystem->unlink($path);
	}


}
