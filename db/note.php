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
 * @method integer getContent()
 * @method void setContent(integer $value)
 * @package OCA\Notes\Db
 */
class Note extends Entity {

    public $modified;
    public $title;
    public $content;

    public function __construct() {
        $this->addType('modified', 'integer');
    }

    /**
     * @param File $file
     * @return static
     */
    public static function fromFile(File $file){
        $note = new static();
        $note->setId($file->getId());
        $note->setContent($file->getContent());
        $note->setModified($file->getMTime());
        $note->setTitle(substr($file->getName(), 0, -4)); // remove trailing .txt
        $note->resetUpdatedFields();
        return $note;
    }


}