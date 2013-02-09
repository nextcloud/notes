<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Notes;

class Notes {
	private $user;

	/**
	 * @var \OC\Files\View
	 */
	private $view;

	public function __construct($user) {
		$this->user = $user;
		$this->view = new \OC\Files\View('/' . $this->user . '/files/Notes');
		if ($this->view->file_exists('/')) {
			$this->view->mkdir('/');
		}
	}

	/**
	 * get all notes and modified dates in a category
	 *
	 * @param string $category
	 * @return array
	 */
	public function listNotes($category = '') {
		$content = $this->view->getDirectoryContent('/' . $category);
		$notes = array();
		foreach ($content as $item) {
			if ($item['type'] === 'file') {
				$notes[$item['name']] = $item['mtime'];
			}
		}
		arsort($notes);
		return $notes;
	}

	/**
	 * move a note to a different category
	 *
	 * @param string $sourceCategory
	 * @param string $note
	 * @param string $targetCategory
	 * @return bool
	 */
	public function move($sourceCategory, $note, $targetCategory) {
		return $this->view->rename('/' . $sourceCategory . '/' . $note, '/' . $targetCategory . '/' . $note);
	}

	/**
	 * rename a note
	 *
	 * @param string $category
	 * @param string $old
	 * @param string $new
	 * @return bool
	 */
	public function rename($category, $old, $new) {
		return $this->view->rename('/' . $category . '/' . $old, '/' . $category . '/' . $new);
	}

	/**
	 * remove a note
	 *
	 * @param string $catergory
	 * @param string $note
	 * @return bool
	 */
	public function remove($category, $note) {
		if ($note) {
			return $this->view->unlink('/' . $category . '/' . $note);
		} else {
			return false;
		}
	}

	/**
	 * get the contents of a note
	 *
	 * @param string $category
	 * @param string $note
	 * @return string
	 */
	public function get($category, $note) {
		require_once 'markdown.php';
		$content = $this->getSource($category, $note);
		return Markdown($content);
	}

	/**
	 * @param string $category
	 * @param string $note
	 * @return string
	 */
	public function getSource($category, $note) {
		return $this->view->file_get_contents('/' . $category . '/' . $note);
	}

	/**
	 * get the contents of all notes in a category
	 *
	 * @param string $category
	 * @return array
	 */
	public function getAll($category) {
		require_once 'markdown.php';
		$content = $this->view->getDirectoryContent('/' . $category);
		$notes = array();
		foreach ($content as $item) {
			if ($item['type'] === 'file') {
				$note = $this->getSource($category, $item['name']);
				$notes[$item['name']] = Markdown($note);
			}
		}
		return $notes;
	}

	/**
	 * save a note
	 *
	 * @param string $category
	 * @param string $note
	 * @param string $content
	 * @return bool
	 */
	public function save($category, $note, $content) {
		return $this->view->file_put_contents('/' . $category . '/' . $note, $content);
	}

	/**
	 * @param string $category
	 * @return array
	 */
	public function getTitles($category) {
		$notes = $this->listNotes($category);
		$titles = array();
		foreach ($notes as $note => $mtime) {
			$content = $this->getSource($category, $note);
			list($title,) = explode("\n", $content);
			$titles[$note] = $title;
		}
		return $titles;
	}

	public static function createFileName($content) {
		list($title,) = explode("\n", $content);
		$title = substr($title, 0, 40);
		return preg_replace("/[^A-Za-z0-9 ]/", '-', $title) . '.txt';
	}
}
