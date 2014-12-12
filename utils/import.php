<?php
use NatureQuizzer\Database\Model\Concept;
use NatureQuizzer\Database\Model\Organism;
use Nette\Database\Context;
use Nette\DI\Container;
use Nette\Utils\ArrayHash;
use Nette\Utils\Finder;
use Nette\Utils\Image;
use Nette\Utils\Strings;
use Nette\Utils\UnknownImageFileException;
use NatureQuizzer\Database\Utils\ImportTransaction;

include_once __DIR__ . "/../app/bootstrap.php";

$import = new ImportTransaction($container);
$import->perform(function($container) {
	$csCode = 1; /* Czech */

	$result = [];
	$result = unserialize(file_get_contents(__DIR__ . '/../staging/testing/data.txt'));


	/** @var Concept $conceptModel */
	$conceptModel = $container->getByType('App\\Model\\Concept');
	$conceptId = $conceptModel->insert(
		['code_name' =>'czech_animals'],
		[$csCode => ArrayHash::from(['name' => 'Česká zvěř', 'description' => ''])]
	);

	/** @var Organism $organismModel */
	$organismModel = $container->getByType('App\\Model\\Organism');

	foreach ($result as $latin => $czech) {
		echo "Processing... ". $latin . ' ... ' . $czech;
		$organismId = $organismModel->insert(
			['latin_name' => $latin],
			[$csCode => ArrayHash::from(['name' => $czech])]
		)->id_organism;
		$organismModel->addBelonging($organismId, $conceptId);

		// Process representations
		echo "...";
		$files = Finder::findFiles('*')->in(__DIR__ . '/../staging/testing/' . Strings::webalize($latin));
		foreach ($files as $file) {
			try {
				$image = Image::fromFile($file);
			} catch (UnknownImageFileException $ex) {
				echo " ! ";
				continue;
			}
			$image->resize(300, 300);
			$representationId = $organismModel->addRepresentation($organismId, ArrayHash::from(['source' => 'TBD']));
			$image->save(__DIR__ . '/../www/images/organisms/' . $representationId, 95, Image::JPEG);
			echo " X ";
		}
		echo "\n";
	}
});