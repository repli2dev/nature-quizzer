<?php
namespace NatureQuizzer\Model;

use NatureQuizzer\Model\Utils\BasicElo;

class EloTaxonomyDistractors extends BasicElo implements IModelFacade
{

	public function getName()
	{
		return 'ELO_TAXONOMY_DISTRACTORS';
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
		$modelId = $this->getId();
		$priorK = $this->priorKnowledge->fetch($modelId, $userId);

		$output = [];
		foreach ($organismIds as $seqId => $organismId) {
			// TODO: performance optimalization

			$currentK = $this->currentKnowledge->fetch($modelId, $organismId, $userId);
			$eloScore = ($currentK->getValue() !== NULL) ? $currentK->getValue() : $priorK->getValue();
			$distance = $this->distance($this->probabilityEstimated($eloScore));

			//fdump('eloScore: ', $eloScore, 'pst:', $this->probabilityEstimated($eloScore), 'distance:', $distance);

			$output[$seqId] = $this->organism->findInDistance($organismId, $distance, $distractorCount);
		}
		return $output;
	}

	// Helpers methods
	private function probabilityEstimated($score)
	{
		return 1 / (1 + pow(M_E, -$score));
	}

	private function distance($probability)
	{
		$distance = -23 * $probability + 25;
		return round($distance);
	}
}