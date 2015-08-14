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

use NatureQuizzer\Tools\EOLPage;
use NatureQuizzer\Tools\EOLSearch;
use NatureQuizzer\Utils\Normalizator;
use Nette\Utils\Json;
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
			$temp = explode(';', $line);
			$latin = $temp[0];
			$czech = NULL;
			if (isset($temp[1])) {
				$czech = $temp[1];
			}
			if (!isset($output[$latin])) {
				$output[$latin] = $czech;
			} else {
				if (!$output[$latin] && $czech) {
					$output[$latin] = $czech;
				}
			}
		}
	}
	return $output;
}

$eolKey = $container->getParameters()['eol']['key'];
$eolSearcher = new EOLSearch($eolKey);
$eolPage = new EOLPage(0, FALSE, $eolKey);

$animals = loadFiles(['sources/wikipedia.txt', 'sources/biolib.txt', 'sources/gbif.txt']);

$united = [];
$missed = [];

$total = 0;
$cannonizedNames = 0;
$missingCzechNames = 0;
$missingEnglishNames = 0;

foreach ($animals as $latin => $czech) {
	echo "Querying:  ". $latin . " ";
	$eolId = $eolSearcher->findId($latin);
	if (!$eolId) {
		$missed[] = $latin;
		echo "... NOT FOUND \n";
		continue;
	} else {
		echo "... FOUND (ID: ". $eolId;
	}

	$data = $eolPage->getData($eolId);

	$scientificName = processScientificName($data->scientificName);
	echo ', canonical name: ' . $scientificName . ')';
	if (!$scientificName) {
		echo "\nPROBLEM WITH CANONICAL NAME... QUITTING.\n";
		dump($data);
		exit(1);
	}
	if ($scientificName != $latin) {
		$cannonizedNames++;
	}

	$enName = NULL;
	$csName = NULL;
	if (isset($data->vernacularNames)) {
		$enName = getLanguage($data->vernacularNames, 'en');
		$csName = getLanguage($data->vernacularNames, 'cs');
	}
	if (!$csName && !$czech) {
		$missingCzechNames++;
	}
	if (!$enName) {
		$missingEnglishNames++;
	}
	if (isset($united[$eolId])) {
		echo "... ALREADY FOUND";
	} else {
		$united[$eolId] = ['cs' => ($czech) ? $czech : $csName, 'en' => $enName, 'scientific' => $scientificName];
	}
	echo "\n";
	$total++;
}

file_put_contents(__DIR__ . '/sources/united.json', Json::encode($united, Json::PRETTY));

echo "FINISHED\n\n";

echo 'Processed: ' . $total . "\n";
echo 'Canonized: ' . $cannonizedNames . "\n";
echo 'Missing czech/english names: ' . $missingCzechNames . "/" . $missingEnglishNames. "\n";

if (count($missed) > 0) {
	echo "These organisms were not found:\n";
	echo implode("\n", $missed);
}
echo "\n\n";