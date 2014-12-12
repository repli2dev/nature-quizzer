<?php

namespace NatureQuizzer\Tools;

use Exception;
use Nette\Utils\Json;
use Nette\Utils\Strings;

class GoogleImageDownloader
{

	const LIMIT = 6;

	private $storageDir;

	public function __construct($storageDir)
	{
		$this->storageDir = $storageDir;
	}

	public function getUrl($query)
	{
		return 'https://ajax.googleapis.com/ajax/services/search/images?v=1.0&q=' . urlencode($query);
	}

	public function fetch($query)
	{
		$dir = __DIR__ . $this->storageDir . Strings::webalize($query);
		if (is_dir($dir)) {
			return false;
		}
		$url = $this->getUrl($query);
		$response = $this->fetchContent($url);
		$parsed = Json::decode($response);
		$i = 0;
		@mkdir(__DIR__ . $this->storageDir . Strings::webalize($query));
		foreach ($parsed->responseData->results as $item) {
			if ($i > self::LIMIT) break;
			$image = $this->fetchContent($item->unescapedUrl);
			file_put_contents($dir . '/' . $i, $image);
			$i++;
		}
		return true;
	}

	private function fetchContent($url)
	{
		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true
		]);
		$result = curl_exec($curl);
		if ($result === false) {
			throw new Exception('Fetching given page have failed.');
		}
		curl_close($curl);
		return $result;
	}
}