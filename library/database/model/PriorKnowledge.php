<?php

namespace NatureQuizzer\Database\Model;


use NatureQuizzer\Model\PriorKnowledgeEntry;

class PriorKnowledge extends Table
{
	public function fetch($user)
	{
		$data = $this->getTable()->where('id_user = ?', $user)->fetch();
		if ($data === FALSE) {
			return new PriorKnowledgeEntry($user, null);
		}
		return new PriorKnowledgeEntry($data->id_user, $data->value);
	}

	public function persist(PriorKnowledgeEntry $entry)
	{
		$data = [
			'value' => $entry->getValue(),
			'id_user' => $entry->getUser()
		];
		if ($this->getTable()->where('id_user = ?', $entry->getUser())->fetch() !== FALSE) {
			$this->getTable()->where('id_user = ?', $entry->getUser())->update($data);
		} else {
			$this->getTable()->insert($data);
		}
	}
}
