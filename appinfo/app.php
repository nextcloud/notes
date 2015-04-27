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

use OCP\AppFramework\App;

$app = new App('notes');
$container = $app->getContainer();

$container->query('OCP\INavigationManager')->add(function () use ($container) {
    $urlGenerator = $container->query('OCP\IURLGenerator');
    $l10n = $container->query('OCP\IL10N');
    return [
        'id' => 'notes',
        'order' => 10,
        'href' => $urlGenerator->linkToRoute('notes.page.index'),
        'icon' => $urlGenerator->imagePath('notes', 'notes.svg'),
        'name' => $l10n->t('Notes')
    ];
});

