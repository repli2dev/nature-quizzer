<?php

namespace NatureQuizzer\Database\Model;


class Language extends Table
{

	public function getLanguagePairs()
	{
		return $this->getTable()->fetchPairs('id_language', 'name');
	}
}
