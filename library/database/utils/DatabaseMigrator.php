<?php
namespace NatureQuizzer\Database\Utils;

use Exception;
use Nette\Database\Context;
use Nette\Database\SqlLiteral;
use Nette\Object;
use Nette\Utils\Finder;
use PDOException;

/**
 * Class that performs database migration (all SQL scripts from given path)...
 * 1. Sort SQL scripts from given path by name (prefix them with 001 to ensure correct order).
 * 2. Perform all scripts which are not present in database table migrations. All migrations are done in one transaction.
 */
final class DatabaseMigrator extends Object
{

	const MIGRATION_TABLE_NAME = "meta_migrations";

	/** @var Context */
	private $context;
	private $path;

	/**
	 * @param Context $context Database connection
	 * @param string $path Real absolute path where SQL scripts are stored
	 */
	public function __construct(Context $context, $path)
	{
		$this->context = $context;
		$this->path = $path;
	}

	private function getMigrationFiles()
	{
		$output = Finder::findFiles('*.sql')
			->in($this->path)
			->exclude('.*');
		$output = iterator_to_array($output);
		usort($output, function (\SplFileInfo $first, \SplFileInfo $second) {
			return strcmp($first->getBasename(), $second->getBasename());
		});
		return $output;
	}

	private function ensureMigrationTable()
	{
		$rows = $this->context->query('SELECT relname AS a FROM pg_class WHERE relname = ?', self::MIGRATION_TABLE_NAME);
		if ($rows->getRowCount() == 0) {
			$this->context->query('
				CREATE TABLE ? (file VARCHAR(255) NOT NULL, UNIQUE(file))
			', new SqlLiteral(self::MIGRATION_TABLE_NAME));
		}
	}

	private function wasProcessed($file)
	{
		return $this->context->query(
			'SELECT TRUE FROM ? WHERE file = ?',
			new SqlLiteral(self::MIGRATION_TABLE_NAME),
			$file
		)->fetch();
	}

	private function getNotMigratedFiles()
	{
		$files = $this->getMigrationFiles();
		$notProcessed = [];
		foreach ($files as $file) {
			if (!$this->wasProcessed(basename($file))) {
				$notProcessed[] = $file;
			}
		}
		return $notProcessed;
	}

	private function getBaseNames($files)
	{
		return array_map(function($value) { return basename($value); }, $files);
	}

	public function migrate()
	{
		echo "Starting Database Migrations\n";
		echo "----------------------------\n";
		$this->ensureMigrationTable();
		$files = $this->getNotMigratedFiles();
		if (count($files) == 0) {
			echo "No outgoing migrations found.\n";
			exit();
		}
		echo "These migrations will be performed:\n";
		echo implode("\n", $this->getBaseNames($files)) . "\n";

		try {
			$this->context->getConnection()->getPdo()->beginTransaction();
			foreach ($files as $file) {
				$content = file_get_contents($file);
				$content .= " INSERT INTO " . self::MIGRATION_TABLE_NAME . " (file) VALUES ('" . basename($file) . "');";
				$this->context->getConnection()->getPdo()->exec($content);
			}
			$this->context->commit();
		} catch (PDOException $ex) {
			echo "MIGRATION FAILS\n";
			echo "Error message: " . $ex->getMessage() . "\n";
			$this->context->rollBack();
		} catch (Exception $ex) {
			echo "MIGRATION FAILS\n";
			$this->context->rollBack();
		}
	}
}