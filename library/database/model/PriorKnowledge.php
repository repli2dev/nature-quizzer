<?php

namespace NatureQuizzer\Database\Model;


use NatureQuizzer\Model\PriorKnowledgeEntry;

class PriorKnowledge extends Table
{
	public function fetch($model, $user)
	{
		$data = $this->getTable()->where('id_model = ? AND id_user = ?', $model, $user)->fetch();
		if ($data === NULL) {
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
		if ($this->getTable()->where('id_model = ? AND id_user = ?', $model, $entry->getUser())->fetch() !== NULL) {
			$this->getTable()->where('id_model = ? AND id_user = ?', $model, $entry->getUser())->update($data);
		} else {
			$this->getTable()->insert(array_merge($data, ['id_model' => $model]));
		}
	}

	public function reown($oldUserId, $newUserId)
	{
		$item = $this->context->query('SELECT * FROM prior_knowledge WHERE id_user = ?', $oldUserId)->fetch();
		$temp = NULL;
		if ($item !== NULL) {
			$temp = $this->context->query('SELECT TRUE FROM prior_knowledge WHERE id_user = ? AND id_model = ?', $newUserId, $item->id_model)->fetchField();
		}
		if ($temp === NULL) {
			$this->context->query('UPDATE prior_knowledge SET id_user = ? WHERE id_user = ?', $newUserId, $oldUserId);
		}
	}
}
