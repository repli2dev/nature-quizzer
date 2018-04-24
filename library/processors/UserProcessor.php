<?php
namespace NatureQuizzer\Processors;

use Exception;
use NatureQuizzer\Database\Model\CurrentKnowledge;
use NatureQuizzer\Database\Model\PriorKnowledge;
use NatureQuizzer\Database\Model\Round;
use NatureQuizzer\Database\Model\Model as SettingModel;
use NatureQuizzer\Database\Model\User as UserModel;
use NatureQuizzer\Runtime\CurrentUser;
use Nette\Forms\Form;
use Nette\Object;
use Nette\Security\AuthenticationException;
use Nette\Security\IAuthenticator;
use Nette\Security\Identity;
use Nette\Security\Passwords;
use Nette\Security\User;
use Nette\Utils\DateTime;
use Nette\Utils\Strings;
use Nette\Utils\Validators;
use Tracy\Debugger;

class UserProcessor extends Object
{
	/** @var User */
	private $user;

	/** @var UserModel */
	private $userModel;
	/** @var SettingModel */
	private $settingModel;
	/** @var PriorKnowledge */
	private $priorKnowledge;
	/** @var CurrentKnowledge */
	private $currentKnowledge;

	/** @var CurrentUser */
	private $currentUser;
	/** @var Round */
	private $round;

	public function __construct(User $user, UserModel $userModel, SettingModel $settingModel, Round $round,
								PriorKnowledge $priorKnowledge, CurrentKnowledge $currentKnowledge, CurrentUser $currentUser)
	{
		$this->user = $user;
		$this->userModel = $userModel;
		$this->settingModel = $settingModel;
		$this->currentUser = $currentUser;
		$this->priorKnowledge = $priorKnowledge;
		$this->currentKnowledge = $currentKnowledge;
		$this->round = $round;
	}

	public function profile()
	{
		$output = [];
		// Get current user (will try to initialize if not any)
		$this->currentUser->get();
		// If there is still no user, then something went wrong
		if (!$this->user->isLoggedIn()) {
			$output['status'] = 'fail';
		} else {
			$output['status'] = 'success';
			$output['anonymous'] = $this->user->isInRole(UserModel::GUEST_ROLE);
			$output['id'] = $this->user->getId();
			$output['name'] = $this->user->getIdentity()->name;
			$output['model'] = $this->settingModel->getModelNameByUser($this->user->getId());
			$output['email'] = $this->user->getIdentity()->email;
		}
		return $output;
	}

	public function login($data)
	{
		$output = [];
		$form = $this->getLoginForm();
		$form->setValues($data);
		if (!$form->isSuccess()) {
			$output['errors'] = $form->getErrors();
			$output['status'] = 'fail';
		} else {
			$oldIdentity = $this->user->getIdentity();
			try {
				$this->user->login(Strings::lower($data['email']), $data['password']);
				$output['status'] = 'success';
				$this->reownStuff($oldIdentity, $this->user->getIdentity());
			} catch (AuthenticationException $ex) {
				$output['status'] = 'fail';
				if ($ex->getCode() == IAuthenticator::IDENTITY_NOT_FOUND) {
					$output['result'] = 'user.not_found';
				} elseif ($ex->getCode() == IAuthenticator::INVALID_CREDENTIAL) {
					$output['result'] = 'user.invalid_credentials';
				} elseif ($ex->getCode() == IAuthenticator::FAILURE) {
					$output['result'] = 'user.failure';
				} elseif ($ex->getCode() == IAuthenticator::NOT_APPROVED) {
					$output['result'] = 'user.not_approved';
				}
				// Restore previous identity
				$this->user->login($oldIdentity);
			}
		}
		return $output;
	}

	public function register($data)
	{
		$output = [];
		$form = $this->getRegisterForm();
		$form->setValues($data);
		if (!$form->isSuccess()) {
			$output['errors'] = $form->getErrors();
			$output['status'] = 'fail';
		} else {
			$oldIdentity = $this->user->getIdentity();
			$this->userModel->insert([
				'name' => $data['name'],
				'email' => Strings::lower($data['email']),
				'password' => Passwords::hash($data['password']),
				'inserted' => new DateTime(),
				'anonymous' => FALSE,
				'id_model' => $this->userModel->getModel($oldIdentity->getId())
			]);
			try {
				$this->user->login(Strings::lower($data['email']), $data['password']);
				$this->reownStuff($oldIdentity, $this->user->getIdentity());
			} catch (Exception $ex) {
				$output['status'] = 'fail';
				return $output;
				// Restore previous identity
			}
			$output['status'] = 'success';
		}
		return $output;
	}

	public function logout()
	{
		$output = [
			'status' => 'not-logged',
		];
		if (!$this->currentUser->isInitialized() || $this->currentUser->isAnonymous()) {
			// If the user is not yet initialized or when anonymous the logout takes no effect
			// This is preventing to creating yet another anonymous user in DB
			$output['logout'] = 'success';
		} elseif ($this->user->isLoggedIn()) {
			$this->user->logout(TRUE);
			$output['logout'] = 'success';
		} else {
			$output['logout'] = 'fail';
		}
		return $output;
	}

