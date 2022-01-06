<?php

namespace NatureQuizzer\Tools;

use Exception;

/**
 * Simple downloader of files into specified storage dir
 */
class FileDownloader
{
	use CurlDownloader;

	private $storageDir;

	public function __construct($storageDir)
	{
		$this->storageDir = $storageDir;
	}

	/**
	 * Saves given URL into file with given name
	 * @param string $url URL to fetch
	 * @param string $name string Desired name of file
	 * @return bool
	 */
	public function fetch($url, $name)
	{
		$dir = $this->storageDir;
		if (!is_dir($dir)) {
			return false;
		}
		try {
			$image = $this->fetchByCurl($url);
		} catch (Exception $ex) {
			return false;
		}
		@mkdir($this->storageDir);
		file_put_contents($dir . '/' . $name, $image);
		return true;
	}
}
