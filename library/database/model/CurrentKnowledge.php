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
		$items = $this->context->query('SELECT * FROM current_knowledge WHERE id_user = ?', $oldUserId)->fetchAll();
		foreach ($items as $item) {
			$temp = $this->context->query('SELECT TRUE FROM current_knowledge WHERE id_user = ? AND id_organism = ? AND id_model = ?', $newUserId, $item->id_organism, $item->id_model)->fetch();
			if ($temp === FALSE) {
				$this->context->query('UPDATE current_knowledge SET id_user = ? WHERE id_user = ? AND id_organism = ? AND id_model = ?', $newUserId, $oldUserId, $item->id_organism, $item->id_model);
			}
		}
	}

	public function getEntriesCount($model, $userId, $conceptId = null, $threshold = null)
	{
		$query = $this->getTable()
			->where('id_model = ?', $model)
			->where('id_user = ?', $userId);

		if ($threshold) {
			$query->where('value > ?', (double) $threshold);
		}
		if ($conceptId) {
			$query->where('organism:organism_concept.id_concept = ?', $conceptId);
		}
		return $query->count();
	}
}
