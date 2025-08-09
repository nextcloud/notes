<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2013 Bernhard Posselt <nukeawhale@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

return ['routes' => [
	//////////  P A G E  //////////
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
		'name' => 'page#create',
		'url' => '/new',
		'verb' => 'GET',
	],
	[
		'name' => 'page#index',
		'url' => '/note/{id}',
		'verb' => 'GET',
		'postfix' => 'note',
		'requirements' => ['id' => '\d+'],
	],


	//////////  N O T E S  //////////
	[
		'name' => 'notes#index',
		'url' => '/notes',
		'verb' => 'GET',
	],
	[
		'name' => 'notes#dashboard',
		'url' => '/notes/dashboard',
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
		'name' => 'notes#autotitle',
		'url' => '/notes/{id}/autotitle',
		'verb' => 'PUT',
		'requirements' => ['id' => '\d+'],
	],
	[
		'name' => 'notes#update',
		'url' => '/notes/{id}',
		'verb' => 'PUT',
		'requirements' => ['id' => '\d+'],
	],
	[
		'name' => 'notes#updateProperty',
		'url' => '/notes/{id}/{property}',
		'verb' => 'PUT',
		'requirements' => [
			'id' => '\d+',
			'property' => '(modified|title|category|favorite)',
		],
	],
	[
		'name' => 'notes#destroy',
		'url' => '/notes/{id}',
		'verb' => 'DELETE',
		'requirements' => ['id' => '\d+'],
	],

	//////////  A T T A C H M E N T S  //////////

	[
		'name' => 'notes#getAttachment',
		'url' => '/notes/{noteid}/attachment',
		'verb' => 'GET',
		'requirements' => ['noteid' => '\d+'],
	],
	[
		'name' => 'notes#uploadFile',
		'url' => '/notes/{noteid}/attachment',
		'verb' => 'POST',
		'requirements' => ['noteid' => '\d+'],
	],

	//////////  S E T T I N G S  //////////
	['name' => 'settings#set', 'url' => '/settings', 'verb' => 'PUT'],
	['name' => 'settings#get', 'url' => '/settings', 'verb' => 'GET'],
	['name' => 'settings#migrate', 'url' => '/settings/migrate', 'verb' => 'POST'],


	//////////  A P I  //////////
	[
		'name' => 'notes_api#index',
		'url' => '/api/{apiVersion}/notes',
		'verb' => 'GET',
		'requirements' => [
			'apiVersion' => '(v0.2|v1)',
		],
	],
	[
		'name' => 'notes_api#get',
		'url' => '/api/{apiVersion}/notes/{id}',
		'verb' => 'GET',
		'requirements' => [
			'apiVersion' => '(v0.2|v1)',
			'id' => '\d+',
		],
	],
	[
		'name' => 'notes_api#createAutoTitle',
		'url' => '/api/{apiVersion}/notes',
		'verb' => 'POST',
		'requirements' => [
			'apiVersion' => '(v0.2)',
		],
	],
	[
		'name' => 'notes_api#create',
		'url' => '/api/{apiVersion}/notes',
		'verb' => 'POST',
		'requirements' => [
			'apiVersion' => '(v1)',
		],
	],
	[
		'name' => 'notes_api#updateAutoTitle',
		'url' => '/api/{apiVersion}/notes/{id}',
		'verb' => 'PUT',
		'requirements' => [
			'apiVersion' => '(v0.2)',
			'id' => '\d+',
		],
	],
	[
		'name' => 'notes_api#update',
		'url' => '/api/{apiVersion}/notes/{id}',
		'verb' => 'PUT',
		'requirements' => [
			'apiVersion' => '(v1)',
			'id' => '\d+',
		],
	],
	[
		'name' => 'notes_api#destroy',
		'url' => '/api/{apiVersion}/notes/{id}',
		'verb' => 'DELETE',
		'requirements' => [
			'apiVersion' => '(v0.2|v1)',
			'id' => '\d+',
		],
	],
	[
		'name' => 'notes_api#setSettings',
		'url' => '/api/{apiVersion}/settings',
		'verb' => 'PUT',
		'requirements' => [
			'apiVersion' => '(v1)',
		],
	],
	[
		'name' => 'notes_api#getSettings',
		'url' => '/api/{apiVersion}/settings',
		'verb' => 'GET',
		'requirements' => [
			'apiVersion' => '(v1|v1.4)',
		],
	],
	[
		'name' => 'notes_api#preflighted_cors',
		'url' => '/api/{apiVersion}/{path}',
		'verb' => 'OPTIONS',
		'requirements' => [
			'apiVersion' => '(v0.2|v1)',
			'path' => '.+',
		],
	],
	[
		'name' => 'notes_api#getAttachment',
		'url' => '/api/{apiVersion}/attachment/{noteid}',
		'verb' => 'GET',
		'requirements' => [
			'apiVersion' => '(v1.4)',
			'noteid' => '\d+'
		],
	],
	[
		'name' => 'notes_api#uploadFile',
		'url' => '/api/{apiVersion}/attachment/{noteid}',
		'verb' => 'POST',
		'requirements' => [
			'apiVersion' => '(v1.4)',
			'noteid' => '\d+'
		],
	],
	[
		'name' => 'notes_api#fail',
		'url' => '/api/{catchAll}',
		'verb' => 'GET',
		'requirements' => [
			'catchAll' => '.*',
		],
	],
]];
