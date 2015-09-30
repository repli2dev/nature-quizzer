<?php
/**
 * This is simple tool for fast and easy overview of images in certain folder
 *
 */
use Nette\Utils\Finder;

include_once __DIR__ . "/../app/bootstrap.php";

function printHelp()
{
	print "Image overview\n";
	print "Usage: image-overview.php PATH where files folder is located\n\n";
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
if (!is_dir($wholePath . '/files/')) {
	print "The specified path doesn't contain files folder.\n";
	exit(1);
}

$output = '';
foreach (Finder::findFiles('*')->from($wholePath . '/files/') as $key => $file) {
	$output .= sprintf('%s<br><img src="%s"><br><br>', $file->getBaseName(), $key);
}

$body = '<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Image overview</title>
  </head>
  <body>
    ' . $output . '
  </body>
</html>';

file_put_contents('overview.html', $body);
