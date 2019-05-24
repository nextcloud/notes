<?php

namespace OCA\Notes\Db;

use OCP\Files\File;
use OCP\Files\Folder;
use OCP\AppFramework\Db\Entity;

/**
 * Class Note
 * @method integer getId()
 * @method void setId(integer $value)
 * @method string getEtag()
 * @method void setEtag(string $value)
 * @method integer getModified()
 * @method void setModified(integer $value)
 * @method string getTitle()
 * @method void setTitle(string $value)
 * @method string getCategory()
 * @method void setCategory(string $value)
 * @method string getContent()
 * @method void setContent(string $value)
 * @method boolean getFavorite()
 * @method void setFavorite(boolean $value)
 * @method boolean getError()
 * @method void setError(boolean $value)
 * @method string getErrorMessage()
 * @method void setErrorMessage(string $value)
 * @package OCA\Notes\Db
 */
class Note extends Entity {
	public $etag;
	public $modified;
	public $title;
	public $category;
	public $content = null;
	public $favorite = false;
	public $error = false;
	public $errorMessage='';

	public function __construct() {
		$this->addType('modified', 'integer');
		$this->addType('favorite', 'boolean');
	}

	/**
	 * @param File $file
	 * @return static
	 */
	public static function fromFile(File $file, Folder $notesFolder, $tags = [], $onlyMeta = false) {
		$note = new static();
		$note->setId($file->getId());
		if (!$onlyMeta) {
			$fileContent=$file->getContent();
			if ($fileContent===false) {
				throw new \Exception("File not found");
			}
			$note->setContent(self::convertEncoding($fileContent));
		}
		$note->setModified($file->getMTime());
		$note->setTitle(pathinfo($file->getName(), PATHINFO_FILENAME)); // remove extension
		$subdir = substr(dirname($file->getPath()), strlen($notesFolder->getPath())+1);
		$note->setCategory($subdir ? $subdir : '');
		if (is_array($tags) && in_array(\OC\Tags::TAG_FAVORITE, $tags)) {
			$note->setFavorite(true);
			//unset($tags[array_search(\OC\Tags::TAG_FAVORITE, $tags)]);
		}
		if (!$onlyMeta) {
			$note->updateETag();
		}
		$note->resetUpdatedFields();
		return $note;
	}

	/**
	 * @param File $file
	 * @return static
	 */
	public static function fromException($message, File $file, Folder $notesFolder, $tags = []) {
		$note = new static();
		$note->setId($file->getId());
		$note->setErrorMessage($message);
		$note->setError(true);
		$note->setContent($message);
		$note->setModified(null);
		$note->setTitle(pathinfo($file->getName(), PATHINFO_FILENAME)); // remove extension
		$subdir = substr(dirname($file->getPath()), strlen($notesFolder->getPath())+1);
		$note->setCategory($subdir ? $subdir : null);
		if (is_array($tags) && in_array(\OC\Tags::TAG_FAVORITE, $tags)) {
			$note->setFavorite(true);
			//unset($tags[array_search(\OC\Tags::TAG_FAVORITE, $tags)]);
		}
		$note->updateETag();
		$note->resetUpdatedFields();
		return $note;
	}

	private static function convertEncoding($str) {
		if (!mb_check_encoding($str, 'UTF-8')) {
			$str = mb_convert_encoding($str, 'UTF-8');
		}
		return $str;
	}

	private function updateETag() {
		// collect all relevant attributes
		$data = '';
		foreach (get_object_vars($this) as $key => $val) {
			if ($key!=='etag') {
				$data .= $val;
			}
		}
		$etag = md5($data);
		$this->setEtag($etag);
	}
}
