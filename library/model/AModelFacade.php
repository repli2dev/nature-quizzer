<?php
namespace NatureQuizzer\Model;

use NatureQuizzer\Database\Model\Model;
use Nette\InvalidStateException;

abstract class AModelFacade implements IModelFacade
{
	/** @var Model */
	private $model;

	private $modelId;
	private $persistenceModelId;

	public function __construct(Model $model)
	{
		$this->model = $model;
	}

	public function getId()
	{
		if (!$this->modelId) {
			$row = $this->model->getModelByName($this->getName());
			if ($row === NULL) {
				throw new InvalidStateException('Cannot find model with name: [' . $this->getName() . ']');
			}
			$this->modelId = $row->id_model;
		}
		return $this->modelId;
	}

	public function getPersistenceId()
	{
		return $this->getId();
	}

	public function getPersistenceIdForName($name)
	{
		if (!$this->persistenceModelId) {
			$row = $this->model->getModelByName($name);
			if ($row === NULL) {
				throw new InvalidStateException('Cannot find model with name: [' . $name . ']');
			}
			$this->persistenceModelId = $row->id_model;
		}
		return $this->persistenceModelId;
	}
}