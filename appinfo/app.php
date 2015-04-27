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

namespace OCA\Notes\AppInfo;

use \OCP\AppFramework\App;

$app = new App('notes');
$serverContainer = $app->getContainer()->getServer();

$app->getContainer()->getServer()->getNavigationManager()->add([
        'id' => $app->getContainer()->getAppName(),
        'order' => 10,
        'href' => $serverContainer->getURLGenerator()->linkToRoute('notes.page.index'),
        'icon' => $serverContainer->getURLGenerator()->imagePath('notes', 'notes.svg'),
        'name' => $serverContainer->getL10N('Notes')->t('Notes'),
    ]
);
