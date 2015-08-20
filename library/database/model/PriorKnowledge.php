<?php

namespace NatureQuizzer\Database\Model;


use NatureQuizzer\Model\PriorKnowledgeEntry;

class PriorKnowledge extends Table
{
	public function fetch($model, $user)
	{
		$data = $this->getTable()->where('id_model = ? AND id_user = ?', $model, $user)->fetch();
		if ($data === FALSE) {
			return new PriorKnowledgeEntry($user, null);
		}
		return new PriorKnowledgeEntry($data->id_user, $data->value);
	}

	public function persist($model, PriorKnowledgeEntry $entry)
	{
		$data = [
			'value' => $entry->getValue(),
			'id_user' => $entry->getUser()
		];
		if ($this->getTable()->where('id_model = ? AND id_user = ?', $model, $entry->getUser())->fetch() !== FALSE) {
			$this->getTable()->where('id_model = ? AND id_user = ?', $model, $entry->getUser())->update($data);
		} else {
			$this->getTable()->insert(array_merge($data, ['id_model' => $model]));
		}
	}

	public function reown($oldUserId, $newUserId)
	{
		$this->context->query('UPDATE prior_knowledge SET id_user = ? WHERE id_user = ?', $newUserId, $oldUserId);
	}
}
