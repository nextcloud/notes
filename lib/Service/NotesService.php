<?php

namespace OCA\Notes\Service;

use OCP\Encryption\Exceptions\GenericEncryptionException;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IL10N;
use OCP\ILogger;
use OCP\ITagManager;

use OCA\Notes\Db\Note;
use OCA\Notes\Service\SettingsService;

/**
 * Class NotesService
 *
 * @package OCA\Notes\Service
 */
class NotesService {

	private $l10n;
	private $root;
	private $logger;
	private $config;
	private $tags;
	private $settings;
	private $noteUtil;
	private $appName;

	/**
	 * @param IRootFolder $root
	 * @param IL10N $l10n
	 * @param ILogger $logger
	 * @param IConfig $config
	 * @param ITagManager $tagManager
	 * @param SettingsService $settings
	 * @param NoteUtil $noteUtil
	 * @param String $appName
	 */
	public function __construct(
		IRootFolder $root,
		IL10N $l10n,
		ILogger $logger,
		IConfig $config,
		ITagManager $tagManager,
		SettingsService $settings,
		NoteUtil $noteUtil,
		$appName
	) {
		$this->root = $root;
		$this->l10n = $l10n;
		$this->logger = $logger;
		$this->config = $config;
		$this->tags = $tagManager->load('files');
		$this->settings = $settings;
		$this->noteUtil = $noteUtil;
		$this->appName = $appName;
	}


