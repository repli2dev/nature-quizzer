<?php

namespace NatureQuizzer\Database\Model;


use Nette\Database\Table\ActiveRow;
use Nette\Utils\Arrays;
use Nette\Utils\DateTime;

class Concept extends Table
{
	const ALL = 'ALL';

	public function insert($data, $infos = [])
	{
		return $this->performInTransaction(function () use ($data, $infos) {
			$result =  $this->getTable()->insert($data);
			if (!$result instanceof ActiveRow) {
				throw new \RuntimeException('Insertion into database has failed.');
			}
			foreach ($infos as $langId => $tempData) {
				$tempData = iterator_to_array($tempData);
				$this->getInfoTable()->insert(array_merge($tempData, ['id_concept' => $result->id_concept, 'id_language' => $langId]));
			}
			return $result;
		});
	}


	public function update($key, $data, $infos = [])
	{
		return $this->performInTransaction(function () use ($key, $data, $infos) {
			$result = $this->getTable()->wherePrimary($key)->update($data);

			foreach ($infos as $langId => $tempData) {
				$oldInfo = $this->getInfoTable()->where('id_concept = ? AND id_language IN (?)', $key, $langId)->fetch();
				$tempData = iterator_to_array($tempData);
				if ($oldInfo === NULL) {
					$this->getInfoTable()->insert(array_merge($tempData, ['id_concept' => $key, 'id_language' => $langId]));
				} elseif ($oldInfo->name != $tempData['name'] || $oldInfo->description != $tempData['description']) {
					$this->getInfoTable()->where('id_concept = ? AND id_language IN (?)', $key, $langId)->update(array_merge($tempData, ['updated' => new DateTime()]));
				}
			}
			return $result;
		});
	}

	public function findByCodeName($codeName)
	{
		return $this->getTable()->where('code_name = ?', $codeName)->fetch();
	}

	public function getPairs()
	{
		return $this->getTable()->fetchPairs('id_concept', 'code_name');
	}

	public function getInfos($concept)
	{
		$data = $this->getInfoTable()
			->select('id_language, name, description')
			->where('id_concept = ?', $concept)
			->fetchAll();
		$output = [];
		foreach($data as $row) {
			$output[] = $row->toArray();
		}
		return Arrays::associate(
			$output,
			'id_language');
	}

	public function getWithInfo($idConcept, $idLanguage)
	{
		return $this->getTable()
			->select('concept.*, :concept_info.*')
			->where('id_language', $idLanguage)
			->where('concept.id_concept', $idConcept)
			->fetch();
	}

	public function getAllWithInfo($idLanguage)
	{
		return $this->getTable()
			->select('concept.*, :concept_info.*, (SELECT COUNT(*) FROM organism_concept WHERE id_concept = concept.id_concept) AS count')
			->where('id_language', $idLanguage)
			->fetchAll();
	}

	public function getQuickWithInfo($idLanguage)
	{
		return $this->getTable()
			->select('concept.*, :concept_info.*')
			->where('id_language', $idLanguage)
			->where('quick', 'true')
			->fetchAll();
	}

	private function getInfoTable()
	{
		return $this->context->table('concept_info');
	}
}
