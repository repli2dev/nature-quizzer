<?php

namespace NatureQuizzer\Database\Model;


use NatureQuizzer\Model\OrganismDifficultyEntry;

class OrganismDifficulty extends Table
{
	public function fetch($model, $organism)
	{
		$data = $this->getTable()->where('id_model = ? AND id_organism = ?', $model, $organism)->fetch();
		if ($data === NULL) {
			return new OrganismDifficultyEntry($organism, null);
		}
		return new OrganismDifficultyEntry($data->id_organism, $data->value);
	}

	public function persist($model, OrganismDifficultyEntry $entry)
	{
		$data = [
			'value' => $entry->getValue(),
			'id_organism' => $entry->getOrganism()
		];
		if ($this->getTable()->where('id_model = ? AND id_organism = ?', $model, $entry->getOrganism())->fetch() !== NULL) {
			$this->getTable()->where('id_model = ? AND id_organism = ?', $model, $entry->getOrganism())->update($data);
		} else {
			$this->getTable()->insert(array_merge($data, ['id_model' => $model]));
		}

	}
}
