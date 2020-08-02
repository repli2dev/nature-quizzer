<?php
namespace NatureQuizzer\Model;

use NatureQuizzer\Model\IValueEntry;
use Nette\SmartObject;

/**
 * "POJO" which encapsulate current knowledge value of user of organism.
 * @package NatureQuizzer\Model
 */
class CurrentKnowledgeEntry implements IValueEntry
{
	use SmartObject;

	private $organism;
	private $value;
	private $user;

	public function __construct($organism, $user, $value)
	{
		$this->organism = $organism;
		$this->value = $value;
		$this->user = $user;
	}

	public function getOrganism()
	{
		return $this->organism;
	}

	public function getUser()
	{
		return $this->user;
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