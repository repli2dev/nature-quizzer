<?php
/**
 * Simple utility for fetching new representation and producing desired output.
 */
use NatureQuizzer\Tools\FileDownloader;
use Nette\Utils\Image;
use Nette\Utils\Json;
use Nette\Utils\Random;

include_once __DIR__ . "/../app/bootstrap.php";

function printHelp()
{
	print "Representation fetcher\n";
	print "Usage: fetch-representation.php URL\n\n";
}

if (count ($argv) <= 1) {
	printHelp();
	exit(0);
}
$url = $argv[1];
$output = getcwd();

$imageDownloader = new FileDownloader($output);

$tempImageName = Random::generate(30);
if ($imageDownloader->fetch($url, $tempImageName)) {
	try {
		$image = Image::fromFile($output . '/' . $tempImageName);
		$image->resize(500, 333);
		$image->save($output . '/' . $tempImageName, 90, Image::JPEG);
		$imageHash = hash_file('sha512', $output . '/' . $tempImageName);
		rename($output . '/' . $tempImageName, $output . '/' . $imageHash);
		unset($image);
	} catch (Exception $ex) {
		print "Operations with image have failed.\n";
		exit(1);
	}

	$temp = [
		'hash' => $imageHash,
		'url' => $url,
		'rights_holder' => '',
		'source' => '',
		'license' => '',
	];
	print(Json::encode($temp, Json::PRETTY));
	print "\n";
} else {
	print "Fetching of an image have failed.\n";
	exit(1);
}