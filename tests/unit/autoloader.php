<?php
require_once __DIR__ . '/../../../../3rdparty/autoload.php';


class OC {
	public static $server;
	public static $session;
}

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
	} else if(strpos($className, 'OC_') === 0) {
		$path = strtolower(str_replace('\\', '/', substr($className, 3)) . '.php');
		$relPath = __DIR__ . '/../../../../lib/private/' . $path;

		if(file_exists($relPath)){
			require_once $relPath;
		}
	} else if(strpos($className, 'Test\\') === 0) {
        $path = strtolower(str_replace('\\', '/', substr($className, 4)) . '.php');
        $relPath = __DIR__ . '/../../../../tests/lib/' . $path;

        if(file_exists($relPath)){
            require_once $relPath;
        }
	} else if(strpos($className, 'OC\\') === 0) {
		$path = strtolower(str_replace('\\', '/', substr($className, 2)) . '.php');
		$relPath = __DIR__ . '/../../../../lib/private' . $path;

		if(file_exists($relPath)){
			require_once $relPath;
		}
	}
});

// create a new server instance
OC::$server = new \OC\Server('');