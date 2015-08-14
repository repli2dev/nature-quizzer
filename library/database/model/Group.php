<?php

namespace NatureQuizzer\Database\Model;


use Nette\Utils\Arrays;
use Nette\Utils\DateTime;

class Group extends Table
{
	public function insert($data, $infos = [])
	{
		return $this->performInTransaction(function () use ($data, $infos) {
			$result = $this->getTable()->insert($data);
			foreach ($infos as $langId => $tempData) {
				$tempData = iterator_to_array($tempData);
				$this->getInfoTable()->insert(array_merge($tempData, ['id_group' => $result->id_group, 'id_language' => $langId]));
			}
			return $result;
		});
	}


	public function update($key, $data, $infos = [])
	{
		return $this->performInTransaction(function () use ($key, $data, $infos) {
			$result = $this->getTable()->wherePrimary($key)->update($data);

			foreach ($infos as $langId => $tempData) {
				$oldInfo = $this->getInfoTable()->where('id_group = ? AND id_language IN (?)', $key, $langId)->fetch();
				$tempData = iterator_to_array($tempData);
				if ($oldInfo === FALSE) {
					$this->getInfoTable()->insert(array_merge($tempData, ['id_group' => $key, 'id_language' => $langId]));
				} elseif ($oldInfo->name != $tempData['name']) {
					$this->getInfoTable()->where('id_group = ? AND id_language IN (?)', $key, $langId)->update(array_merge($tempData, ['updated' => new DateTime()]));
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
		return $this->getTable()->fetchPairs('id_group', 'code_name');
	}

	public function getInfos($group)
	{
		$data = $this->getInfoTable()
			->select('id_language, name')
			->where('id_group = ?', $group)
			->fetchAll();
		$output = [];
		foreach($data as $row) {
			$output[] = $row->toArray();
		}
		return Arrays::associate(
			$output,
			'id_language');
	}

	public function getWithInfo($idGroup, $idLanguage)
	{
		return $this->getTable()
			->select('group.*, :group_info.*')
			->where('id_language', $idLanguage)
			->where('group.id_group', $idGroup)
			->fetch();
	}

	public function getAllWithInfo($idLanguage)
	{
		return $this->getTable()
			->select('group.*, :group_info.*')
			->where('id_language', $idLanguage)
			->fetchAll();
	}

	private function getInfoTable()
	{
		return $this->context->table('group_info');
	}
}
