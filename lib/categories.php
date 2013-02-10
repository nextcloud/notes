<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Notes;

class Categories {
	private $user;
	/**
	 * @var \OC\Files\View
	 */
	private $view;

	/**
	 * @var Notes
	 */
	private $notes;

	public function __construct($user) {
		$this->user = $user;
		$this->view = new \OC\Files\View('/' . $this->user . '/files/Notes');
		$this->notes = new Notes($user);
	}

	public function listCategories() {
		$content = $this->view->getDirectoryContent('/');
		$categories = array();
		foreach ($content as $item) {
			if ($item['type'] === 'dir') {
				$categories[] = $item['name'];
			}
		}
		return $categories;
	}

	public function create($name) {
		return $this->view->mkdir($name);
	}

	/**
	 * Delete a category, moving all notes inside it to Geneal
	 *
	 * @param string $name
	 * @return bool
	 */
	public function delete($name) {
		$notes = $this->notes->listNotes($name); //move all notes in the category to General
		foreach ($notes as $note => $mtime) {
			$this->notes->move($name, $note, '');
		}

		return $this->view->rmdir($name);
	}
}
