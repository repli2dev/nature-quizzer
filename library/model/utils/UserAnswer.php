<?php
namespace NatureQuizzer\Model\Utils;
use NatureQuizzer\Database\Model\QuestionType;
use Nette\Object;
use Nette\Utils\Json;

/**
 * Wrapper class to encapsulate user's answer data
 */
class UserAnswer extends Object
{
	public $hasErrors = false;

	public $id_round;
	public $question_seq_num;
	public $question_type;
	public $extra;
	/**
	 * Each row should have:
	 *  - id_organism
	 *  - option_seq_num
	 *  - correct
	 *  - main
	 */
	public $options = [];

	public function isValid()
	{
		$state = !$this->hasErrors;
		$state &= $this->id_round !== null;
		$state &= $this->question_seq_num !== null && $this->question_seq_num > 0;
		$state &= QuestionType::isValid($this->question_type);
		$state &= count($this->options) > 0;
		$hasMain = false;
		foreach ($this->options as $option) {
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
			'id_round' => (int) $this->id_round,
			'question_seq_num' => (int) $this->question_seq_num,
			'question_type' => (int) $this->question_type,
			'extra' => $this->extra,
		];
		$output = [];
		foreach ($this->options as $option) {
			$output[] = array_merge($common, iterator_to_array($option));
		}
		return $output;
	}
} 