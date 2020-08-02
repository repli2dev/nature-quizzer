<?php
/**
 * This is a wrapper for exporting data for analyses.
 * For the sake of clarity, this uses standard unix utilities for accessing database and archiving.
 */

use NatureQuizzer\Utils\CLI;
use NatureQuizzer\Utils\CLI\ExecutionProblem;
use NatureQuizzer\Utils\Helpers;
use Nette\DI\Container;
use Nette\SmartObject;
use Nette\Utils\FileSystem;

include_once __DIR__ . "/../app/bootstrap.php";

class Export
{
	use SmartObject;

	/** @var Container */
	private $container;

	private $exportFileName;

	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	private function prepareDestination($destination) {
		$destination = realpath($destination);
		if (!file_exists($destination) || !is_dir($destination)) {
			throw new CLI\InvalidArguments("Export directory doesn't exist.");
		}
		$file = $destination . '/' . $this->getName();
		if (file_exists($file)) {
			throw new CLI\ExecutionProblem(sprintf('Export [%s] already exists.', $file));
		}
		return $destination;
	}

	private function getName()
	{
		if (!isset($this->exportFileName)) {
			$this->exportFileName = sprintf('%s.tar.gz', date('Y-m-d--H-i-s'));
		}
		return $this->exportFileName;
	}

	private function getConnectionParameters()
	{
		try {
			return Helpers::getConnectionParametersFromConfig(__DIR__ . '/../app/config/config.local.neon');
		} catch (Exception $ex) {
			throw new ExecutionProblem('Invalid configuration cannot parse database and host');
		}
	}

	private function dumpDatabase($destination)
	{
		$credentials = $this->getConnectionParameters();

		$tempDir = tempnam($destination, 'EAX');
		try {
			FileSystem::delete($tempDir);
			FileSystem::createDir($tempDir);
		} catch (Exception $ex) {
			FileSystem::delete($tempDir);
			throw new ExecutionProblem('Cannot create temporary directory for dumping data.');
		}

		$command = sprintf('cd %s && PGPASSWORD=%s psql -h %s -U %s %s < %s && tar -zcvf %s -C %s .',
			$tempDir,
			escapeshellarg($credentials['password']),
			escapeshellarg($credentials['host']),
			escapeshellarg($credentials['username']),
			escapeshellarg($credentials['database']),
			__DIR__ . '/export-data.sql',
			'../' . $this->getName(),
			$tempDir
		);
		if (system($command) === FALSE) {
			throw new ExecutionProblem('Execution of export have failed.');
		}
		try {
			FileSystem::delete($tempDir);
		} catch (Exception $ex) {
			throw new ExecutionProblem('Cannot clean temporary directory for dumping data.');
		}
	}

	public function export($destination)
	{
		$backupDir = $this->prepareDestination($destination);
		$this->dumpDatabase($backupDir);
	}
}

$export = new Export($container);

$cli = new CLI($argv);
$cli->setName('Nature Quizzer Export analysis data tool');
$cli->addCommand('export', 'DESTINATION', 'Create new export dump.', $export->export);
$cli->execute();