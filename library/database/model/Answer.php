<?php

namespace NatureQuizzer\Database\Model;

class Answer extends Table
{
	public function insert($rows)
	{
		return $this->performInTransaction(function () use ($rows) {
			$result = NULL;
			foreach ($rows as $row) {
				$result = $this->getTable()->insert($row);
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

	public function findFromLastRound($userId, $languageId)
	{
		return $this->context->query('
			SELECT
			 answer.id_organism,
			 answer.id_representation,
			 (SELECT bool_and(correct) FROM answer AS distractor WHERE distractor.id_round = answer.id_round AND answer.question_seq_num = distractor.question_seq_num AND distractor.main = FALSE) AS correct,
			 organism_name.name
			FROM answer
			JOIN organism_name ON answer.id_organism = organism_name.id_organism AND organism_name.id_language = ?
			WHERE answer.main = TRUE AND answer.id_round = (SELECT id_round FROM round WHERE id_user = ? ORDER BY id_round DESC LIMIT 1)
			ORDER BY answer.question_seq_num ASC
		', $languageId, $userId)->fetchAll();
	}

	public function getStats()
	{
		return $this->context->query('
			SELECT
				inserted::DATE,
				inserted::DATE::TEXT AS "dummy_date",
				COUNT(*) AS all,
				COUNT(NULLIF(NOT (SELECT bool_and(correct) FROM answer AS distractor WHERE distractor.id_round = answer.id_round AND answer.question_seq_num = distractor.question_seq_num AND distractor.main = FALSE), TRUE)) AS correct
			FROM answer
			WHERE main = TRUE AND inserted > NOW() - INTERVAL \'14 DAY\'
			GROUP BY inserted::DATE
		')->fetchAssoc('dummy_date=');
	}

	public function getOrganismDistribution()
	{
		return $this->context->query('
			SELECT
				answer.id_organism,
				organism.latin_name,
				COUNT(*) AS count
			FROM answer
			JOIN organism ON organism.id_organism = answer.id_organism
			WHERE main = TRUE AND answer.inserted > NOW() - INTERVAL \'14 DAY\'
			GROUP BY answer.id_organism, latin_name
			ORDER BY count DESC
		')->fetchAll();
	}
}
