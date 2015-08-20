<?php

namespace NatureQuizzer\Database\Model;

class Model extends Table
{
	public function getAll()
	{
		return $this->getTable()->order('id_model ASC')->fetchAll();
	}

	public function getModelNameByUser($userId)
	{
		return $this->getConnection()->query('SELECT "name" FROM model WHERE id_model IN (SELECT id_model FROM "user" WHERE id_user = ?)', $userId)->fetch();
	}

	public function getModelByName($name)
	{
		return $this->getTable()->where('name = ?', $name)->fetch();
	}

	/**
	 * Function for obtain random model with respect of their weights (ratio in database)
	 * @return int|null ID of model or NULL
	 */
	public function getRandomModel()
	{
		$models = $this->getAll();

		$totalWeight = array_reduce($models, function($carry, $row) { return $carry + $row->ratio; }, 0);
		$random = rand(1, $totalWeight);
		$current = 0;
		foreach ($models as $model) {
			$current += $model->ratio;
			if ($random <= $current) {
				return $model->id_model;
			}
		}
		return NULL;
	}
}
