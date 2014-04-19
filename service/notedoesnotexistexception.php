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

namespace OCA\Notes\Service;

class NoteDoesNotExistException extends \Exception {

	/**
	 * Constructor
	 * @param string $msg the error message
	 */
	public function __construct($msg=''){
		parent::__construct($msg);
	}

}