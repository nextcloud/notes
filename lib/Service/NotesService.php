<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2013 Bernhard Posselt <nukeawhale@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Service;

use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\NotPermittedException;

class NotesService {
	private MetaService $metaService;
	private SettingsService $settings;
	private NoteUtil $noteUtil;

	public function __construct(
		MetaService $metaService,
		SettingsService $settings,
		NoteUtil $noteUtil,
	) {
		$this->metaService = $metaService;
		$this->settings = $settings;
		$this->noteUtil = $noteUtil;
	}

	public function getAll(string $userId, bool $autoCreateNotesFolder = false) : array {
		$customExtension = $this->getCustomExtension($userId);
		try {
			$notesFolder = $this->getNotesFolder($userId, $autoCreateNotesFolder);
			$data = self::gatherNoteFiles($customExtension, $notesFolder);
			$fileIds = array_keys($data['files']);
			// pre-load tags for all notes (performance improvement)
			$this->noteUtil->getTagService()->loadTags($fileIds);
			$notes = array_map(function (File $file) use ($notesFolder) : Note {
				return new Note($file, $notesFolder, $this->noteUtil);
			}, $data['files']);
		} catch (NotesFolderException $e) {
			$notes = [];
			$data = [ 'categories' => [] ];
		}
		return [ 'notes' => $notes, 'categories' => $data['categories'] ];
	}

	public function getTopNotes(string $userId) : array {
		$notes = $this->getAll($userId)['notes'];
		usort($notes, function (Note $a, Note $b) {
			$favA = $a->getFavorite();
			$favB = $b->getFavorite();
			if ($favA === $favB) {
				return $b->getModified() - $a->getModified();
			} else {
				return $favA > $favB ? -1 : 1;
			}
		});
		return $notes;
	}

	public function countNotes(string $userId) : int {
		$customExtension = $this->getCustomExtension($userId);
		try {
			$notesFolder = $this->getNotesFolder($userId, false);
			$data = self::gatherNoteFiles($customExtension, $notesFolder);
			return count($data['files']);
		} catch (NotesFolderException $e) {
			return 0;
		}
	}

	/**
	 * @throws NoteDoesNotExistException
	 */
	public function get(string $userId, int $id) : Note {
		$customExtension = $this->getCustomExtension($userId);
		$notesFolder = $this->getNotesFolder($userId);
		$note = new Note(self::getFileById($customExtension, $notesFolder, $id), $notesFolder, $this->noteUtil);
		$this->metaService->update($userId, $note);
		return $note;
	}

	public function search(string $userId, string $search) : array {
		$terms = preg_split('/\s+/', $search);
		$notes = $this->getAll($userId)['notes'];
		return array_values(array_filter(
			$notes,
			function (Note $note) use ($terms) : bool {
				return $this->searchTermsInNote($note, $terms);
			}
		));
	}
	private function searchTermsInNote(Note $note, array $terms) : bool {
		try {
			$d = $note->getData();
			$strings = [ $d['title'], $d['category'], $d['content'] ];
			foreach ($terms as $term) {
				if (!$this->searchTermInData($strings, $term)) {
					return false;
				}
			}
			return true;
		} catch (\Throwable $e) {
			return false;
		}
	}
	private function searchTermInData(array $strings, string $term) : bool {
		foreach ($strings as $str) {
			if (stripos($str, $term) !== false) {
				return true;
			}
		}
		return false;
	}


	/**
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function create(string $userId, string $title, string $category) : Note {
		// get folder based on category
		$notesFolder = $this->getNotesFolder($userId);
		$folder = $this->noteUtil->getCategoryFolder($notesFolder, $category);
		$this->noteUtil->ensureSufficientStorage($folder, 1);

		// get file name
		$fileSuffix = $this->settings->get($userId, 'fileSuffix');
		if ($fileSuffix === 'custom') {
			$fileSuffix = $this->settings->get($userId, 'customSuffix');
		}
		$filename = $this->noteUtil->generateFileName($folder, $title, $fileSuffix, -1);
		// create file
		$file = $folder->newFile($filename);

		return new Note($file, $notesFolder, $this->noteUtil);
	}


	/**
	 * @throws NoteDoesNotExistException if note does not exist
	 */
	public function delete(string $userId, int $id) {
		$customExtension = $this->getCustomExtension($userId);
		$notesFolder = $this->getNotesFolder($userId);
		$file = self::getFileById($customExtension, $notesFolder, $id);
		$this->noteUtil->ensureNoteIsWritable($file);
		$parent = $file->getParent();
		$file->delete();
		$this->noteUtil->deleteEmptyFolder($parent, $notesFolder);
	}

