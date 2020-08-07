<?php
namespace NatureQuizzer\Runtime;

use NatureQuizzer\Database\Model\Authorizator;
use NatureQuizzer\Database\Model\Model as ModelModel;
use NatureQuizzer\Database\Model\User as UserModel;
use Nette\Security\User;
use Nette\SmartObject;

class CurrentUser
{
	use SmartObject;

	/** @var User */
	private $user;
	/** @var UserModel */
	private $userModel;
	/** @var ModelModel */
	private $modelModel;

	public function __construct(User $user, UserModel $userModel, ModelModel $settingModel)
	{
		$this->user = $user;
		$this->userModel = $userModel;
		$this->modelModel = $settingModel;
	}

	public function get()
	{
		if ($this->user->isInRole(Authorizator::ROLE_ADMIN) || $this->user->isInRole(Authorizator::ROLE_VIEWER)) {
			$this->user->logout(TRUE);
		}
		if ($this->user->isLoggedIn()) {
			return $this->user->getId();
		}
		$user = $this->userModel->insert([
			'anonymous' => 'true',
			'id_model' => $this->modelModel->getRandomModel()
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