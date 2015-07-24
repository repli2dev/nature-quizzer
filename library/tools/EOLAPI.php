<?php
namespace NatureQuizzer\Tools;

use Exception;
use Nette\Utils\Json;

class EOLAPI
{
	private $key;

	public function __construct($key = NULL)
	{
		$this->key = $key;
	}


	private function appendKey($url)
	{
		if (!$this->key) {
			return $url;
		}
		return $url . '&key=' . $this->key;
	}

	protected function fetch($url)
	{
		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_URL => $this->appendKey($url),
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_RETURNTRANSFER => true
		]);
		$result = curl_exec($curl);
		if ($result === false || $curl['http_code'] != 201) {
			throw new Exception('Fetching URL have failed.');
		}
		curl_close($curl);
		return $result;
	}
}