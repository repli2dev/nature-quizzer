<?php
/**
 * Uses Encyclopedia of Life (http://eol.org) to fetch additional data for organisms:
 *   - Images
 */

use NatureQuizzer\Tools\EOLHabitats;
use NatureQuizzer\Tools\EOLPage;
use NatureQuizzer\Tools\FileDownloader;
use Nette\Utils\Image;
use Nette\Utils\Json;
use Nette\Utils\Random;
use Tracy\Debugger;

ini_set('memory_limit','1024M');

include_once __DIR__ . "/../../app/bootstrap.php";

Debugger::enable(Debugger::DEVELOPMENT);

$eolKey = $container->getParameters()['eol']['key'];
$eolPage = new EOLPage(18, TRUE, EOLPage::VETTED_ONLY, $eolKey);
$eolHabitats = new EOLHabitats($eolKey);
$imageDownloader = new FileDownloader(__DIR__ . '/output/files/');

$animals = Json::decode(file_get_contents(__DIR__ . '/sources/united.json'));

$result = [];
if (file_exists(__DIR__ . '/output/backup.json')) {
	$result = Json::decode(file_get_contents(__DIR__ . '/output/backup.json'), Json::FORCE_ARRAY);
}
Debugger::$onFatalError[] = function () {
	global $result;
	if (isset($result)) {
		file_put_contents(__DIR__ . '/output/backup.json', Json::encode($result, Json::PRETTY));
	}
};
pcntl_signal(SIGINT, function() {
	global $result;
	if (isset($result)) {
		file_put_contents(__DIR__ . '/output/backup.json', Json::encode($result, Json::PRETTY));
	}
});
$failed = [];
$i = 0;

function retryOnException(closure $closure, $attempts = 5)
{
	$sleep = 10;
	while($attempts > 0) {
		$attempts--;
		try {
			$closure();
			return TRUE;
		} catch (Exception $ex) {
			print "Network problem, press any key to continue \n";
			$handle = fopen ("php://stdin","r");
			$line = fgets($handle);
			fclose($handle);
		}
	}
	return FALSE;
}

foreach ($animals as $eolId => $names) {
	$scientific = $names->scientific;
	echo "Querying:  ". $scientific . " -> EOL ID: " . $eolId;
	if (isset($result[$scientific])) {
		echo "... ALREADY DONE\n";
		continue;
	}

	echo "... DATA ...";
	$data = [];
	if (!retryOnException(function() use (&$data, $eolPage, $eolId) {
		$data = $eolPage->getData($eolId);
	})) {
		$failed[] = $names->scientific;
		echo "FAILED\n";
		continue;
	}

	$temp = [
		'names' => ['cs' => $names->cs, 'en' => $names->en],
		'concepts' => ['czech_trees_shrubs'],
		'representations' => []
	];

	echo "... IMAGES ...";
	if (isset($data->dataObjects)) {
		foreach ($data->dataObjects as $object) {
			if (!isset($object->eolMediaURL) || !isset($object->license) || $object->dataType != 'http://purl.org/dc/dcmitype/StillImage') continue;

			$tempImageName = Random::generate(30);
			var_dump($object);
			if ($imageDownloader->fetch($object->eolMediaURL, $tempImageName)) {
				try {
					$image = Image::fromFile(__DIR__ . '/output/files/' . $tempImageName);
					$image->resize(500, 333);
					$image->save(__DIR__ . '/output/files/' . $tempImageName, 90, Image::JPEG);
					$imageHash = hash_file('sha512', __DIR__ . '/output/files/' . $tempImageName);
					rename(__DIR__ . '/output/files/' . $tempImageName, __DIR__ . '/output/files/' . $imageHash);
					print 'I';
					unset($image);
				} catch (Exception $ex) {
					print 'E';
					continue;
				}

				$temp['representations'][] = [
					'hash' => $imageHash,
					'url' => $object->mediaURL,
					'rights_holder' => (isset($object->rightsHolder)) ? $object->rightsHolder : NULL,
					'source' => (isset($object->source)) ? $object->source : NULL,
					'license' => $object->license,
				];
			} else {
				print 'F';
				continue;
			}
		}
	}
	$result[$names->scientific] = $temp;

	echo "MEM: " . round(memory_get_usage()/(1024*1024)) . 'MB';
	echo " DONE\n";
	$i++;
	//if ($i > 2) throw new Exception();
}

$output = [
	"language" => [
		"cs" => [
			"name" => "Czech",
			"local_name" => "Čeština",
			"is_default" => TRUE
		],
		"en" => [
			"name" => "English",
			"local_name" => "English",
			"is_default" => FALSE
		],
	],
	"group" => [
		"czech_republic" => [
			"cs" => "Česká republika",
			"en" => "Czech Republic",
		]
	],
	"concept" => [
		"cz_mushroom" => [
			"group" => "czech_republic",
			"names" => [
				"cs" => [
					"name" => "Houby ČR",
					"description" => "Houby vyskytující se na území České republiky."
				],
				"en" => [
					"name" => "Czech mushrooms",
					"description" => "Mushrooms in the Czech republic."
				]
			]
		],
	]
];
$output['organism'] = $result;

file_put_contents(__DIR__ . '/output/package.json', Json::encode($output, Json::PRETTY));
@unlink(__DIR__ . '/output/backup.json');

if (count($failed) > 0) {
	echo "These organisms were not found:\n";
	echo implode("\n", $failed);
	echo "\n\n";
}

