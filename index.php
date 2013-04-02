<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Notes;

\OCP\User::checkLoggedIn();
\OCP\App::checkAppEnabled('notes');
\OCP\Util::addStyle('notes', 'notes');
//\OCP\Util::addscript('notes', 'notes');

\OCP\App::setActiveNavigationEntry('notes_index');

$categories = new Categories(\OCP\User::getUser());
$notes = new Notes(\OCP\User::getUser());

$tmpl = new \OCP\Template('notes', 'notes', 'user');
$tmpl->assign('categories', $categories->listCategories());
$tmpl->assign('notes', $notes->getTitles(''));
$tmpl->printPage();
