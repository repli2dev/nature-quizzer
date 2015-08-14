<?php
/**
 * Uses Encyclopedia of Life (http://eol.org) to fetch additional data for organisms:
 *   - English/Czech names
 *   - Habitats
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
include_once __DIR__ . '/../HabitatConcepts.php';

Debugger::enable(Debugger::DEVELOPMENT);

$eolKey = $container->getParameters()['eol']['key'];
$eolPage = new EOLPage(6, TRUE, $eolKey);
$eolHabitats = new EOLHabitats($eolKey);
$imageDownloader = new FileDownloader(__DIR__ . '/output/files/');

$animals = Json::decode(file_get_contents('sources/united.cleaned.json'));

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
			sleep($sleep);
			$sleep *= 4;
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
	$data = [123];
	if (!retryOnException(function() use (&$data, $eolPage, $eolId) {
		$data = $eolPage->getData($eolId);
	})) {
		$failed[] = $names->scientific;
		echo "FAILED\n";
		continue;
	}

	echo "... HABITATS ...";
	$habitats = [];
	if (!retryOnException(function() use (&$habitats, $eolHabitats, $eolId) {
		$habitats = HabitatConcepts::simplify($eolHabitats->getData($eolId));
	})) {
		$failed[] = $names->scientific;
		echo "FAILED\n";
		continue;
	}
	$temp = [
		'names' => ['cs' => $names->cs, 'en' => $names->en],
		'concepts' => array_map(function ($item) { return 'cz_' . $item; }, $habitats),
		'representations' => []
	];

	echo "... IMAGES ...";
	if (isset($data->dataObjects)) {
		foreach ($data->dataObjects as $object) {
			if (!isset($object->mediaURL) || !isset($object->license) || $object->dataType != 'http://purl.org/dc/dcmitype/StillImage') continue;

			$tempImageName = Random::generate(30);
			if ($imageDownloader->fetch($object->mediaURL, $tempImageName)) {
				try {
					$image = Image::fromFile(__DIR__ . '/output/files/' . $tempImageName);
					$image->resize(500, 333);
					$image->save(__DIR__ . '/output/files/' . $tempImageName, 90, Image::JPEG);
					$imageHash = hash_file('sha512', __DIR__ . '/output/files/' . $tempImageName);
					rename(__DIR__ . '/output/files/' . $tempImageName, __DIR__ . '/output/files/' . $imageHash);
					unset($image);
				} catch (Exception $ex) {
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
		"cz_animals" => [
			"cs" => "Česká zvěř",
			"en" => "Animals of Czech Republic",
		]
	],
	"concept" => [
		"cz_forest" => [
			"group" => "cz_animals",
			"names" => [
				"cs" => [
					"name" => "Lesy",
					"description" => "Zvířata žijící v zalesněných oblastech."
				],
				"en" => [
					"name" => "Forest",
					"description" => "Animals living in different types of woodland."
				]
			]
		],
		"cz_water" => [
			"group" => "cz_animals",
			"names" => [
				"cs" => [
					"name" => "Vody",
					"description" => "Zvířata žijící ve vodních tocích, nádržích, mořích, oceánech."
				],
				"en" => [
					"name" => "Waters",
					"description" => "Animals living in water bodies such as rivers, brooks, seas and oceans."
				]
			]
		],
		"cz_water_by" => [
			"group" => "cz_animals",
			"names" => [
				"cs" => [
					"name" => "Okolí vod",
					"description" => "Zvířata žijící v okolí vodních toků, nádrží, moří atp."
				],
				"en" => [
					"name" => "Waters",
					"description" => "Animals living in near water."
				]
			]
		],
		"cz_mountain" => [
			"group" => "cz_animals",
			"names" => [
				"cs" => [
					"name" => "Hory a skály",
					"description" => "Zvířata žijící v nepřístupném terénu hor, skal, jeskyní a jejich okolí."
				],
				"en" => [
					"name" => "Mountains",
					"description" => "Animals living in harder accessible terrain of mountains, rocks, caves and around."
				]
			]
		],
		"cz_desert" => [
			"group" => "cz_animals",
			"names" => [
				"cs" => [
					"name" => "Pouště",
					"description" => "Zvířata žijící v nehostinných prostředích pouští, blízko vulkánů apod."
				],
				"en" => [
					"name" => "Desert",
					"description" => "Animals living in hostile environments such as deserts, vulcanos etc."
				]
			]
		],
		"cz_plain" => [
			"group" => "cz_animals",
			"names" => [
				"cs" => [
					"name" => "Pláně",
					"description" => "Zvířata žijící v otevřeném prostředí plání, prérií, luk apod."
				],
				"en" => [
					"name" => "Plains",
					"description" => "Animals living in mostly treeless environments such as prairies, grasslands or meadows."
				]
			]
		],
		"cz_cultural" => [
			"group" => "cz_animals",
			"names" => [
				"cs" => [
					"name" => "Kulturní krajina",
					"description" => "Zvířata žijící v místech ovlivněných či zcela vytvořených člověkem."
				],
				"en" => [
					"name" => "Cultural landscape",
					"description" => "Animals living in places influenced or entirely created by human."
				]
			]
		]
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

