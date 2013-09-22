<?php
/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
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
$this->create('notes_get_all', '/notes')->get()->action(
	function($params){
		App::main('NotesController', 'getAll', $params, new DIContainer());
	}
);

$this->create('notes_get', '/notes/{id}')->get()->action(
	function($params){
		App::main('NotesController', 'get', $params, new DIContainer());
	}
);

$this->create('notes_create', '/notes')->post()->action(
	function($params){
		App::main('NotesController', 'create', $params, new DIContainer());
	}
);

$this->create('notes_update', '/notes/{id}')->put()->action(
	function($params){
		App::main('NotesController', 'update', $params, new DIContainer());
	}
);

$this->create('notes_delete', '/notes/{id}')->delete()->action(
	function($params){
		App::main('NotesController', 'delete', $params, new DIContainer());
	}
);


/**
 * API requests
 */
$this->create('notes_api_cors', '/api/v0.2/{path}')->method('options')->action(
	function($params) {
		return App::main('NotesAPI', 'cors', $params, new DIContainer());
	}
)->requirements(array('path' => '.+'));

$this->create('notes_api_get_all', '/api/v0.2/notes')->get()->action(
	function($params){
		App::main('NotesAPI', 'getAll', $params, new DIContainer());
	}
);

$this->create('notes_api_get', '/api/v0.2/notes/{id}')->get()->action(
	function($params){
		App::main('NotesAPI', 'get', $params, new DIContainer());
	}
);

$this->create('notes_api_create', '/api/v0.2/notes')->post()->action(
	function($params){
		App::main('NotesAPI', 'create', $params, new DIContainer());
	}
);

$this->create('notes_api_update', '/api/v0.2/notes/{id}')->put()->action(
	function($params){
		App::main('NotesAPI', 'update', $params, new DIContainer());
	}
);

$this->create('notes_api_delete', '/api/v0.2/notes/{id}')->delete()->action(
	function($params){
		App::main('NotesAPI', 'delete', $params, new DIContainer());
	}
);