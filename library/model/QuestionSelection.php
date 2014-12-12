<?php
namespace NatureQuizzer\Model;

use NatureQuizzer\Database\Model\Organism;
use Nette\Object;

class QuestionSelection extends Object
{
	const ALL_CONCEPTS = 'ALL';

	/** @var Organism */
	private $organism;

	public function __construct(Organism $organism)
	{
		$this->organism = $organism;
	}

	public function fetch($userId, $concept)
	{
		$this->prepareData($userId);
	}

	private function prepareData($userId)
	{
		$data = $this->organism->getSelectionAttributes($userId)->fetchAll();
		fdump($data);
		$scores = [];
		// TODO: refactor out
		$a = 10; $b = 120; $c = 10;
		$pTarget = 0.75;
		foreach ($data as $row) {
			$eloScore = ($row->current_knowledge !== NULL) ? $row->current_knowledge : $row->prior_knowledge;
			$score = $a * $this->scoreProbability($this->probabilityEstimated($eloScore), $pTarget);
			$time = 0;
			$score += $b * $this->scoreTime($row->last_answer);
			$score += $c * $this->scoreCount($row->total_answered);
			$scores[$row->id_organism] = $score;
		}
		arsort($scores);
		fdump($scores);
	}

	private function probabilityEstimated($score)
	{
		return 1 / (1 + pow(M_E, -$score));
	}

	private function scoreProbability($pEst, $pTarget)
	{
		if ($pTarget >= $pEst) {
			return $pEst / $pTarget;
		} else {
			return (1 - $pEst) / (1 - $pTarget);
		}
	}

	private function scoreTime($lastAnswerTime)
	{
		if ($lastAnswerTime === NULL) {
			return 0;
		}
		$time = time() - $lastAnswerTime->getTimestamp();
		if ($time == 0) {
			$time = 0.0001;
		}
		return -1 / $time;
	}

	private function scoreCount($count)
	{
		return 1 / sqrt(1 + $count);
	}
} 