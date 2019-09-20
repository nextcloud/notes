<?php

namespace OCA\Notes\Util;

use OCP\IL10N;
use OCP\ILogger;
use OCP\Encryption\Exceptions\GenericEncryptionException;
use OCP\Files\IRootFolder;
use OCP\Files\FileInfo;
use OCP\Files\File;
use OCP\Files\Folder;

use OCA\Notes\Db\Note;

class NoteUtil {

	private $l10n;
	private $root;
	private $logger;
	private $appName;

	/**
	 * @param IRootFolder $root
	 * @param IL10N $l10n
	 * @param ILogger $logger
	 * @param String $appName
	 */
	public function __construct(
		IRootFolder $root,
		IL10N $l10n,
		ILogger $logger,
		$appName
	) {
		$this->root = $root;
		$this->l10n = $l10n;
		$this->logger = $logger;
		$this->appName = $appName;
	}

	public function getSafeTitleFromContent($content) {
		// prepare content: remove markdown characters and empty spaces
		$content = preg_replace("/^\s*[*+-]\s+/mu", "", $content); // list item
		$content = preg_replace("/^#+\s+(.*?)\s*#*$/mu", "$1", $content); // headline
		$content = preg_replace("/^(=+|-+)$/mu", "", $content); // separate line for headline
		$content = preg_replace("/(\*+|_+)(.*?)\\1/mu", "$2", $content); // emphasis

		// sanitize: prevent directory traversal, illegal characters and unintended file names
		$content = $this->sanitisePath($content);

		// generate title from the first line of the content
		$splitContent = preg_split("/\R/u", $content, 2);
		$title = trim($splitContent[0]);

		// using a maximum of 100 chars should be enough
		$title = mb_substr($title, 0, 100, "UTF-8");

		// ensure that title is not empty
		if (empty($title)) {
			$title = $this->l10n->t('New note');
		}

		return $title;
	}

	/** removes characters that are illegal in a file or folder name on some operating systems */
	public function sanitisePath($str) {
		// remove characters which are illegal on Windows (includes illegal characters on Unix/Linux)
		// prevents also directory traversal by eliminiating slashes
		// see also \OC\Files\Storage\Common::verifyPosixPath(...)
		$str = str_replace(['*', '|', '/', '\\', ':', '"', '<', '>', '?'], '', $str);

		// if mysql doesn't support 4byte UTF-8, then remove those characters
		// see \OC\Files\Storage\Common::verifyPath(...)
		if (!\OC::$server->getDatabaseConnection()->supports4ByteText()) {
			$str = preg_replace('%(?:
                \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
              | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
              | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
              )%xs', '', $str);
		}

		// prevent file to be hidden
		$str = preg_replace("/^[\. ]+/mu", "", $str);
		return trim($str);
	}

	/**
	 * get path of file and the title.txt and check if they are the same
	 * file. If not the title needs to be renamed
	 *
	 * @param Folder $folder a folder to the notes directory
	 * @param string $title the filename which should be used
	 * @param string $suffix the suffix (incl. dot) which should be used
	 * @param int $id the id of the note for which the title should be generated
	 * used to see if the file itself has the title and not a different file for
	 * checking for filename collisions
	 * @return string the resolved filename to prevent overwriting different
	 * files with the same title
	 */
	public function generateFileName(Folder $folder, $title, $suffix, $id) {
		$path = $title . $suffix;

		// if file does not exist, that name has not been taken. Similar we don't
		// need to handle file collisions if it is the filename did not change
		if (!$folder->nodeExists($path) || $folder->get($path)->getId() === $id) {
			return $path;
		} else {
			// increments name (2) to name (3)
			$match = preg_match('/\((?P<id>\d+)\)$/u', $title, $matches);
			if ($match) {
				$newId = ((int) $matches['id']) + 1;
				$newTitle = preg_replace(
					'/(.*)\s\((\d+)\)$/u',
					'$1 (' . $newId . ')',
					$title
				);
			} else {
				$newTitle = $title . ' (2)';
			}
			return $this->generateFileName($folder, $newTitle, $suffix, $id);
		}
	}

