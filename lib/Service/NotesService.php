<?php

declare(strict_types=1);

namespace OCA\Notes\Service;

use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\NotPermittedException;

class NotesService {
	private $metaService;
	private $settings;
	private $noteUtil;

	public function __construct(
		MetaService $metaService,
		SettingsService $settings,
		NoteUtil $noteUtil
	) {
		$this->metaService = $metaService;
		$this->settings = $settings;
		$this->noteUtil = $noteUtil;
	}

	public function getAll(string $userId) : array {
		$notesFolder = $this->getNotesFolder($userId);
		$data = $this->gatherNoteFiles($notesFolder);
		$fileIds = array_keys($data['files']);
		// pre-load tags for all notes (performance improvement)
		$this->noteUtil->getTagService()->loadTags($fileIds);
		$notes = array_map(function (File $file) use ($notesFolder) : Note {
			return new Note($file, $notesFolder, $this->noteUtil);
		}, $data['files']);
		return [ 'notes' => $notes, 'categories' => $data['categories'] ];
	}

	public function getTopNotes(string $userId, int $count) : array {
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
		return array_slice($notes, 0, $count);
	}

	public function get(string $userId, int $id) : Note {
		$notesFolder = $this->getNotesFolder($userId);
		$note = new Note($this->getFileById($notesFolder, $id), $notesFolder, $this->noteUtil);
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
		$filename = $this->noteUtil->generateFileName($folder, $title, $fileSuffix, -1);

		// create file
		$file = $folder->newFile($filename);

		return new Note($file, $notesFolder, $this->noteUtil);
	}


	/**
	 * @throws NoteDoesNotExistException if note does not exist
	 */
	public function delete(string $userId, int $id) {
		$notesFolder = $this->getNotesFolder($userId);
		$file = $this->getFileById($notesFolder, $id);
		$this->noteUtil->ensureNoteIsWritable($file);
		$parent = $file->getParent();
		$file->delete();
		$this->noteUtil->deleteEmptyFolder($parent, $notesFolder);
	}

	public function getTitleFromContent(string $content) : string {
		$content = $this->noteUtil->stripMarkdown($content);
		return $this->noteUtil->getSafeTitle($content);
	}






	/**
	 * @param string $userId the user id
	 * @return Folder
	 */
	public function getNotesFolder(string $userId) : Folder {
		$userPath = $this->noteUtil->getRoot()->getUserFolder($userId)->getPath();
		$path = $userPath . '/' . $this->settings->get($userId, 'notesPath');
		$folder = $this->noteUtil->getOrCreateFolder($path);
		return $folder;
	}

	/**
	 * gather note files in given directory and all subdirectories
	 */
	private static function gatherNoteFiles(Folder $folder, string $categoryPrefix = '') : array {
		$data = [
			'files' => [],
			'categories' => [],
		];
		$nodes = $folder->getDirectoryListing();
		foreach ($nodes as $node) {
			if ($node->getType() === FileInfo::TYPE_FOLDER && $node instanceof Folder) {
				$subCategory = $categoryPrefix . $node->getName();
				$data['categories'][] = $subCategory;
				$data_sub = self::gatherNoteFiles($node, $subCategory . '/');
				$data['files'] = $data['files'] + $data_sub['files'];
				$data['categories'] = $data['categories'] + $data_sub['categories'];
			} elseif (self::isNote($node)) {
				$data['files'][$node->getId()] = $node;
			}
		}
		return $data;
	}

	/**
	 * test if file is a note
	 */
	private static function isNote(FileInfo $file) : bool {
		static $allowedExtensions = ['txt', 'org', 'markdown', 'md', 'note'];
		$ext = strtolower(pathinfo($file->getName(), PATHINFO_EXTENSION));
		return $file->getType() === 'file' && in_array($ext, $allowedExtensions);
	}

	/**
	 * @throws NoteDoesNotExistException
	 */
	private static function getFileById(Folder $folder, int $id) : File {
		$file = $folder->getById($id);

		if (!array_key_exists(0, $file) || !($file[0] instanceof File) || !self::isNote($file[0])) {
			throw new NoteDoesNotExistException();
		}
		return $file[0];
	}

	/**
	 * @param $cardId
	 * @param $type
	 * @param $data
	 * @return array
	 * https://github.com/nextcloud/deck/blob/master/lib/Service/AttachmentService.php
	 */
	public function createImage($uid, $noteid, $fileDataArray) {
		$note = $this->get($uid, $noteid);
		$notesFolder = $this->getNotesFolder($uid);
		$parent = $this->noteUtil->getCategoryFolder($notesFolder, $note->getCategory());


		// get file name
		// todo: check if it is truly unique
		$filename = uniqid("", true) . "." . explode(".", $fileDataArray['name'])[1];

		// read uploaded file from disk
		$fp = fopen($fileDataArray['tmp_name'], "r");
		$content = fread($fp, $fileDataArray['size']);
		fclose($fp);

		$result['filename'] = $filename;
		$result['filepath'] = $parent->getPath() . "/" . $filename;
		$result['wasUploaded'] = true;

		try {
			$this->noteUtil->getRoot()->newFile($parent->getPath() . "/" . $filename, $content);
		} catch (NotPermittedException $e) {
			$result['wasUploaded'] = false;
		}

		return $result;
	}
}
