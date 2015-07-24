<?php
/**
 * Animals (cca Vertebrata) related to Czech Republic.
 *
 * GBIF.org (24th July 2015) GBIF Occurrence Download http://doi.org/10.15468/dl.edzndj
 */
use NatureQuizzer\Utils\Normalizator;
use NatureQuizzer\Tools\WebProcessor;
use Tracy\Debugger;

include_once __DIR__ . "/../../app/bootstrap.php";

Debugger::enable(Debugger::DEVELOPMENT);

$input = explode("\n", file_get_contents('gbif-cz.csv'));

$result = [];

foreach ($input as $line) {
	$temp = explode("\t", $line);
	if (!isset($temp[11]) || $temp[11] != 'SPECIES' || $temp[28] == 'FOSSIL_SPECIMEN') continue;
	if (!in_array($temp[5], ['Aves', 'Reptilia', 'Mammalia', 'Rhynchonellata', 'Amphibia', 'Osteichthyes', 'Chondrichthyes', 'Agnatha'])) continue;

	echo $temp[9] . "\n";
	$result[$temp[9]] = true;
	//dump($temp);
	//die;
}

$output = Normalizator::normalizeAssociativeArray($result);

file_put_contents(__DIR__ . '/gbif.txt', implode("\n", array_keys($output)));

