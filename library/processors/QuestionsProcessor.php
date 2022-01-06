<?php
namespace NatureQuizzer\Processors;

use NatureQuizzer\Database\Model\Concept;
use NatureQuizzer\Database\Model\Organism;
use NatureQuizzer\Database\Model\QuestionType;
use NatureQuizzer\Model\EloRandomDistractors;
use NatureQuizzer\Model\IModelFacade;
use NatureQuizzer\Model\ModelFacadeFactory;
use NatureQuizzer\RequestProcessorException;
use NatureQuizzer\Runtime\CurrentLanguage;
use NatureQuizzer\Runtime\CurrentUser;
use Nette\Database\Table\ActiveRow;
use Nette\SmartObject;
use Nette\Utils\Html;
use Nette\Utils\Validators;

class QuestionsProcessor
{
	use SmartObject;

	/** @var CurrentUser */
	private $currentUser;
	/** @var CurrentLanguage */
	private $currentLanguage;
	/** @var Concept */
	private $concept;
	/** @var IModelFacade */
	private $quizGenerator;

	public function __construct(CurrentUser $currentUser, CurrentLanguage $currentLanguage, Concept $concept, ModelFacadeFactory $quizGeneratorFactory)
	{
		$this->currentUser = $currentUser;
		$this->currentLanguage = $currentLanguage;
		$this->concept = $concept;
		$this->quizGenerator = $quizGeneratorFactory->get($this->currentUser->get());
	}

	/**
	 * @param string|int $conceptId
	 * @return ActiveRow|string
	 */
	private function fetchConceptInfo($conceptId)
	{
		if (Validators::isNumericInt($conceptId)) {
			$concept = $this->concept->getWithInfo($conceptId, $this->currentLanguage->get());
			if ($concept === NULL) {
				throw new RequestProcessorException('No such concept.', 3000);
			}
			return $concept;
		} else {
			return Concept::ALL;
		}
	}

	private function prepareOutput($concept, $questions)
	{
		$output = [];
		$output['count'] = count($questions);
		if ($concept !== Concept::ALL) {
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
		$questions = $this->quizGenerator->get($this->currentUser->get(), $concept, $count);
		return $this->prepareOutput($concept, $questions);
	}
}
