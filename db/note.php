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

namespace OCA\Notes\Db;

use OCP\Files\File;
use OCP\AppFramework\Db\Entity;

/**
 * Class Note
 * @method integer getId()
 * @method void setId(integer $value)
 * @method integer getModified()
 * @method void setModified(integer $value)
 * @method string getTitle()
 * @method void setTitle(string $value)
 * @method string getContent()
 * @method void setContent(string $value)
 * @method boolean getFavorite()
 * @method void setFavorite(boolean $value)
 * @package OCA\Notes\Db
 */
class Note extends Entity {

    public $modified;
    public $title;
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
    public static function fromFile(File $file, $tags=[]){
        $note = new static();
        $note->setId($file->getId());
        $note->setContent($file->getContent());
        $note->setModified($file->getMTime());
        $note->setTitle(pathinfo($file->getName(),PATHINFO_FILENAME)); // remove extension
        if(is_array($tags) && in_array(\OC\Tags::TAG_FAVORITE, $tags)) {
            $note->setFavorite(true);
            //unset($tags[array_search(\OC\Tags::TAG_FAVORITE, $tags)]);
        }
        $note->resetUpdatedFields();
        return $note;
    }

}
