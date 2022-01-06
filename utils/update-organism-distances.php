<?php
/**
 * This scripts ensure updating each-to-each organism distance for usage by the distractors selection algorithm.
 *
 * Warning: Execution of this script removes all previous distances from `organism_distance` table and insert new ones for
 * those organism inside the database.
 */
use NatureQuizzer\Database\Utils\ImportTransaction;
use Nette\Database\Connection;

$container = include __DIR__ . "/../app/bootstrap.php";


$import = new ImportTransaction($container);
$import->perform(function($container) {
	/** @var Connection */
	$connection = $container->getByType('Nette\\Database\\Connection');
	$connection->query('TRUNCATE organism_distance;');
	$connection->query('
		INSERT INTO organism_distance
		SELECT
		  o1.id_organism AS id_organism_from,
		  o2.id_organism AS id_organism_to,
		  compute_organism_distance(o1.latin_name, o2.latin_name) AS distance
		FROM organism AS o1
		CROSS JOIN organism AS o2
		WHERE o1.id_organism != o2.id_organism AND compute_organism_distance(o1.latin_name, o2.latin_name) IS NOT NULL
	');

	echo "\nFINISHED\n";
});
