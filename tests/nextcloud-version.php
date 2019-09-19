<?php

function getNCVersionFromComposer($path) {
	if(!file_exists($path)) {
		throw new Exception('Composer file does not exists: '.$path);
	}
	if(!is_readable($path)) {
		throw new Exception('Composer file is not readable: '.$path);
	}
	$content = file_get_contents($path);
	$json = json_decode($content);
	if(!is_object($json)) {
		throw new Exception('Composer file does not contain valid JSON');
	}
	if(!property_exists($json, 'require-dev')) {
		throw new Exception('Composer file has no "require-dev" section');
	}
	$dev = $json->{'require-dev'};
	if(!is_object($dev)) {
		throw new Exception('Composer file has no valid "require-dev" section');
	}
	if(!property_exists($dev, 'christophwurst/nextcloud')) {
		throw new Exception('Composer file has no "nextcloud" dependency');
	}
	$v = $dev->{'christophwurst/nextcloud'};
	if(substr($v, 0, 1)=='^') {
		$v = substr($v, 1);
	}
	return $v;
}

function getNCVersionFromAppInfo($path) {
	if(!file_exists($path)) {
		throw new Exception('AppInfo does not exists: '.$path);
	}
	if(!is_readable($path)) {
		throw new Exception('AppInfo is not readable: '.$path);
	}
	$content = file_get_contents($path);
	$info = new SimpleXMLElement($content);
	$nc = $info->dependencies->nextcloud;
	$v = (string)$nc->attributes()->{'min-version'};
	if(strpos($v, '.') === false) {
		$v .= '.0';
	}
	return $v;
}

echo 'Testing Nextcloud min-version: ';
try {
	$vComposer = getNCVersionFromComposer(__DIR__.'/../composer.json');
	$vAppInfo = getNCVersionFromAppInfo(__DIR__.'/../appinfo/info.xml');
	if($vComposer === $vAppInfo) {
		echo $vAppInfo.PHP_EOL;
	} else {
		echo 'FAILED with '.$vComposer.' (Composer) vs. '.$vAppInfo.' (AppInfo)'.PHP_EOL;
		exit(1);
	}
} catch(Exception $e) {
	echo $e->getMessage().PHP_EOL;
	exit(1);
}
?>
