<?php
/**
 * Nextcloud - Notes
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @copyright Bernhard Posselt 2012, 2014
 */

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
 * @package OCA\Notes\Db
 */
class Note extends Entity {

    public $etag;
    public $modified;
    public $title;
    public $category;
    public $content;
    public $favorite = false;

    public function __construct() {
        $this->addType('modified', 'integer');
        $this->addType('favorite', 'boolean');
    }

    /**
     * @param File $file
     * @return static
     */
    public static function fromFile(File $file, Folder $notesFolder, $tags=[]){
        $note = new static();
        $note->setId($file->getId());
        $note->setContent($file->getContent());
        $note->setModified($file->getMTime());
        $note->setTitle(pathinfo($file->getName(),PATHINFO_FILENAME)); // remove extension
        $subdir = substr(dirname($file->getPath()), strlen($notesFolder->getPath())+1);
        $note->setCategory($subdir ? $subdir : null);
        if(is_array($tags) && in_array(\OC\Tags::TAG_FAVORITE, $tags)) {
            $note->setFavorite(true);
            //unset($tags[array_search(\OC\Tags::TAG_FAVORITE, $tags)]);
        }
        $note->updateETag();
        $note->resetUpdatedFields();
        return $note;
    }

    private function updateETag() {
        $etag = '';
        // collect all relevant attributes
        $data = '';
        foreach(get_object_vars($this) as $key => $val) {
            if($key!='etag') {
                $data .= $val;
            }
        }
        // create binary checksum
        $md5 = md5($data, true);
        // binary-to-text using a base85 derivate:
        // - only 25% larger than binary data
        // - replace problematic characters by not-used ones (no escaping necessary in JSON or HTTP-header)
        // - the result has always the same length (20 characters for 16byte md5-checksum)
        foreach(unpack('N*', $md5) as $chunk) {
            for ($a = 0; $a < 5; $a++) {
                $b = intval($chunk / (pow(85,4 - $a)));
                $chr = str_replace(array('\\', '/', '<', '>'), array('z', '|', '{', '}'), chr($b + 35));
                $etag .= $chr;
                $chunk -= $b * pow(85,4 - $a);
            }
        }
        $this->setEtag($etag);
    }
}
