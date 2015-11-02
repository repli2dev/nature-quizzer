<?php
/**
 * This is a wrapper for manipulation with database and related items (such as representations).
 * For the sake of clarity, this uses standard unix utilities for accessing database and archiving.
 */

use NatureQuizzer\Utils\CLI;
use NatureQuizzer\Utils\CLI\ExecutionProblem;
use Nette\Object;

include_once __DIR__ . "/../app/bootstrap.php";

class Backup extends Object
{
	private $transactionName;

	private function prepareDestination($destination) {
		$destination = realpath($destination);
		if (!file_exists($destination) || !is_dir($destination)) {
			throw new CLI\InvalidArguments("Backup directory doesn't exist.");
		}
		$dir = $destination . '/' . $this->getName();
		if (file_exists($dir)) {
			throw new CLI\ExecutionProblem(sprintf('Backup folder [%s] already exists.', $dir));
		}
		if (!mkdir($dir,0777, TRUE)) {
			throw new CLI\ExecutionProblem("Cannot create backup folder.");
		}
		return $dir;
	}

	private function getName()
	{
		if (!isset($this->transactionName)) {
			$this->transactionName = sprintf('%s/%s/', $this->getInstance(), date('Y-m-d--H-i-s'));	// For unique destination per transaction
		}
		return $this->transactionName;
	}

	private function getInstance()
	{
		$temp = explode('/', __DIR__);
		return $temp[count($temp)-2];
	}

	private function getConnectionParameters()
	{
		$neon = new \Nette\DI\Config\Adapters\NeonAdapter();
		$params = $neon->load(__DIR__ . '/../app/config/config.local.neon');
		$database = $params['nette']['database'];
		preg_match('/^pgsql:host=(.*?);dbname=(.*?)$/', $database['dsn'], $matches);
		if (count($matches) != 3) {
			throw new ExecutionProblem('Invalid configuration cannot parse database and host');
		}

		return [
			'host' => $matches[1],
			'database' => $matches[2],
			'username' => $database['user'],
			'password' => $database['password']
		];
	}

	private function dumpDatabase($backupDir)
	{
		$credentials = $this->getConnectionParameters();

		$command = sprintf('PGPASSWORD=%s pg_dump -Fp --no-owner -h %s -U %s %s > %s',
			escapeshellarg($credentials['password']),
			escapeshellarg($credentials['host']),
			escapeshellarg($credentials['username']),
			escapeshellarg($credentials['database']),
			$backupDir . '/database.dump'
		);
		if (exec($command)) {
			throw new ExecutionProblem('Execution of database dump have failed.');
			return;
		}
	}

	private function dumpRepresentations($backupDir)
	{
		$dir = realpath(__DIR__ . '/../www/images/organisms');
		$command = sprintf('cd %s; tar -zcf %s .',
			escapeshellarg($dir),
			$backupDir . '/representations.tar.gz'
		);
		if (exec($command)) {
			throw new ExecutionProblem('Execution of representation dump have failed.');
			return;
		}
	}

	public function database($destination)
	{
		$backupDir = $this->prepareDestination($destination);
		$this->dumpDatabase($backupDir);
		printf("FINISHED\n");
	}

	public function complete($destination)
	{
		$backupDir = $this->prepareDestination($destination);
		$this->dumpDatabase($backupDir);
		$this->dumpRepresentations($backupDir);
		printf("FINISHED\n");
	}
}

$backup = new Backup();

$cli = new CLI($argv);
$cli->setName('Nature Quizzer Backup tool');
$cli->addCommand('database', 'DESTINATION', 'Backup database only.', $backup->database);
$cli->addCommand('complete', 'DESTINATION', 'Backup database and relevant files.', $backup->complete);
$cli->execute();