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

		$this->weightCorrectAnswer = 3.4;
		$this->weightInvalidAnswer = 0.3;

		$this->eloUpdateFactorA = 0.8;
		$this->eloUpdateFactorB = 0.05;
	}

	public function getPersistenceId()
	{
		return $this->getPersistenceIdForName('ELO_RANDOM_DISTRACTORS');
	}

	protected function selectDistractors($userId, $organismIds, $distractorCount)
	{
		$modelId = $this->getPersistenceId();
		$priorK = $this->priorKnowledge->fetch($modelId, $userId);

		$output = [];
		foreach ($organismIds as $seqId => $organismId) {
			// TODO: performance optimalization

			$currentK = $this->currentKnowledge->fetch($modelId, $organismId, $userId);
			$organismD = $this->organismDifficulty->fetch($modelId, $organismId);
			$eloScore = ($currentK->getValue() !== NULL) ? $currentK->getValue() : $priorK->getValue() - $organismD->getValue();
			$distance = $this->distance($this->probabilityEstimated($eloScore));

			//fdump('eloScore: ', $eloScore, 'pst:', $this->probabilityEstimated($eloScore), 'distance:', $distance);

			$output[$seqId] = $this->organism->findInDistance($organismId, $distance, $distractorCount);
			//fdump($output[$seqId]);
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
		//$distance = -23 * $probability + 25;
		$distance = 20 / ( $probability + 0.57 ) - 10.5;
		return round($distance);
	}
}