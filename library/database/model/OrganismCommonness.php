<?php

namespace NatureQuizzer\Database\Model;


use Nette\InvalidArgumentException;
use Nette\Utils\Arrays;
use Nette\Utils\DateTime;

class OrganismCommonness extends Table
{

	public function getMaximum()
	{
		$output = $this->getConnection()->query('
			SELECT MAX(value) FROM organism_commonness
		')->fetchField();
		if ($output === FALSE || $output == NULL) {
			return 1; // fallback for the case where there are no data
		} else {
			return $output;
		}
	}

	public function setValue($organismId, $value)
	{
		$row = $this->getTable()->where('id_organism = ?', $organismId)->fetch();
		if ($row === FALSE) {
			$this->getTable()->insert(['id_organism' => $organismId, 'value' => (int) $value]);
		} else {
			$this->getTable()->where('id_organism = ?', $organismId)->update(['value' => $value]);
		}
	}
}
