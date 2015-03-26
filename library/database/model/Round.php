<?php

namespace NatureQuizzer\Database\Model;

class Round extends Table
{
	public function reownRounds($oldUserId, $newUserId)
	{
		$this->context->query('UPDATE round SET id_user = ? WHERE id_user = ?', $newUserId, $oldUserId);
	}
}
