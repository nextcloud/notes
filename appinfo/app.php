<?php
/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */

namespace OCA\Notes;

use \OCA\Notes\Core\API;

$api = new API('notes');

$api->addNavigationEntry(array(

	// the string under which your app will be referenced in owncloud
	'id' => $api->getAppName(),

	// sorting weight for the navigation. The higher the number, the higher
	// will it be listed in the navigation
	'order' => 10,

	// the route that will be shown on startup
	'href' => $api->linkToRoute('notes_index'),

	// the icon that will be shown in the navigation
	// this file needs to exist in img/example.png
	'icon' => $api->imagePath('notes.svg'),

	// the title of your application. This will be used in the
	// navigation or on the settings page of your app
	'name' => $api->getTrans()->t('Notes')

));
