<?php
/**
 * Animals (cca Vertebrata) related to Czech Republic.
 *
 * GBIF.org (24th July 2015) GBIF Occurrence Download http://doi.org/10.15468/dl.edzndj
 */
use NatureQuizzer\Tools\WikipediaNames;
use NatureQuizzer\Utils\Normalizator;
use Tracy\Debugger;

include_once __DIR__ . "/../../../app/bootstrap.php";

Debugger::enable(Debugger::DEVELOPMENT);

$wikiSpeciesNames = new WikipediaNames();

$input = explode("\n", file_get_contents('gbif-cz.csv'));

$result = [];

foreach ($input as $line) {
	$temp = explode("\t", $line);
	if (!isset($temp[11]) || $temp[11] != 'SPECIES' /* only species wanted */ || !$temp[22] /* date of observation empty */ || $temp[28] == 'FOSSIL_SPECIMEN' /* not interested in fossils */) continue;
	if (!in_array($temp[5], ['Aves', 'Reptilia', 'Mammalia', 'Rhynchonellata', 'Amphibia', 'Osteichthyes', 'Chondrichthyes', 'Agnatha'])) continue;


	if (isset($result[$temp[9]]) && $result[$temp[9]]) {
		$czech = $result[$temp[9]];
	} else {
		$czech = $wikiSpeciesNames->getData($temp[9], 'cs');
	}
	echo $temp[9] . '... ' . $czech . "\n";
	$result[$temp[9]] = ($czech) ? $czech : true;
}

$output = Normalizator::normalizeAssociativeArray($result);

$temp = [];
foreach ($output as $key => $value) {
	if ($value == 1) {
		$temp[] = $key;
	} else {
		$temp[] = $key . ';' . $value;
	}
}

file_put_contents(__DIR__ . '/gbif.txt', implode("\n", $temp));

