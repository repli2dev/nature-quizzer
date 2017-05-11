<?php
/**
 * Tool for generation of package overview per organism, usable for checks.
 */
use Nette\Utils\Json;

include_once __DIR__ . '/../app/bootstrap.php';

function printHelp()
{
	print "Package overview\n";
	print "Usage: image-overview.php PACKAGE DESTINATION\n\n";
}

if (count ($argv) <= 2) {
	printHelp();
	exit(0);
}
$path = $argv[1];
$destination = $argv[2];

$wholePath = getcwd() . '/' . $path;
$wholeDestination = getcwd() . '/' . $destination;

if (!is_dir($wholePath)) {
	print "The specified path to package doesn't exists.\n";
	exit(1);
}
if (!is_dir($wholePath . '/files/')) {
	print "The specified path to package doesn't contain files folder.\n";
	exit(1);
}

if (!is_dir($wholeDestination)) {
	print "The specified path to destination doesn't exists.\n";
	exit(1);
}

if (!@mkdir($wholeDestination . '/files/') && !is_dir($wholeDestination . '/files/')) {
	print "The directory files could not been created in destination path.\n";
	exit(1);
}

// Load package data
$data = JSON::decode(file_get_contents($wholePath . '/package.json'));

$output = [];

foreach ($data->organism as $latinName => $organismData) {
	$output[] = sprintf('<h1>%s</h1>', $latinName);
	foreach ($organismData->names as $lang => $localName) {
		$output[] = sprintf('<p>%s: <b>%s</b></p>', $lang, $localName);
	}
	$output[] = sprintf('<p>Concepts: <b>%s</b></p>', implode(', ', $organismData->concepts));

	foreach ($organismData->representations as $representation) {
		copy($wholePath . '/files/' . $representation->hash, $wholeDestination . '/files/' . $representation->hash);
		$output[] = sprintf('<a href="files/%s" target="_blank">%s:<br><img src="files/%s"></a><br><br>', $representation->hash, $representation->hash, $representation->hash);
	}
	$output [] = '<hr>';
}



$body = '<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Package overview</title>
  </head>
  <body>
    ' . implode("\n", $output) . '
  </body>
</html>';

file_put_contents($wholeDestination . '/overview.html', $body);
