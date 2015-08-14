<?php
/**
 * This is data importing tool for importing package with data about concepts, subconcepts, organisms and its variants.
 * Key principles:
 *   - Works in upsert fashion -- tries to find and use item (organism, concept etc.) if it already exists.
 *   - For retrieval uniqueness these are used:
 *      - concept: code_name
 *      - group: code_name
 *      - language: code (according to ISO 639-1 (or ISO 639-2 when 1 not set))
 *      - organism: latin_name (normalized)
 *      - organism representation: image hash
 *   - The packages has following structure inside given folder:
 *      - package.json (contains all data to be stored in the database)
 *      - files/ (contains all referenced files)
 *   - Each package should contain everything needed by the
 *
 * Example structure of package.json
 *
 * 	{
 * 		"language": {
 * 			"cs": {
 *				"name": "Czech",
 * 				"local_name": "Čeština",
 * 				"is_default": TRUE
 * 			},
 * 			"en": {
 *				"name": "English",
 * 				"local_name": "English"
 * 				"is_default": FALSE
 * 			},
 * 		},
 * 		"group": {
 * 			"cz_animals": {
 * 				"cs": "Česká zvěř",
 * 				"en": "Animals of Czech Republic",
 * 			}
 *		},
 * 		"concept": {
 * 			"cz_desert": {
 * 				"group": "cz_animals",
 * 				"names": {
 *					"cs": {
 * 						"name": "Pouště",
 * 						"description": "",
 * 					},
 *					"en": {
 * 						"name": "Desert",
 * 						"description": "",
 * 					}
 * 				},
 * 			},
 * 			"cz_city": {
 * 				"group": "cz_animals",
 * 				"names": {
 *					"cs": {
 * 						"name": "Města",
 * 						"description": "",
 * 					},
 *					"en": {
 * 						"name": "City",
 * 						"description": "",
 * 					}
 * 				},
 * 			},
 * 		},
 * 		"organism": {
 *			"vulpes vulpes": {
 * 				"names": {
 * 					"cs": "Liška obecná",
 * 					"en": "Red fox",
 * 				},
 * 				"concepts": ["cz_desert", "cz_city"]
 * 				"representations": [
 * 					{
 * 						"hash": "<Image hash to prevent duplicity>",
 * 						"source": "<Source where image was downloaded>",
 * 						"license": "<License information>",
 * 						"rights_holder": "<Rights holder description>",
 * 					}
 * 				]
 * 			}
 * 		}
 *	}
 */
use NatureQuizzer\Database\Model\Concept;
use NatureQuizzer\Database\Model\Group;
use NatureQuizzer\Database\Model\Language;
use NatureQuizzer\Database\Model\Organism;
use NatureQuizzer\Tools\BatchFilesCopy;
use Nette\Database\Context;
use Nette\DI\Container;
use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;
use Nette\Utils\Finder;
use Nette\Utils\Image;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use Nette\Utils\UnknownImageFileException;
use NatureQuizzer\Database\Utils\ImportTransaction;

include_once __DIR__ . "/../app/bootstrap.php";

function printHelp()
{
	print "Import tool for packages with concepts, organisms... into Nature Quizzer\n";
	print "Usage: import.php PATH where package.json and files folder are located\n\n";
}

if (count ($argv) <= 1) {
	printHelp();
	exit(0);
}
$path = $argv[1];
$wholePath = getcwd() . '/' . $path;
if (!is_dir($wholePath)) {
	print "The specified path doesn't exists.\n";
	exit(1);
}
if (!is_dir($wholePath . '/files/') || !is_file($wholePath . '/package.json')) {
	print "The specified path doesn't contain package.json or files folder.\n";
	exit(1);
}

$data = JSON::decode(file_get_contents($wholePath . '/package.json'));
echo "DATA LOADED...\n";

