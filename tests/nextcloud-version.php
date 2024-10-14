<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

function getNCVersionFromComposer($path) {
	if (!file_exists($path)) {
		throw new Exception('Composer file does not exists: ' . $path);
	}
	if (!is_readable($path)) {
		throw new Exception('Composer file is not readable: ' . $path);
	}
	$content = file_get_contents($path);
	$json = json_decode($content);
	if (!is_object($json)) {
		throw new Exception('Composer file does not contain valid JSON');
	}
	$dev = getValidProperty($json, 'require-dev');
	$v = getValidProperty($dev, 'nextcloud/ocp');
	if (substr($v, 0, 1) == '^') {
		$v = substr($v, 1);
	} elseif (substr($v, 0, 2) == '>=') {
		$v = substr($v, 2);
	} elseif (substr($v, 0, 10) == 'dev-stable') {
		$v = substr($v, 10);
		if (substr($v, -4) == '@dev') {
			$v = substr($v, 0, -4);
		}
	}
	return $v;
}


function getNCVersionFromComposerBranchAlias($path) {
	if (!file_exists($path)) {
		throw new Exception('Composer file does not exists: ' . $path);
	}
	if (!is_readable($path)) {
		throw new Exception('Composer file is not readable: ' . $path);
	}
	$content = file_get_contents($path);
	$json = json_decode($content);
	if (!is_object($json)) {
		throw new Exception('Composer file does not contain valid JSON');
	}
	$extra = getValidProperty($json, 'extra');
	$branchAlias = getValidProperty($extra, 'branch-alias');
	$v = getValidProperty($branchAlias, 'dev-master');
	if (substr($v, -4) == '-dev') {
		$v = substr($v, 0, -4);
	}
	return $v;
}


function getValidProperty($json, $prop) {
	if (!property_exists($json, $prop)) {
		throw new Exception('Composer file has no "' . $prop . '" section');
	}
	return $json->{$prop};
}

function getDependencyVersionFromAppInfo($path, $type = 'nextcloud', $minmax = 'min') {
	if (!file_exists($path)) {
		throw new Exception('AppInfo does not exists: ' . $path);
	}
	if (!is_readable($path)) {
		throw new Exception('AppInfo is not readable: ' . $path);
	}
	$content = file_get_contents($path);
	$info = new SimpleXMLElement($content);
	$nc = $info->dependencies->$type;
	$v = (string)$nc->attributes()->{$minmax . '-version'};
	return $v;
}

function isServerBranch($branch) : bool {
	require_once dirname(__FILE__) . '/../vendor/autoload.php';
	$http = new \GuzzleHttp\Client(['http_errors' => false]);
	$response = $http->request('GET', 'https://api.github.com/repos/nextcloud/server/branches/' . $branch);
	$status = $response->getStatusCode();
	switch ($status) {
		case 200:
			return true;
		case 404:
			return false;
		default:
			throw new \Exception('HTTP Error while checking branch ' . $branch . ' for Nextcloud server: ' . $status);
	}
}

function versionCompare($sv1, $sv2, $type) {
	$v1 = explode('.', $sv1);
	$v2 = explode('.', $sv2);
	$count = min(count($v1), count($v2));
	for ($i = 0; $i < $count; $i++) {
		if ($type == 'max' && $v1[$i] < $v2[$i]) {
			return true;
		}
		if ($v1[$i] !== $v2[$i]) {
			return false;
		}
	}
	return true;
}

$pathAppInfo = __DIR__ . '/../appinfo/info.xml';


if (in_array('--appinfo', $argv)) {
	echo getDependencyVersionFromAppInfo($pathAppInfo, 'nextcloud', 'min');
	exit;
}
if (in_array('--serverbranch', $argv)) {
	$ncVersion = getDependencyVersionFromAppInfo($pathAppInfo, 'nextcloud', 'min');
	$branch = 'stable' . $ncVersion;
	if (!isServerBranch($branch)) {
		$branch = 'master';
	}
	echo $branch;
	exit;
}
if (in_array('--php-min', $argv)) {
	echo getDependencyVersionFromAppInfo($pathAppInfo, 'php', 'min');
	exit;
}
if (in_array('--php-max', $argv)) {
	echo getDependencyVersionFromAppInfo($pathAppInfo, 'php', 'max');
	exit;
}

echo 'Testing Nextcloud version ';
try {
	$vComposer = getNCVersionFromComposer(__DIR__ . '/../composer.json');
	if ($vComposer === 'dev-master') {
		$vComposer = getNCVersionFromComposerBranchAlias(__DIR__ . '/../vendor/nextcloud/ocp/composer.json');
		$type = 'max';
	} else {
		$type = 'min';
	}
	$vAppInfo = getDependencyVersionFromAppInfo($pathAppInfo, 'nextcloud', $type);
	echo $type . ': ' . $vAppInfo . ' (AppInfo) vs. ' . $vComposer . ' (Composer) => ';
	if (versionCompare($vComposer, $vAppInfo, $type)) {
		echo 'OK' . PHP_EOL;
	} else {
		echo 'FAILED' . PHP_EOL;
		exit(1);
	}
} catch (Exception $e) {
	echo $e->getMessage() . PHP_EOL;
	exit(1);
}
