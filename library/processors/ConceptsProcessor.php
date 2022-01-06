<?php
namespace NatureQuizzer\Processors;

use NatureQuizzer\Database\Model\Concept;
use NatureQuizzer\Database\Model\Group;
use NatureQuizzer\Database\Model\Organism;
use NatureQuizzer\RequestProcessorException;
use NatureQuizzer\Runtime\CurrentLanguage;
use NatureQuizzer\Runtime\CurrentUser;
use NatureQuizzer\Utils\Helpers;
use Nette\SmartObject;
use Nette\Utils\Validators;

class ConceptsProcessor
{
	use SmartObject;

	/** @var CurrentLanguage */
	private $currentLanguage;
	/** @var Concept */
	private $concept;
	/** @var Group */
	private $group;
	/** @var Organism */
	private $organism;

	public function __construct(CurrentLanguage $currentLanguage, Concept $concept, Group $group, Organism $organism)
	{
		list ($this->currentLanguage, $this->concept, $this->group, $this->organism) = func_get_args();
	}

	public function get($conceptId)
	{
		if (!$conceptId) {
			throw new RequestProcessorException('Concept cannot be empty.', 1000);
		}
		$output = [];
		if (Validators::isNumericInt($conceptId)) {
			$concept = $this->concept->getWithInfo($conceptId, $this->currentLanguage->get());
			if ($concept === NULL) {
				throw new RequestProcessorException('No such concept.', 1001);
			}
			$output = [
				'mix' => false,
				'id_concept' => $concept->id_concept,
				'code_name' => $concept->code_name,
				'name' => $concept->name,
				'description' => $concept->description,
				'warning' => $concept->warning,
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
			throw new RequestProcessorException('No concepts found.', 2000);
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
				'items_count' => $concept->count,
				'warning' => $concept->warning,
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
			throw new RequestProcessorException('No concepts found.', 2000);
		}

		$output = ['concepts' => []];

		foreach ($concepts as $concept) {
			$output['concepts'][] = [
				'id_concept' => $concept->id_concept,
				'code_name' => $concept->code_name,
				'name' => $concept->name,
				'description' => $concept->description,
				'warning' => $concept->warning,
			];
		}
		return $output;
	}

	public function getDetail($conceptId)
	{
		if (!$conceptId || !Validators::isNumericInt($conceptId)) {
			throw new RequestProcessorException('Concept cannot be empty.', 1000);
		}
		$concept = $this->concept->getWithInfo($conceptId, $this->currentLanguage->get());
		$organisms = $this->organism->getFirstRepresentationsWithInfoByConcept($this->currentLanguage->get(), $conceptId);
		if ($concept === NULL) {
			throw new RequestProcessorException('No such concept.', 1001);
		}
		$output = [
			'mix' => false,
			'id_concept' => $concept->id_concept,
			'code_name' => $concept->code_name,
			'name' => $concept->name,
			'description' => $concept->description,
			'warning' => $concept->warning,
			'organisms' => [],
			'items_count' => count($organisms),
		];
		foreach ($organisms as $organism) {
			$output['organisms'][] = [
				'name' => $organism->name,
				'image' => Helpers::getRepresentationImage($organism->id_representation),
				'imageRaw' => Helpers::getRepresentationImageRaw($organism->id_representation),
				'imageRightsHolder' => $organism->rights_holder,
				'imageLicense' => $organism->license,
			];
		}
		return $output;
	}
}
