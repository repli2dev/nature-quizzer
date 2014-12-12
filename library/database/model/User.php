<?php

namespace NatureQuizzer\Database\Model;

use Nette\Database\Table\ActiveRow;
use Nette\NotImplementedException;
use Nette\Security\AuthenticationException;
use Nette\Security\IAuthenticator;
use Nette\Security\Identity;
use Nette\Security\Passwords;

class User extends Table implements IAuthenticator
{
	const ID = 2;

	const COLUMN_ID = 'id_user';
	const COLUMN_NAME = 'name';
	const COLUMN_EMAIL = 'email';
	const COLUMN_ANONYMOUS = 'anonymous';

	const GUEST_ROLE = 'guest';
	const USER_ROLE = 'user';

	public function authenticate(array $credentials)
	{
		fdump($credentials);
		if (isset($credentials[self::ID])) {
			$row = $this->findAnonymousById($credentials[self::ID]);
			if (!$row) {
				throw new AuthenticationException('No such user with ID [' . $credentials[self::ID] . '].', self::IDENTITY_NOT_FOUND);
			}
			return $this->prepareIdentity($row, self::GUEST_ROLE);
		} elseif (isset($credentials[self::USERNAME])) {
			$row = $this->findByEmail($credentials[self::USERNAME]);
			if ($row === FALSE) {
				throw new AuthenticationException('No such user with e-mail [' .$credentials[self::USERNAME]. '].', self::IDENTITY_NOT_FOUND);
			}
			if (!Passwords::verify($credentials[self::PASSWORD], $row->password)) {
				throw new AuthenticationException('Wrong password', self::INVALID_CREDENTIAL);
			}
			return $this->prepareIdentity($row, self::USER_ROLE);
		}
		throw new NotImplementedException('Other methods of authentication are not implemented yet.');
	}

	private function prepareIdentity(ActiveRow $row, $userRole)
	{
		$arr = $row->toArray();
		unset($arr['password']);
		return new Identity($row[self::COLUMN_ID], $userRole, $arr);
	}

	public function findByEmail($email)
	{
		return $this->getTable()->where(self::COLUMN_EMAIL, $email)->fetch();
	}

	public function findAnonymousById($id)
	{
		return $this->getTable()->where(self::COLUMN_ID, $id)->where(self::COLUMN_ANONYMOUS, TRUE)->fetch();
	}
}
