<?php

$json = file_get_contents('php://stdin');
$items = json_decode($json);
$errors = 0;

foreach ($items as $item) {
	$type = ($item->severity >= 10 ? 'error' : 'warning');
	echo '::' . $type;
	echo ' file=' . $item->location->path;
	echo ',line=' . $item->location->lines->begin;
	$message = explode(' ', $item->description, 3);
	echo '::' . $message[2] . ' ['.$message[1].']';
	echo PHP_EOL;
	if ($type === 'error') {
		$errors++;
	}
}

exit($errors);

