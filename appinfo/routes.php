<?php
/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */

namespace OCA\Notes;

use \OCA\AppFramework\App;

use \OCA\Notes\DependencyInjection\DIContainer;


/**
 * Webinterface
 */

// matches /owncloud/index.php/apps/notes/
$this->create('notes_index', '/')->get()->action(
	function($params){
		App::main('PageController', 'index', $params, new DIContainer());
	}
);


/**
 * Ajax requests
 */
$this->create('notes_save', '/notes')->get()->action(
	function($params){
		App::main('NotesController', 'getAll', $params, new DIContainer());
	}
);

$this->create('notes_save', '/note')->get()->action(
	function($params){
		App::main('NotesController', 'get', $params, new DIContainer());
	}
);

$this->create('notes_save', '/note/save')->post()->action(
	function($params){
		App::main('NotesController', 'save', $params, new DIContainer());
	}
);

$this->create('notes_save', '/note/delete')->post()->action(
	function($params){
		App::main('NotesController', 'delete', $params, new DIContainer());
	}
);