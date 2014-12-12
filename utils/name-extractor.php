<?php
use Nette\Utils\Strings;
use NatureQuizzer\Tools\WebProcessor;

include_once __DIR__ . "/../app/bootstrap.php";

Tracy\Debugger::enable(\Tracy\Debugger::DEVELOPMENT);

$result = [];

$mammals = new WebProcessor('http://cs.wikipedia.org/wiki/Seznam_savců_Česka');
$mammals->setParser(function($input) {
	$matches = [];
	preg_match_all('#<td><a href=.*?>([\w\s]*?)</a><br />\n<i>([\w\s]*?)</i>(</td>\n|<sup id)#su', $input, $matches);
	return array_combine($matches[2], $matches[1]);
});
$mammalsResult = $mammals->getOutput();
$result = array_merge($result, Normalizator::normalizeAssociativeArray($mammalsResult));

$bats = new WebProcessor('http://cs.wikipedia.org/wiki/Seznam_netopýrů_Česka');
$bats->setParser(function($input) {
	$matches = [];
	preg_match_all('#<td><a href=.*?>([\w\s]*?)</a><br />\n<i>([\w\s]*?)</i>#su', $input, $matches);
	return array_combine($matches[2], $matches[1]);
});
$batsResult = $bats->getOutput();
$result = array_merge($result, Normalizator::normalizeAssociativeArray($batsResult));

$reptiles = new WebProcessor('http://cs.wikipedia.org/wiki/Seznam_plazů_Česka');
$reptiles->setParser(function($input) {
	$matches = [];
	preg_match_all('#<li><a href=.*?>([\w\s]*?)</a> - <i>([\w\s,]*?)</i>.*?</li>#su', $input, $matches);
	return array_combine($matches[2], $matches[1]);
});
$reptilesResult = $reptiles->getOutput();
$result = array_merge($result, Normalizator::normalizeAssociativeArray($reptilesResult));


$birds = new WebProcessor('http://cs.wikipedia.org/wiki/Seznam_ptáků_Česka');
$birds->setParser(function ($input) {
	$matches = [];
	preg_match_all('#<li><a href=.*?>([\w\s]*?)</a> \(<i>([\w\s,]*?)</i>\).*?</li>#su', $input, $matches);
	return array_combine($matches[2], $matches[1]);
});
$birdsResult = $birds->getOutput();
$result = array_merge($result, Normalizator::normalizeAssociativeArray($birdsResult));

$fishes = new WebProcessor('http://cs.wikipedia.org/wiki/Seznam_ryb_Česka');
$fishes->setParser(function ($input) {
	$matches = [];
	preg_match_all('#<tr>\n<td><a href=.*?>([\w\s,]*?)</a></td>\n<td><i>([\w\s,]*?)</i>(.*?)</td>#su', $input, $matches);
	return array_combine($matches[2], $matches[1]);
});
$fishesResult = $fishes->getOutput();
$result = array_merge($result, Normalizator::normalizeAssociativeArray($fishesResult));

file_put_contents(__DIR__ . '/../staging/testing/data.txt', serialize($result));

