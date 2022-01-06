<?php
/**
 * Simple utility for removing certain representations.
 */
use NatureQuizzer\Tools\FileDownloader;
use Nette\Utils\Image;
use Nette\Utils\Json;
use Nette\Utils\Random;

$container = include __DIR__ . "/../app/bootstrap.php";

function printHelp()
{
	print "Representation removal tool\n";
	print "Usage: remove-representation.php PATH where files folder and package.json are located\n\n";
}

if (count ($argv) <= 1) {
	printHelp();
	exit(0);
}
$path = $argv[1];
$wholePath = getcwd() . '/' . $path;
if (!is_dir($wholePath)) {
	print "The specified path doesn't exists.\n";
	exit(1);
}
if (!is_dir($wholePath . '/files/') || !is_file($wholePath . '/package.json')) {
	print "The specified path doesn't contain package.json or files folder.\n";
	exit(1);
}

// Obtain the removed representations images
$toRemove = [];
do {
	print "> ";
	$input = '';
	$input = trim(fgets(STDIN));
	if ($input != 'quit' && $input != 'done' && !empty($input)) {
		$toRemove[] = $input;
	}

} while ($input != 'quit' && $input != 'done');

if ($input == 'quit') {
	print "ABORTING!\n";
}

// Show summary
print "Following " . count($toRemove) . " representation(s) will be removed from the package:\n";
foreach ($toRemove as $item) {
	print $item . "\n";
}

// Ask for confirmation
print "Delete? [y/N]: ";
$input = fgets(STDIN);
if ($input !== "y\n") {
	print "ABORTING!\n";
	exit(0);
}

// Load package data
$data = JSON::decode(file_get_contents($wholePath . '/package.json'));

$removed = 0;
foreach ($data->organism as $latinName => $organismData) {
	foreach ($organismData->representations as $key => &$representation) {
		if (in_array($representation->hash, $toRemove, TRUE)) {
			print "FOUND " . $representation->hash . "\n";
			@unlink($wholePath . '/files/' . $representation->hash);
			unset($organismData->representations[$key]);
			$removed++;
		}
	}
	$organismData->representations = array_values($organismData->representations);
}

file_put_contents($wholePath . '/package.new.json', Json::encode($data, Json::PRETTY));

print "Removed " . $removed . " representations.\n";
