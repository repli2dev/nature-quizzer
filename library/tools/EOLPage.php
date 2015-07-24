<?php
namespace NatureQuizzer\Tools;

use Exception;
use Nette\Utils\Json;

class EOLPage extends EOLAPI
{
	const API_URL = 'http://eol.org/api/pages/1.0/__ID__.json?images=6&videos=0&sounds=0&maps=0&text=&iucn=false&subjects=overview&licenses=cc-by%2Ccc-by-nc%2Ccc-by-sa+cc-by-nc-sa%2Cpd&details=true&common_names=true&synonyms=false&references=false&vetted=1&cache_ttl=';


	private function prepareUrl($id)
	{
		return str_replace('__ID__', urlencode($id), self::API_URL);
	}

	public function getData($id)
	{
		$result = $this->fetch($this->prepareUrl($id));

		$parsed = Json::decode($result);
		return $parsed;
	}
}