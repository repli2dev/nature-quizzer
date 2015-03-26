<?php
namespace NatureQuizzer\Processors;

use NatureQuizzer\Database\Model\Concept;
use NatureQuizzer\Database\Model\Organism;
use NatureQuizzer\Database\Model\QuestionType;
use NatureQuizzer\Model\QuestionSelection;
use NatureQuizzer\RequestProcessorException;
use NatureQuizzer\Runtime\CurrentLanguage;
use NatureQuizzer\Runtime\CurrentUser;
use Nette\Object;
use Nette\Utils\Html;
use Nette\Utils\Validators;

class QuestionsProcessor extends Object
{
	/** @var CurrentUser */
	private $currentUser;
	/** @var CurrentLanguage */
	private $currentLanguage;
	/** @var Concept */
	private $concept;
	/** @var Organism */
	private $organism;
	/** @var QuestionSelection */
	private $questionSelection;

	public function __construct(CurrentUser $currentUser, CurrentLanguage $currentLanguage, Concept $concept, Organism $organism, QuestionSelection $questionSelection)
	{
		list ($this->currentUser, $this->currentLanguage, $this->concept, $this->organism, $this->questionSelection) = func_get_args();
	}

	private function fetchConceptInfo($conceptId)
	{
		if (Validators::isNumericInt($conceptId)) {
			$concept = $this->concept->getWithInfo($conceptId, $this->currentLanguage->get());
			if ($concept === FALSE) {
				throw new RequestProcessorException('No such concept.', 3000);
			}
			return $concept;
		} else {
			return QuestionSelection::ALL_CONCEPTS;
		}
	}

	private function prepareOutput($concept, $questions)
	{
		$output = [];
		$output['count'] = count($questions);
		if ($concept !== QuestionSelection::ALL_CONCEPTS) {
			$output['concept'] = [
				'id_concept' => $concept->id_concept,
				'code_name' => $concept->code_name,
				'name' => $concept->name,
				'description' => $concept->description
			];
		} else {
			$output['all'] = TRUE;
		}
		$output['questions'] = $questions;
		return $output;
	}

	public function get($conceptId, $count)
	{
		$count = min($count, 10);
		$concept = $this->fetchConceptInfo($conceptId);
		$questions = $this->questionSelection->fetch($this->currentUser->get(), $concept, $count);
		return $this->prepareOutput($concept, $questions);
	}
}