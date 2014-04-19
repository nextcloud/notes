<?php
/**
 * ownCloud - Notes
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @copyright Bernhard Posselt 2012, 2014
 */

namespace OCA\Notes\Utility;

class FileSystemUtility {

	private $fileSystem;

	/**
	 * @param \OC\Files\View $fileSystem a filesystem which points to the users
	 * notes directory
	 */
	public function __construct($fileSystem) {
		$this->fileSystem = $fileSystem;
	}


	/**
	 * get path of file and the title.txt and check if they are the same
	 * file. If not the title needs to be renamed
	 * @param string $title the filename which should be used, .txt is appended
	 * @param int $id the id of the note for which the title should be generated
	 * used to see if the file itself has the title and not a different file for
	 * checking for filename collisions
	 * @return string the resolved filename to prevent overwriting different
	 * files with the same title
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