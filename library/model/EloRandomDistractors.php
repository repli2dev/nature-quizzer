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

	protected function selectDistractors($userId, $organismIds, $distractorCount)
	{
		$desiredCount = count($organismIds) * $distractorCount;
		$organisms = $this->organism->findRandom($desiredCount);

		$output = [];
		foreach ($organismIds as $seqId => $organismId) {
			// Some harakiri due to need to exclude organism itself.
			$distractors = array_slice(array_filter(array_slice($organisms, 0, $distractorCount+1), function ($item) use ($organismId) { return $item != $organismId; }), 0, $distractorCount);
			$output[$seqId] = $distractors;
			shuffle($organisms);
		}
		return $output;
	}
}