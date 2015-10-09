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
		$correct = 0;
		foreach ($data as $row) {
			$output[] = [
				'id_organism' => $row->id_organism,
				'id_representation' => $row->id_representation,
				'image' => Helpers::getRepresentationImage($row->id_representation),
				'name' => $row->name,
				'correct' => $row->correct
			];
			if ($row->correct) {
				$correct += 1;
			}
		}
		return [
			'count' => count($output),
			'success_rate' => $correct,
			'answered' => $output
		];
	}
}