<?php

namespace NatureQuizzer\Database\Model;

use NatureQuizzer\Model\CurrentKnowledgeEntry;

class CurrentKnowledge extends Table
{
	public function fetch($model, $organism, $user)
	{
		$data = $this->getTable()->where('id_model = ? AND id_organism = ? AND id_user = ?', $model, $organism, $user)->fetch();
		if ($data === FALSE) {
			return new CurrentKnowledgeEntry($organism, $user, null);
		}
		return new CurrentKnowledgeEntry($data->id_organism, $data->id_user, $data->value);
	}

	public function persist($model, CurrentKnowledgeEntry $entry)
	{
		$data = [
			'value' => $entry->getValue(),
			'id_user' => $entry->getUser(),
			'id_organism' => $entry->getOrganism()
		];

		if ($this->getTable()->where('id_model = ? AND id_organism = ? AND id_user = ?', $model, $entry->getOrganism(), $entry->getUser())->fetch() !== FALSE) {
			$this->getTable()->where('id_model = ? AND id_organism = ? AND id_user = ?', $model, $entry->getOrganism(), $entry->getUser())->update($data);
		}else {
			$this->getTable()->insert(array_merge($data, ['id_model' => $model]));
		}
	}

	public function reown($oldUserId, $newUserId)
	{
		$this->context->query('UPDATE current_knowledge SET id_user = ? WHERE id_user = ?', $newUserId, $oldUserId);
	}
}
