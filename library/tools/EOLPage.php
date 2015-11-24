<?php
namespace NatureQuizzer\Tools;

use Nette\Utils\Json;

/**
 * Class for obtaining information about organisms from http://eol.org API.
 * Warning: for now it supports fetching only subset of information.
 *
 * @see http://eol.org/api/docs/pages/1.0
 */
class EOLPage extends EOLAPI
{
	const VETTED_ONLY = 1;
	const VETTED_UNREVIEWED = 2;

	const API_URL = 'http://eol.org/api/pages/1.0/__ID__.json?images=__IMAGES__&videos=0&sounds=0&maps=0&text=&iucn=false&subjects=overview&licenses=cc-by%2Ccc-by-nc%2Ccc-by-sa+cc-by-nc-sa%2Cpd&details=__DETAILS__&common_names=true&synonyms=false&references=false&vetted=__VETTED__&cache_ttl=';

	private $images;
	private $details;
	private $vetted;

	public function __construct($images = 0, $details = FALSE, $vetted = self::VETTED_ONLY, $key = NULL)
	{
		parent::__construct($key);
		$this->images = $images;
		$this->details = $details;
	}


	private function prepareUrl($id)
	{
		$temp = str_replace('__ID__', urlencode($id), self::API_URL);
		$temp = str_replace('__IMAGES__', urlencode($this->images), $temp);
		$temp = str_replace('__DETAILS__', urlencode($this->details), $temp);
		$temp = str_replace('__VETTED__', urlencode($this->vetted), $temp);
		return $temp;
	}

	public function getData($id)
	{
		$result = $this->fetch($this->prepareUrl($id));

		$parsed = Json::decode($result);
		return $parsed;
	}
}