<?php

namespace NatureQuizzer\Database\Model;

class Answer extends Table
{
	public function insert($rows)
	{
		return $this->performInTransaction(function () use ($rows) {
			foreach ($rows as $row) {
				$result =  $this->getTable()->insert($row);
			}
			return $result;
		});
	}

	public function organismFirstAnswersCount($organismId)
	{
		return $this->context->query('SELECT COUNT(id_user) FROM answer JOIN round USING (id_round) WHERE id_organism = ? GROUP BY id_user', $organismId)->fetchField();
	}

	public function userFirstAnswerCount($userId)
	{
		return $this->context->query('SELECT COUNT(id_organism) FROM answer JOIN round USING (id_round) WHERE id_user = ? GROUP BY id_organism', $userId)->fetchField();
	}
}
