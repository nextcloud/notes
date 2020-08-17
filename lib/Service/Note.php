<?php

declare(strict_types=1);

namespace OCA\Notes\Service;

use OCP\Files\File;
use OCP\Files\Folder;

class Note {
	private $file;
	private $notesFolder;
	private $noteUtil;

	public function __construct(File $file, Folder $notesFolder, NoteUtil $noteUtil) {
		$this->file = $file;
		$this->notesFolder = $notesFolder;
		$this->noteUtil = $noteUtil;
	}


	public function getId() : int {
		return $this->file->getId();
	}

	public function getTitle() : string {
		return pathinfo($this->file->getName(), PATHINFO_FILENAME);
	}

	public function getCategory() : string {
		$subdir = substr(
			dirname($this->file->getPath()),
			strlen($this->notesFolder->getPath()) + 1
		);
		return $subdir === false ? '' : $subdir;
	}

	public function getContent() : string {
		$content = $this->file->getContent();
		// blank files return false when using object storage as primary storage
		if ($content === false) $content = '';
		if (!is_string($content)) {
			throw new \Exception('Can\'t read file content.');
		}
		if (!mb_check_encoding($content, 'UTF-8')) {
			$content = mb_convert_encoding($content, 'UTF-8');
		}
		return $content;
	}

	public function getModified() : int {
		return $this->file->getMTime();
	}

	public function getFavorite() : bool {
		return $this->noteUtil->getTagService()->isFavorite($this->getId());
	}


	public function getData(array $exclude = []) : array {
		$data = [];
		if (!in_array('id', $exclude)) {
			$data['id'] = $this->getId();
		}
		if (!in_array('title', $exclude)) {
			$data['title'] = $this->getTitle();
		}
		if (!in_array('modified', $exclude)) {
			$data['modified'] = $this->getModified();
		}
		if (!in_array('category', $exclude)) {
			$data['category'] = $this->getCategory();
		}
		if (!in_array('favorite', $exclude)) {
			$data['favorite'] = $this->getFavorite();
		}
		$data['error'] = false;
		$data['errorMessage'] = '';
		if (!in_array('content', $exclude)) {
			try {
				$data['content'] = $this->getContent();
			} catch (\Throwable $e) {
				$this->noteUtil->logException($e);
				$message = $this->noteUtil->getL10N()->t('Error').': ('.$this->file->getName().') '.$e->getMessage();
				$data['content'] = $message;
				$data['error'] = true;
				$data['errorMessage'] = $message;
			}
		}
		return $data;
	}

	public function getFileEtag() : string {
		return $this->file->getEtag();
	}


	public function setTitle(string $title) : void {
		$this->setTitleCategory($title);
	}

	public function setCategory(string $category) : void {
		$this->setTitleCategory($this->getTitle(), $category);
	}

	/**
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function setTitleCategory(string $title, ?string $category = null) : void {
		if ($category===null) {
			$category = $this->getCategory();
		}
		$oldParent = $this->file->getParent();
		$currentFilePath = $this->noteUtil->getRoot()->getFullPath($this->file->getPath());
		$fileSuffix = '.' . pathinfo($this->file->getName(), PATHINFO_EXTENSION);

		$folder = $this->noteUtil->getCategoryFolder($this->notesFolder, $category);
		$filename = $this->noteUtil->generateFileName($folder, $title, $fileSuffix, $this->getId());
		$newFilePath = $folder->getPath() . '/' . $filename;

		// if the current path is not the new path, the file has to be renamed
		if ($currentFilePath !== $newFilePath) {
			$this->file->move($newFilePath);
		}
		$this->noteUtil->deleteEmptyFolder($oldParent, $this->notesFolder);
	}

	public function setContent(string $content) : void {
		$this->noteUtil->ensureSufficientStorage($this->file->getParent(), strlen($content));
		$this->file->putContent($content);
	}

	public function setModified(int $modified) : void {
		$this->file->touch($modified);
	}

	public function setFavorite(bool $favorite) : void {
		if ($favorite !== $this->getFavorite()) {
			$this->noteUtil->getTagService()->setFavorite($this->getId(), $favorite);
		}
	}
}
