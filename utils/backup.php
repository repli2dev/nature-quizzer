<?php
/**
 * This is a wrapper for manipulation with database and related items (such as representations).
 * For the sake of clarity, this uses standard unix utilities for accessing database and archiving.
 */

use NatureQuizzer\Utils\CLI;
use NatureQuizzer\Utils\CLI\ExecutionProblem;
use NatureQuizzer\Utils\Helpers;
use Nette\Database\Connection;
use Nette\DI\Config\Adapters\NeonAdapter;
use Nette\DI\Container;
use Nette\Object;

include_once __DIR__ . "/../app/bootstrap.php";

class Backup extends Object
{

	const DATABASE_FILE = 'database.gz';
	const REPRESENTATION_FILE = 'representations.tar.gz';

	/** @var Container */
	private $container;

	private $transactionName;

	public function __construct(Container $container)
	{
		$this->container = $container;
	}

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
		$neon = new NeonAdapter();
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

		// Produce compressed but SQL compliant output without owners, privileges and ACLs (which can be not transferable)
		$command = sprintf('PGPASSWORD=%s pg_dump -Fp -Z7 --no-privileges --no-acl --no-owner -h %s -U %s %s > %s',
			escapeshellarg($credentials['password']),
			escapeshellarg($credentials['host']),
			escapeshellarg($credentials['username']),
			escapeshellarg($credentials['database']),
			$backupDir . '/' . self::DATABASE_FILE
		);
		if (system($command) === FALSE) {
			throw new ExecutionProblem('Execution of database dump have failed.');
		}
	}

	private function dumpRepresentations($backupDir)
	{
		$dir = realpath(__DIR__ . '/../www/images/organisms');
		$command = sprintf('cd %s; tar -zcf %s .',
			escapeshellarg($dir),
			$backupDir . '/' . self::REPRESENTATION_FILE
		);
		if (system($command) === FALSE) {
			throw new ExecutionProblem('Execution of representation dump have failed.');
		}
	}

	private function checkBackup($backupDir)
	{
		$backupDir = realpath($backupDir);
		if (!file_exists($backupDir) || !is_dir($backupDir)) {
			throw new CLI\InvalidArguments(sprintf("Backup [%s] doesn't exists", $backupDir));
		}
		return $backupDir;
	}

	private function checkEmptyDatabase()
	{
		/** @var Connection $connection */
		$connection = $this->container->getByType('Nette\\Database\\Connection');
		$count = $connection->query("SELECT schema_name FROM information_schema.schemata WHERE schema_name = 'itis' OR schema_name = 'web_nature_quizzer'")->getRowCount();
		if ($count > 0) {
			return FALSE;
		}
		return TRUE;
	}

	public function restore($source)
	{
		$source = $this->checkBackup($source);

		$databaseFile = $source . '/' . self::DATABASE_FILE;
		$representationFile = $source . '/' . self::REPRESENTATION_FILE;

		$restoreDatabase = file_exists($databaseFile);
		$restoreRepresentations = file_exists($representationFile);

		if (!$restoreDatabase) {
			throw new CLI\InvalidArguments("Backup doesn't contain database backup.");
		}
		$credentials = $this->getConnectionParameters();
		$emptyDB = $this->checkEmptyDatabase();

		printf("Restoring backup\n");
		printf("----------------\n");
		printf(" Dir: %s\n", $source);
		printf(" Database: %s\n", ($restoreDatabase) ? 'YES' : 'NO');
		printf(" Representations: %s\n", ($restoreRepresentations) ? 'YES' : 'NO');
		printf("----------------\n");
		printf(" Destination DB: %s (empty %s)\n", $credentials['database'], ($emptyDB ? 'YES' : 'NO'));
		if (!$emptyDB) {
			printf(" WARNING: Database must be emptied manually!\n");
		}
		printf("----------------\n");
		$decision = Helpers::confirmPrompt('Continue?');
		if (!$decision) {
			printf("ABORTING\n");
			exit(1);
		}

		printf ("Restoring database...\n");
		$command = sprintf('gzip -c -d %s | PGPASSWORD=%s psql -h %s -U %s %s',
			$databaseFile,
			escapeshellarg($credentials['password']),
			escapeshellarg($credentials['host']),
			escapeshellarg($credentials['username']),
			escapeshellarg($credentials['database'])
		);
		if (system($command) === FALSE) {
			throw new ExecutionProblem('Restoration of database dump have failed.');
		}
		printf ("Restoring representations...\n");
		$oldDir = realpath(__DIR__ . '/../www/images') . '/organisms';
		$command = sprintf('tar zxvf %s -C %s', $representationFile, $oldDir);
		if (system($command) === FALSE) {
			throw new ExecutionProblem('Restoration of representations have failed. Cannot untar backup.');
		}
		printf("WARNING: Now you may want to run garbage collection on representations.\n");
	}

	public function database($destination)
	{
		$backupDir = $this->prepareDestination($destination);
		$this->dumpDatabase($backupDir);
	}

	public function complete($destination)
	{
		$backupDir = $this->prepareDestination($destination);
		$this->dumpDatabase($backupDir);
		$this->dumpRepresentations($backupDir);
	}
}

$backup = new Backup($container);

$cli = new CLI($argv);
$cli->setName('Nature Quizzer Backup tool');
$cli->addCommand('database', 'DESTINATION', 'Backup database only.', $backup->database);
$cli->addCommand('complete', 'DESTINATION', 'Backup database and relevant files.', $backup->complete);
$cli->addCommand('restore', 'SOURCE', 'Restore from given backup.', $backup->restore);
$cli->execute();