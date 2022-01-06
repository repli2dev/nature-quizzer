<?php

namespace NatureQuizzer\Presenters;

use NatureQuizzer\Database\Model\Group;
use NatureQuizzer\Database\Model\Language;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nextras\Datagrid\Datagrid;
use PDOException;


class GroupPresenter extends BasePresenter
{
	protected $resource = 'content';

	/** @var Group */
	private $groupModel;
	/** @var Language */
	private $languageModel;

	public function injectBase(Language $languageModel, Group $groupModel)
	{
		$this->languageModel = $languageModel;
		$this->groupModel = $groupModel;
	}

	public function startup()
	{
		$this->perm();
		parent::startup();
	}

	public function actionEdit($id)
	{
		$data = $this->groupModel->get($id);
		if ($data === NULL) {
			throw new BadRequestException();
		}
		$values = $data->toArray();
		$values['infos'] = $this->groupModel->getInfos($id);
		/** @var Form $form */
		$form = $this->getComponent('editForm');
		$form->setDefaults($values);
		$this->template->data = $data;
	}

	public function actionDelete($id)
	{
		$data = $this->groupModel->get($id);
		if ($data === NULL) {
			throw new BadRequestException();
		}
		$this->template->data = $data;
	}

	protected function createComponentAddForm()
	{
		$form = $this->prepareForm();
		$form->addSubmit('send', 'Add group');
		$form->onSuccess[] = [$this, 'addFormSucceeded'];
		return $form;
	}

	public function addFormSucceeded(Form $form, $values)
	{
		$infos = $values['infos'];
		unset($values['infos']);
		try {
			$this->groupModel->insert($values, $infos);
		} catch (PDOException $ex) {
			if ($ex->errorInfo[0] != 23505) {
				throw $ex;
			} else {
				$form->addError('This code name is already used. Please select different one.');
			}
			return;
		}
		$this->flashMessage('Group has been successfully added.', 'success');
		$this->redirect('default');
	}

	protected function createComponentEditForm()
	{
		$form = $this->prepareForm();
		$form->addSubmit('send', 'Update group');
		$form->onSuccess[] = [$this, 'editFormSucceeded'];
		return $form;
	}

	public function editFormSucceeded(Form $form, $values)
	{
		$infos = $values['infos'];
		unset($values['infos']);
		try {
			$this->groupModel->update($this->getParameter('id'), $values, $infos);
		} catch (PDOException $ex) {
			if ($ex->errorInfo[0] != 23505) {
				throw $ex;
			} else {
				$form->addError('This code name is already used. Please select different one.');
			}
			return;
		}
		$this->flashMessage('Group has been successfully updated.', 'success');
		$this->redirect('default');
	}

	protected function createComponentDeleteForm()
	{
		$form = new Form();
		$form->addSubmit('yes', 'Yes');
		$form->addSubmit('no', 'No');
		$form->onSuccess[] = [$this, 'deleteFormSucceeded'];
		return $form;
	}

	public function deleteFormSucceeded($form, $values)
	{
		if ($form['yes']->isSubmittedBy()) {
			$this->groupModel->delete($this->getParameter('id'));
			$this->flashMessage('Group has been successfully deleted.', 'success');
		}
		$this->redirect('default');
	}

	private function prepareForm()
	{
		$form = new Form();
		$form->addGroup('General');
		$form->addText('code_name', 'Code name:')
			->setRequired('Please enter concept code name.');

		$form->setCurrentGroup(null);

		$languagePairs = $this->languageModel->getLanguagePairs();
		$infos = $form->addContainer('infos');
		foreach ($languagePairs as $langId => $langName) {
			$inner = $infos->addContainer($langId);
			$group = $form->addGroup($langName);
			$inner->setCurrentGroup($group);
			$inner->addText('name', 'Name');
		}

		$form->setCurrentGroup(null);
		return $form;
	}

	public function createComponentGroupList()
	{
		$grid = new Datagrid();
		$grid->setRowPrimaryKey('id_group');
		$grid->setDatasourceCallback(function ($filter, $order) {
			return $this->groupModel->getAll();
		});

		$grid->addCellsTemplate(__DIR__ . '/../grids/groups-list.latte');
		$grid->addColumn('id_group', 'ID');
		$grid->addColumn('code_name', 'Code name');

		return $grid;
	}

}
