<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

OC_JSON::callCheck();
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('notes');

$notes = new \OCA\Notes\Notes(\OCP\User::getUser());

$category = $_GET['category'];
$note = $_GET['note'];

header('Content-Type: text/plain');
echo $notes->getSource($category, $note);
