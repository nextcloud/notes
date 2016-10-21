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

return ['routes' => [
    // page
    ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],

    // notes
    ['name' => 'notes#index', 'url' => '/notes', 'verb' => 'GET'],
    ['name' => 'notes#get', 'url' => '/notes/{id}', 'verb' => 'GET'],
    ['name' => 'notes#create', 'url' => '/notes', 'verb' => 'POST'],
    ['name' => 'notes#update', 'url' => '/notes/{id}', 'verb' => 'PUT'],
    ['name' => 'notes#favorite', 'url' => '/notes/{id}/favorite', 'verb' => 'PUT'],
    ['name' => 'notes#destroy', 'url' => '/notes/{id}', 'verb' => 'DELETE'],

    // api
    ['name' => 'notes_api#index', 'url' => '/api/v0.2/notes', 'verb' => 'GET'],
    ['name' => 'notes_api#get', 'url' => '/api/v0.2/notes/{id}', 'verb' => 'GET'],
    ['name' => 'notes_api#create', 'url' => '/api/v0.2/notes', 'verb' => 'POST'],
    ['name' => 'notes_api#update', 'url' => '/api/v0.2/notes/{id}', 'verb' => 'PUT'],
    ['name' => 'notes_api#destroy', 'url' => '/api/v0.2/notes/{id}', 'verb' => 'DELETE'],
    ['name' => 'notes_api#preflighted_cors', 'url' => '/api/v0.2/{path}',
     'verb' => 'OPTIONS', 'requirements' => ['path' => '.+']],
]];
