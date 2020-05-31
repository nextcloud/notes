<?php

use OCA\Notes\Application;

$app = \OC::$server->query(Application::class);
$app->register();
