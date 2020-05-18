<?php declare(strict_types=1);

namespace OCA\Notes\Service;

use OCA\Notes\Service\SettingsService;

use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;

class NotesService {

	private $settings;
	private $noteUtil;

	public function __construct(
		SettingsService $settings,
		NoteUtil $noteUtil
	) {
		$this->settings = $settings;
		$this->noteUtil = $noteUtil;
	}

	public function getAll(string $userId) : array {
		$notesFolder = $this->getNotesFolder($userId);
		$data = $this->gatherNoteFiles($notesFolder);
		$fileIds = array_map(function (File $file) : int {
			return $file->getId();
		}, $data['files']);
		// pre-load tags for all notes (performance improvement)
		$this->noteUtil->getTagService()->loadTags($fileIds);
		$notes = array_map(function (File $file) use ($notesFolder) : Note {
			return new Note($file, $notesFolder, $this->noteUtil);
		}, $data['files']);
		return [ 'notes' => $notes, 'categories' => $data['categories'] ];
	}

	public function get(string $userId, int $id) : Note {
		$notesFolder = $this->getNotesFolder($userId);
		return new Note($this->getFileById($notesFolder, $id), $notesFolder, $this->noteUtil);
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

		// try to write some content
		try {
			// If server-side encryption is activated, the server creates an empty file without signature
			// which leads to an GenericEncryptionException('Missing Signature') afterwards.
			// Saving a space-char (and removing it later) is a working work-around.
			$file->putContent(' ');
		} catch (\Throwable $e) {
			// if writing the content fails, we have to roll back the note creation
			$this->delete($userId, $file->getId());
			throw $e;
		}

		return new Note($file, $notesFolder, $this->noteUtil);
	}


	/**
	 * @throws NoteDoesNotExistException if note does not exist
	 */
	public function delete(string $userId, int $id) {
		$notesFolder = $this->getNotesFolder($userId);
		$file = $this->getFileById($notesFolder, $id);
		$parent = $file->getParent();
		$file->delete();
		$this->noteUtil->deleteEmptyFolder($parent, $notesFolder);
	}

	public function getTitleFromContent(string $content) : string {
		// prepare content: remove markdown characters and empty spaces
		$content = preg_replace("/^\s*[*+-]\s+/mu", "", $content); // list item
		$content = preg_replace("/^#+\s+(.*?)\s*#*$/mu", "$1", $content); // headline
		$content = preg_replace("/^(=+|-+)$/mu", "", $content); // separate line for headline
		$content = preg_replace("/(\*+|_+)(.*?)\\1/mu", "$2", $content); // emphasis
		return $this->noteUtil->getSafeTitle($content);
	}






	/**
	 * @param string $userId the user id
	 * @return Folder
	 */
	private function getNotesFolder(string $userId) : Folder {
		$userPath = $this->noteUtil->getRoot()->getUserFolder($userId)->getPath();
		$path = $userPath . '/' . $this->settings->get($userId, 'notesPath');
		try {
			$folder = $this->noteUtil->getOrCreateFolder($path);
		} catch (\Exception $e) {
			throw new NotesFolderException($path);
		}
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
				$data['files'] = array_merge($data['files'], $data_sub['files']);
				$data['categories'] = array_merge($data['categories'], $data_sub['categories']);
			} elseif (self::isNote($node)) {
				$data['files'][] = $node;
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

		if (count($file) <= 0 || !($file[0] instanceof File) || !self::isNote($file[0])) {
			throw new NoteDoesNotExistException();
		}
		return $file[0];
	}
}
