<?php
/**
 * This script is util for manipulation withrecalculate all parameters for given model using Basic ELO.
 * Warning: do not try to use it on different models;
 */
use NatureQuizzer\Database\Model\CurrentKnowledge;
use NatureQuizzer\Database\Model\Model;
use NatureQuizzer\Database\Model\OrganismDifficulty;
use NatureQuizzer\Database\Model\PriorKnowledge;
use NatureQuizzer\Database\Utils\ImportTransaction;
use NatureQuizzer\Model\ModelFacadeFactory;
use NatureQuizzer\Model\Utils\BasicElo;
use NatureQuizzer\Utils\LookupTable;
use Nette\Database\Connection;

$container = include __DIR__ . "/../app/bootstrap.php";


function printHelp()
{
	print "Nature Quizzer Basic Elo utility\n";
	print "-----------------------------\n";
	print "Usage:\n\n";
	print "\t php basic-elo.php list \t\t\t List all available models.\n";
	print "\t php basic-elo.php truncate <NAME> \t\t\t Remove all data related to model <NAME>.\n";
	print "\t php basic-elo.php recalculate <DESTINATION MODEL> <SOURCE MODELS> \t Recalculate all data for given DESTINATION model taking into account all answers from SOURCE MODELS.\n";
	print "\n\n";
	print "Backup first. Use with care!";
	print "\n\n";
}

function deleteModelData(Connection $connection, $modelId)
{
	$connection->query('DELETE FROM organism_difficulty WHERE id_model = ?', $modelId);
	$connection->query('DELETE FROM prior_knowledge WHERE id_model = ?', $modelId);
	$connection->query('DELETE FROM current_knowledge WHERE id_model = ?', $modelId);
}

if (count ($argv) <= 1) {
	printHelp();
	exit(0);
}

$command = $argv[1];

