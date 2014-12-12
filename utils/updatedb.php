<?php
use NatureQuizzer\Database\Utils\DatabaseMigrator;

include_once __DIR__ . "/../app/bootstrap.php";

// Check if migrations should be done
$databaseContext = $container->getByType('Nette\\Database\\Context');
$dm = new DatabaseMigrator($databaseContext, __DIR__ . '/../resources/migrations/');
$dm->migrate();