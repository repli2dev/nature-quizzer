<?php
namespace NatureQuizzer\Database\Model;

use Nette\Object;
use Nette\Security\IAuthorizator;
use Nette\Security\Permission;

class Authorizator extends Object implements IAuthorizator {

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

		// Adding of user's role
		$this->acl->addRole('guest');
		$this->acl->addRole('viewer');
		$this->acl->addRole('admin');

		// Settings of allowed/denied resources
		$this->acl->allow('admin');
		$this->acl->allow('viewer', 'commons');
		$this->acl->allow('viewer', 'commons');

		$this->acl->deny('guest');
	}

	public function isAllowed($role = self::ALL, $resource = self::ALL, $privilege = self::ALL) {
		if(!$this->acl->hasResource($resource)) {
			return self::DENY;
		}
		return $this->acl->isAllowed($role, $resource, $privilege);
	}


}