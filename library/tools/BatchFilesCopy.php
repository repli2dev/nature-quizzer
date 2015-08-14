<?php
namespace NatureQuizzer\Tools;

/**
 * Simple tool for batch copying given files in one transaction (such as at the end of database transaction).
 */
class BatchFilesCopy
{
	private $batch = [];

	public function add($source, $destination)
	{
		$this->batch[$source] = $destination;
	}

	public function  execute()
	{
		foreach ($this->batch as $source => $destination) {
			copy($source, $destination);
		}
		$this->batch = [];
	}
}