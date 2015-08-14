<?php

namespace NatureQuizzer\Database\Model;


class Language extends Table
{

	public function findByCode($code)
	{
		return $this->getTable()->where('code = ?', $code)->fetch();
	}

	public function getLanguagePairs()
	{
		return $this->getTable()->fetchPairs('id_language', 'name');
	}

	public function getLanguageCodes()
	{
		return $this->getTable()->fetchPairs('id_language', 'code');
	}
}
