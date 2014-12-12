<?php

namespace NatureQuizzer\Database\Model;

use NatureQuizzer\Model\CurrentKnowledgeEntry;

class CurrentKnowledge extends Table
{
	public function fetch($organism, $user)
	{
		$data = $this->getTable()->where('id_organism = ? AND id_user = ?', $organism, $user)->fetch();
		if ($data === FALSE) {
			return new CurrentKnowledgeEntry($organism, $user, null);
		}
		return new CurrentKnowledgeEntry($data->id_organism, $data->id_user, $data->value);
	}

	public function persist(CurrentKnowledgeEntry $entry)
	{
		$data = [
			'value' => $entry->getValue(),
			'id_user' => $entry->getUser(),
			'id_organism' => $entry->getOrganism()
		];

		if ($this->getTable()->where('id_organism = ? AND id_user = ?', $entry->getOrganism(), $entry->getUser())->fetch() !== FALSE) {
			$this->getTable()->where('id_organism = ? AND id_user = ?', $entry->getOrganism(), $entry->getUser())->update($data);
		}else {
			$this->getTable()->insert($data);
		}
	}
}
