<?php
/**
 * This is simple packaging tool for managing data packages which encapsulate some coherent set of data to be used by Nature Quizzer.
 * Especially this handles:
 *  - languages
 *  - groups
 *  - concepts
 *  - organisms
 *  - representations
 *
 * Only groups, concepts, organisms and representations are tracked for automated garbage collection.
 *
 *
 * Key principles (of import):
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
 *   - Each package should contain everything needed by it
 *   - When doing import only current relations are marked as dependent to the package
 *     (therefore on another import with less items, some of them will become not referenced by any package -> use gc command)
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
use NatureQuizzer\Database\Model\Package;
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
	print "Nature Quizzer Packaging tool\n";
	print "-----------------------------\n";
	print "Usage:\n\n";
	print "\t php package.php import <PATH>\t\t Transactionally imports package from <PATH> where package.json and files folder are located\n";
	print "\t php package.php delete <NAME>\t\t Remove package with name <NAME> from database (this doesn't remove the items! Use GC for that.)\n";
	print "\t php package.php gc \t\t\t Performs garbage collection and will ask for confirmation.\n";
	print "\t php package.php gc-representations \t Performs garbage collection of representations and will ask for confirmation.";
	print "\n\n";
	print "Typical order: import/delete -> gc -> gc-representations!\n\n";
	print "Use with care!";
	print "\n\n";
}

if (count ($argv) <= 1) {
	printHelp();
	exit(0);
}

$command = $argv[1];

if ($command == 'import') {
	if (count ($argv) <= 2) {
		printHelp();
		exit(0);
	}
	$path = $argv[2];
	$wholePath = getcwd() . '/' . $path;
	if (!is_dir($wholePath)) {
		print "The specified path doesn't exists.\n";
		exit(1);
	}
	if (!is_dir($wholePath . '/files/') || !is_file($wholePath . '/package.json')) {
		print "The specified path doesn't contain package.json or files folder.\n";
		exit(1);
	}

	$packageName = basename($wholePath);
	echo "IMPORTING PACKAGE: " . $packageName . "...\n";

	$data = JSON::decode(file_get_contents($wholePath . '/package.json'));
	echo "DATA LOADED...\n";

	$import = new ImportTransaction($container);
	$import->perform(function($container) use($data, $wholePath, $packageName) {
		$batchFilesCopy = new BatchFilesCopy();
		/** @var Language $languageModel */
		$languageModel = $container->getByType('NatureQuizzer\\Database\\Model\\Language');
		/** @var Group $groupModel */
		$groupModel = $container->getByType('NatureQuizzer\\Database\\Model\\Group');
		/** @var Concept $conceptModel */
		$conceptModel = $container->getByType('NatureQuizzer\\Database\\Model\\Concept');
		/** @var Organism $organismModel */
		$organismModel = $container->getByType('NatureQuizzer\\Database\\Model\\Organism');
		/** @var Package $package */
		$packageModel = $container->getByType('NatureQuizzer\\Database\\Model\\Package');

		/* Remove package relationships (will be set to new ones after import) */
		$packageModel->delete($packageName);
		$imported = [
			'groups' => [],
			'concepts' => [],
			'organisms' => [],
			'representations' => [],
		];

		/* First import languages */
		foreach ($data->language as $code => $info) {
			$row = $languageModel->findByCode($code);
			if ($row === NULL) {
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
			if ($row === NULL) {
				$temp = $groupModel->insert(['code_name' => $code], $groupInfo);
				$imported['groups'][] = $temp->id_group;
			} else {
				$groupModel->update($row->id_group, [], $groupInfo);
				$imported['groups'][] = $row->id_group;
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
			if ($row === NULL) {
				$temp = $conceptModel->insert($conceptInfo, $conceptLangInfo);
				$imported['concepts'][] = $temp->id_concept;
			} else {
				$conceptModel->update($row->id_concept, $conceptInfo, $conceptLangInfo);
				$imported['concepts'][] = $row->id_concept;
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

			if ($row === NULL) {
				$row = $organismModel->insert(['latin_name' => $latinName, 'inserted' => new DateTime(), 'updated' => new DateTime()], $organismNames);
			} else {
				$organismModel->update($row->id_organism, [], $organismNames);
			}
			$organismId = $row->id_organism;
			$imported['organisms'][] = $organismId;

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
				if ($representationRow === NULL) {
					$representationRow = $organismModel->addRepresentation($organismId, ArrayHash::from($representation));
					$batchFilesCopy->add($wholePath . '/files/' . $representation->hash, __DIR__ . '/../www/images/organisms/' . $representationRow->id_representation);
				}
				$imported['representations'][] = $representationRow->id_representation;
			}
		}
		echo "... ORGANISMS IMPORTED\n";
		echo "... COPYING ORGANISM REPRESENTATIONS\n";
		$batchFilesCopy->execute();
		echo "... SAVING PACKAGE DEPENDENCIES\n";
		$packageModel->add($packageName, array_unique($imported['groups']), array_unique($imported['concepts']), array_unique($imported['organisms']), array_unique($imported['representations']));
		echo "\nFINISHED\n";
	});
} elseif ($command == 'delete') {
	if (count ($argv) <= 2) {
		printHelp();
		exit(0);
	}
	$name = $argv[2];

	/** @var Package $package */
	$packageModel = $container->getByType('NatureQuizzer\\Database\\Model\\Package');

	if ($packageModel->exist($name)) {
		$packageModel->delete($name);
		print "Package was successfully removed from database, now it is time do the garbage collection.\n\n";
		exit(0);
	} else {
		print "No such package.\n\n";
		exit(1);
	}
} elseif ($command == 'gc') {
	$import = new ImportTransaction($container);
	$import->perform(function($container){
		/** @var Package $package */
		$packageModel = $container->getByType('NatureQuizzer\\Database\\Model\\Package');

		print "To be deleted\n";
		print "-------------\n";

		$groups = $packageModel->leftoverGroups();
		print " Groups:\n";
		foreach ($groups as $group) {
			print "  - " . $group->code_name . "\n";
		}

		$concepts = $packageModel->leftoverConcepts();
		print "\n Concepts:\n";
		foreach ($concepts as $concept) {
			print "  - " . $concept->code_name . "\n";
		}

		$organisms = $packageModel->leftoverOrganisms();
		print "\n Organisms:\n";
		foreach ($organisms as $organism) {
			print "  - " . $organism->latin_name . "\n";
		}

		$representations = $packageModel->leftoverRepresentations();
		print "\n Representations:\n";
		foreach ($representations as $representation) {
			print "  - " . $representation->id_representation . " (" . $representation->hash .  ")\n";
		}

		$stats = $packageModel->stats();
		print "\n--------------\n";
		print "Database stats: \n";
		print " Groups: ". $stats['group_count'] . "\n";
		print " Concepts: ". $stats['concept_count'] . "\n";
		print " Organisms: ". $stats['organism_count'] . "\n";
		print " Representations: ". $stats['representation_count'] . "\n";
		print "\n--------------\n";
		print "To be deleted: \n";
		print " Groups: ". count($groups) . "\n";
		print " Concepts: ". count($concepts) . "\n";
		print " Organisms: ". count($organisms) . "\n";
		print " Representations: ". count($representations) . "\n";
		print "--------------\n";
		print "Continue? [y/N]: ";
		$input = fgets(STDIN);
		if ($input !== "y\n") {
			print "ABORTING!\n";
			throw new Exception("Aborted by user.");
		}
		// Perform cleaning
		$packageModel->clean(array_projection($groups, 'id_group'), array_projection($concepts, 'id_concept'), array_projection($organisms, 'id_organism'), array_projection($representations, 'id_representation'));
		// Offer last change to see real impact (due to constraints and cascading)
		$stats = $packageModel->stats();
		print "\n--------------\n";
		print "Database stats: \n";
		print " Groups: ". $stats['group_count'] . "\n";
		print " Concepts: ". $stats['concept_count'] . "\n";
		print " Organisms: ". $stats['organism_count'] . "\n";
		print " Representations: ". $stats['representation_count'] . "\n";
		print "\n--------------\n";
		print "Commit transaction? [y/N]: ";
		$input = fgets(STDIN);
		if ($input !== "y\n") {
			print "ABORTING!\n";
			throw new Exception("Aborted by user.");
		}
	});

	exit(0);
} elseif ($command == 'gc-representations') {
	/** @var Package $package */
	$packageModel = $container->getByType('NatureQuizzer\\Database\\Model\\Package');

	print "Representation garbage collection\n";
	print "---------------------------------\n";
	print "Run package.php gc before this!\n\n";

	print "To be deleted\n";
	print "-------------\n";
	$allFiles = [];
	foreach (Finder::findFiles('*')->exclude('.*')->in(__DIR__ . '/../www/images/organisms/') as $item) {
		$allFiles[] = $item->getBasename();
	}
	$trackedFiles = $packageModel->getTrackedRepresentations();
	$leftovers = array_diff($allFiles, $trackedFiles);
	foreach ($leftovers as $item) {
		print " - ". $item . "\n";
	}
	print "\n--------------\n";
	print "Count: " . count($leftovers)  ." \n";
	print "Delete? [y/N]: ";
	$input = fgets(STDIN);
	if ($input !== "y\n") {
		print "ABORTING!\n";
		exit(0);
	}
	foreach ($leftovers as $item) {
		unlink(__DIR__ . '/../www/images/organisms/' . $item);
	}
} else {
	printHelp();
	exit(1);
}

function array_projection($array, $key) {
	$output = [];
	foreach ($array as $item) {
		if (isset($item[$key])) {
			$output[] = $item[$key];
		}
	}
	return $output;
}