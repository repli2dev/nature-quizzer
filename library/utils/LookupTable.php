<?php
namespace NatureQuizzer\Utils;


class LookupTable
{
	private $storage;

	public function get($key)
	{
		if (isset($this->storage[$key])) {
			return $this->storage[$key];
		}
		return NULL;
	}

	public function store($key, $value)
	{
		$this->storage[$key] = $value;
	}

	public function getAll()
	{
		return $this->storage;
	}

}