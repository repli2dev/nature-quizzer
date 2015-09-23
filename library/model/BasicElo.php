<?php

namespace NatureQuizzer\Model\Utils;

use NatureQuizzer\Database\Model\Answer;
use NatureQuizzer\Database\Model\Concept;
use NatureQuizzer\Database\Model\CurrentKnowledge;
use NatureQuizzer\Database\Model\Model;
use NatureQuizzer\Database\Model\Organism;
use NatureQuizzer\Database\Model\OrganismDifficulty;
use NatureQuizzer\Database\Model\PriorKnowledge;
use NatureQuizzer\Database\Model\QuestionType;
use NatureQuizzer\Model\AModelFacade;
use NatureQuizzer\Model\Scoring\ELO;
use NatureQuizzer\Runtime\CurrentLanguage;
use Nette\Utils\ArrayHash;
use Tracy\Debugger;
use Tracy\ILogger;

abstract class BasicElo extends AModelFacade
{
	/** @var Organism */
	protected $organism;
	/** @var Answer */
	protected $answer;
	/** @var OrganismDifficulty */
	protected $organismDifficulty;
	/** @var PriorKnowledge */
	protected $priorKnowledge;
	/** @var CurrentKnowledge */
	protected $currentKnowledge;
	/** @var CurrentLanguage */
	protected $currentLanguage;

	protected $targetProbability;		// Target probability of round. e.g. 0.75 (75 %)
	protected $weightProbability;
	protected $weightTime;
	protected $weightCount;
	protected $weightInvalidAnswer;		// ELO update impact if the answer was invalid
	protected $weightCorrectAnswer;		// ELO update impact if the answer was correct
	protected $eloUpdateFactorA;		// ELO `K` function parameters for uncertainty function
	protected $eloUpdateFactorB;
	protected $distractorCount = 3;

	public function __construct(Organism $organism, CurrentLanguage $currentLanguage, Answer $answer,
								OrganismDifficulty $organismDifficulty,PriorKnowledge $priorKnowledge,
								CurrentKnowledge $currentKnowledge, Model $model)
	{
		parent::__construct($model);

		$this->organism = $organism;
		$this->currentLanguage = $currentLanguage;
		$this->answer = $answer;
		$this->organismDifficulty = $organismDifficulty;
		$this->priorKnowledge = $priorKnowledge;
		$this->currentKnowledge = $currentKnowledge;

		$this->initParameters();
	}

	/** Inits all needed parameters */
	protected abstract function initParameters();

	public function get($userId, $concept, $count)
	{
		Debugger::timer('a');
		$organismIds = $this->selectMainQuestions($userId, $concept, $count);
		$distractorsIds = $this->selectDistractors($userId, $organismIds, $this->distractorCount);

		$data = $this->fetchData($organismIds, $distractorsIds);

		$questions = [];
		foreach ($organismIds as $seqId => $organismId) {
			if (!isset ($data[$organismId])) {
				continue; // TBD: prober handling
			}
			$questionType = (rand(0, 1) == 1) ? QuestionType::CHOOSE_NAME : QuestionType::CHOOSE_REPRESENTATION;
			$questionDistractors = $distractorsIds[$seqId];
			if ($questionType === QuestionType::CHOOSE_NAME) {
				$questions[] = $this->prepareChooseNameQuestion($data, $organismId, $questionDistractors);
			} elseif ($questionType === QuestionType::CHOOSE_REPRESENTATION) {
				$questions[] = $this->prepareChooseRepresentationQuestion($data, $organismId, $questionDistractors);
			}
		}
		return $questions;
	}

	private function fetchData($organismIds, $distractorsIds)
	{
		$allOrganisms = array_merge($organismIds, call_user_func_array('array_merge', $distractorsIds));
		$result = $this->organism->getRepresentationsWithInfoByOrganisms($this->currentLanguage->get(), $allOrganisms);
		return $result;
	}

	private function getRandomItem($array)
	{
		$index = array_rand($array);
		return $array[$index];
	}

	private function prepareChooseNameQuestion($data, $organismId, $questionDistractors)
	{
		$organism = ArrayHash::from($this->getRandomItem($data[$organismId]));
		$question = [
			'type' => QuestionType::CHOOSE_NAME,
			'id_representation' => $organism->id_representation,
			'questionImage' => $this->getRepresentationImage($organism->id_representation),
			'questionImageRightsHolder' => $organism->rights_holder,
			'questionImageLicense' => $organism->license,
		];
		$options = [];
		$options[] = ['id_organism' => $organism->id_organism, 'text' => $organism->name, 'correct' => TRUE];
		foreach ($questionDistractors as $distractorId) {
			if (!isset($data[$distractorId])) {
				Debugger::log('Missing data for distractor organism: ['.$distractorId.']', ILogger::WARNING);
				continue;
			}
			// We are interested only in the name, so we can ignore other representation and take the first one.
			$otherOrganism = ArrayHash::from($data[$distractorId][0]);
			$options[] = ['id_organism' => $otherOrganism->id_organism, 'text' => $otherOrganism->name, 'correct' => FALSE];
		}
		shuffle($options);
		$question['options'] = $options;
		return $question;
	}