$import = new ImportTransaction($container);
$import->perform(function($container) use($data, $wholePath) {
	$batchFilesCopy = new BatchFilesCopy();
	/** @var Language $languageModel */
	$languageModel = $container->getByType('NatureQuizzer\\Database\\Model\\Language');
	/** @var Group $groupModel */
	$groupModel = $container->getByType('NatureQuizzer\\Database\\Model\\Group');
	/** @var Concept $conceptModel */
	$conceptModel = $container->getByType('NatureQuizzer\\Database\\Model\\Concept');
	/** @var Organism $organismModel */
	$organismModel = $container->getByType('NatureQuizzer\\Database\\Model\\Organism');

	/* First import languages */
	foreach ($data->language as $code => $info) {
		$row = $languageModel->findByCode($code);
		if ($row === FALSE) {
			$languageModel->insert(array_merge($info, ['code' => $code]));
		} else {
			$temp = iterator_to_array($row);
			unset($temp['id_language'], $temp['code']);
			$temp = array_merge($temp, (array) $info);
			$languageModel->update($row->id_language, $temp);
		}
	}
	$langLookup = array_flip($languageModel->getLanguageCodes());
	print "... LANGUAGES IMPORTED\n";

	/* Import groups */
	foreach ($data->group as $code => $info) {
		$row = $groupModel->findByCodeName($code);
		$groupInfo = [];
		foreach ($info as $langCode => $groupName) {
			$groupInfo[$langLookup[$langCode]] = ['name' => $groupName, 'inserted' => new DateTime(), 'updated' => new DateTime()];
		}
		$groupInfo = ArrayHash::from($groupInfo);
		if ($row === FALSE) {
			$groupModel->insert(['code_name' => $code], $groupInfo);
		} else {
			$groupModel->update($row->id_group, [], $groupInfo);
		}
	}
	$groupLookup = array_flip($groupModel->getPairs());
	echo "... GROUPS IMPORTED\n";

	/* Import concepts */
	foreach ($data->concept as $code => $conceptData) {
		$row = $conceptModel->findByCodeName($code);
		$conceptInfo = ['code_name' => $code, 'id_group' => $groupLookup[$conceptData->group]];
		$conceptLangInfo = [];
		foreach ($conceptData->names as $langCode => $temp) {
			$conceptLangInfo[$langLookup[$langCode]] = array_merge((array) $temp, ['inserted' => new DateTime(), 'updated' => new DateTime()]);
		}
		$conceptLangInfo = ArrayHash::from($conceptLangInfo);
		if ($row === FALSE) {
			$conceptModel->insert($conceptInfo, $conceptLangInfo);
		} else {
			$conceptModel->update($row->id_concept, $conceptInfo, $conceptLangInfo);
		}
	}
	$conceptLookup = array_flip($conceptModel->getPairs());
	echo "... CONCEPTS IMPORTED\n";

	/* Import organisms */
	foreach ($data->organism as $latinName => $organismData) {
		/* Organism and its names */
		$row = $organismModel->findByLatinName($latinName);
		$organismNames = [];
		foreach ($organismData->names as $langCode => $name) {
			$organismNames[$langLookup[$langCode]] = ['name' => $name, 'inserted' => new DateTime(), 'updated' => new DateTime()];
		}
		$organismNames = ArrayHash::from($organismNames);

		if ($row === FALSE) {
			$row = $organismModel->insert(['latin_name' => $latinName, 'inserted' => new DateTime(), 'updated' => new DateTime()], $organismNames);
		} else {
			$organismModel->update($row->id_organism, [], $organismNames);
		}
		$organismId = $row->id_organism;

		/* Mapping to concepts, this only adds new belongings, older one will remain and have to be removed manually. */
		foreach ($organismData->concepts as $conceptCode) {
			if ($organismModel->existsBelonging($organismId, $conceptLookup[$conceptCode])) {
				continue;
			}
			$organismModel->addBelonging($organismId, $conceptLookup[$conceptCode]);
		}

		/* Add representations */
		foreach ($organismData->representations as $representation) {
			$representationRow = $organismModel->findRepresentationByHash($representation->hash);
			if ($representationRow === FALSE) {
				$representationRow = $organismModel->addRepresentation($organismId, ArrayHash::from($representation));
				$batchFilesCopy->add($wholePath . '/files/' . $representation->hash, __DIR__ . '/../www/images/organisms/' . $representationRow->id_representation);
			}
		}
	}
	echo "... ORGANISMS IMPORTED\n";
	echo "... COPYING ORGANISM REPRESENTATIONS\n";
	$batchFilesCopy->execute();
	echo "\nFINISHED\n";
});