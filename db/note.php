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
        $note->setTitle(pathinfo($file->getName(),PATHINFO_FILENAME)); // remove extension
        $note->resetUpdatedFields();
        return $note;
    }


}