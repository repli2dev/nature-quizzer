<?php
/**
 * Uses Encyclopedia of Life to fetch other data:
 *   - English name
 *   - Habitats
 *   - Images
 */

use NatureQuizzer\Tools\EOLPage;
use NatureQuizzer\Tools\EOLSearch;
use NatureQuizzer\Tools\EOLTraits;
use Tracy\Debugger;

include_once __DIR__ . "/../../app/bootstrap.php";

Debugger::enable(Debugger::DEVELOPMENT);



$animals = explode("\n", file_get_contents(__DIR__ . '/animals.txt'));

$eolSearcher = new EOLSearch();
$eolPage = new EOLPage();
$eolTraits = new EOLTraits();

$result = [];
$missed = [];
foreach ($animals as $line) {
	list($latin, $czech) = implode($line);
	echo "Finding:  ". $latin . " ";
	$eolId = $eolSearcher->findId($latin);
	if (!$eolId) {
		$missed[] = $latin;
		echo "... MISS \n";
		continue;
	} else {
		echo "... FOUND ". $eolId . "\n";
	}

	$data = $eolPage->getData($eolId);
	//dump($data);
	$enName = NULL;
	if (isset($data->vernacularNames)) {
		$enName = array_filter($data->vernacularNames, function ($item) {return isset($item->eol_preferred, $item->language) && $item->language == 'en'; });
	}
	$result[$latin] = [
		'names' => ['cz' => $czech, 'en' => $enName],
		'habitats' => [],
		'images' => []
	];

	if (isset($data->dataObjects)) {
		foreach ($data->dataObjects as $object) {
			if (!isset($object->mediaURL) || !isset($object->license) || $object->dataType != 'http://purl.org/dc/dcmitype/StillImage') continue;

			$result[$latin]['images'][] = [
				'url' => $object->mediaURL,
				'rights_holder' => (isset($object->rightsHolder)) ? $object->rightsHolder : NULL,
				'source' => (isset($object->source)) ? $object->source : NULL,
				'license' => $object->license,
			];
		}
	}
	//dump($result[$latin]);
}

file_put_contents(__DIR__ . '/animals.serialized', serialize($result));

