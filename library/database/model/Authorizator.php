<?php
namespace NatureQuizzer\Database\Model;
use Nette\Security\Authorizator as NetteAuthorizator;
use Nette\Security\Permission;
use Nette\SmartObject;

class Authorizator implements NetteAuthorizator
{

	use SmartObject;

	const ROLE_USER = 'user';
	const ROLE_GUEST = 'guest';
	const ROLE_VIEWER = 'viewer';
	const ROLE_ADMIN = 'admin';

	/** @var Permission */
	private $acl;
	public function __construct(){
		$this->acl = new Permission();

		// Add resources
		$this->acl->addResource('commons');
		$this->acl->addResource("admins");
		$this->acl->addResource("users");
		$this->acl->addResource("content");
		$this->acl->addResource("statistics");
		$this->acl->addResource("exports");

		// Adding of user's role
		$this->acl->addRole(self::ROLE_USER);
		$this->acl->addRole(self::ROLE_GUEST);
		$this->acl->addRole(self::ROLE_VIEWER);
		$this->acl->addRole(self::ROLE_ADMIN);

		// Settings of allowed/denied resources
		$this->acl->allow(self::ROLE_ADMIN);
		$this->acl->allow(self::ROLE_VIEWER, 'commons');
		$this->acl->allow(self::ROLE_VIEWER, 'commons');

		$this->acl->deny(self::ROLE_GUEST);
		$this->acl->deny(self::ROLE_USER);
	}

	public function isAllowed($role = self::ALL, $resource = self::ALL, $privilege = self::ALL): bool
	{
		if(!$this->acl->hasResource($resource)) {
			return self::DENY;
		}
		return $this->acl->isAllowed($role, $resource, $privilege);
	}


}
