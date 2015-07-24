<?php
namespace NatureQuizzer\Tools;

use Exception;
use Nette\Utils\Json;

class EOLTraits
{
	const API_URL = 'http://eol.org/api/traits/__ID__';


	private function prepareUrl($id)
	{
		return str_replace('__ID__', urlencode($id), self::API_URL);
	}

	public function findId($query)
	{
		$result = $this->fetch($this->prepareUrl($query));

		$parsed = Json::decode($result);
		if ($parsed && count($parsed->results) > 0) {
			return $parsed->results[0]->id;
		}
		return NULL;
	}
}