	public function moveNote(Folder $notesFolder, File $file, $category, $title) {
		$id = $file->getId();
		$currentFilePath = $this->root->getFullPath($file->getPath());
		$currentBasePath = pathinfo($currentFilePath, PATHINFO_DIRNAME);
		$fileSuffix = '.' . pathinfo($file->getName(), PATHINFO_EXTENSION);

		// detect (new) folder path based on category name
		if ($category===null) {
			$basePath = $currentBasePath;
		} else {
			$basePath = $notesFolder->getPath();
			if (!empty($category)) {
				// sanitise path
				$cats = explode('/', $category);
				$cats = array_map([$this, 'sanitisePath'], $cats);
				$cats = array_filter($cats, function ($str) {
					return !empty($str);
				});
				$basePath .= '/'.implode('/', $cats);
			}
		}
		$folder = $this->getOrCreateFolder($basePath);
		$this->logger->debug('BasePath is '.$basePath);

		// assemble new file path
		$newFilePath = $basePath . '/' . $this->generateFileName($folder, $title, $fileSuffix, $id);

		// if the current path is not the new path, the file has to be renamed
		if ($currentFilePath !== $newFilePath) {
			$this->logger->debug('Move from '.$currentFilePath.' to '.$newFilePath);
			$file->move($newFilePath);
		}
		if ($currentBasePath !== $basePath) {
			$this->deleteEmptyFolder($notesFolder, $this->root->get($currentBasePath));
		}
	}

	/**
	 * Finds a folder and creates it if non-existent
	 * @param string $path path to the folder
	 * @return Folder
	 */
	public function getOrCreateFolder($path) : Folder {
		$this->logger->debug('get/create folder '.$path);
		if ($this->root->nodeExists($path)) {
			$folder = $this->root->get($path);
		} else {
			$folder = $this->root->newFolder($path);
		}
		if (!($folder instanceof Folder)) {
			throw new Exception($path.' is not a folder');
		}
		return $folder;
	}

	/*
	 * Delete a folder and it's parent(s) if it's/they're empty
	 * @param Folder root folder for notes
	 * @param Folder folder to delete
	 */
	public function deleteEmptyFolder(Folder $notesFolder, Folder $folder) {
		$content = $folder->getDirectoryListing();
		$isEmpty = !count($content);
		$isNotesFolder = $folder->getPath()===$notesFolder->getPath();
		if ($isEmpty && !$isNotesFolder) {
			$this->logger->info('Deleting empty category folder '.$folder->getPath(), ['app' => $this->appName]);
			$parent = $folder->getParent();
			$folder->delete();
			$this->deleteEmptyFolder($notesFolder, $parent);
		}
	}

	/**
	 * gather note files in given directory and all subdirectories
	 * @param Folder $folder
	 * @return array
	 */
	public function gatherNoteFiles(Folder $folder) {
		$notes = [];
		$nodes = $folder->getDirectoryListing();
		foreach ($nodes as $node) {
			if ($node->getType() === FileInfo::TYPE_FOLDER) {
				$notes = array_merge($notes, $this->gatherNoteFiles($node));
				continue;
			}
			if ($this->isNote($node)) {
				$notes[] = $node;
			}
		}
		return $notes;
	}


	/**
	 * test if file is a note
	 *
	 * @param \OCP\Files\File $file
	 * @return bool
	 */
	public function isNote(File $file) {
		$allowedExtensions = ['txt', 'org', 'markdown', 'md', 'note'];

		if ($file->getType() !== 'file') {
			return false;
		}

		$ext = pathinfo($file->getName(), PATHINFO_EXTENSION);
		$iext = strtolower($ext);
		if (!in_array($iext, $allowedExtensions)) {
			return false;
		}
		return true;
	}
}
