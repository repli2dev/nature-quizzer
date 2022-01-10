<?php
/**
 * This script merges all data sources into one file while performing:
 *  - removing of duplicity
 *  - canonizing names (some organisms have more than one latin name, one is prefered)
 *  - fetch EOL ID
 *
 * After running this script the data can be reviewed and fixed (missing names and organism not found through API search)
 *
 */

use NatureQuizzer\Tools\BioLibNames;
use NatureQuizzer\Utils\Normalizator;
use Tracy\Debugger;

ini_set('memory_limit','1024M');

include_once __DIR__ . "/../../app/bootstrap.php";

Debugger::enable(Debugger::DEVELOPMENT);

function getLanguage($data, $languageCode)
{
	$temp = array_filter($data, function ($item) use($languageCode) {return isset($item->eol_preferred, $item->language) && $item->language == $languageCode; });
	if (count($temp) > 0) {
		return Normalizator::normalize(reset($temp)->vernacularName);
	}
	return NULL;
}

function processScientificName($value)
{
	preg_match('#(\w* \w*)#su', $value, $matches);
	if (count($matches) >= 2) {
		return Normalizator::normalize($matches[1]);
	}
	return NULL;
}

function loadFiles($files)
{
	$output = [];
	foreach ($files as $file) {
		foreach (explode("\n", file_get_contents(__DIR__ . '/' . $file)) as $line) {
			$output[] = $line;
		}
	}
	return $output;
}

$animals = loadFiles(['sources/input.txt']);

$bioLibNames = new BioLibNames();

$output = [];
foreach ($animals as $czechName) {
	$output[] = [$bioLibNames->getData($czechName), $czechName];
}
file_put_contents(__DIR__ . '/sources/input_with_latin_names.txt', implode("\n", array_map(function ($line) { return implode(';', $line); }, $output)));
