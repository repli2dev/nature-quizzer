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

	public function get($conceptId, $count)
	{
		$count = min($count, 10);
		if (Validators::isNumericInt($conceptId)) {
			$concept = $conceptId;
			$concept = $this->concept->getWithInfo($conceptId, $this->currentLanguage->get());
			if ($concept === FALSE) {
				throw new RequestProcessorException('No such concept.', 3000);
			}
		} else {
			$concept = QuestionSelection::ALL_CONCEPTS;
		}
		$this->questionSelection->fetch($this->currentUser->get(), $concept);
		$output = [];
		$output['count'] = null;
		$output['questions'] = [];
		if (Validators::isNumericInt($conceptId)) {
			$output['concept'] = [
				'id_concept' => $concept->id_concept,
				'code_name' => $concept->code_name,
				'name' => $concept->name,
				'description' => $concept->description
			];
		} else {
			$output['all'] = true;
		}
		if (Validators::isNumericInt($conceptId)) {
			$organisms = $this->organism->getRepresentationsWithInfo($this->currentLanguage->get(), $conceptId);
		} else {
			$organisms = $this->organism->getRepresentationsWithInfo($this->currentLanguage->get());
		}
		$temp = [];
		foreach ($organisms as $row) {
			$temp[$row->id_organism][] = $row;
		}
		shuffle($temp);
		$output['count'] = $count;
		$output['questions'] = [];
		foreach ($temp as $organism) {
			if (count($output['questions']) > $count) continue;
			$questionType = (rand(0, 1) == 1) ? QuestionType::CHOOSE_NAME : QuestionType::CHOOSE_REPRESENTATION;
			if ($questionType === QuestionType::CHOOSE_NAME) {
				$question = [
					'type' => $questionType,
					'questionImage' => $this->getRepresentationImage($organism[0]->id_representation)
				];
				$options = [];
				$options[] = ['id_organism' => $organism[0]->id_organism, 'text' => $organism[0]->name, 'correct' => true];
				for ($i = 0; $i < 10; $i++) {
					$item = array_rand($temp);
					$item = $temp[$item];
					if ($item == $organism) continue;
					if (count($options) == 4) break;
					$options[] = ['id_organism' => $item[0]->id_organism, 'text' => $item[0]->name, 'correct' => false];
				}
				shuffle($options);
				$question['options'] = $options;
			} elseif ($questionType === QuestionType::CHOOSE_REPRESENTATION) {
				$question = [
					'type' => $questionType,
					'questionText' => $organism[0]->name
				];
				$options = [];
				$options[] = [
					'id_representation' => $organism[0]->id_representation,
					'image' => $this->getRepresentationImage($organism[0]->id_representation),
					'correct' => true
				];
				for ($i = 0; $i < 10; $i++) {
					$item = array_rand($temp);
					$item = $temp[$item];
					if ($item == $organism) continue;
					if (count($options) == 4) break;
					$options[] = [
						'id_representation' => $item[0]->id_representation,
						'image' => $this->getRepresentationImage($item[0]->id_representation),
						'correct' => false
					];
				}
				shuffle($options);
				$question['options'] = $options;
			}
			$output['questions'][] = $question;
		}
		return $output;
	}

	private function getRepresentationImage($representationId)
	{
		return Html::el('img')->src('/images/organisms/' . $representationId)->render();
	}
}