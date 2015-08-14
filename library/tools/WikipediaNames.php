<?php
namespace NatureQuizzer\Tools;


use Nette\Utils\Json;

class WikipediaNames
{
	use CurlDownloader;

	const API_URL = "https://cs.wikipedia.org//w/api.php?action=query&titles=__QUERY__&prop=links&continue&format=json";

	private function prepareUrl($query)
	{
		return str_replace('__QUERY__', urlencode($query), self::API_URL);
	}

	public function getData($query, $languageCode)
	{
		$result = $this->fetchByCurl($this->prepareUrl($query));
		$parsed = Json::decode($result);
		if (isset($parsed->query) && isset($parsed->query->pages)) {
			$temp = reset($parsed->query->pages);
			if (isset($temp->links) && count($temp->links) > 0 && isset($temp->links[0]->title)) {
				return $temp->links[0]->title;
			}
		}

		return NULL;
	}
}