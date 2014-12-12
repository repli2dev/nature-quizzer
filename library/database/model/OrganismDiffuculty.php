<?php

namespace NatureQuizzer\Database\Model;


use NatureQuizzer\Model\OrganismDifficultyEntry;

class OrganismDifficulty extends Table
{
	public function fetch($organism)
	{
		$data = $this->getTable()->where('id_organism = ?', $organism)->fetch();
		if ($data === FALSE) {
			return new OrganismDifficultyEntry($organism, null);
		}
		return new OrganismDifficultyEntry($data->id_organism, $data->value);
	}

	public function persist(OrganismDifficultyEntry $entry)
	{
		$data = [
			'value' => $entry->getValue(),
			'id_organism' => $entry->getOrganism()
		];
		if ($this->getTable()->where('id_organism = ?', $entry->getOrganism())->fetch() !== FALSE) {
			$this->getTable()->where('id_organism = ?', $entry->getOrganism())->update($data);
		} else {
			$this->getTable()->insert($data);
		}

	}
}
