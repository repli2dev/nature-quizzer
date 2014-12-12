<?php

namespace NatureQuizzer\Database\Utils;

use Exception;
use Nette\Database\Context;
use Nette\DI\Container;

class ImportTransaction
{
	/** @var Container */
	private $container;

	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	public function perform($function)
	{
		/** @var Context $databaseContext */
		$databaseContext = $this->container->getByType('Nette\\Database\\Context');
		$databaseContext->beginTransaction();
		try {
			call_user_func($function, $this->container);
			$databaseContext->commit();
			echo "\n\nIMPORT DONE\n";
		} catch (Exception $ex) {
			echo "\n\nIMPORT FAILED\n";
			echo "Error message: " . $ex->getMessage() . "\n";
			$databaseContext->rollBack();
			throw $ex;
		}
	}
} 