<?php
/**
 * Animals (cca Vertebrata) related to Czech Republic.
 *
 * Source: BioLib database
 * @see http://www.biolib.cz/
 */
use NatureQuizzer\Utils\Normalizator;
use NatureQuizzer\Tools\WebProcessor;
use Tracy\Debugger;

include_once __DIR__ . "/../../../app/bootstrap.php";

Debugger::enable(Debugger::DEVELOPMENT);


$sources = [
	'http://www.biolib.cz/cz/speciesmappingtaxa/id1/',	// Savci
	'http://www.biolib.cz/cz/speciesmappingtaxa/id2/',	// Plazi a obojživelníci
	'http://www.biolib.cz/cz/speciesmappingtaxa/id13/'	// Ryby
];
$result = [];

foreach ($sources as $source) {

	$temp = new WebProcessor($source);
	$temp->setParser(function ($input) {
		$matches = [];
		preg_match_all('#<em>([\w\s]*?)</em></a> <small>.*?</small> - <b>([\w\s]*?)</b>#su', $input, $matches);
		$temp = array_combine($matches[1], $matches[2]);
		/* extra cleaning as the regex pass also higher taxon categories */
		$output = [];
		foreach ($temp as $latin => $czech) {
			if (count(explode(' ', $latin)) != 2) continue;
			$output[$latin] = $czech;
		}
		return $output;
	});
	$temp2 = $temp->getOutput();
	$result = array_merge($result, Normalizator::normalizeAssociativeArray($temp2));
}

$output = [];
foreach ($result as $latin => $czech) {
	$output[] = implode(';', [$latin, $czech]);
}

file_put_contents(__DIR__ . '/biolib.txt', implode("\n", $output));