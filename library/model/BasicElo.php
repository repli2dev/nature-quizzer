<?php

namespace NatureQuizzer\Model\Utils;

use NatureQuizzer\Database\Model\Answer;
use NatureQuizzer\Database\Model\Concept;
use NatureQuizzer\Database\Model\CurrentKnowledge;
use NatureQuizzer\Database\Model\Model;
use NatureQuizzer\Database\Model\Organism;
use NatureQuizzer\Database\Model\OrganismCommonness;
use NatureQuizzer\Database\Model\OrganismDifficulty;
use NatureQuizzer\Database\Model\PriorKnowledge;
use NatureQuizzer\Database\Model\QuestionType;
use NatureQuizzer\Model\AModelFacade;
use NatureQuizzer\Runtime\CurrentLanguage;
use NatureQuizzer\Utils\Helpers;
use Nette\Database\Table\ActiveRow;
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
	/** @var OrganismCommonness */
	protected $organismCommonness;
	/** @var PriorKnowledge */
	protected $priorKnowledge;
	/** @var CurrentKnowledge */
	protected $currentKnowledge;
	/** @var CurrentLanguage */
	protected $currentLanguage;

	protected $targetProbability;        // Target probability of round. e.g. 0.75 (75 %)
	protected $weightProbability;
	protected $weightTime;
	protected $weightCount;
	protected $weightCommonness;
	protected $weightInvalidAnswer;        // ELO update impact if the answer was invalid
	protected $weightCorrectAnswer;        // ELO update impact if the answer was correct
	protected $eloUpdateFactorA;        // ELO `K` function parameters for uncertainty function
	protected $eloUpdateFactorB;
	protected $distractorCount = 3;

	public function __construct(Organism $organism, CurrentLanguage $currentLanguage, Answer $answer,
								OrganismDifficulty $organismDifficulty, OrganismCommonness $organismCommonness,
								PriorKnowledge $priorKnowledge, CurrentKnowledge $currentKnowledge, Model $model)
	{
		parent::__construct($model);

		$this->organism = $organism;
		$this->organismCommonness = $organismCommonness;
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
		$organismIds = $this->selectMainQuestions($userId, $concept, $count);
		$distractorsIds = $this->selectDistractors($userId, $organismIds, $this->distractorCount);

		$data = $this->fetchData($organismIds, $distractorsIds);

		$questions = [];
		foreach ($organismIds as $seqId => $organismId) {
			if (!isset ($data[$organismId])) {
				continue; // TBD: prober handling
			}
			$questionType = (rand(0, 100) >= 60) ? QuestionType::CHOOSE_NAME : QuestionType::CHOOSE_REPRESENTATION;
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
			'questionImage' => Helpers::getRepresentationImage($organism->id_representation),
			'questionImageRightsHolder' => $organism->rights_holder,
			'questionImageLicense' => $organism->license,
		];
		$options = [];
		$options[] = [
			'id_organism' => $organism->id_organism,
			'text' => $organism->name,
			'correct' => TRUE,
			'image' => Helpers::getRepresentationImage($organism->id_representation),
			'imageRightsHolder' => $organism->rights_holder,
			'imageLicense' => $organism->license,
		];
		foreach ($questionDistractors as $distractorId) {
			if (!isset($data[$distractorId])) {
				Debugger::log('Missing data for distractor organism: [' . $distractorId . ']', ILogger::WARNING);
				continue;
			}
			// We are interested only in the name, so we can ignore other representation and take the first one.
			$otherOrganism = ArrayHash::from($data[$distractorId][0]);
			$options[] = [
				'id_organism' => $otherOrganism->id_organism,
				'text' => $otherOrganism->name,
				'correct' => FALSE,
				'image' => Helpers::getRepresentationImage($otherOrganism->id_representation),
				'imageRightsHolder' => $otherOrganism->rights_holder,
				'imageLicense' => $otherOrganism->license,
			];
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
			'image' => Helpers::getRepresentationImage($organism->id_representation),
			'imageRightsHolder' => $organism->rights_holder,
			'imageLicense' => $organism->license,
			'correct' => TRUE,
			'name' => $organism->name,
		];
		foreach ($questionDistractors as $distractorId) {
			if (!isset($data[$distractorId])) {
				Debugger::log('Missing data for distractor organism: [' . $distractorId . ']', ILogger::WARNING);
				continue;
			}
			$otherOrganism = ArrayHash::from($this->getRandomItem($data[$distractorId]));
			$options[] = [
				'id_representation' => $otherOrganism->id_representation,
				'image' => Helpers::getRepresentationImage($otherOrganism->id_representation),
				'imageRightsHolder' => $otherOrganism->rights_holder,
				'imageLicense' => $otherOrganism->license,
				'correct' => FALSE,
				'name' => $otherOrganism->name
			];
		}
		shuffle($options);
		$question['options'] = $options;
		return $question;
	}

	/**
	 * Returns array where for each organism there is number of distractor organisms.
	 * @param int $userId ID of user
	 * @param array $organismIds Sequential array of organism IDs (@see selectMainQuestions)
	 * @return array
	 */
	protected abstract function selectDistractors($userId, $organismIds, $distractorCount);

	/**
	 * Returns sequential array with ID organisms to ask.
	 *
	 * @param int $userId ID of user
	 * @param ActiveRow|string $concept ID of concept or constant Concept::ALL
	 * @param int $count Desired count of questions
	 * @return array
	 */
	protected function selectMainQuestions($userId, $concept, $count)
	{
		$modelId = $this->getPersistenceId();
		$conceptId = NULL;
		if ($concept !== Concept::ALL && $concept instanceof ActiveRow) {
			$conceptId = $concept->id_concept;
		}
		$optionsCount = $this->distractorCount + 1; // The number of options is fixed, so we can take this into account of probability

		$generalData = $this->organism->getGeneralSelectionAttributes($modelId, $conceptId)->fetchAssoc('id_organism=');
		$userData = $this->organism->getUserSelectionAttributes($userId, $modelId)->fetchAssoc('id_organism=');
		$priorK = $this->priorKnowledge->fetch($modelId, $userId);
		$maximalCommonness = $this->organismCommonness->getMaximum();

		$scores = [];
		$temp = [];

		foreach ($generalData as $organismId => $o) {
			$o = ArrayHash::from($o);
			if ($o->representation_count == 0) {
				Debugger::log('Question skipped as no representation for organism: [' . $organismId . ']', ILogger::WARNING);
				continue;
			}
			if (!isset($userData[$organismId])) {
				Debugger::log('Question skipped as user data are missing for user, organism: [' . $userId . ', ' . $organismId . ']', ILogger::CRITICAL);
				continue;
			}
			$u = ArrayHash::from($userData[$organismId]);

			$eloScore = ($u->current_knowledge !== NULL) ? $u->current_knowledge : ($priorK->getValue() - $o->organism_difficulty);
			$score = $this->weightProbability * $this->scoreProbability($this->probabilityEstimated($eloScore, $optionsCount), $this->targetProbability);
			$score += $this->weightTime * $this->scoreTime($u->last_answer);
			$score += $this->weightCount * $this->scoreCount($u->total_answered);
			$score += $this->weightCommonness * $this->scoreCommonness($o->organism_commonness, $maximalCommonness);
			$scores[$o->id_organism] = $score;
			$temp[$o->id_organism] =
				(sprintf("%s: %f; estimated: %f; probability: %f; time: %f; count: %f; commonness: %f (input data > total_answered: %f; current_knowledge: %f; prior_knowledge: %f; organism_difficult: %f)\n",
					$o->id_organism,
					$score,
					$this->probabilityEstimated($eloScore, $optionsCount),
					$this->weightProbability * $this->scoreProbability($this->probabilityEstimated($eloScore, $optionsCount), $this->targetProbability),
					$this->weightTime * $this->scoreTime($u->last_answer),
					$this->weightCount * $this->scoreCount($u->total_answered),
					$this->weightCommonness * $this->scoreCommonness($o->organism_commonness, $maximalCommonness),
					$u->total_answered,
					$u->current_knowledge,
					$priorK->getValue(),
					$o->organism_difficulty
				));
		}
		arsort($scores);
		$organisms = array_keys(array_slice($scores, 0, $count, true));
//		foreach ($organisms as $organism) {
//			fdump($temp[$organism]);
//		}
		return $organisms;
	}

	public function answer($userId, UserAnswer $answer)
	{
		$modelId = $this->getPersistenceId();
		$organismId = $answer->getMainOrganism();
		$optionsCount = $answer->getOptionsCount();
		$isCorrect = $answer->isCorrect();

		// Load data
		$organismD = $this->organismDifficulty->fetch($modelId, $organismId);
		$priorK = $this->priorKnowledge->fetch($modelId, $userId);
		$currentK = $this->currentKnowledge->fetch($modelId, $organismId, $userId);

		$organismFirstAnswers = $this->answer->organismFirstAnswersCount($organismId);
		$userFirstAnswer = $this->answer->userFirstAnswerCount($userId);

		// Do computation
		$changes = $this->computeNewStudentModel($organismD->getValue(), $priorK->getValue(), $currentK->getValue(), $optionsCount, $isCorrect, $organismFirstAnswers, $userFirstAnswer);

		// Persist
		if (isset($changes['priorKnowledge']) && isset($changes['organismDifficulty'])) {
			$priorK->setValue($changes['priorKnowledge']);
			$organismD->setValue($changes['organismDifficulty']);

			$this->organismDifficulty->persist($modelId, $organismD);
			$this->priorKnowledge->persist($modelId, $priorK);
		}

//		fdump(sprintf('CK: %f -> %f', $currentK->getValue(), $changes['currentKnowledge']));
		$currentK->setValue($changes['currentKnowledge']);
		$this->currentKnowledge->persist($modelId, $currentK);
	}

	/**
	 * This method only compute new student model from given values.
	 * There is NO data persistence as it used from console based utility too.
	 */
	public function computeNewStudentModel($organismD, $priorK, $currentK, $optionsCount, $isCorrect, $organismFirstAnswers, $userFirstAnswers)
	{
		$output = [];
		// Update prior knowledge and item difficulty (only when previous answer from this user is present)
		if ($currentK === NULL) {
			$pF = function ($organismD, $priorK, $optionsCount, $isCorrect) {
				$val = 1 / $optionsCount + (1 - 1 / $optionsCount) * (1 / (1 + pow(M_E, -($priorK - $organismD))));
				if ($isCorrect) {
					return 1 - $val;
				} else {
					return -$val;
				}
			};
			// Item difficulty
			$k = ($this->eloUpdateFactorA / (1 + $this->eloUpdateFactorB * $organismFirstAnswers));
			$p = $pF($organismD, $priorK, $optionsCount, $isCorrect);
			$newOrganismD = $organismD - $k * $p;

			// Prior knowledge of user
			$k = $this->eloUpdateFactorA / (1 + $this->eloUpdateFactorB * $userFirstAnswers);
			$p = $pF($organismD, $priorK, $optionsCount, $isCorrect);
			$newPriorK = $priorK + $k * $p;

//			fdump(sprintf('PK: %f -> %f', $priorK, $newPriorK));
//			fdump(sprintf('OD: %f -> %f', $organismD, $newOrganismD));

			$output['priorKnowledge'] = $newPriorK;
			$output['organismDifficulty'] = $newOrganismD;
		}

		// Update current knowledge
		$k = ($isCorrect) ? $this->weightCorrectAnswer : $this->weightInvalidAnswer;
		$pF = function ($optionsCount, $isCorrect, $currentK, $priorK, $organismD) {
			if ($currentK === NULL) {
				$v = $priorK - $organismD;
			} else {
				$v = $currentK;
			}
			$val = 1 / $optionsCount + (1 - 1 / $optionsCount) * (
					1 / (1 + pow(M_E, - ($v)))
				);
			if ($isCorrect) {
				return 1 - $val;
			} else {
				return -$val;
			}
		};
		$p = $pF($optionsCount, $isCorrect, $currentK, $priorK, $organismD);
		$newCurrentK = $currentK + $k * $p;
		$output['currentKnowledge'] = $newCurrentK;
		return $output;
	}

	// Helpers methods
	private function probabilityEstimated($score, $optionsCount)
	{
		return 1 / $optionsCount + (1 - 1 / $optionsCount) * (1 / (1 + pow(M_E, -$score)));
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

	private function scoreCommonness($value, $maximum)
	{
		return $value / $maximum;
	}
}
