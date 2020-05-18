<?php

function getNCVersionFromComposer($path) {
	if (!file_exists($path)) {
		throw new Exception('Composer file does not exists: '.$path);
	}
	if (!is_readable($path)) {
		throw new Exception('Composer file is not readable: '.$path);
	}
	$content = file_get_contents($path);
	$json = json_decode($content);
	if (!is_object($json)) {
		throw new Exception('Composer file does not contain valid JSON');
	}
	$dev = getValidProperty($json, 'require-dev');
	$v = getValidProperty($dev, 'christophwurst/nextcloud');
	if (substr($v, 0, 1)=='^') {
		$v = substr($v, 1);
	}
	return $v;
}


function getNCVersionFromComposerBranchAlias($path) {
	if (!file_exists($path)) {
		throw new Exception('Composer file does not exists: '.$path);
	}
	if (!is_readable($path)) {
		throw new Exception('Composer file is not readable: '.$path);
	}
	$content = file_get_contents($path);
	$json = json_decode($content);
	if (!is_object($json)) {
		throw new Exception('Composer file does not contain valid JSON');
	}
	$extra = getValidProperty($json, 'extra');
	$branchAlias = getValidProperty($extra, 'branch-alias');
	$v = getValidProperty($branchAlias, 'dev-master');
	if (substr($v, -4)=='-dev') {
		$v = substr($v, 0, -4);
	}
	return $v;
}


function getValidProperty($json, $prop) {
	if (!property_exists($json, $prop)) {
		throw new Exception('Composer file has no "'.$prop.'" section');
	}
	return $json->{$prop};
}

function getNCVersionFromAppInfo($path, $minmax = 'min') {
	if (!file_exists($path)) {
		throw new Exception('AppInfo does not exists: '.$path);
	}
	if (!is_readable($path)) {
		throw new Exception('AppInfo is not readable: '.$path);
	}
	$content = file_get_contents($path);
	$info = new SimpleXMLElement($content);
	$nc = $info->dependencies->nextcloud;
	$v = (string)$nc->attributes()->{$minmax.'-version'};
	return $v;
}

function versionCompare($sv1, $sv2, $type) {
	$v1 = explode('.', $sv1);
	$v2 = explode('.', $sv2);
	$count = min(count($v1), count($v2));
	for ($i=0; $i<$count; $i++) {
		if ($type == 'max' && $v1[$i] < $v2[$i]) {
			return true;
		}
		if ($v1[$i] !== $v2[$i]) {
			return false;
		}
	}
	return true;
}

if (in_array('--appinfo', $argv)) {
	echo getNCVersionFromAppInfo(__DIR__.'/../appinfo/info.xml', 'min');
	exit;
}

echo 'Testing Nextcloud version ';
try {
	$vComposer = getNCVersionFromComposer(__DIR__.'/../composer.json');
	if ($vComposer === 'dev-master') {
		$vComposer = getNCVersionFromComposerBranchAlias(__DIR__.'/../vendor/christophwurst/nextcloud/composer.json');
		$vAppInfo = getNCVersionFromAppInfo(__DIR__.'/../appinfo/info.xml', 'max');
		$type = 'max';
	} else {
		$vAppInfo = getNCVersionFromAppInfo(__DIR__.'/../appinfo/info.xml', 'min');
		$type = 'min';
	}
	echo $type.': '.$vAppInfo.' (AppInfo) vs. '.$vComposer.' (Composer) => ';
	if (versionCompare($vComposer, $vAppInfo, $type)) {
		echo 'OK'.PHP_EOL;
	} else {
		echo 'FAILED'.PHP_EOL;
		exit(1);
	}
} catch (Exception $e) {
	echo $e->getMessage().PHP_EOL;
	exit(1);
}
