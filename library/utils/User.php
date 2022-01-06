<?php

namespace NatureQuizzer\Utils;


use NatureQuizzer\Database\Model\Admin;
use NatureQuizzer\Database\Model\User as UserModel;
use Nette\Security\Authorizator;
use Nette\Security\IUserStorage;
use Nette\Security\User as NetteUser;

class User extends NetteUser
{

	/** @var Admin */
	private $adminAuthenticator;
	/** @var UserModel */
	private $userAuthenticator;


	public function __construct(IUserStorage $storage, UserModel $userAuthenticator = NULL, Admin $adminAuthenticator, Authorizator $authorizator = NULL)
	{
		parent::__construct($storage, $userAuthenticator, $authorizator);
		$this->adminAuthenticator = $adminAuthenticator;
		$this->userAuthenticator = $userAuthenticator;
	}

	public function useAdminAuthenticator()
	{
		$this->setAuthenticator($this->adminAuthenticator);
	}

	public function useUserAuthenticator()
	{
		$this->setAuthenticator($this->userAuthenticator);
	}
}
