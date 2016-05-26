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

	public function getAllWithName($languageId)
	{
		return $this->getTable()->select('organism.*, :organism_name.name, :organism_commonness.value')->where(':organism_name.id_language = ?', $languageId)->order('id_organism ASC')->fetchAll();
	}

	public function findByLatinName($latinName)
	{
		return $this->getTable()->where('latin_name = ?', $latinName)->fetch();
	}

	public function findRandom($count)
	{
		return $this->getTable()->order('random()')->limit($count)->select('id_organism')->fetchPairs(NULL, 'id_organism');
	}

	public function findInDistance($organismId, $distance = 0, $count)
	{
		$data = $this->getConnection()->query('
			SELECT * FROM (
				SELECT id_organism_to
				FROM organism_distance
				WHERE id_organism_from = ? AND distance > ?
				ORDER BY distance, RANDOM() ASC
				LIMIT ?
			  ) candidates
			  ORDER BY RANDOM()
		', $organismId, $distance, $count)->fetchPairs(NULL, 'id_organism_to');

		if (count($data) >= $count) {
			return $data;
		}
		// if not enough organisms in wanted distance is found we take the most far organisms
		$data2 = $this->getConnection()->query('
			SELECT * FROM (
				SELECT id_organism_to
				FROM organism_distance
				WHERE id_organism_from = ? AND distance <= ?
				ORDER BY distance DESC, RANDOM()
				LIMIT ?
			) candidates
			ORDER BY RANDOM()
		', $organismId, $distance, $count)->fetchPairs(NULL, 'id_organism_to');

		shuffle($data2);
		return array_merge($data, array_splice($data2, 0, $count - count($data)));
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

	public function countFromConcept($conceptId = null)
	{
		$query = $this->getBelongingTable();
		if ($conceptId) {
			$query->where('id_concept = ?', (int)$conceptId);
		}
		return $query->count();
	}

	public function addBelonging($organismId, $conceptId)
	{
		if (!$conceptId) throw new InvalidArgumentException('No concept id given.');
		if (!$organismId) throw new InvalidArgumentException('No organism id given.');
		$this->getBelongingTable()->insert(['id_concept' => $conceptId, 'id_organism' => $organismId]);
	}

	public function existsBelonging($organismId, $conceptId)
	{
		if (!$conceptId) throw new InvalidArgumentException('No concept id given.');
		if (!$organismId) throw new InvalidArgumentException('No organism id given.');
		return $this->getBelongingTable()->where('id_concept = ? AND id_organism = ?', $conceptId, $organismId)->count() > 0;
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

	public function getDistanceTable()
	{
		return $this->context->table('organism_distance');
	}

	public function getRepresentations($organism)
	{
		return $this->getRepresentationTable()->where('id_organism = ?', $organism)->fetchAll();
	}
	public function getRepresentationsByIds($representationIds)
	{
		$representationIds = (array) $representationIds;
		$representationIds[] = NULL;
		return $this->getRepresentationTable()->where('id_representation IN (?)', $representationIds)->fetchAll();
	}

	public function addRepresentation($organism, $data)
	{
		$tempData = iterator_to_array($data);
		$tempData['id_organism'] = $organism;
		return $this->getRepresentationTable()->insert($tempData);
	}

	public function findRepresentationByHash($hash)
	{
		return $this->getRepresentationTable()->where('hash = ?', $hash)->fetch();
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

	public function getRepresentationsWithInfoByOrganisms($languageId, $organismIds)
	{
		return $this->context->query('
			SELECT
				organism_representation.*,
				organism_name.name
			FROM organism_representation
			LEFT JOIN organism_name USING (id_organism)
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

	public function getUserSelectionAttributes($userId, $modelId)
	{
		/* This query fetches dense data for each organism! */
		return $this->context->query('
			SELECT
				organism.id_organism,
				(SELECT COUNT(id_answer) FROM answer JOIN round ON round.id_round = answer.id_round WHERE id_user = ? AND answer.id_organism = organism.id_organism AND answer.main = TRUE) AS total_answered,
				(SELECT MAX(answer.inserted) FROM answer JOIN round ON round.id_round = answer.id_round WHERE id_user = ? AND answer.id_organism = organism.id_organism AND answer.main = TRUE) AS last_answer,
				(SELECT current_knowledge.value FROM current_knowledge WHERE current_knowledge.id_model = ? AND current_knowledge.id_organism = organism.id_organism AND current_knowledge.id_user = ?) AS current_knowledge
			FROM organism
		', $userId, $userId, $modelId, $userId);
	}

	public function getGeneralSelectionAttributes($modelId, $conceptId = NULL)
	{
		/* This query fetches dense data for each organism! */
		return $this->context->query('
			SELECT
				organism.id_organism,
				organism_difficulty.value AS organism_difficulty,
				organism_commonness.value AS organism_commonness,
				(SELECT COUNT(*) FROM organism_representation WHERE organism_representation.id_organism = organism.id_organism) AS representation_count
			FROM organism
			LEFT JOIN organism_commonness ON organism_commonness.id_organism = organism.id_organism
			LEFT JOIN organism_difficulty ON organism_difficulty.id_organism = organism.id_organism
			WHERE
				(organism_difficulty.id_model = ? OR organism_difficulty.id_model IS NULL) AND
				(? IS NULL OR organism.id_organism IN (SELECT id_organism FROM organism_concept WHERE id_concept = ?))
		', $modelId, $conceptId, $conceptId);
	}

	public function getGlobalStats()
	{
		return $this->context->query('
		SELECT
			(SELECT COUNT(*) FROM organism) AS organism_count,
			(SELECT COUNT(*) FROM organism_representation) AS representation_count,
			(SELECT COUNT(*) FROM "user") AS user_count,
			(SELECT COUNT(*) FROM "user" WHERE anonymous = FALSE) AS registered_count,
			(SELECT COUNT(*) FROM answer WHERE main = TRUE) AS question_count,
			(SELECT COUNT(DISTINCT id_organism) FROM answer WHERE main = TRUE) AS exercised_organism_count
		')->fetch();
	}
}
