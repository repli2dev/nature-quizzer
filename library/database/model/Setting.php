<?php

namespace NatureQuizzer\Database\Model;

use InvalidArgumentException;
use Nette\Database\Table\ActiveRow;
use Nette\NotImplementedException;
use Nette\Security\AuthenticationException;
use Nette\Security\IAuthenticator;
use Nette\Security\Identity;
use Nette\Security\Passwords;

class Setting extends Table
{
	public function getAll()
	{
		return $this->getTable()->order('id_setting ASC')->fetchAll();
	}

	public function getModelNameByUser($userId)
	{
		return $this->getConnection()->query('SELECT "name" FROM setting WHERE id_setting IN (SELECT id_setting FROM "user" WHERE id_user = ?)', $userId)->fetch();
	}

	public function getRandomSetting()
	{
		$settings = $this->getAll();

		$totalWeight = array_reduce($settings, function($carry, $row) { return $carry + $row->ratio; }, 0);
		$random = rand(1, $totalWeight);
		$current = 0;
		foreach ($settings as $setting) {
			$current += $setting->ratio;
			if ($random <= $current) {
				return $setting->id_setting;
			}
		}
		return NULL;
	}
}
