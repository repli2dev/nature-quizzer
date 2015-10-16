<?php

namespace NatureQuizzer\Database\Model;

class Round extends Table
{

	public function getStats()
	{
		return $this->context->query('
			SELECT
				inserted::DATE AS "date",
				inserted::DATE::TEXT AS "dummy_date",
				COUNT(*) AS count
			FROM round
			WHERE inserted > NOW() - INTERVAL \'14 DAY\'
			GROUP BY inserted::DATE
			ORDER BY date ASC
		')->fetchAssoc('dummy_date=');
	}

	public function getUserStats()
	{
		return $this->context->query('
			SELECT
				inserted::DATE AS "date",
				inserted::DATE::TEXT AS "dummy_date",
				COUNT(DISTINCT id_user) AS count
			FROM round
			WHERE inserted > NOW() - INTERVAL \'14 DAY\'
			GROUP BY inserted::DATE
			ORDER BY date ASC
		')->fetchAssoc('dummy_date=');
	}

	public function reownRounds($oldUserId, $newUserId)
	{
		$this->context->query('UPDATE round SET id_user = ? WHERE id_user = ?', $newUserId, $oldUserId);
	}
}
