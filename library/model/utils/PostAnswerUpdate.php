<?php
namespace NatureQuizzer\Model\Utils;

use NatureQuizzer\Database\Model\Answer;
use NatureQuizzer\Database\Model\CurrentKnowledge;
use NatureQuizzer\Database\Model\OrganismDifficulty;
use NatureQuizzer\Database\Model\PriorKnowledge;
use NatureQuizzer\Model\Scoring\ELO;
use Nette\Object;

class PostAnswerUpdate extends Object
{
	/** @var Answer */
	private $answer;
	/** @var OrganismDifficulty */
	private $organismDifficulty;
	/** @var PriorKnowledge */
	private $priorKnowledge;
	/** @var CurrentKnowledge */
	private $currentKnowledge;

	public function __construct(Answer $answer, OrganismDifficulty $organismDifficulty,
								PriorKnowledge $priorKnowledge, CurrentKnowledge $currentKnowledge)
	{
		list (
			$this->answer,
			$this->organismDifficulty,
			$this->priorKnowledge,
			$this->currentKnowledge,
		) = func_get_args();
	}

	public function perform($userId, UserAnswer $answer)
	{
		$organismId = $answer->getMainOrganism();
		$optionsCount = $answer->getOptionsCount();
		$isCorrect = $answer->isCorrect();

		// Load data
		$organismD = $this->organismDifficulty->fetch($organismId);
		$priorK = $this->priorKnowledge->fetch($userId);
		$currentK = $this->currentKnowledge->fetch($organismId, $userId);

		// Update prior knowledge and item difficulty (only when previous answer from this user is present)
		if ($currentK->getValue() === null) {
			// Item difficulty
			$elo = new ELO();
			$elo->k = function () use ($organismId) {
				// TODO: refactor a = 1 and b = 0.05 out
				return (1 / (1 + 0.05 * $this->answer->organismFirstAnswersCount($organismId)));
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
				// TODO: refactor a = 1 and b = 0.05 out
				return 1 / (1 + 0.05 * $this->answer->userFirstAnswerCount($userId));
			};
			$elo->update($priorK);

			// Persist data
			$this->organismDifficulty->persist($organismD);
			$this->priorKnowledge->persist($priorK);
		}

		// Update current knowledge
		// TODO: refactor out
		$gamma = 1;	// Answered correctly
		$beta = 1;	// Answered not correctly
		$elo = new ELO();
		$elo->k = function () use ($gamma, $beta, $isCorrect) {
			if ($isCorrect) {
				return $gamma;
			} else {
				return $beta;
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
		$this->currentKnowledge->persist($currentK);
	}

} 