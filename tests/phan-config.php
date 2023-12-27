<?php

$testDirs = [
	'lib/',
	'vendor/',
];

if (getenv('NC_API_TAG') === 'dev-stable25') {
	$testDirs[] = 'tests/stubs/';
}

return [
	'directory_list' => $testDirs,
	"exclude_analysis_directory_list" => [
		'vendor/',
	],
];
