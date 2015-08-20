<?php

namespace NatureQuizzer\Model;


use NatureQuizzer\Database\Model\Model;
use Nette\InvalidStateException;

class ModelFacadeFactory
{
	/** @var Model */
	private $model;

	private $modelFacades = [];

	public function __construct(Model $setting)
	{
		$this->model = $setting;
	}

	public function get($userId)
	{
		$temp = $this->model->getModelNameByUser($userId);
		return $this->getModel($temp->name);
	}

	public function register(AModelFacade $modelFacade)
	{
		$name = $modelFacade->getName();
		if (isset($this->modelFacades[$name])) {
			throw new InvalidStateException('Model Facade named [' . $name .'] is already registered.');
		}
		$this->modelFacades[$name] = $modelFacade;
	}
	public function getModel($modelName)
	{
		if (!isset($this->modelFacades[$modelName])) {
			throw new InvalidStateException('Model Facade named [' . $modelName .'] is not registered.');
		}
		return $this->modelFacades[$modelName];
	}
}