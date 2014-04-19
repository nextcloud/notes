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

namespace OCA\Notes;

use \OCA\Notes\App\Notes;

$application = new Notes();
$application->registerRoutes($this, array('routes' => array(
	// page
	array('name' => 'page#index', 'url' => '/', 'verb' => 'GET'),

	// notes
	array('name' => 'notes#get_config', 'url' => '/config', 'verb' => 'GET'),
	array('name' => 'notes#set_config', 'url' => '/config', 'verb' => 'POST'),

	array('name' => 'notes#index', 'url' => '/notes', 'verb' => 'GET'),
	array('name' => 'notes#get', 'url' => '/notes/{id}', 'verb' => 'GET'),
	array('name' => 'notes#create', 'url' => '/notes', 'verb' => 'POST'),
	array('name' => 'notes#update', 'url' => '/notes/{id}', 'verb' => 'PUT'),
	array('name' => 'notes#destroy', 'url' => '/notes/{id}', 'verb' => 'DELETE'),

	// api
	array('name' => 'notes_api#index', 'url' => '/api/v0.2/notes', 'verb' => 'GET'),
	array('name' => 'notes_api#get', 'url' => '/api/v0.2/notes/{id}', 'verb' => 'GET'),
	array('name' => 'notes_api#create', 'url' => '/api/v0.2/notes', 'verb' => 'POST'),
	array('name' => 'notes_api#update', 'url' => '/api/v0.2/notes/{id}', 'verb' => 'PUT'),
	array('name' => 'notes_api#destroy', 'url' => '/api/v0.2/notes/{id}', 'verb' => 'DELETE'),	
	array('name' => 'notes_api#cors', 'url' => '/api/v0.2/{path}', 'verb' => 'OPTIONS', 'requirements' => array('path' => '.+')),
)));
