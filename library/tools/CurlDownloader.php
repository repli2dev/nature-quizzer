<?php
namespace NatureQuizzer\Tools;

use Exception;
use function curl_getinfo;
use const CURLINFO_HTTP_CODE;

/**
 * Encapsulation of downloading something using CURL library
 */
trait CurlDownloader
{
	protected function fetchByCurl($url)
	{
		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_URL => $url,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_RETURNTRANSFER => true
		]);
		$result = curl_exec($curl);
		if ($result === false || curl_getinfo($curl, CURLINFO_HTTP_CODE) != 200) {
			curl_close($curl);
			throw new Exception('Fetching of URL have failed.');
		}
		curl_close($curl);
		return $result;
	}
}
