<?php

namespace NatureQuizzer\Model\Scoring;

use NatureQuizzer\Model\IValueEntry;
use Nette\Object;

/**
 * Count new ELO score from given:
 *  - parameters
 *  - value wrapper
 *
 */
class ELO extends Object
{

	public $k = 1;
	public $p = 1;

	public function update(IValueEntry $object)
	{
		$old = $object->getValue();
		$new = $old + $this->getK() * $this->getP();
		$object->setValue($new);
	}

	private function getK()
	{
		if ($this->k instanceof \Closure) {
			return $this->k();
		}
		return $this->k;
	}

	private function getP()
	{
		if ($this->p instanceof \Closure) {
			return $this->p();
		}
		return $this->p;
	}
} 