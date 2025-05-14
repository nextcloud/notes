<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Service;

use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IDBConnection;
use OCP\IUserSession;
use OCP\Share\IManager;
use OCP\Share\IShare;

class NoteUtil {
	private const MAX_TITLE_LENGTH = 100;
	public Util $util;
	private IDBConnection $db;
	private IRootFolder $root;
	private TagService $tagService;
	private IManager $shareManager;
	private IUserSession $userSession;
	private SettingsService $settingsService;

	public function __construct(
		Util $util,
		IRootFolder $root,
		IDBConnection $db,
		TagService $tagService,
		IManager $shareManager,
		IUserSession $userSession,
		SettingsService $settingsService,
	) {
		$this->util = $util;
		$this->root = $root;
		$this->db = $db;
		$this->tagService = $tagService;
		$this->shareManager = $shareManager;
		$this->userSession = $userSession;
		$this->settingsService = $settingsService;
	}

	public function getRoot() : IRootFolder {
		return $this->root;
	}

	public function getPathForUser(File $file) {
		$userFolder = $this->root->getUserFolder($this->userSession->getUser()->getUID());
		return $userFolder->getRelativePath($file->getPath());
	}

	public function getTagService() : TagService {
		return $this->tagService;
	}

	public function getCategoryFolder(Folder $notesFolder, string $category) {
		$path = $notesFolder->getPath();
		// sanitise path
		$cats = explode('/', $category);
		$cats = array_map([$this, 'sanitisePath'], $cats);
		$cats = array_filter($cats, function ($str) {
			return $str !== '';
		});
		$path .= '/' . implode('/', $cats);
		return $this->getOrCreateFolder($path);
	}

	/**
	 * get path of file and the title.txt and check if they are the same
	 * file. If not the title needs to be renamed
	 *
	 * @param Folder $folder a folder to the notes directory
	 * @param string $title the filename which should be used
	 * @param string $suffix the suffix (incl. dot) which should be used
	 * @param int $id the id of the note for which the title should be generated
	 *                used to see if the file itself has the title and not a different file for
	 *                checking for filename collisions
	 * @return string the resolved filename to prevent overwriting different
	 *                files with the same title
	 */
	public function generateFileName(Folder $folder, string $title, string $suffix, int $id) : string {
		$title = $this->getSafeTitle($title);
		$filename = $title . $suffix;

		// if file does not exist, that name has not been taken. Similar we don't
		// need to handle file collisions if it is the filename did not change
		if (!$folder->nodeExists($filename) || $folder->get($filename)->getId() === $id) {
			return $filename;
		} else {
			// increments name (2) to name (3)
			$match = preg_match('/\s\((?P<id>\d+)\)$/u', $title, $matches);
			if ($match) {
				$newId = ((int)$matches['id']) + 1;
				$baseTitle = preg_replace('/\s\(\d+\)$/u', '', $title);
				$idSuffix = ' (' . $newId . ')';
			} else {
				$baseTitle = $title;
				$idSuffix = ' (2)';
			}
			// make sure there's enough room for the ID suffix before appending or it will be
			// trimmed by getSafeTitle() and could cause infinite recursion
			$newTitle = mb_substr($baseTitle, 0, self::MAX_TITLE_LENGTH - mb_strlen($idSuffix), 'UTF-8') . $idSuffix;
			return $this->generateFileName($folder, $newTitle, $suffix, $id);
		}
	}

	public function getSafeTitle(string $content) : string {
		// sanitize: prevent directory traversal, illegal characters and unintended file names
		$content = $this->sanitisePath($content);

		// generate title from the first line of the content
		$splitContent = preg_split("/\R/u", $content, 2);
		$title = trim($splitContent[0]);

		// replace (Unicode) white-space with normal space
		$title = preg_replace('/\s/u', ' ', $title);

		// using a maximum of 100 chars should be enough
		$title = mb_substr($title, 0, self::MAX_TITLE_LENGTH, 'UTF-8');

		// ensure that title is not empty
		if (empty($title)) {
			$title = $this->util->l10n->t('New note');
		}

		return $title;
	}

	/** removes characters that are illegal in a file or folder name on some operating systems */
	private function sanitisePath(string $str) : string {
		// remove characters which are illegal on Windows (includes illegal characters on Unix/Linux)
		// prevents also directory traversal by eliminiating slashes
		// see also \OC\Files\Storage\Common::verifyPosixPath(...)
		$str = str_replace(['*', '|', '/', '\\', ':', '"', '<', '>', '?'], '', $str);

		// if mysql doesn't support 4byte UTF-8, then remove those characters
		// see \OC\Files\Storage\Common::verifyPath(...)
		if (!$this->db->supports4ByteText()) {
			$str = preg_replace('%(?:
                \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
              | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
              | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
              )%xs', '', $str);
		}

		// prevent file to be hidden
		$str = preg_replace('/^[\.\s]+/mu', '', $str);
		return trim($str);
	}