	/**
	 * @param string $userId
	 * @return array with all notes in the current directory
	 */
	public function getAll($userId, $onlyMeta = false) {
		$notesFolder = $this->getFolderForUser($userId);
		$notes = $this->noteUtil->gatherNoteFiles($notesFolder);
		$filesById = [];
		foreach ($notes as $note) {
			$filesById[$note->getId()] = $note;
		}
		$tags = $this->tags->getTagsForObjects(array_keys($filesById));

		$notes = [];
		foreach ($filesById as $id => $file) {
			$noteTags = is_array($tags) && array_key_exists($id, $tags) ? $tags[$id] : [];
			$notes[] = $this->getNote($file, $notesFolder, $noteTags, $onlyMeta);
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
	public function get($id, $userId, $onlyMeta = false) : Note {
		$folder = $this->getFolderForUser($userId);
		return $this->getNote($this->getFileById($folder, $id), $folder, $this->getTags($id), $onlyMeta);
	}

	private function getTags($id) {
		$tags = $this->tags->getTagsForObjects([$id]);
		return is_array($tags) && array_key_exists($id, $tags) ? $tags[$id] : [];
	}

	private function getNote(File $file, Folder $notesFolder, $tags = [], $onlyMeta = false) : Note {
		$id = $file->getId();
		try {
			$note = Note::fromFile($file, $notesFolder, $tags, $onlyMeta);
		} catch (GenericEncryptionException $e) {
			$message = $this->l10n->t('Encryption Error').': ('.$file->getName().') '.$e->getMessage();
			$note = Note::fromException($message, $file, $notesFolder, array_key_exists($id, $tags) ? $tags[$id] : []);
		} catch (\Exception $e) {
			$message = $this->l10n->t('Error').': ('.$file->getName().') '.$e->getMessage();
			$note = Note::fromException($message, $file, $notesFolder, array_key_exists($id, $tags) ? $tags[$id] : []);
		}
		return $note;
	}


	/**
	 * Creates a note and returns the empty note
	 * @param string $userId
	 * @see update for setting note content
	 * @return Note the newly created note
	 */
	public function create($userId) : Note {
		$title = $this->l10n->t('New note');
		$folder = $this->getFolderForUser($userId);
		$this->noteUtil->ensureSufficientStorage($folder, 1);

		// check new note exists already and we need to number it
		// pass -1 because no file has id -1 and that will ensure
		// to only return filenames that dont yet exist
		$path = $this->noteUtil->generateFileName($folder, $title, $this->settings->get($userId, 'fileSuffix'), -1);
		$file = $folder->newFile($path);

		// try to write some content
		try {
			// If server-side encryption is activated, the server creates an empty file without signature
			// which leads to an GenericEncryptionException('Missing Signature') afterwards.
			// Saving a space-char (and removing it later) is a working work-around.
			$file->putContent(' ');
		} catch (\Throwable $e) {
			// if writing the content fails, we have to roll back the note creation
			$this->delete($file->getId(), $userId);
			throw $e;
		}

		return $this->getNote($file, $folder);
	}


	/**
	 * Updates a note. Be sure to check the returned note since the title is
	 * dynamically generated and filename conflicts are resolved
	 * @param int $id the id of the note used to update
	 * @param string|null $content the content which will be written into the note
	 * the title is generated from the first line of the content
	 * @param string|null $category the category in which the note should be saved
	 * @param int $mtime time of the note modification (optional)
	 * @throws NoteDoesNotExistException if note does not exist
	 * @return \OCA\Notes\Db\Note the updated note
	 */
	public function update($id, $content, $userId, $category = null, $mtime = 0) : Note {
		$notesFolder = $this->getFolderForUser($userId);
		$file = $this->getFileById($notesFolder, $id);
		$title = $this->noteUtil->getSafeTitleFromContent($content===null ? $file->getContent() : $content);

		// rename/move file with respect to title/category
		// this can fail if access rights are not sufficient or category name is illegal
		try {
			$this->noteUtil->moveNote($notesFolder, $file, $category, $title);
		} catch (\OCP\Files\NotPermittedException $e) {
			$err = 'Moving note '.$id.' ('.$title.') to the desired target is not allowed.'
				.' Please check the note\'s target category ('.$category.').';
			$this->logger->error($err, ['app' => $this->appName]);
		} catch (\Exception $e) {
			$err = 'Moving note '.$id.' ('.$title.') to the desired target has failed '
				.'with a '.get_class($e).': '.$e->getMessage();
			$this->logger->error($err, ['app' => $this->appName]);
		}

		if ($content !== null) {
			$this->noteUtil->ensureSufficientStorage($file->getParent(), strlen($content));
			$file->putContent($content);
		}

		if ($mtime) {
			$file->touch($mtime);
		}

		return $this->getNote($file, $notesFolder, $this->getTags($id));
	}

	/**
	 * Set or unset a note as favorite.
	 * @param int $id the id of the note used to update
	 * @param boolean $favorite whether the note should be a favorite or not
	 * @throws NoteDoesNotExistException if note does not exist
	 * @return boolean the new favorite state of the note
	 */
	public function favorite($id, $favorite, $userId) {
		$note = $this->get($id, $userId, true);
		if ($favorite !== $note->getFavorite()) {
			if ($favorite) {
				$this->tags->addToFavorites($id);
			} else {
				$this->tags->removeFromFavorites($id);
			}
			$note = $this->get($id, $userId, true);
		}
		return $note->getFavorite();
	}


	/**
	 * Deletes a note
	 * @param int $id the id of the note which should be deleted
	 * @param string $userId
	 * @throws NoteDoesNotExistException if note does not
	 * exist
	 */
	public function delete($id, $userId) {
		$notesFolder = $this->getFolderForUser($userId);
		$file = $this->getFileById($notesFolder, $id);
		$parent = $file->getParent();
		$file->delete();
		$this->noteUtil->deleteEmptyFolder($notesFolder, $parent);
	}

	/**
	 * @param Folder $folder
	 * @param int $id
	 * @throws NoteDoesNotExistException
	 * @return \OCP\Files\File
	 */
	private function getFileById(Folder $folder, $id) : File {
		$file = $folder->getById($id);

		if (count($file) <= 0 || !($file[0] instanceof File) || !$this->noteUtil->isNote($file[0])) {
			throw new NoteDoesNotExistException();
		}
		return $file[0];
	}

	/**
	 * @param string $userId the user id
	 * @return Folder
	 */
	private function getFolderForUser($userId) : Folder {
		// TODO use IRootFolder->getUserFolder()  ?
		$path = '/' . $userId . '/files/' . $this->settings->get($userId, 'notesPath');
		try {
			$folder = $this->noteUtil->getOrCreateFolder($path);
		} catch (\Exception $e) {
			throw new NotesFolderException($path);
		}
		return $folder;
	}
}
