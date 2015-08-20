<?php
namespace NatureQuizzer\Model;

use NatureQuizzer\Model\Utils\BasicElo;

class EloRandomDistractors extends BasicElo implements IModelFacade
{

	public function getName()
	{
		return 'ELO_RANDOM_DISTRACTORS';
	}

	protected function initParameters()
	{
		$this->targetProbability = 0.75;

		$this->weightProbability = 10;
		$this->weightTime = 120;
		$this->weightCount = 10;

		$this->weightInvalidAnswer = 1;
		$this->weightCorrectAnswer = 1;

		$this->eloUpdateFactorA = 1;
		$this->eloUpdateFactorB = 0.05;

	}
}