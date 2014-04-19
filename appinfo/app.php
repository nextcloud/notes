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

\OCP\App::addNavigationEntry(array(

	// the string under which your app will be referenced in owncloud
	'id' => 'notes',

	// sorting weight for the navigation. The higher the number, the higher
	// will it be listed in the navigation
	'order' => 10,

	// the route that will be shown on startup
	'href' => \OCP\Util::linkToRoute('notes.page.index'),

	// the icon that will be shown in the navigation
	// this file needs to exist in img/example.png
	'icon' => \OCP\Util::imagePath('notes', 'notes.svg'),

	// the title of your application. This will be used in the
	// navigation or on the settings page of your app
	'name' => \OC_L10N::get('notes')->t('Notes')
));
