<?php

namespace NatureQuizzer\Presenters;

use NatureQuizzer\Database\Model\Admin;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Security\AuthenticationException;
use Nette\Security\Passwords;
use Nextras\Datagrid\Datagrid;
use PDOException;


class AdminPresenter extends BasePresenter
{
	const LANG = 'cs';

	protected $resource = 'commons';

	/** @var Admin */
	private $userManager;

	/** @var Passwords */
	private $passwords;

	public function injectBase(Admin $userManager, Passwords $passwords)
	{
		$this->userManager = $userManager;
		$this->passwords = $passwords;
	}

	public function startup()
	{
		parent::startup();
		$this->getUser()->useAdminAuthenticator();
	}


	public function actionDefault()
	{
		$this->perm();
	}

	public function actionList()
	{
		$this->perm('admins');
	}

	public function actionAdd()
	{
		$this->perm('admins');
	}

	public function actionEdit($id)
	{
		$this->perm('admins');
		$data = $this->userManager->get($id);
		if($data === NULL) {
			throw new BadRequestException();
		}
		$this->getComponent('editForm')->setDefaults($data);
		$this->template->data = $data;
	}

	public function actionDelete($id)
	{
		$this->perm('admins');
		$data = $this->userManager->get($id);
		if($data === NULL) {
			throw new BadRequestException();
		}
		$this->template->data = $data;
	}

	public function actionLogout()
	{
		$this->perm();

		$this->user->logout();
		$this->flashMessage('Logout successful.');
		$this->redirect('login');
	}
	public function actionPassword()
	{
		$this->perm();
	}

	protected function createComponentChangeForm() {
		$form = new Form;
		$form->addPassword('old', 'Current password')
			->setRequired('Please enter your current password.');
		$form->addPassword('new', 'New password')
			->setRequired('Please enter your new password.');
		$form->addPassword('new2', 'New password again')
			->setRequired('Please enter your new password again for check.')
			->addRule(\Nette\Forms\Form::EQUAL, 'New passwords has to match.', $form['new']);
		$form->addSubmit('send', 'Change password');
		$form->onSuccess[] = [$this, 'changeFormSucceeded'];
		return $form;
	}


	public function changeFormSucceeded($form, $values) {
		$user = $this->userManager->get($this->user->getId());
		if(!$this->passwords->verify($values->old, $user->password)) {
			$form->addError('Old password is wrong, please try again.');
			return;
		}
		$data = ['password' => $this->passwords->hash($values->new)];
		$this->userManager->update($this->user->getId(), $data);
		$this->flashMessage('Password has been successfully changed.', 'success');
		$this->redirect('this');
	}


	protected function createComponentLoginForm()
	{
		$form = new Form;
		$form->addText('username', 'Username:')
			->setRequired('Please enter username.');

		$form->addPassword('password', 'Password:')
			->setRequired('Please enter password.');


		$form->addSubmit('send', 'Login');

		$form->onSuccess[] = [$this, 'loginFormSucceeded'];
		return $form;
	}

	public function loginFormSucceeded($form)
	{
		$values = $form->getValues();
		try {
			$this->getUser()->login($values->username, $values->password);
			$this->redirect('Admin:');

		} catch (AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}

	protected function createComponentAddForm() {
		$form = $this->prepareForm();
		$form['password']->setRequired('Enter the password.');
		$form->addSubmit('send', 'Add user');
		$form->onSuccess[] = [$this, 'addFormSucceeded'];
		return $form;
	}

	public function addFormSucceeded(Form $form, $values) {
		$values['password'] = $this->passwords->hash($values['password']);

		try {
			$this->userManager->insert($values);
		} catch(PDOException $ex) {
			$form->addError('This username is already used. Please select different one.');
			return;
		}
		$this->flashMessage('User has been successfully added.', 'success');
		$this->redirect('list');
	}

	protected function createComponentEditForm() {
		$form = $this->prepareForm();
		$form['password']->setOption('description', '(only when changes)');
		$form->addSubmit('send', 'Update user');
		$form->onSuccess[] = [$this, 'editFormSucceeded'];
		return $form;
	}

	public function editFormSucceeded(Form $form) {
		$values = $form->getValues();
		if(empty($values['password'])) {
			unset($values['password']);
		} else {
			$values['password'] = $this->passwords->hash($values['password']);
		}
		try {
			$this->userManager->update($this->getParameter('id'), $values);
		} catch(PDOException $ex) {
			$form->addError('This username is already used. PLease select different one.');
			return;
		}
		$this->flashMessage('User has been successfully updated.', 'success');
		$this->redirect('list');
	}

	protected function createComponentDeleteForm() {
		$form = new Form();
		$form->addSubmit('yes', 'Yes');
		$form->addSubmit('no', 'No');
		$form->onSuccess[] = [$this, 'deleteFormSucceeded'];
		return $form;
	}

	public function deleteFormSucceeded($form, $values) {
		$userCount = count($this->userManager->getAll());
		if($userCount == 1) {
			$form->addError('Last user cannot be deleted.');
			return;
		}
		if($form['yes']->isSubmittedBy()) {
			$this->userManager->delete($this->getParameter('id'));
			$this->flashMessage('User has been successfully deleted.', 'success');
		}
		$this->redirect('list');
	}

	private function prepareForm() {
		$form = new Form();
		$form->addGroup('Login');
		$form->addText('username', 'Username:')
			->setRequired('Please enter password.');
		$form->addPassword('password', 'Password:');

		$form->addSelect('role', 'Role:', Admin::getRoles())
			->setRequired('Please select role.');

		$form->setCurrentGroup(null);
		return $form;
	}

	public function createComponentAdminList()
	{
		$grid = new Datagrid();
		$grid->setRowPrimaryKey('id_admin');
		$grid->setDatasourceCallback(function($filter, $order) {
			return $this->userManager->getAll();
		});

		$grid->addCellsTemplate(__DIR__  . '/../grids/admins-list.latte');
		$grid->addColumn('id_admin', 'ID');
		$grid->addColumn('username', 'Username');
		$grid->addColumn('role', 'Role');

		return $grid;
	}

}