	/**
	 * Warning: This method is not supposed to be publicly available!
	 * @param $userInfo array Necessary information about the user
	 * @throws Exception if anything goes wrong
	 */
	public function loginViaFacebook($userInfo)
	{
		// Try to find the associated user
		$user = $this->userModel->findByFacebookId($userInfo['id']);
		// Register if no user is found
		if (!$user) {
			// Check if there is an account with the same e-mail
			$userId = $this->userModel->findByEmail(Strings::lower($userInfo['email']));
			// Register new user if not found
			if (!$userId || !Validators::isEmail($userInfo['email'])) {
				$userId = $this->userModel->insert([
					'name' => $userInfo['name'],
					'email' => Validators::isEmail($userInfo['email']) ? Strings::lower($userInfo['email']) : null,
					'inserted' => new DateTime(),
					'anonymous' => FALSE,
					'id_model' => $this->userModel->getModel($this->user->getId())
				]);
			}
			// Expectation: having and user by now
			if (!$userId) {
				throw new Exception('User DB entry expected, no found.');
			}
			// Add token
			$this->userModel->addExternalToken($userId, UserModel::EXTERNAL_FACEBOOK, $userInfo['id']);
			$user = $this->userModel->findByFacebookId($userInfo['id']);
			if (!$user) {
				throw new Exception('User DB entry expected for external login, no found.');
			}
		}
		// Login (expectation having an user now)
		$oldIdentity = $this->user->getIdentity();
		try {
			$identity = $this->userModel->prepareIdentity($user, UserModel::USER_ROLE);
			$this->user->login($identity);
			if ($oldIdentity) {
				$this->reownStuff($oldIdentity, $this->user->getIdentity());
			}
		} catch (Exception $ex) {
			Debugger::log($ex);
			throw new Exception('Login by identity should not fail.', 0, $ex);
		}
	}

	/**
	 * Warning: This method is not supposed to be publicly available!
	 * @param $userInfo array Necessary information about the user
	 * @throws Exception if anything goes wrong
	 */
	public function loginViaGoogle($userInfo)
	{
		// Try to find the associated user
		$user = $this->userModel->findByGoogleId($userInfo['id']);
		// Register if no user is found
		if (!$user) {
			// Check if there is an account with the same e-mail
			$userId = $this->userModel->findByEmail(Strings::lower($userInfo['email']));
			// Register new user if not found
			if (!$userId || !Validators::isEmail($userInfo['email'])) {
				$userId = $this->userModel->insert([
					'name' => $userInfo['name'],
					'email' => Validators::isEmail($userInfo['email']) ? Strings::lower($userInfo['email']) : null,
					'inserted' => new DateTime(),
					'anonymous' => FALSE,
					'id_model' => $this->userModel->getModel($this->user->getId())
				]);
			}
			// Expectation: having and user by now
			if (!$userId) {
				throw new Exception('User DB entry expected, no found.');
			}
			// Add token
			$this->userModel->addExternalToken($userId, UserModel::EXTERNAL_GOOGLE, $userInfo['id']);
			$user = $this->userModel->findByGoogleId($userInfo['id']);
			if (!$user) {
				throw new Exception('User DB entry expected for external login, no found.');
			}
		}
		// Login (expectation having an user now)
		$oldIdentity = $this->user->getIdentity();
		try {
			$identity = $this->userModel->prepareIdentity($user, UserModel::USER_ROLE);
			$this->user->login($identity);
			if ($oldIdentity) {
				$this->reownStuff($oldIdentity, $this->user->getIdentity());
			}
		} catch (Exception $ex) {
			Debugger::log($ex);
			throw new Exception('Login by identity should not fail.', 0, $ex);
		}
	}

	private function reownStuff(Identity $oldIdentity, Identity $newIdentity)
	{
		// TODO: rethink this behaviour, seems to be a bit weird (with different model the migrated data will be not ever used etc.)
		$this->round->reownRounds($oldIdentity->getId(), $newIdentity->getId());
		$this->priorKnowledge->reown($oldIdentity->getId(), $newIdentity->getId());
		$this->currentKnowledge->reown($oldIdentity->getId(), $newIdentity->getId());
	}

	private function getLoginForm()
	{
		$form = new Form();
		$form->addText('email')
			->addRule(Form::FILLED, 'user.empty_email')
			->addRule(Form::EMAIL, 'user.wrong_email_format');
		$form->addText('password')
			->addRule(Form::FILLED, 'user.empty_password');
		return $form;
	}

	private function getRegisterForm()
	{
		$form = new Form();
		$form->addText('name')
			->addRule(Form::FILLED, 'user.empty_name');
		$form->addText('email')
			->addRule(Form::FILLED, 'user.empty_email')
			->addRule(Form::EMAIL, 'user.wrong_email_format')
			->addRule(function ($item) {
				$data = $this->userModel->findByEmail(Strings::lower($item->getValue()));
				return $data === FALSE;
			}, 'user.email_taken');
		$form->addText('password')
			->addRule(Form::FILLED, 'user.empty_password');
		$form->addText('password2')
			->addRule(Form::FILLED, 'user.empty_password2');

		$form['password']
			->addCondition(Form::FILLED)
			->addRule(Form::EQUAL, 'user.password_mismatch', $form['password2']);
		return $form;
	}
}