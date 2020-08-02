<?php

namespace NatureQuizzer\Logging;

use Nette\Application\IRouter;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;
use Nette\Caching\Storages\FileStorage;
use Nette\SmartObject;

class FileLogger
{
	use SmartObject;

	private static $instance;
	private $handle;

	private function __construct($dir, $name)
	{
		$filename = $dir . '/' . $name;
		$this->handle = fopen($filename, 'a');
	}

	public static function getInstance($dir, $name)
	{
		if (!self::$instance) {
			self::$instance = new self($dir, $name);
		}
		return self::$instance;
	}

	public function log($output) {
		fwrite($this->handle, $output);
		fflush($this->handle);
	}

	public function __destruct()
	{
		fclose($this->handle);
	}

}
