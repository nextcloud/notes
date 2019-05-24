<?php

namespace OCA\Notes\Db;

use OCP\AppFramework\Db\Entity;

/**
 * Class Meta
 * @method string getUserId()
 * @method void setUserId(string $value)
 * @method integer getFileId()
 * @method void setFileId(integer $value)
 * @method integer getLastUpdate()
 * @method void setLastUpdate(integer $value)
 * @method string getEtag()
 * @method void setEtag(string $value)
 * @package OCA\Notes\Db
 */
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
