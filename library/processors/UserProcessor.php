<?php
namespace NatureQuizzer\Processors;

use Exception;
use NatureQuizzer\Database\Model\User as UserModel;
use NatureQuizzer\Runtime\CurrentUser;
use Nette\Forms\Form;
use Nette\Object;
use Nette\Security\AuthenticationException;
use Nette\Security\IAuthenticator;
use Nette\Security\Passwords;
use Nette\Security\User;
use Nette\Utils\DateTime;

class UserProcessor extends Object
{
	/** @var User */
	private $user;

	/** @var UserModel */
	private $userModel;

	/** @var CurrentUser */
	private $currentUser;

	public function __construct(User $user, UserModel $userModel, CurrentUser $currentUser)
	{
		$this->user = $user;
		$this->userModel = $userModel;
		$this->currentUser = $currentUser;
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
				$this->user->login($data['email'], $data['password']);
				$output['status'] = 'success';
				// TODO: reown stuff
			} catch (AuthenticationException $ex) {
				$output['status'] = 'fail';
				if ($ex->getCode() == IAuthenticator::IDENTITY_NOT_FOUND) {
					$output['result'] = 'Identity not found';
				} elseif ($ex->getCode() == IAuthenticator::INVALID_CREDENTIAL) {
					$output['result'] = 'Invalid credential';
				} elseif ($ex->getCode() == IAuthenticator::FAILURE) {
					$output['result'] = 'Failure';
				} elseif ($ex->getCode() == IAuthenticator::NOT_APPROVED) {
					$output['result'] = 'Not approved';
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
			$this->userModel->insert([
				'name' => $data['name'],
				'email' => $data['email'],
				'password' => Passwords::hash($data['password']),
				'inserted' => new DateTime(),
				'anonymous' => TRUE
			]);
			try {
				$this->user->login($data['email'], $data['password']);
				// reown stuff
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
	 */
	public function loginViaFacebook($userInfo)
	{
		// Try to find the associated user
		$user = $this->userModel->findByFacebookId($userInfo['id']);
		// Register if no user is found
		if (!$user) {
			// Pottentialy error of duplicate e-mail
			$userId = $this->userModel->insert([
				'name' => $userInfo['name'],
				'email' => $userInfo['email'],
				'inserted' => new DateTime(),
				'anonymous' => FALSE
			]);
			if (!$userId) {
				// Error 1
			}
			$this->userModel->addExternalToken($userId, UserModel::EXTERNAL_FACEBOOK, $userInfo['id']);
			$user = $this->userModel->findByFacebookId($userInfo['id']);
			if (!$user) {
				// Error 2
			}
		}
		// And now... Login!
	}

	private function getLoginForm()
	{
		$form = new Form();
		$form->addText('email')
			->addRule(Form::FILLED, 'Please fill in the e-mail.')
			->addRule(Form::EMAIL, 'E-mail must be in proper format: someone@somewhere.tld.');
		$form->addText('password')
			->addRule(Form::FILLED, 'Please fill in the password.');
		return $form;
	}

	private function getRegisterForm()
	{
		$form = new Form();
		$form->addText('name')
			->addRule(Form::FILLED, 'Please fill in the name');
		$form->addText('email')
			->addRule(Form::FILLED, 'Please fill in the e-mail.')
			->addRule(Form::EMAIL, 'E-mail must be in proper format: someone@somewhere.tld.')
			->addRule(function ($item) {
				$data = $this->userModel->findByEmail($item->getValue());
				return $data === FALSE;
			}, 'This e-mail is already registered.');
		$form->addText('password')
			->addRule(Form::FILLED, 'Please fill in the password.');
		$form->addText('password2')
			->addRule(Form::FILLED, 'Please fill in the password for check.');

		$form['password']
			->addCondition(Form::FILLED)
			->addRule(Form::EQUAL, 'Passwords have to match each other.', $form['password2']);
		return $form;
	}
}