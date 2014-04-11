<?php
/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */


// to execute without owncloud, we need to create our own classloader
spl_autoload_register(function ($className){
	if (strpos($className, 'OCA\\') === 0) {

		$path = strtolower(str_replace('\\', '/', substr($className, 3)) . '.php');
		$relPath = __DIR__ . '/../../..' . $path;

		if(file_exists($relPath)){
			require_once $relPath;
		}
	} else if(strpos($className, 'OCP\\') === 0) {
		$path = strtolower(str_replace('\\', '/', substr($className, 3)) . '.php');
		$relPath = __DIR__ . '/../../../../lib/public' . $path;

		if(file_exists($relPath)){
			require_once $relPath;
		}
	} else if(strpos($className, 'OC\\') === 0) {
		$path = strtolower(str_replace('\\', '/', substr($className, 2)) . '.php');
		$relPath = __DIR__ . '/../../../../lib/private' . $path;

		if(file_exists($relPath)){
			require_once $relPath;
		}
	} else {
		$path = strtolower(str_replace('\\', '/', $className) . '.php');
		$relPath = __DIR__ . '/../../../../3rdparty' . $path;

		die($relPath);

		if(file_exists($relPath)){
			require_once $relPath;
		}
	}
});