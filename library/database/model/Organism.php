<?php

namespace NatureQuizzer\Database\Model;


use Nette\InvalidArgumentException;
use Nette\Utils\Arrays;
use Nette\Utils\DateTime;

class Organism extends Table
{
	public function insert($data, $infos = [])
	{
		return $this->performInTransaction(function () use ($data, $infos) {
			$result =  $this->getTable()->insert($data);
			foreach ($infos as $langId => $tempData) {
				$tempData = iterator_to_array($tempData);
				$this->getInfoTable()->insert(array_merge($tempData, ['id_organism' => $result->id_organism, 'id_language' => $langId]));
			}
			return $result;
		});
	}


	public function update($key, $data, $infos = [])
	{
		return $this->performInTransaction(function () use ($key, $data, $infos) {
			$result = $this->getTable()->wherePrimary($key)->update($data);

			foreach ($infos as $langId => $tempData) {
				$oldInfo = $this->getInfoTable()->where('id_organism = ? AND id_language IN (?)', $key, $langId)->fetch();
				$tempData = iterator_to_array($tempData);
				if ($oldInfo === FALSE) {
					$this->getInfoTable()->insert(array_merge($tempData, ['id_organism' => $key, 'id_language' => $langId]));
				} elseif ($oldInfo->name != $tempData['name']) {
					$this->getInfoTable()->where('id_organism = ? AND id_language IN (?)', $key, $langId)->update(array_merge($tempData, ['updated' => new DateTime()]));
				}
			}
			return $result;
		});
	}

	public function getInfos($organism)
	{
		$data = $this->getInfoTable()
			->select('id_language, name')
			->where('id_organism = ?', $organism)
			->fetchAll();
		$output = [];
		foreach($data as $row) {
			$output[] = $row->toArray();
		}
		return Arrays::associate(
			$output,
			'id_language');
	}

	public function getFromConcept($conceptId)
	{
		return $this->getBelongingTable()->where('id_concept', $conceptId)->fetchAll();
	}

	public function addBelonging($organismId, $conceptId)
	{
		if (!$conceptId) throw new InvalidArgumentException('No concept id given.');
		if (!$organismId) throw new InvalidArgumentException('No organism id given.');
		$this->getBelongingTable()->insert(['id_concept' => $conceptId, 'id_organism' => $organismId]);
	}

	public function syncBelonging($conceptId, $organisms = [])
	{
		if (!$conceptId) throw new InvalidArgumentException('No concept id given.');

		return $this->performInTransaction(function () use ($conceptId, $organisms) {
			$this->getBelongingTable()->where('id_concept', $conceptId)->delete();
			foreach ($organisms as $key => $val) {
				if ($val !== TRUE) continue;
				$this->getBelongingTable()->insert(['id_concept' => $conceptId, 'id_organism' => $key]);
			}
		});
	}

	public function getBelongingTable()
	{
		return $this->context->table('organism_concept');
	}

	public function getRepresentations($organism)
	{
		return $this->getRepresentationTable()->where('id_organism = ?', $organism)->fetchAll();
	}

	public function addRepresentation($organism, $data)
	{
		$tempData = iterator_to_array($data);
		$tempData['id_organism'] = $organism;
		return $this->getRepresentationTable()->insert($tempData);
	}

	public function representationExists($hash)
	{
		return $this->getRepresentationTable()->where('hash = ?', $hash)->count() == 1;
	}

	public function deleteRepresentation($key)
	{
		return $this->getRepresentationTable()->where('id_representation = ?', $key)->delete();
	}

	public function getOrganismByRepresentation($representationId)
	{
		return $this->getTable()->where(':organism_representation.id_representation = ?', $representationId)->fetch();
	}

	public function getRepresentationsWithInfo($languageId, $conceptId = NULL)
	{
		return $this->context->query('
			SELECT
				organism_representation.*,
				organism_name.name,
				organism_concept.id_concept
			FROM organism_representation
			LEFT JOIN organism_name USING (id_organism)
			LEFT JOIN organism_concept USING (id_organism)
			WHERE id_language = ? AND (? IS NULL OR id_concept = ?)
		',
			$languageId,
			$conceptId,
			$conceptId
		)->fetchAssoc('id_organism[]');
	}

	public function getRepresentationsWithInfoByOrganisms($languageId, $organismIds)
	{
		return $this->context->query('
			SELECT
				organism_representation.*,
				organism_name.name
			FROM organism_representation
			LEFT JOIN organism_name USING (id_organism)
			LEFT JOIN organism_concept USING (id_organism)
			WHERE id_language = ? AND id_organism IN (?)
		',
			$languageId,
			$organismIds
		)->fetchAssoc('id_organism[]');
	}

	private function getInfoTable()
	{
		return $this->context->table('organism_name');
	}
	private function getRepresentationTable()
	{
		return $this->context->table('organism_representation');
	}

	public function getSelectionAttributes($userId, $conceptId = NULL)
	{
		return $this->context->query('
			SELECT
				organism.id_organism,
				COUNT(*) AS total_answered,
				MAX(answer.inserted) AS last_answer,
				(SELECT value FROM prior_knowledge WHERE id_user = ?) AS prior_knowledge,
				current_knowledge.value AS current_knowledge
			FROM organism
			LEFT JOIN answer ON answer.id_organism = organism.id_organism
			LEFT JOIN round on round.id_round = answer.id_round AND round.id_user = ?
			LEFT JOIN prior_knowledge ON prior_knowledge.id_user = round.id_user
			LEFT JOIN current_knowledge ON current_knowledge.id_user = round.id_user AND current_knowledge.id_organism = organism.id_organism
			WHERE (? IS NULL OR organism.id_organism IN (SELECT id_organism FROM organism_concept WHERE id_concept = ?))
			GROUP BY organism.id_organism, round.id_user, prior_knowledge.value, current_knowledge.value
		', $userId, $userId, $conceptId, $conceptId);
	}
}
