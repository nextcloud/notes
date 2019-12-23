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

return ['routes' => [
	// page
	[
		'name' => 'page#index',
		'url' => '/',
		'verb' => 'GET',
	],
	[
		'name' => 'page#index',
		'url' => '/welcome',
		'verb' => 'GET',
		'postfix' => 'welcome',
	],
	[
		'name' => 'page#index',
		'url' => '/note/{id}',
		'verb' => 'GET',
		'postfix' => 'note',
		'requirements' => ['id' => '\d+'],
	],

	// notes
	[
		'name' => 'notes#index',
		'url' => '/notes',
		'verb' => 'GET',
	],
	[
		'name' => 'notes#get',
		'url' => '/notes/{id}',
		'verb' => 'GET',
		'requirements' => ['id' => '\d+'],
	],
	[
		'name' => 'notes#create',
		'url' => '/notes',
		'verb' => 'POST',
	],
	[
		'name' => 'notes#undo',
		'url' => '/notes/undo',
		'verb' => 'POST',
	],
	[
		'name' => 'notes#update',
		'url' => '/notes/{id}',
		'verb' => 'PUT',
		'requirements' => ['id' => '\d+'],
	],
	[
		'name' => 'notes#category',
		'url' => '/notes/{id}/category',
		'verb' => 'PUT',
		'requirements' => ['id' => '\d+'],
	],
	[
		'name' => 'notes#favorite',
		'url' => '/notes/{id}/favorite',
		'verb' => 'PUT',
		'requirements' => ['id' => '\d+'],
	],
	[
		'name' => 'notes#destroy',
		'url' => '/notes/{id}',
		'verb' => 'DELETE',
		'requirements' => ['id' => '\d+'],
	],

	// api
	[
		'name' => 'notes_api#index',
		'url' => '/api/v0.2/notes',
		'verb' => 'GET',
	],
	[
		'name' => 'notes_api#get',
		'url' => '/api/v0.2/notes/{id}',
		'verb' => 'GET',
		'requirements' => ['id' => '\d+'],
	],
	[
		'name' => 'notes_api#create',
		'url' => '/api/v0.2/notes',
		'verb' => 'POST',
	],
	[
		'name' => 'notes_api#update',
		'url' => '/api/v0.2/notes/{id}',
		'verb' => 'PUT',
		'requirements' => ['id' => '\d+'],
	],
	[
		'name' => 'notes_api#destroy',
		'url' => '/api/v0.2/notes/{id}',
		'verb' => 'DELETE',
		'requirements' => ['id' => '\d+'],
	],
	[
		'name' => 'notes_api#preflighted_cors',
		'url' => '/api/v0.2/{path}',
		'verb' => 'OPTIONS',
		'requirements' => ['path' => '.+'],
	],

	// settings
	['name' => 'settings#set', 'url' => '/settings', 'verb' => 'PUT'],
	['name' => 'settings#get', 'url' => '/settings', 'verb' => 'GET'],
]];
