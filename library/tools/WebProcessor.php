<?php
namespace NatureQuizzer\Tools;

use Exception;

/**
 * Class for parsing out parts of web page specified by URL.
 */
class WebProcessor
{
	use CurlDownloader;
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
		$content = $this->fetchByCurl($this->url);
		$output = call_user_func($this->parser, $content);
		return $output;
	}
}