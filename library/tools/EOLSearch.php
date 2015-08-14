<?php
namespace NatureQuizzer\Tools;

use Exception;
use Nette\Utils\Json;

/**
 * Class for search organisms through http://eol.org API.
 * For given query (ideally canonized latin name) returns EOL ID.
 *
 * @see http://eol.org/api/docs/search/1.0
 */
class EOLSearch extends EOLAPI
{
	const API_URL = 'http://eol.org/api/search/1.0.json?q=__QUERY__&page=1&exact=true';


	private function prepareUrl($query)
	{
		return str_replace('__QUERY__', urlencode($query), self::API_URL);
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