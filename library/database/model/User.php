<?php

namespace NatureQuizzer\Database\Model;

use InvalidArgumentException;
use Nette\Database\SqlLiteral;
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

	const EXTERNAL_FACEBOOK = 'facebook';
	const EXTERNAL_GOOGLE = 'google';

	public function authenticate(array $credentials)
	{
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

	public function prepareIdentity(ActiveRow $row, $userRole)
	{
		$arr = $row->toArray();
		unset($arr['password']);
		return new Identity($row[self::COLUMN_ID], $userRole, $arr);
	}

	public function findByEmail($email)
	{
		return $this->getTable()->where('LOWER(?) = ?', new SqlLiteral(self::COLUMN_EMAIL), $email)->fetch();
	}

	public function findAnonymousById($id)
	{
		return $this->getTable()->where(self::COLUMN_ID, $id)->where(self::COLUMN_ANONYMOUS, TRUE)->fetch();
	}

	public function findByFacebookId($facebookId)
	{
		return $this->getTable()->where(':user_external.token = ?', self::EXTERNAL_FACEBOOK . ':' . $facebookId)->fetch();
	}

	public function findByGoogleId($googleId)
	{
		return $this->getTable()->where(':user_external.token = ?', self::EXTERNAL_GOOGLE . ':' . $googleId)->fetch();
	}

	public function getModel($userId)
	{
		$row = $this->get($userId);
		if ($row) {
			return $row->id_model;
		}
		return NULL;
	}

	public function addExternalToken($userId, $type, $token)
	{
		if ($type !== self::EXTERNAL_FACEBOOK && $type != self::EXTERNAL_GOOGLE) {
			throw new InvalidArgumentException('[Type] of external token is not valid.');
		}

		$this->context->table('user_external')->insert(
			[
				'id_user' => $userId,
				'token' => $type . ':' . $token
			]
		);
	}
}
