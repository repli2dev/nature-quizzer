<?php
namespace NatureQuizzer\Processors;

use NatureQuizzer\Database\Model\Concept;
use NatureQuizzer\Database\Model\Group;
use NatureQuizzer\RequestProcessorException;
use NatureQuizzer\Runtime\CurrentLanguage;
use NatureQuizzer\Runtime\CurrentUser;
use Nette\Object;
use Nette\Utils\Validators;

class ConceptsProcessor extends Object
{
	/** @var CurrentUser */
	private $currentUser;
	/** @var CurrentLanguage */
	private $currentLanguage;
	/** @var Concept */
	private $concept;
	/** @var Group */
	private $group;

	public function __construct(CurrentUser $currentUser, CurrentLanguage $currentLanguage, Concept $concept, Group $group)
	{
		list ($this->currentUser, $this->currentLanguage, $this->concept, $this->group) = func_get_args();
	}

	public function get($conceptId)
	{
		if (!$conceptId) {
			new RequestProcessorException('Concept cannot be empty.', 1000);
		}
		$output = [];
		if (Validators::isNumericInt($conceptId)) {
			$concept = $this->concept->getWithInfo($conceptId, $this->currentLanguage->get());
			if ($concept === FALSE) {
				new RequestProcessorException('No such concept.', 1001);
			}
			$output = [
				'mix' => false,
				'id_concept' => $concept->id_concept,
				'code_name' => $concept->code_name,
				'name' => $concept->name,
				'description' => $concept->description
			];
		} else {
			$output['mix'] = TRUE;
		}
		return $output;
	}

	public function getAll()
	{
		$concepts = $this->concept->getAllWithInfo($this->currentLanguage->get());
		if ($concepts === NULL) {
			new RequestProcessorException('No concepts found.', 2000);
		}
		$groups = $this->group->getAllWithInfo($this->currentLanguage->get());

		$output = ['groups' => []];
		foreach ($groups as $group) {
			$output['groups'][$group->id_group] = [
				'id_group' => $group->id_group,
				'code_name' => $group->code_name,
				'name' => $group->name,
				'concepts' => []
			];
		}

		foreach ($concepts as $concept) {
			$output['groups'][$concept->id_group]['concepts'][] = [
				'id_concept' => $concept->id_concept,
				'code_name' => $concept->code_name,
				'name' => $concept->name,
				'description' => $concept->description,
				'items_count' => $concept->count
			];
		}
		if (isset($output['groups'][''])) {
			$output['groups']['']['others'] = true;
		}
		$output['groups'] = array_values($output['groups']);
		return $output;
	}

	public function getQuick()
	{
		$concepts = $this->concept->getQuickWithInfo($this->currentLanguage->get());
		if ($concepts === NULL) {
			new RequestProcessorException('No concepts found.', 2000);
		}

		$output = ['concepts' => []];

		foreach ($concepts as $concept) {
			$output['concepts'][] = [
				'id_concept' => $concept->id_concept,
				'code_name' => $concept->code_name,
				'name' => $concept->name,
				'description' => $concept->description
			];
		}
		return $output;
	}
}