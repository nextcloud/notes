<?php
OCP\User::checkLoggedIn();
OCP\Util::addScript('notes', 'settings/personal');

$config = \OC::$server->getConfig();
$tmpl = new \OCP\Template('notes', 'settings/personal');
$uid = \OC::$server->getUserSession()->getUser()->getUID();
$tmpl->assign('notesPath', $config->getUserValue($uid, 'notes', 'notesPath'));

return $tmpl->fetchPage();