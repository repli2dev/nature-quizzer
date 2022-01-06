<?php
namespace NatureQuizzer\Tools;


class BioLibNames
{
	use CurlDownloader;

	const API_URL = "https://www.biolib.cz/cz/formsearch/?action=execute&searcharea=1&string=__QUERY__";

	private function prepareUrl($query)
	{
		return str_replace('__QUERY__', urlencode($query), self::API_URL);
	}

	public function getData($query)
	{
		$result = $this->fetchByCurl($this->prepareUrl($query));
		if ($result) {
			preg_match('#<h1>.*?<strong><em>(.*?)</em>.*?<\/strong>.*?<\/h1>#su', $result, $matches);
			if (isset($matches[1])) {
				return $matches[1];
			}
		}
		return NULL;
	}
}
