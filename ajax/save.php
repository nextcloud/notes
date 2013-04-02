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

// oldname is used to get the existing text file which is identified by the 
// first line
$oldName = $_POST['oldname'];
$old = explode('\n', $oldName);
$old = $old[0] . '.txt';

$content = $_POST['content'];

$new = \OCA\Notes\Notes::createFileName($content);


if ($new != $old) {
	$notes->remove($category, $old);
}

if ($content) {
	$notes->save($category, $new, $content);
}
