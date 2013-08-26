<?php
/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
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