	public function stripMarkdown(string $str) : string {
		// prepare content: remove markdown characters and empty spaces
		$str = preg_replace("/^\s*[*+-]\s+/mu", '', $str); // list item
		$str = preg_replace("/^#+\s+(.*?)\s*#*$/mu", '$1', $str); // headline
		$str = preg_replace('/^(=+|-+)$/mu', '', $str); // separate line for headline
		$str = preg_replace("/(\*+|_+)(.*?)\\1/mu", '$2', $str); // emphasis
		return $str;
	}

	/**
	 * Finds a folder and creates it if non-existent
	 * @param string $path path to the folder
	 * @return Folder
	 */
	public function getOrCreateFolder(string $path, bool $create = true) : Folder {
		$folder = null;
		if ($this->root->nodeExists($path)) {
			$folder = $this->root->get($path);
		} elseif ($create) {
			$folder = $this->root->newFolder($path);
		}

		if (!($folder instanceof Folder)) {
			throw new NotesFolderException($path . ' is not a folder');
		}

		return $folder;
	}

	public function getNotesFolderUserPath(string $userId, bool $saveInitial = false): ?string {
		try {
			$notesFolder = $this->settingsService->get($userId, 'notesPath', $saveInitial);
			return $notesFolder;
		} catch (NotesFolderException $e) {
			$this->util->logger->debug("Failed to get notes folder for user $userId: " . $e->getMessage());
			return null;
		}
	}

	public function getOrCreateNotesFolder(string $userId, bool $create = true) : Folder {
		$userFolder = $this->getRoot()->getUserFolder($userId);
		$notesPath = $this->settingsService->get($userId, 'notesPath');
		$allowShared = $notesPath !== $this->settingsService->getDefaultNotesPath($userId);

		$folder = null;
		$updateNotesPath = false;
		if ($userFolder->nodeExists($notesPath)) {
			$folder = $userFolder->get($notesPath);
			if (!$allowShared && $folder->isShared()) {
				$notesPath = $userFolder->getNonExistingName($notesPath);
				$folder = $userFolder->newFolder($notesPath);
				$updateNotesPath = true;
			}
		} elseif ($create) {
			$folder = $userFolder->newFolder($notesPath);
			$updateNotesPath = true;
		}

		if (!($folder instanceof Folder)) {
			throw new NotesFolderException($notesPath . ' is not a folder');
		}

		if ($updateNotesPath) {
			$this->settingsService->set($userId, [
				'notesPath' => $notesPath,
			], true);
		}

		return $folder;
	}

	/*
	 * Delete a folder and it's parent(s) if it's/they're empty
	 * @param Folder $folder folder to delete
	 * @param Folder $notesFolder root notes folder
	 */
	public function deleteEmptyFolder(Folder $folder, Folder $notesFolder) : void {
		$content = $folder->getDirectoryListing();
		$isEmpty = !count($content);
		$isNotesFolder = $folder->getPath() === $notesFolder->getPath();
		if ($isEmpty && !$isNotesFolder) {
			$this->util->logger->debug('Deleting empty category folder ' . $folder->getPath());
			$parent = $folder->getParent();
			$folder->delete();
			$this->deleteEmptyFolder($parent, $notesFolder);
		}
	}

	/**
	 * Checks if there is enough space left on storage. Throws an Exception if storage is not sufficient.
	 * @param Folder $folder that needs storage
	 * @param int $requiredBytes amount of storage needed in $folder
	 * @throws InsufficientStorageException
	 */
	public function ensureSufficientStorage(Folder $folder, int $requiredBytes) : void {
		$availableBytes = $folder->getFreeSpace();
		if ($availableBytes >= 0 && $availableBytes < $requiredBytes) {
			$this->util->logger->error(
				'Insufficient storage in ' . $folder->getPath() . ': ' .
				'available are ' . $availableBytes . '; ' .
				'required are ' . $requiredBytes
			);
			throw new InsufficientStorageException($requiredBytes . ' are required in ' . $folder->getPath());
		}
	}

	/**
	 * Checks if the file/folder is writable. Throws an Exception if not.
	 * @param Node $node to be checked
	 * @throws NoteNotWritableException
	 */
	public function ensureNoteIsWritable(Node $node) : void {
		if (!$node->isUpdateable()) {
			throw new NoteNotWritableException();
		}
	}

	public function getShareTypes(File $file): array {
		$userId = $file->getOwner()->getUID();
		$requestedShareTypes = [
			IShare::TYPE_USER,
			IShare::TYPE_GROUP,
			IShare::TYPE_LINK,
			IShare::TYPE_REMOTE,
			IShare::TYPE_EMAIL,
			IShare::TYPE_ROOM,
			IShare::TYPE_DECK,
			// FIXME: Move to constant once Nextcloud 26 is the minimum supported version
			15, // IShare::TYPE_SCIENCEMESH,
		];
		$shareTypes = [];

		foreach ($requestedShareTypes as $shareType) {
			$shares = $this->shareManager->getSharesBy($userId, $shareType, $file, false, 1, 0);

			if (count($shares)) {
				$shareTypes[] = $shareType;
			}
		}

		return $shareTypes;
	}
}
