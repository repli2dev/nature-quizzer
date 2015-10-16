<?php
/**
 * This is simple tool for fast and easy check that all referenced files in package are present and they are not shared.
 *
 */
use Nette\Utils\Finder;
use Nette\Utils\Json;

include_once __DIR__ . "/../app/bootstrap.php";

function printHelp()
{
	print "Image checker\n";
	print "Usage: image-checker.php PATH where files folder and package.json are located\n\n";
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

// Load existing files
$presentFiles = [];
foreach (Finder::findFiles('*')->exclude('.*')->from($wholePath . '/files/') as $key => $file) {
	$presentFiles[] = $file->getBaseName();
}

// Load package data
$data = JSON::decode(file_get_contents($wholePath . '/package.json'));

// Check
$errors = 0;
$temp = [];
foreach ($data->organism as $latinName => $organismData) {
	foreach ($organismData->representations as $representation) {
		if (in_array($representation->hash, $temp)) {
			print "Shared: " . $representation->hash. "\n";
			$errors += 1;
		}
		$temp[] = $representation->hash;
		if (!in_array($representation->hash, $presentFiles)) {
			print "Missing: " . $representation->hash. "\n";
			$errors += 1;
		}
	}
}
if ($errors == 0) {
	print "Everything is OK.\n";
} else {
	print $errors . " problematic representations.\n";
}