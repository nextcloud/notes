<?php
/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */

namespace OCA\Notes\Utility;


class FileSystemUtility {

	private $fileSystem;

	public function __construct($fileSystem) {
		$this->fileSystem = $fileSystem;
	}


	/**
	 * get path of file and the title.txt and check if they are the same
	 * file. If not the title needs to be renamed
	 */
	public function generateFileName($title, $id) {
		$path = '/' . $title . '.txt';

		// if file does not exist, that name has not been taken
		if (!$this->fileSystem->file_exists($path)) {
			return $title . '.txt';
		}

		$fileInfo = $this->fileSystem->getFileInfo($path);

		if($fileInfo['fileid'] === $id) {
			return $title . '.txt';
		} else {
			// increments name (2) to name (3)
			$match = preg_match('/\((?P<id>\d+)\)$/', $title, $matches);
			if($match) {
				$newId = ((int) $matches['id']) + 1;
				$newTitle = preg_replace('/(.*)\s\((\d+)\)$/', 
					'$1 (' . $newId . ')', $title);
			} else {
				$newTitle = $title . ' (2)'; 
			}

			return $this->generateFileName($newTitle, $id);
		}
	}


}