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
use OCP\Files\IFilenameValidator;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;

class NotesService {
	public function __construct(
		private MetaService $metaService,
		private SettingsService $settings,
		private NoteUtil $noteUtil,
		private IFilenameValidator $filenameValidator,
	) {
	}

	public function getAll(string $userId, bool $autoCreateNotesFolder = false) : array {
		$customExtension = $this->getCustomExtension($userId);
		try {
			$notesFolder = $this->getNotesFolder($userId, $autoCreateNotesFolder);
			$showHidden = $this->settings->getValueBool($userId, 'showHidden');
			$data = self::gatherNoteFiles($customExtension, $notesFolder, $showHidden);
			$fileIds = array_keys($data['files']);
			// pre-load tags for all notes (performance improvement)
			$this->noteUtil->getTagService()->loadTags($fileIds);
			// same for share types, which are otherwise one query per share type
			// per note (performance improvement)
			$this->noteUtil->loadShareTypes($data['folders'], $fileIds);
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
			$showHidden = $this->settings->getValueBool($userId, 'showHidden');
			$data = self::gatherNoteFiles($customExtension, $notesFolder, $showHidden);
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
		$fileSuffix = $this->settings->getValueString($userId, 'fileSuffix');
		if ($fileSuffix === 'custom') {
			$fileSuffix = $this->settings->getValueString($userId, 'customSuffix');
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
		$this->noteUtil->deleteAttachmentFolder($parent, $id);
		$file->delete();
		$this->noteUtil->deleteEmptyFolder($parent, $notesFolder);
	}

	/**
	 * @throws NoteDoesNotExistException
	 */
	public function renameCategory(string $userId, string $oldCategory, string $newCategory) : array {
		$oldCategory = $this->noteUtil->normalizeCategoryPath($oldCategory);
		$newCategory = $this->noteUtil->normalizeCategoryPath($newCategory);
		if ($oldCategory === '' || $newCategory === '') {
			throw new \InvalidArgumentException('Category must not be empty');
		}
		if ($oldCategory === $newCategory) {
			return [
				'oldCategory' => $oldCategory,
				'newCategory' => $newCategory,
			];
		}
		if (str_starts_with($newCategory, $oldCategory . '/')) {
			throw new \InvalidArgumentException('Target category must not be a descendant of source category');
		}

		$notesFolder = $this->getNotesFolder($userId);
		try {
			$oldFolder = $this->noteUtil->getCategoryFolder($notesFolder, $oldCategory, false);
		} catch (NotesFolderException $e) {
			throw new NoteDoesNotExistException();
		}

		if ($notesFolder->nodeExists($newCategory)) {
			throw new \InvalidArgumentException('Target category already exists');
		}

		$targetParentCategory = dirname($newCategory);
		if ($targetParentCategory === '.') {
			$targetParentCategory = '';
		}
		$targetParent = $this->noteUtil->getCategoryFolder($notesFolder, $targetParentCategory, true);

		$oldParent = $oldFolder->getParent();
		$targetPath = $targetParent->getPath() . '/' . basename($newCategory);
		$oldFolder->move($targetPath);
		if ($oldParent instanceof Folder) {
			$this->noteUtil->deleteEmptyFolder($oldParent, $notesFolder);
		}

		return [
			'oldCategory' => $oldCategory,
			'newCategory' => $newCategory,
		];
	}

	/**
	 * @throws NoteDoesNotExistException
	 */
	public function deleteCategory(string $userId, string $category) : array {
		$category = $this->noteUtil->normalizeCategoryPath($category);
		if ($category === '') {
			throw new \InvalidArgumentException('Category must not be empty');
		}

		$notesFolder = $this->getNotesFolder($userId);
		try {
			$folder = $this->noteUtil->getCategoryFolder($notesFolder, $category, false);
		} catch (NotesFolderException $e) {
			// If category folder was already removed (e.g. last note moved away),
			// treat delete as idempotent success.
			return [
				'category' => $category,
			];
		}

		$parent = $folder->getParent();
		$folder->delete();
		if ($parent instanceof Folder) {
			$this->noteUtil->deleteEmptyFolder($parent, $notesFolder);
		}

		return [
			'category' => $category,
		];
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
	 *
	 * `folders` carries every folder that was walked, the given one included.
	 * NoteUtil::loadShareTypes() needs them because the bulk share lookup only
	 * covers a folder's direct children.
	 *
	 * @return array{files: array<int, File>, categories: list<string>, folders: list<Folder>}
	 */
	private static function gatherNoteFiles(
		string $customExtension,
		Folder $folder,
		bool $showHidden,
		string $categoryPrefix = '',
	) : array {
		$data = [
			'files' => [],
			'categories' => [],
			'folders' => [$folder],
		];
		$nodes = $folder->getDirectoryListing();
		foreach ($nodes as $node) {
			$hidden = str_starts_with($node->getName(), '.');
			if ($hidden && !$showHidden) {
				continue;
			}
			// a note's attachment folder is an implementation detail, not a category
			if ($node instanceof Folder && preg_match('/^\.attachments\.\d+$/', $node->getName())) {
				continue;
			}
			if ($node->getType() === FileInfo::TYPE_FOLDER && $node instanceof Folder) {
				$subCategory = $categoryPrefix . $node->getName();
				$data['categories'][] = $subCategory;
				$data_sub = self::gatherNoteFiles($customExtension, $node, $showHidden, $subCategory . '/');
				$data['files'] = $data['files'] + $data_sub['files'];
				$data['categories'] = $data['categories'] + $data_sub['categories'];
				$data['folders'] = array_merge($data['folders'], $data_sub['folders']);
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
		$suffix = $this->settings->getValueString($userId, 'customSuffix');
		return ltrim($suffix, '.');
	}

	/**
	 * @throws NoteDoesNotExistException
	 */
	private static function getFileById(string $customExtension, Folder $folder, int $id) : File {
		$file = $folder->getFirstNodeById($id);

		if (!($file instanceof File) || !self::isNote($file, $customExtension)) {
			throw new NoteDoesNotExistException();
		}
		return $file;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return \OCP\Files\File
	 */
	public function getAttachment(string $userId, int $noteId, string $path) : File {
		$note = $this->get($userId, $noteId);
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
		if (!($targetNode instanceof File)) {
			throw new NoteDoesNotExistException();
		}
		return $targetNode;
	}

	/**
	 * Delete a single attachment from a note's own attachment folder.
	 * Only files inside the note's `.attachments.<id>` folder can be removed;
	 * basename() is used so the given path cannot traverse outside that folder.
	 *
	 * @throws NoteDoesNotExistException if the note or attachment does not exist
	 * @throws NoteNotWritableException if the note is read-only
	 * @throws InvalidPathException if the file name is invalid
	 * @throws NotPermittedException
	 */
	public function deleteAttachment(string $userId, int $noteId, string $path) : void {
		$note = $this->get($userId, $noteId);
		$noteFile = $note->getFile();
		$this->noteUtil->ensureNoteIsWritable($noteFile);

		// restrict deletion to the note's own attachment folder;
		// basename() strips any directory part so the path cannot traverse out
		$fileName = basename($path);
		$this->filenameValidator->validateFilename($fileName);

		$attachmentFolderName = $this->noteUtil->getAttachmentFolderName($noteId);
		$categoryFolder = $noteFile->getParent();
		if (!$categoryFolder->nodeExists($attachmentFolderName)) {
			throw new NoteDoesNotExistException();
		}
		$attachmentFolder = $categoryFolder->get($attachmentFolderName);
		if (!($attachmentFolder instanceof Folder) || !$attachmentFolder->nodeExists($fileName)) {
			throw new NoteDoesNotExistException();
		}
		$target = $attachmentFolder->get($fileName);
		if (!($target instanceof File)) {
			throw new NoteDoesNotExistException();
		}
		$target->delete();

		// tidy up the attachment folder if it is now empty
		if (count($attachmentFolder->getDirectoryListing()) === 0) {
			$attachmentFolder->delete();
		}
	}

	/**
	 * @param $userId
	 * @param $noteId
	 * @param $fileDataArray
	 *
	 * @return array
	 * @throws NotPermittedException
	 * @throws ImageNotWritableException
	 * @throws NotFoundException
	 * @throws InvalidPathException
	 *                              https://github.com/nextcloud/text/blob/main/lib/Service/AttachmentService.php
	 */
	public function createImage(string $userId, int $noteId, $fileDataArray) : array {
		$note = $this->get($userId, $noteId);

		// validate the requested name before it is used in any filesystem lookup
		$this->filenameValidator->validateFilename($fileDataArray['name']);

		if ($fileDataArray['tmp_name'] === '') {
			throw new ImageNotWritableException();
		}

		$saveDir = $this->getAttachmentDirectoryForNote($note, $userId);
		$fileName = self::getUniqueFileName($saveDir, $fileDataArray['name']);

		// read uploaded file from disk
		$fp = fopen($fileDataArray['tmp_name'], 'r');
		$content = fread($fp, $fileDataArray['size']);
		fclose($fp);

		$result = [];
		$result['filename'] = $this->noteUtil->getAttachmentFolderName($note->getId()) . '/' . $fileName;
		$saveDir->newFile($fileName, $content);
		return $result;
	}

	/**
	 * Get unique file name in a directory. Add '(n)' suffix, starting at '(1)' for the first conflict.
	 *
	 * @param Folder $dir
	 * @param string $fileName
	 *
	 * @return string
	 */
	public static function getUniqueFileName(Folder $dir, string $fileName) : string {
		$extension = pathinfo($fileName, PATHINFO_EXTENSION);
		$counter = 0;
		$uniqueFileName = $fileName;
		while ($dir->nodeExists($uniqueFileName)) {
			$counter++;
			if ($extension !== '') {
				$uniqueFileName = (string)preg_replace('/\.' . preg_quote($extension, '/') . '$/', ' (' . $counter . ').' . $extension, $fileName);
			} else {
				$uniqueFileName = $fileName . ' (' . $counter . ')';
			}
		}
		return $uniqueFileName;
	}

	/**
	 * Get or create note-specific attachment folder
	 *
	 * @param Note $note
	 * @param string $userId
	 *
	 * @return Folder
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 * @throws InvalidPathException
	 */
	private function getAttachmentDirectoryForNote(Note $note, string $userId) : Folder {
		$notesFolder = $this->getNotesFolder($userId);
		$parentFolder = $this->noteUtil->getCategoryFolder($notesFolder, $note->getCategory());

		$attachmentFolderName = $this->noteUtil->getAttachmentFolderName($note->getId());
		if ($parentFolder->nodeExists($attachmentFolderName)) {
			$attachmentFolder = $parentFolder->get($attachmentFolderName);
			if ($attachmentFolder instanceof Folder) {
				return $attachmentFolder;
			}
		} else {
			return $parentFolder->newFolder($attachmentFolderName);
		}
		throw new NotFoundException('Attachment dir for note ' . $note->getId() . ' was not found or could not be created.');
	}
}
