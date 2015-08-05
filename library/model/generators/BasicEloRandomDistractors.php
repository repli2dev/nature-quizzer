<?php
namespace NatureQuizzer\Model;

use NatureQuizzer\Database\Model\Organism;
use NatureQuizzer\Database\Model\QuestionType;
use NatureQuizzer\Runtime\CurrentLanguage;
use Nette\Object;
use Nette\Utils\ArrayHash;
use Nette\Utils\Html;

class BasicEloRandomDistractors extends Object implements IQuizGenerator
{
	const ALL_CONCEPTS = 'ALL';

	const A = 10;
	const B = 120;
	const C = 10;

	const TARGET_PROBABILITY = 0.75;

	/** @var Organism */
	private $organism;
	/** @var CurrentLanguage */
	private $currentLanguage;

	public function __construct(Organism $organism, CurrentLanguage $currentLanguage)
	{
		$this->organism = $organism;
		$this->currentLanguage = $currentLanguage;
	}

	public function get($userId, $concept, $count)
	{
		$organismIds = $this->selectMainQuestions($userId, $concept, $count);
		$organisms = $this->organism->getRepresentationsWithInfoByOrganisms($this->currentLanguage->get(), $organismIds);
		$otherOptions = $this->organism->getRepresentationsWithInfo($this->currentLanguage->get());

		$questions = [];
		foreach ($organisms as $organismId => $organismRepresentations) {
			$organism = ArrayHash::from($organismRepresentations[rand(0, count($organismRepresentations) - 1)]);
			$questionType = (rand(0, 1) == 1) ? QuestionType::CHOOSE_NAME : QuestionType::CHOOSE_REPRESENTATION;
			if ($questionType === QuestionType::CHOOSE_NAME) {
				$questions[] = $this->prepareChooseNameQuestion($organism, $otherOptions);
			} elseif ($questionType === QuestionType::CHOOSE_REPRESENTATION) {
				$questions[] = $this->prepareChooseRepresentationQuestion($organism, $otherOptions);
			}
		}
		return $questions;
	}

	private function prepareChooseNameQuestion($organism, $otherOptions)
	{
		$question = [
			'type' => QuestionType::CHOOSE_NAME,
			'questionImage' => $this->getRepresentationImage($organism->id_representation)
		];
		$options = [];
		$options[] = ['id_organism' => $organism->id_organism, 'text' => $organism->name, 'correct' => TRUE];
		for ($i = 0; $i < 10; $i++) {
			// Select other (confusing) organisms
			$otherOrganism = array_rand($otherOptions);
			$otherOrganism = $otherOptions[$otherOrganism];
			// We are interested only in the name, so we can ignore other representation and take the first one.
			$item = ArrayHash::from($otherOrganism[0]);
			if ($item->id_organism == $organism->id_organism) continue;
			if (count($options) == 4) break;
			$options[] = ['id_organism' => $item->id_organism, 'text' => $item->name, 'correct' => FALSE];
		}
		shuffle($options);
		$question['options'] = $options;
		return $question;
	}

	private function prepareChooseRepresentationQuestion($organism, $otherOptions)
	{
		$question = [
			'type' => QuestionType::CHOOSE_REPRESENTATION,
			'questionText' => $organism->name
		];
		$options = [];
		$options[] = [
			'id_representation' => $organism->id_representation,
			'image' => $this->getRepresentationImage($organism->id_representation),
			'correct' => TRUE
		];
		for ($i = 0; $i < 10; $i++) {
			// Select other (confusing) organisms
			$otherOrganism = array_rand($otherOptions);
			$otherOrganism = $otherOptions[$otherOrganism];
			// Select random representation
			$item = rand(0, count($otherOrganism) - 1);
			$item = ArrayHash::from($otherOrganism[$item]);
			if ($item->id_organism == $organism->id_organism) continue;
			if (count($options) == 4) break;
			$options[] = [
				'id_representation' => $item->id_representation,
				'image' => $this->getRepresentationImage($item->id_representation),
				'correct' => FALSE
			];
		}
		shuffle($options);
		$question['options'] = $options;
		return $question;
	}

	private function getRepresentationImage($representationId)
	{
		return Html::el('img')->src('/images/organisms/' . $representationId)->render();
	}

	private function selectMainQuestions($userId, $concept, $count)
	{
		$conceptId = NULL;
		if ($concept !== self::ALL_CONCEPTS) {
			$conceptId = $concept->id_concept;
		}
		$data = $this->organism->getSelectionAttributes($userId, $conceptId)->fetchAll();
		$scores = [];
		foreach ($data as $row) {
			$eloScore = ($row->current_knowledge !== NULL) ? $row->current_knowledge : $row->prior_knowledge;
			$score = self::A * $this->scoreProbability($this->probabilityEstimated($eloScore), self::TARGET_PROBABILITY);
			$score += self::B * $this->scoreTime($row->last_answer);
			$score += self::C * $this->scoreCount($row->total_answered);
			$scores[$row->id_organism] = $score;
		}
		arsort($scores);
		$organisms = array_keys(array_slice($scores, 0, $count, true));
		return $organisms;
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