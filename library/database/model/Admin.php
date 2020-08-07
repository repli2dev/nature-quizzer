<?php

namespace NatureQuizzer\Database\Model;

use Nette\Security\AuthenticationException;
use Nette\Security\IAuthenticator;
use Nette\Security\Identity;
use Nette\Security\IIdentity;

class Admin extends Table implements IAuthenticator
{
	const
		TABLE_NAME = 'admin',
		COLUMN_ID = 'id_admin',
		COLUMN_NAME = 'username',
		COLUMN_PASSWORD_HASH = 'password',
		COLUMN_ROLE = 'role';

	public static function getRoles() {
		return [
			'admin' => 'Admin',
			'viewer' => 'Viewer',
			'guest' => 'Guest'
		];
	}

	public function authenticate(array $credentials): IIdentity
	{
		list($username, $password) = $credentials;

		$row = $this->context->table(self::TABLE_NAME)->where(self::COLUMN_NAME, $username)->fetch();

		if (!$row) {
			throw new AuthenticationException('The username is incorrect.', self::IDENTITY_NOT_FOUND);

		} elseif (!$this->passwords->verify($password, $row[self::COLUMN_PASSWORD_HASH])) {
			throw new AuthenticationException('The password is incorrect.', self::INVALID_CREDENTIAL);

		} elseif ($this->passwords->needsRehash($row[self::COLUMN_PASSWORD_HASH])) {
			$row->update(array(
				self::COLUMN_PASSWORD_HASH => $this->passwords->hash($password),
			));
		}

		$arr = $row->toArray();
		unset($arr[self::COLUMN_PASSWORD_HASH]);
		return new Identity($row[self::COLUMN_ID], $arr[self::COLUMN_ROLE], $arr);
	}

}
