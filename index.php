<?php
/**************************************************
 * ownCloud - Notes Plugin                        *
 *                                                *
 * (c) Copyright 2012 - 2013 by George Ruinelli   *
 * This file is licensed under the GPL2           *
 *************************************************/

OCP\User::checkLoggedIn();
OCP\App::checkAppEnabled('notes');
OCP\Util::addStyle('notes', 'notes');

OCP\App::setActiveNavigationEntry('notes_index');

if(isset($_GET['page'])){
  $page = $_GET['page'];
}
else{
  $page = ""; //show main page
}

if($page == "Help"){
    $output = new OCP\Template('notes', 'help', 'user');
}
else if($page == "Edit Categories"){
    $output = new OCP\Template('notes', 'categories', 'user');
}
else{
    $output = new OCP\Template('notes', 'notes', 'user');
}
$output -> printPage();
