<?php
namespace NatureQuizzer\Model;

use NatureQuizzer\Model\IValueEntry;
use Nette\SmartObject;

/**
 * "POJO" which encapsulate prior knowledge value of user.
 * @package NatureQuizzer\Model
 */
class PriorKnowledgeEntry implements IValueEntry
{
	use SmartObject;

	private $user;
	private $value;

	public function __construct($user, $value)
	{
		$this->user = $user;
		$this->value = $value;
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