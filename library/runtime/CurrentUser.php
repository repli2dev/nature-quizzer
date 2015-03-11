<?php
namespace NatureQuizzer\Runtime;

use NatureQuizzer\Database\Model\User as UserModel;
use Nette\Object;
use Nette\Security\Identity;
use Nette\Security\User;

class CurrentUser extends Object
{
	/** @var User */
	private $user;
	/** @var UserModel */
	private $userModel;

	public function __construct(User $user, UserModel $userModel)
	{
		$this->user = $user;
		$this->userModel = $userModel;
	}

	public function get()
	{
		if ($this->user->isLoggedIn()) {
			return $this->user->getId();
		}
		$user = $this->userModel->insert([
			'anonymous' => true,
		]);
		$this->user->login(NULL, NULL, $user->getPrimary());
		return $this->user->getId();
	}

	public function isInitialized() {
		return $this->user->getIdentity() !== NULL;
	}

	public function isAnonymous()
	{
		$identity = $this->user->getIdentity();
		if (in_array(UserModel::GUEST_ROLE, $identity->getRoles())) {
			return TRUE;
		}
		return FALSE;
	}
}