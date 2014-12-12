<?php

namespace NatureQuizzer\Database\Model;

use Closure;
use Nette\Database\Context;
use Nette\Database\Table\Selection;
use Nette\Object;
use PDOException;

abstract class Table extends Object
{

	protected $tableName; // Can override name based on class name

	/** @var Context */
	protected $context;

	public function __construct(Context $context)
	{
		$this->context = $context;
	}

	public function insert($data)
	{
		return $this->getTable()->insert($data);
	}

	public function delete($key)
	{
		return $this->getTable()->wherePrimary($key)->delete();
	}

	public function update($key, $data)
	{
		return $this->getTable()->wherePrimary($key)->update($data);
	}

	public function get($key)
	{
		return $this->getTable()->wherePrimary($key)->fetch();
	}

	public function getAll()
	{
		return $this->getTable()->fetchAll();
	}

	protected function getName()
	{
		if (!empty($this->tableName)) {
			return $this->tableName;
		}
		$class = new \ReflectionClass($this);
		$ns = $class->getNamespaceName();
		$name = $class->getName();
		$name = substr($name, strlen($ns) + 1, strlen($name));
		$this->tableName = $this->fromCamelCase($name);
		return $this->tableName;
	}

	/**
	 * Returns table selection
	 * @return Selection
	 */
	protected function getTable()
	{
		return $this->context->table($this->getName());
	}

	protected function getConnection()
	{
		return $this->context->getConnection();
	}

	/**
	 * Returns table_name from TableName
	 * @param $str Input string in camel case
	 * @return mixed Output string in underscore syntax
	 */
	private function fromCamelCase($str)
	{
		$str[0] = strtolower($str[0]);
		$func = create_function('$c', 'return "_" . Nette\Utils\Strings::lower($c[1]);');
		return preg_replace_callback('/([A-Z])/', $func, $str);
	}

	/**
	 * Takes function to be evaluated in the context of this model and in database transaction.
	 * When there is already performing transaction this does nothing.
	 * @param Closure $function
	 */
	protected function performInTransaction(Closure $function)
	{
		$pdo = $this->context->getConnection()->getPdo();
		$inTransaction = $pdo->inTransaction();
		try {
			if (!$inTransaction) $pdo->beginTransaction();
			$bindFunction = $function->bindTo($this);
			$returnValue = $bindFunction();
			if (!$inTransaction) $pdo->commit();
			return $returnValue;
		} catch (PDOException $ex) {
			if (!$inTransaction) $pdo->rollBack();
			throw $ex;
		}
	}
}