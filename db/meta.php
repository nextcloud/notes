<?php
/**
 * Nextcloud - Notes
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 */

namespace OCA\Notes\Db;

use OCP\AppFramework\Db\Entity;

class Meta extends Entity {

	public $userId;
	public $fileId;
	public $lastUpdate;
	public $etag;

	/**
	 * @param Note $note
	 * @return static
	 */
	public static function fromNote(Note $note, $userId) {
		$meta = new static();
		$meta->setUserId($userId);
		$meta->setFileId($note->getId());
		$meta->setLastUpdate(time());
		$meta->setEtag($note->getEtag());
		return $meta;
	}
}
