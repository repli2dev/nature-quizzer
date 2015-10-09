<?php
namespace NatureQuizzer\Model;

use NatureQuizzer\Database\Model\Model;
use Nette\InvalidStateException;

abstract class AModelFacade implements IModelFacade
{
	/** @var Model */
	private $model;

	private $modelId;

	public function __construct(Model $model)
	{
		$this->model = $model;
	}

	public function getId()
	{
		if (!$this->modelId) {
			$row = $this->model->getModelByName($this->getName());
			if ($row === FALSE) {
				throw new InvalidStateException('Cannot find model with name: [' . $this->getName() . ']');
			}
			$this->modelId = $row->id_model;
		}
		return $this->modelId;
	}
}