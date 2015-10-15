<?php

namespace NatureQuizzer\Database\Model;

class Package extends Table
{

	public function delete($package)
	{
		$this->context->query('DELETE FROM package_group WHERE name = ?', $package);
		$this->context->query('DELETE FROM package_concept WHERE name = ?', $package);
		$this->context->query('DELETE FROM package_organism WHERE name = ?', $package);
		$this->context->query('DELETE FROM package_organism_representation WHERE name = ?', $package);
	}

	public function add($package, $groups, $concepts, $organisms, $representations)
	{
		$this->context->table('package_group')->insert(array_map(function ($group) use($package) { return ['name' => $package, 'id_group' => $group]; }, $groups));
		$this->context->table('package_concept')->insert(array_map(function ($concept) use($package) { return ['name' => $package, 'id_concept' => $concept]; }, $concepts));
		$this->context->table('package_organism')->insert(array_map(function ($organism) use($package) { return ['name' => $package, 'id_organism' => $organism]; }, $organisms));
		$this->context->table('package_organism_representation')->insert(array_map(function ($representation) use($package) { return ['name' => $package, 'id_representation' => $representation]; }, $representations));
	}

	public function exist($package) {
		$res1 = $this->context->query('SELECT EXISTS(SELECT TRUE FROM package_group WHERE name = ?)', $package)->fetchField();
		$res2 = $this->context->query('SELECT EXISTS(SELECT TRUE FROM package_concept WHERE name = ?)', $package)->fetchField();
		$res3 = $this->context->query('SELECT EXISTS(SELECT TRUE FROM package_organism WHERE name = ?)', $package)->fetchField();
		$res4 = $this->context->query('SELECT EXISTS(SELECT TRUE FROM package_organism_representation WHERE name = ?)', $package)->fetchField();
		return $res1 || $res2 || $res3 || $res4;
	}

	public function leftoverGroups()
	{
		return $this->context->query('
			SELECT * FROM "group" WHERE id_group NOT IN (SELECT id_group FROM package_group)
		')->fetchAll();
	}

	public function leftoverConcepts()
	{
		return $this->context->query('
			SELECT * FROM "concept" WHERE id_concept NOT IN (SELECT id_concept FROM package_concept)
		')->fetchAll();
	}

	public function leftoverOrganisms()
	{
		return $this->context->query('
			SELECT * FROM "organism" WHERE id_organism NOT IN (SELECT id_organism FROM package_organism)
		')->fetchAll();
	}

	public function leftoverRepresentations()
	{
		return $this->context->query('
			SELECT * FROM "organism_representation" WHERE id_representation NOT IN (SELECT id_representation FROM package_organism_representation)
		')->fetchAll();
	}

	public function clean($groups, $concepts, $organisms, $representations)
	{
		if (count($groups) > 0) {
			$this->context->query('DELETE FROM "group" WHERE id_group IN (?)', $groups);
		}
		if (count($concepts) > 0 ) {
			$this->context->query('DELETE FROM "concept" WHERE id_concept IN (?)', $concepts);
		}
		if (count($organisms) > 0 ) {
			$this->context->query('DELETE FROM "organism" WHERE id_organism IN (?)', $organisms);
		}
		if (count($representations) > 0) {
			$this->context->query('DELETE FROM "organism_representation" WHERE id_representation IN (?)', $representations);
		}
	}

	public function stats()
	{
		return $this->context->query('
			SELECT
				(SELECT COUNT(*) FROM organism) AS organism_count,
				(SELECT COUNT(*) FROM organism_representation) AS representation_count,
				(SELECT COUNT(*) FROM "group") AS group_count,
				(SELECT COUNT(*) FROM concept) AS concept_count
		')->fetch();
	}

	public function getTrackedRepresentations()
	{
		return $this->context->query('
			SELECT DISTINCT id_representation FROM package_organism_representation
		')->fetchPairs(NULL, 'id_representation');
	}
}
