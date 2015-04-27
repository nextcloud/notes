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

use OCP\IL10N;
use OCP\Files\IRootFolder;
use OCP\Files\Folder;

use OCA\Notes\Db\Note;

/**
 * Class NotesService
 *
 * @package OCA\Notes\Service
 */
class NotesService {

	private $l10n;

	/**
	 * @param IRootFolder $root
	 * @param IL10N $l10n
	 */
	public function __construct (IRootFolder $root, IL10N $l10n) {
		$this->root = $root;
		$this->l10n = $l10n;
	}


	/**
	 * @param string $userId
	 * @return array with all notes in the current directory
	 */
	public function getAll ($userId){
		$folder = $this->getFolderForUser($userId);
		$files = $folder->getDirectoryListing();
		$notes = [];

		foreach($files as $file) {
			if($file->getType() === 'file' && $file->getMimeType() === 'text/plain') {
				$notes[] = Note::fromFile($file);
			}
		}

		return $notes;
	}


	/**
	 * Used to get a single note by id
	 * @param int $id the id of the note to get
	 * @param string $userId
	 * @throws NoteDoesNotExistException if note does not exist
	 * @return Note
	 */
	public function get ($id, $userId) {
		$folder = $this->getFolderForUser($userId);
		return Note::fromFile($this->getFileById($folder, $id, $userId));
	}


	/**
	 * Creates a note and returns the empty note
	 * @param string $userId
	 * @see update for setting note content
	 * @return Note the newly created note
	 */
	public function create ($userId) {
		$title = $this->l10n->t('New note');
		$folder = $this->getFolderForUser($userId);

		// check new note exists already and we need to number it
		// pass -1 because no file has id -1 and that will ensure
		// to only return filenames that dont yet exist
		$path = $this->generateFileName($folder, $title, -1);
		$file = $folder->newFile($path);

		return Note::fromFile($file);
	}


	/**
	 * Updates a note. Be sure to check the returned note since the title is
	 * dynamically generated and filename conflicts are resolved
	 * @param int $id the id of the note used to update
	 * @param string $content the content which will be written into the note
	 * the title is generated from the first line of the content
	 * @throws NoteDoesNotExistException if note does not exist
	 * @return \OCA\Notes\Db\Note the updated note
	 */
	public function update ($id, $content, $userId){
		$folder = $this->getFolderForUser($userId);
		$file = $this->getFileById($folder, $id, $userId);

		// generate content from the first line of the title
		$splitContent = explode("\n", $content);
		$title = $splitContent[0];

		if(!$title) {
			$title = $this->l10n->t('New note');
		}

		// prevent directory traversal
		$title = str_replace(array('/', '\\'), '',  $title);
		// using a maximum of 100 chars should be enough
		$title = substr($title, 0, 100);

		// generate filename if there were collisions
		$currentFilePath = $file->getPath();
		$basePath = '/' . $userId . '/files/Notes/';
		$newFilePath = $basePath . $this->generateFileName($folder, $title, $id);

		// if the current path is not the new path, the file has to be renamed
		if($currentFilePath !== $newFilePath) {
			$file->move($newFilePath);
		}

		$file->putContent($content);

		return Note::fromFile($file);
	}


	/**
	 * Deletes a note
	 * @param int $id the id of the note which should be deleted
	 * @param string $userId
	 * @throws NoteDoesNotExistException if note does not
	 * exist
	 */
	public function delete ($id, $userId) {
		$folder = $this->getFolderForUser($userId);
		$file = $this->getFileById($folder, $id, $userId);
		$file->delete();
	}


	/**
	 * @param Folder $folder
	 * @param int $id
	 * @param string $userId
	 * @throws NoteDoesNotExistException
	 * @return \OCP\Files\File
	 */
	private function getFileById ($folder, $id, $userId) {
		$file = $folder->getById($id);

		if(count($file) <= 0 || $file[0]->getMimeType() !== 'text/plain') {
			throw new NoteDoesNotExistException();
		}

		return $file[0];
	}


	/**
	 * @param string $userId the user id
	 * @return Folder
	 */
	private function getFolderForUser ($userId) {
		$path = '/' . $userId . '/files/Notes';
		if ($this->root->nodeExists($path)) {
			$folder = $this->root->get($path);
		} else {
			$folder = $this->root->newFolder($path);
		}
		return $folder;
	}


	/**
	 * get path of file and the title.txt and check if they are the same
	 * file. If not the title needs to be renamed
	 * @param Folder $folder a folder to the notes directory
	 * @param string $title the filename which should be used, .txt is appended
	 * @param int $id the id of the note for which the title should be generated
	 * used to see if the file itself has the title and not a different file for
	 * checking for filename collisions
	 * @return string the resolved filename to prevent overwriting different
	 * files with the same title
	 */
	private function generateFileName (Folder $folder, $title, $id) {
		$path = $title . '.txt';

		// if file does not exist, that name has not been taken. Similar we don't
		// need to handle file collisions if it is the filename did not change
		if (!$folder->nodeExists($path) || $folder->get($path)->getId() === $id) {
			return $path;
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
			return $this->generateFileName($folder, $newTitle, $id);
		}
	}


}