if ($command == 'list') {
	/** @var Model $model */
	$model = $container->getByType('NatureQuizzer\\Database\\Model\\Model');
	$data = $model->getAll();
	$mask = "|%5s |%-30s |%5s | \n";
	printf($mask, 'ID', 'Name', 'Ratio');
	foreach ($data as $row) {
		printf($mask, $row->id_model, $row->name, $row->ratio);
	}
} elseif ($command == 'delete') {
	if (count ($argv) <= 2) {
		printHelp();
		exit(0);
	}
	$name = $argv[2];

	$import = new ImportTransaction($container);
	$import->perform(function($container) use($name) {
		/** @var Model $model */
		$model = $container->getByType('NatureQuizzer\\Database\\Model\\Model');
		/** @var Connection $connection */
		$connection = $container->getByType('Nette\\Database\\Connection');


		$modelRow = $model->getModelByName($name);
		if ($modelRow === NULL) {
			printf("No such model: %sy\n", $name);
			exit(1);
		}
		$modelId = $modelRow->id_model;
		// Delete old data
		deleteModelData($connection, $modelId);
		// Ask for confirmation
		print "Commit transaction? [y/N]: ";
		$input = fgets(STDIN);
		if ($input !== "y\n") {
			print "ABORTING!\n";
			exit();
		}
	});
} elseif ($command == 'recalculate') {
	if (count ($argv) <= 3) {
		printHelp();
		exit(0);
	}
	$destination = $argv[2];
	$sources = explode(',', $argv[3]);

	/** @var Model $model */
	$model = $container->getByType('NatureQuizzer\\Database\\Model\\Model');

	$modelRow = $model->getModelByName($destination);
	if ($modelRow === NULL) {
		printf("No such model: %sy\n", $destination);
		exit(1);
	}
	$destinationModelId = $modelRow->id_model;
	$sourceIds = [];
	foreach ($sources as $sourceName) {
		$modelRow = $model->getModelByName($sourceName);
		if ($modelRow === NULL) {
			printf("No such model: %sy\n", $destination);
			exit(1);
		}
		$sourceIds[] = $modelRow->id_model;
	}
	printf("Migration from answers of models: %s [%s]\n", implode(', ', $sources), implode(', ', $sourceIds));
	printf("Migration to model: %s [%s]\n", $destination, $destinationModelId);
	$import = new ImportTransaction($container);
	$import->perform(function($container) use($sourceIds, $destination, $destinationModelId) {
		/** @var ModelFacadeFactory $studentModelFactory */
		$studentModelFactory = $container->getByType('NatureQuizzer\\Model\\ModelFacadeFactory');
		/** @var BasicElo $studentModel */
		$studentModel = $studentModelFactory->getModel($destination);

		$organismDifficulty = new LookupTable();
		$priorKnowledge = new LookupTable();
		$currentKnowledge = new LookupTable();
		$organismFirstAnswers = new LookupTable();
		$userFirstAnswers = new LookupTable();

		/** @var Connection $connection */
		$connection = $container->getByType('Nette\\Database\\Connection');
		$answers = $connection->query('
			SELECT
				answer.id_answer,
				answer.id_organism,
				answer.question_type,
				(SELECT bool_and(correct) FROM answer AS distractor WHERE distractor.id_round = answer.id_round AND answer.question_seq_num = distractor.question_seq_num AND distractor.main = FALSE) AS question_correct,
				(SELECT COUNT(*) FROM answer AS distractor WHERE distractor.id_round = answer.id_round AND answer.question_seq_num = distractor.question_seq_num AND distractor.main = FALSE) AS distractor_count,
				round.id_user
			FROM answer
			JOIN round USING (id_round)
			WHERE main = TRUE AND id_model IN (?)
			ORDER BY answer.inserted ASC, id_answer ASC
		', $sourceIds);

		foreach ($answers as $answer) {
			// Prepare data
			$organismId = $answer->id_organism;
			$userId = $answer->id_user;
			$wasCorrect = $answer->question_correct;
			$optionsCount = $answer->distractor_count + 1;
			// Load previous data
			$organismD = $organismDifficulty->get($organismId);
			$priorK = $priorKnowledge->get($userId);
			$currentK = $currentKnowledge->get(sprintf('%d_%d', $userId, $organismId));
			$organismFA = $organismFirstAnswers->get($organismId);
			if ($organismFA === NULL) { $organismFA = []; }
			$userFA = $userFirstAnswers->get($userId);
			if ($userFA === NULL) { $userFA = []; }

			// Do computations
			$output = $studentModel->computeNewStudentModel($organismD, $priorK, $currentK, $optionsCount, $wasCorrect, count($organismFA), count($userFA));
//			print(sprintf(
//				"DATA > organism: %d; user, %d; correct %s; options: %d; first answers on organism: %d; first answers of user: %d\n",
//				$organismId, $userId, $wasCorrect ? 'true' : 'false', $optionsCount, count($organismFA), count($userFA)
//			));
//			print(sprintf(
//				"OLD > current knowledge: %f; prior knowledge: %f; organism_difficulty: %f\n",
//				$currentK, $priorK, $organismD
//			));

			// Store data back
			if (isset($output['priorKnowledge']) && isset($output['organismDifficulty'])) {
				$organismDifficulty->store($organismId, $output['organismDifficulty']);
				$priorKnowledge->store($userId, $output['priorKnowledge']);
			}
			$currentKnowledge->store(sprintf('%d_%d', $userId, $organismId), $output['currentKnowledge']);
//			print(sprintf(
//				"NEW > current knowledge: %f; prior knowledge: %f; organism_difficulty: %f\n",
//				$output['currentKnowledge'], isset($output['priorKnowledge']) ? $output['priorKnowledge'] : NULL, isset($output['organismDifficulty']) ? $output['organismDifficulty'] : NULL
//			));
//			fgets(STDIN);

			$organismFA[$userId] = 1;
			$organismFirstAnswers->store($organismId, $organismFA);
			$userFA[$organismId] = 1;
			$userFirstAnswers->store($userId, $userFA);
		}


		// Delete old data
		deleteModelData($connection, $destinationModelId);
		// Insert all into database
		/** @var OrganismDifficulty $modelOrganismDifficulty */
		$modelOrganismDifficulty = $container->getByType('NatureQuizzer\\Database\\Model\\OrganismDifficulty');
		/** @var PriorKnowledge $modelPriorKnowledge */
		$modelPriorKnowledge = $container->getByType('NatureQuizzer\\Database\\Model\\PriorKnowledge');
		/** @var CurrentKnowledge $modelCurrentKnowledge */
		$modelCurrentKnowledge = $container->getByType('NatureQuizzer\\Database\\Model\\CurrentKnowledge');

		foreach ($organismDifficulty->getAll() as $key => $value) {
			$temp = new \NatureQuizzer\Model\OrganismDifficultyEntry($key, $value);
			$modelOrganismDifficulty->persist($destinationModelId, $temp);
		}
		foreach ($priorKnowledge->getAll() as $key => $value) {
			$temp = new \NatureQuizzer\Model\PriorKnowledgeEntry($key, $value);
			$modelPriorKnowledge->persist($destinationModelId, $temp);
		}
		foreach ($currentKnowledge->getAll() as $keys => $value) {
			list($userId, $organismId) = explode('_', $keys);
			$temp = new \NatureQuizzer\Model\CurrentKnowledgeEntry($organismId, $userId, $value);
			$modelCurrentKnowledge->persist($destinationModelId, $temp);
		}

		// Ask for confirmation
		print "Commit transaction? [y/N]: ";
		$input = fgets(STDIN);
		if ($input !== "y\n") {
			print "ABORTING!\n";
			exit();
		}
	});
}
