<?php
namespace NatureQuizzer\Model\Utils;
use NatureQuizzer\Database\Model\QuestionType;
use Nette\SmartObject;
use Nette\Utils\Json;

/**
 * Wrapper class to encapsulate user's answer data
 */
class UserAnswer
{
	use SmartObject;

	public $hasErrors = false;

	public $id_model;
	public $id_persistence_model;
	public $id_round;
	public $question_seq_num;
	public $question_type;
	public $extra;
	/**
	 * Each row should have:
	 *  - id_model
	 *  - id_persistence_model
	 *  - id_organism
	 *  - option_seq_num
	 *  - correct
	 *  - main
	 *
	 * Mandatory on question type CHOOSE_REPRESENTATION and main question of CHOOSE_NAME:
	 *  - id_representation
	 */
	public $options = [];

	public function isValid()
	{
		$state = !$this->hasErrors;
		$state &= $this->id_model !== null;
		$state &= $this->id_persistence_model !== null;
		$state &= $this->id_round !== null;
		$state &= $this->question_seq_num !== null && $this->question_seq_num > 0;
		$state &= QuestionType::isValid($this->question_type);
		$state &= count($this->options) > 0;
		$hasMain = false;
		foreach ($this->options as $option) {
			if ($this->question_type == QuestionType::CHOOSE_REPRESENTATION) {
				$state &= isset($option->id_representation);
			}
			if ($this->question_type == QuestionType::CHOOSE_NAME && $option->main) {
				$state &= isset($option->id_representation);
			}
			$state &= isset($option->main);
			if (isset($option->main)) {
				$hasMain |= $option->main;
			}
			$state &= isset($option->id_organism);
			$state &= isset($option->option_seq_num) && $option->option_seq_num >= 0;
			$state &= isset($option->correct);
		}
		$state &= $hasMain;
		return (bool) $state;
	}

	public function getMainOrganism()
	{
		foreach ($this->options as $option) {
			if ($option->main) {
				return $option->id_organism;
			}
		}
	}

	public function getOptionsCount()
	{
		return count($this->options);
	}

	/**
	 * Returns true if the user answer was correct on first attempt.
	 */
	public function isCorrect()
	{
		$state = true;
		foreach ($this->options as $option) {
			if (!$option->correct && !$option->main) {
				$state &= false;
			}
		}
		return (bool) $state;
	}

	public function toRows()
	{
		$common = [
			'id_model' => (int) $this->id_model,
			'id_persistence_model' => (int) $this->id_persistence_model,
			'id_round' => (int) $this->id_round,
			'question_seq_num' => (int) $this->question_seq_num,
			'question_type' => (int) $this->question_type,
			'extra' => $this->extra,
		];
		$output = [];
		foreach ($this->options as $option) {
			$temp = array_merge($common, iterator_to_array($option));
			$temp['correct'] = $temp['correct'] ? 'true' : 'false';
			$temp['main'] = $temp['main'] ? 'true' : 'false';
			$output[] = $temp;
		}
		return $output;
	}
} 