	public function getTitleFromContent(string $content) : string {
		$content = $this->noteUtil->stripMarkdown($content);
		return $this->noteUtil->getSafeTitle($content);
	}

	private function getNotesFolder(string $userId, bool $create = true) : Folder {
		return $this->noteUtil->getOrCreateNotesFolder($userId, $create);
	}

	/**
	 * gather note files in given directory and all subdirectories
	 */
	private static function gatherNoteFiles(
		string $customExtension,
		Folder $folder,
		string $categoryPrefix = '',
	) : array {
		$data = [
			'files' => [],
			'categories' => [],
		];
		$nodes = $folder->getDirectoryListing();
		foreach ($nodes as $node) {
			if ($node->getType() === FileInfo::TYPE_FOLDER && $node instanceof Folder) {
				$subCategory = $categoryPrefix . $node->getName();
				$data['categories'][] = $subCategory;
				$data_sub = self::gatherNoteFiles($customExtension, $node, $subCategory . '/');
				$data['files'] = $data['files'] + $data_sub['files'];
				$data['categories'] = $data['categories'] + $data_sub['categories'];
			} elseif (self::isNote($node, $customExtension)) {
				$data['files'][$node->getId()] = $node;
			}
		}
		return $data;
	}

	/**
	 * test if file is a note
	 */
	private static function isNote(FileInfo $file, string $customExtension) : bool {
		static $allowedExtensions = ['txt', 'org', 'markdown', 'md', 'note'];
		$ext = strtolower(pathinfo($file->getName(), PATHINFO_EXTENSION));
		return $file->getType() === 'file' && (in_array($ext, $allowedExtensions) || $ext === $customExtension);
	}

	/**
	 * Retrieve the value of user defined files extension
	 */
	private function getCustomExtension(string $userId) {
		$suffix = $this->settings->get($userId, 'customSuffix');
		return ltrim($suffix, '.');
	}

	/**
	 * @throws NoteDoesNotExistException
	 */
	private static function getFileById(string $customExtension, Folder $folder, int $id) : File {
		$file = $folder->getById($id);

		if (!array_key_exists(0, $file) || !($file[0] instanceof File) || !self::isNote($file[0], $customExtension)) {
			throw new NoteDoesNotExistException();
		}
		return $file[0];
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return \OCP\Files\File
	 */
	public function getAttachment(string $userId, int $noteid, string $path) : File {
		$note = $this->get($userId, $noteid);
		$notesFolder = $this->getNotesFolder($userId);
		$path = str_replace('\\', '/', $path); // change windows style path
		$p = explode('/', $note->getCategory());
		// process relative target path
		foreach (explode('/', $path) as $f) {
			if ($f == '..') {
				array_pop($p);
			} elseif ($f !== '') {
				array_push($p, $f);
			}
		}
		$targetNode = $notesFolder->get(implode('/', $p));
		assert($targetNode instanceof \OCP\Files\File);
		return $targetNode;
	}

	/**
	 * @param $userId
	 * @param $noteid
	 * @param $fileDataArray
	 * @throws NotPermittedException
	 * @throws ImageNotWritableException
	 *                                   https://github.com/nextcloud/deck/blob/master/lib/Service/AttachmentService.php
	 */
	public function createImage(string $userId, int $noteid, $fileDataArray) {
		$note = $this->get($userId, $noteid);
		$notesFolder = $this->getNotesFolder($userId);
		$parent = $this->noteUtil->getCategoryFolder($notesFolder, $note->getCategory());

		// try to generate long id, if not available on system fall back to a shorter one
		try {
			$filename = bin2hex(random_bytes(16));
		} catch (\Exception $e) {
			$filename = uniqid();
		}
		$filename = $filename . '.' . explode('.', $fileDataArray['name'])[1];

		if ($fileDataArray['tmp_name'] === '') {
			throw new ImageNotWritableException();
		}

		// read uploaded file from disk
		$fp = fopen($fileDataArray['tmp_name'], 'r');
		$content = fread($fp, $fileDataArray['size']);
		fclose($fp);

		$result = [];
		$result['filename'] = $filename;
		$this->noteUtil->getRoot()->newFile($parent->getPath() . '/' . $filename, $content);
		return $result;
	}
}
