<?php

namespace NatureQuizzer\Utils;

use Nette\SmartObject;
use function array_values;
use Exception;
use NatureQuizzer\Utils\CLI\ExecutionProblem;
use NatureQuizzer\Utils\CLI\InvalidArguments;
use Nette\InvalidStateException;
use Nette\Utils\Strings;
use Tracy\Debugger;

class CLI
{
	use SmartObject;

	private $argv;

	private $name;

	private $commands = [];
	private $options = [];
	private $callbacks = [];

	private $usedOptions = [];

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

	public function addOption($name, $description, $value = false)
	{
		$this->options[$name] = [$description, $value];
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
		printf("Options:\n");
		foreach ($this->options as $name => $infos) {
			list($description, $value) = $infos;
			printf("\t%s%s%s\t\t%s\n", Strings::length($name) > 1 ? '--' : '-', $name, ($value ? ' <V>' : ''), $description);
		}

	}

	private function getFilename()
	{
		return basename($this->argv[0]);
	}

	private function extractOptions()
	{
		$this->usedOptions = [];
		foreach ($this->argv as $key => $value) {
			foreach ($this->options as $name => $infos) {
				list($description, $hasValue) = $infos;
				$fullname = (Strings::length($name) > 1 ? '--' : '-') . $name;
				if (Strings::startsWith($value, $fullname)) {
					if ($hasValue && !isset($this->argv[$key + 1])) {
						printf("Error: Invalid option value.\n");
						$this->printHelp();
						exit(1);
					}
					$this->usedOptions[$name] = ($hasValue) ? $this->argv[$key + 1] : true;
					unset($this->argv[$key]);
					unset($this->argv[$key + 1]);
				}
			}
		}
		$this->argv = array_values($this->argv); // reindex
	}

	public function execute()
	{
		if (!isset($this->argv[1])) {
			$this->printHelp();
			return;
		}
		$this->extractOptions();
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

	public function hasOption($name)
	{
		return isset($this->usedOptions[$name]);
	}

	public function getOption($name)
	{
		return !isset($this->usedOptions[$name]) ? null : $this->usedOptions[$name];
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