	private function prepareChooseRepresentationQuestion($data, $organismId, $questionDistractors)
	{
		$organism = ArrayHash::from($this->getRandomItem($data[$organismId]));
		$question = [
			'type' => QuestionType::CHOOSE_REPRESENTATION,
			'questionText' => $organism->name
		];
		$options = [];
		$options[] = [
			'id_representation' => $organism->id_representation,
			'image' => $this->getRepresentationImage($organism->id_representation),
			'imageRightsHolder' => $organism->rights_holder,
			'imageLicense' => $organism->license,
			'correct' => TRUE
		];
		foreach ($questionDistractors as $distractorId) {
			if (!isset($data[$distractorId])) {
				Debugger::log('Missing data for distractor organism: ['.$distractorId.']', ILogger::WARNING);
				continue;
			}
			$otherOrganism = ArrayHash::from($this->getRandomItem($data[$distractorId]));
			$options[] = [
				'id_representation' => $otherOrganism->id_representation,
				'image' => $this->getRepresentationImage($otherOrganism->id_representation),
				'imageRightsHolder' => $otherOrganism->rights_holder,
				'imageLicense' => $otherOrganism->license,
				'correct' => FALSE
			];
		}
		shuffle($options);
		$question['options'] = $options;
		return $question;
	}

	/**
	 * Returns array where for each organism there is number of distractor organisms.
	 * @param $userId int ID of user
	 * @param $organismIds array Sequential array of organism IDs (@see selectMainQuestions)
	 * @return array
	 */
	protected abstract function selectDistractors($userId, $organismIds, $distractorCount);

	/**
	 * Returns sequential array with ID organisms to ask.
	 *
	 * @param $userId int ID of user
	 * @param $concept int|string ID of concept or constant Concept::ALL
	 * @param $count int Desired count of questions
	 * @return array
	 */
	protected function selectMainQuestions($userId, $concept, $count)
	{
		$conceptId = NULL;
		if ($concept !== Concept::ALL) {
			$conceptId = $concept->id_concept;
		}
		$data = $this->organism->getSelectionAttributes($userId, $this->getId(), $conceptId)->fetchAll();
		$scores = [];
		foreach ($data as $row) {
			if ($row->representation_count == 0) {
				Debugger::log('Question skipped as no representation for organism: ['.$row->id_organism.']', ILogger::WARNING);
				continue;
			}
			$eloScore = ($row->current_knowledge !== NULL) ? $row->current_knowledge : $row->prior_knowledge;
			$score = $this->weightProbability * $this->scoreProbability($this->probabilityEstimated($eloScore), $this->targetProbability);
			$score += $this->weightTime * $this->scoreTime($row->last_answer);
			$score += $this->weightCount * $this->scoreCount($row->total_answered);
			$scores[$row->id_organism] = $score;
		}
		arsort($scores);
		$organisms = array_keys(array_slice($scores, 0, $count, true));
		return $organisms;
	}

	public function answer($userId, UserAnswer $answer)
	{
		$modelId = $this->getId();
		$organismId = $answer->getMainOrganism();
		$optionsCount = $answer->getOptionsCount();
		$isCorrect = $answer->isCorrect();

		// Load data
		$organismD = $this->organismDifficulty->fetch($modelId, $organismId);
		$priorK = $this->priorKnowledge->fetch($modelId, $userId);
		$currentK = $this->currentKnowledge->fetch($modelId, $organismId, $userId);

		// Update prior knowledge and item difficulty (only when previous answer from this user is present)
		if ($currentK->getValue() === null) {
			// Item difficulty
			$elo = new ELO();
			$elo->k = function () use ($organismId) {
				return ($this->eloUpdateFactorA / (1 + $this->eloUpdateFactorB * $this->answer->organismFirstAnswersCount($organismId)));
			};
			$elo->p = function () use ($organismD, $priorK, $optionsCount, $isCorrect) {
				$val = 1 / $optionsCount + (1 - 1 / $optionsCount) * (1 / (1 + pow(M_E, -($priorK->getValue() - $organismD->getValue()))));
				if ($isCorrect) {
					return 1 - $val;
				} else {
					return $val;
				}
			};
			$elo->update($organismD);

			// Prior knowledge of user
			$elo->k = function () use ($userId) {
				return $this->eloUpdateFactorA / (1 + $this->eloUpdateFactorB * $this->answer->userFirstAnswerCount($userId));
			};
			$elo->update($priorK);

			// Persist data
			$this->organismDifficulty->persist($modelId, $organismD);
			$this->priorKnowledge->persist($modelId, $priorK);
		}

		// Update current knowledge
		$elo = new ELO();
		$elo->k = function () use ($isCorrect) {
			if ($isCorrect) {
				return $this->weightCorrectAnswer;
			} else {
				return $this->weightInvalidAnswer;
			}
		};
		$elo->p = function () use ($optionsCount, $isCorrect, $currentK, $priorK, $organismD) {
			if ($currentK->getValue() === NULL) {
				$v = $priorK->getValue() - $organismD->getValue();
			} else {
				$v = $currentK->getValue();
			}
			$v =
			$val = 1 / $optionsCount + (1 - 1 / $optionsCount) * (
					1 / (1 + pow(M_E, - ($v)))
				);
			if ($isCorrect) {
				return 1 - $val;
			} else {
				return $val;
			}
		};
		$elo->update($currentK);
		$this->currentKnowledge->persist($modelId, $currentK);
	}

	// Helpers methods
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