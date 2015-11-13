<?php

use NatureQuizzer\Logging\FileLogger;
use Nette\Configurator;

require __DIR__ . '/../vendor/autoload.php';

$configurator = new Configurator;

// Always enable debug mode when running from command line
if (PHP_SAPI === 'cli') {
	$configurator->setDebugMode(true);
}

if (PHP_SAPI !== 'cli' && (file_exists(__DIR__ . '/../.deployment-in-progress') || file_exists(__DIR__ . '/../.deployment'))) {
	// AJAX requests needs special treatment
	if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
		echo json_encode(['deployment' => TRUE]);
		exit;
	} else {
		include(__DIR__ . '/../www/.maintenance.php');
	}
}

$configurator->enableDebugger(__DIR__ . '/../log');
$configurator->setTempDirectory(__DIR__ . '/../temp');
$configurator->createRobotLoader()
	->addDirectory(__DIR__)
	->addDirectory(__DIR__ . '/../library/')
	->register();

$configurator->addConfig(__DIR__ . '/config/config.neon');
if ($configurator->isDebugMode()) {
	$configurator->addConfig(__DIR__ . '/config/config.development.neon');
	// Create extra dumping function to dump it into file
	function fdump()
	{
		foreach (func_get_args() as $arg) {
			if (!is_string($arg)) {
				ob_start();
				if (is_array($arg)) {
					print_r($arg);
				} else {
					var_dump($arg);
				}
				$output = ob_get_contents();
				ob_end_clean();
			} else {
				$output = $arg;
			}
			$fileLogger = FileLogger::getInstance(__DIR__ . '/../log', 'debug.log');
			$fileLogger->log($output);
		}
	}
	fdump(sprintf("\n\n\n=== %s === %s =========================\n", ((!isset($_SERVER['REQUEST_URI'])) ? 'CLI' : $_SERVER['REQUEST_URI']), date('Y-m-d H:i:s')));
} else {
	// Create extra dummy dumping function to prevent fatal errors when forgotten
	function fdump()
	{
		// Intentionally blank
	}
}
$configurator->addConfig(__DIR__ . '/config/config.local.neon');

$container = $configurator->createContainer();

// Set search_path in database
$container->getByType('Nette\Database\Connection')->onConnect[] = function($conn) {
	$conn->query('SET search_path TO "web_nature_quizzer"');
};

return $container;
