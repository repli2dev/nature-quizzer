<?php
namespace NatureQuizzer\Model;

use NatureQuizzer\Model\IValueEntry;
use Nette\SmartObject;

/**
 * "POJO" which encapsulate difficulty of organism.
 * @package NatureQuizzer\Model
 */
class OrganismDifficultyEntry implements IValueEntry
{
	use SmartObject;

	private $organism;
	private $value;

	public function __construct($organism, $value)
	{
		$this->organism = $organism;
		$this->value = $value;
	}

	public function getOrganism()
	{
		return $this->organism;
	}

	public function getValue()
	{
		return $this->value;
	}

	public function setValue($value)
	{
		return $this->value = $value;
	}

} 