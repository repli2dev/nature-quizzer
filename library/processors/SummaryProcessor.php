<?php
namespace NatureQuizzer\Processors;

use NatureQuizzer\Database\Model\Answer;
use NatureQuizzer\Runtime\CurrentLanguage;
use NatureQuizzer\Runtime\CurrentUser;
use NatureQuizzer\Utils\Helpers;
use Nette\Object;

class SummaryProcessor extends Object
{
	/** @var CurrentLanguage */
	private $currentLanguage;
	/** @var CurrentUser */
	private $currentUser;
	/** @var Answer */
	private $answer;

	public function __construct(CurrentUser $currentUser, CurrentLanguage $currentLanguage, Answer $answer)
	{
		$this->currentUser = $currentUser;
		$this->currentLanguage = $currentLanguage;
		$this->answer = $answer;
	}

	public function get()
	{
		$data = $this->answer->findFromLastRound($this->currentUser->get(), $this->currentLanguage->get());
		$output = [];
		$incorrect = [];
		foreach ($data as $questionNumber => $options) {
			$question = [];
			$question['question_number'] = $questionNumber;
			$question['overall_correct'] = true;
			$question['relevant_options'] = [];
			foreach ($options as $row) {
				$row = (object) $row;
				// Include only relevant options (incorrect or main)
				if (!($row->main || $row->correct == false)) {
					continue;
				}
				$question['relevant_options'][] = [
					'id_organism' => $row->id_organism,
					'general_representation' => $row->general_representation,
					'id_representation' => $row->id_representation,
					'image' => ($row->id_representation) ? Helpers::getRepresentationImage($row->id_representation) : null,
					'name' => $row->name,
					'correct' => $row->correct,
					'main' => $row->main,
				];
				if (!$row->main) {
					$question['overall_correct'] = false;
					$incorrect[$questionNumber] = true;
				}
			}
			$output[] = $question;
		}
		return [
			'count' => count($output),
			'success_rate' => count($output) - count($incorrect),
			'answered' => $output
		];
	}
}