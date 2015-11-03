<?php

namespace NatureQuizzer\Utils;

use Exception;
use NatureQuizzer\Utils\CLI\ExecutionProblem;
use NatureQuizzer\Utils\CLI\InvalidArguments;
use Nette\InvalidStateException;
use Nette\Object;
use Nette\Utils\Strings;
use Tracy\Debugger;

class CLI extends Object
{
	private $argv;

	private $name;

	private $commands = [];
	private $callbacks = [];

	public function __construct($argv)
	{
		if (PHP_SAPI !== 'cli') {
			throw new InvalidStateException('Cannot execute in non-cli mode.');
		}
		if (count($argv) == 0) {
			throw new InvalidStateException('Invalid array of arguments.');
		}
		$this->argv = $argv;

	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function addCommand($name, $args, $description, $callback)
	{
		$this->commands[$name] = [$args, $description];
		$this->callbacks[$name] = $callback;
	}

	public function printHelp()
	{
		printf("%s\n", $this->name);
		printf("%s\n", Strings::padRight('', Strings::length($this->name), '-'));
		printf("Usage:\n");
		foreach ($this->commands as $name => $infos) {
			list($args, $description) = $infos;
			printf("\tphp %s %s %s\t\t%s\n", $this->getFilename(), $name, $args, $description);
		}

	}

	private function getFilename()
	{
		return basename($this->argv[0]);
	}

	public function execute()
	{
		if (!isset($this->argv[1])) {
			$this->printHelp();
			return;
		}
		$command = $this->argv[1];
		foreach ($this->callbacks as $name => $callback) {
			if ($command === $name) {
				$args = array_slice($this->argv, 2);
				if (!$this->checkArgumentsCount($callback, $args)) {
					printf("Error: Invalid argument count.\n");
					$this->printHelp();
					exit(1);
				}
				try {
					$this->timer('execution');
					call_user_func_array($callback, $args);
					$elapsed = $this->timer('execution');
					printf("FINISHED in %s s\n", round($elapsed));
				} catch (InvalidArguments $ex) {
					if ($ex->getMessage()) {
						printf("Error: %s\n", $ex->getMessage());
					}
					exit(1);
				} catch (ExecutionProblem $ex) {
					printf("Error: %s\n", $ex->getMessage());
					exit(1);
				} catch (Exception $ex) {
					printf("Error: %s\n", $ex->getMessage());
					printf("-------------------------------\n");
					printf($ex);
					exit(1);
				}
				return;
			}
		}
		$this->printHelp();
	}

	protected function timer($name)
	{
		return Debugger::timer($name);
	}

	private function checkArgumentsCount($callback, $args)
	{
		$provided = count($args);
		$temp = new \ReflectionFunction($callback);
		return $provided >= $temp->getNumberOfRequiredParameters();
	}
}