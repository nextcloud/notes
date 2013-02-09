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

$category = $_POST['category'];
$old = $_POST['oldname'];
$content = $_POST['content'];
$content = trim($content);

$new = \OCA\Notes\Notes::createFileName($content);

if ($new != $old) {
	$notes->remove($category, $old);
}

if ($content) {
	$notes->save($category, $new, $content);
}

echo $new;

