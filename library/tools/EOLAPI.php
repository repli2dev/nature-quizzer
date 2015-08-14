<?php
namespace NatureQuizzer\Tools;

use Exception;
use Nette\Utils\Json;

class EOLAPI
{
	use CurlDownloader;

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
		return $this->fetchByCurl($this->appendKey($url));
	}
}