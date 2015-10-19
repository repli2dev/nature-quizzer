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

	/**
	 * Returns summed count of first answers for given organism.
	 * (That is equal to query of unique users answering question about particular organism.)
	 */
	public function organismFirstAnswersCount($organismId)
	{
		return $this->context->query('SELECT COUNT(DISTINCT id_user) FROM answer JOIN round USING (id_round) WHERE id_organism = ? AND main = TRUE', $organismId)->fetchField();
	}

	/**
	 * Returns summed count of first answers of given user.
	 * (That is equal to query of unique organism ever answered by the user.)
	 */
	public function userFirstAnswerCount($userId)
	{
		return $this->context->query('SELECT COUNT(DISTINCT id_organism) FROM answer JOIN round USING (id_round) WHERE id_user = ? AND main = TRUE', $userId)->fetchField();
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

	public function getOrganismDistribution($languageId, $modelId)
	{
		return $this->context->query('
			SELECT
				answer.id_organism,
				organism.latin_name,
				organism_name.name AS name,
			  	organism_difficulty.value AS difficulty,
				COUNT(*) AS count
			FROM answer
			JOIN organism ON organism.id_organism = answer.id_organism
			JOIN organism_name ON organism_name.id_organism = organism.id_organism AND organism_name.id_language = ?
			LEFT JOIN organism_difficulty ON organism_difficulty.id_organism = organism.id_organism AND organism_difficulty.id_model = ? -- For displayed organism we join estimated difficulty (not much so related to the query)
			WHERE main = TRUE AND answer.inserted > NOW() - INTERVAL \'14 DAY\'
			GROUP BY answer.id_organism, latin_name, name, difficulty
			ORDER BY count DESC
		', $languageId, $modelId)->fetchAll();
	}
}
