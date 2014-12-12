<?php
namespace NatureQuizzer\Tools;

use Exception;

class WebProcessor
{
	private $url;

	/** @var callable */
	private $parser;

	public function __construct($url, $parser = null)
	{
		if ($parser) {
			$this->parser = $parser;
		} else {
			$this->parser = function ($in) {
				return $in;
			};
		}
		$this->url = $url;
	}

	public function setParser($function)
	{
		$this->parser = $function;
	}

	public function getOutput()
	{
		$content = $this->fetchContent();
		$output = call_user_func($this->parser, $content);
		return $output;
	}

	private function fetchContent()
	{
		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_URL => $this->url,
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