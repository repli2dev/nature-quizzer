<?php

namespace NatureQuizzer\Presenters;

use NatureQuizzer\Database\Model\Setting;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Utils\DateTime;
use Nextras\Datagrid\Datagrid;
use PDOException;


class SettingPresenter extends BasePresenter
{
	protected $resource = 'content';

	/** @var Setting */
	private $settingModel;

	public function injectBase(Setting $settingModel)
	{
		$this->settingModel = $settingModel;
	}

	public function startup()
	{
		$this->perm();
		parent::startup();
	}

	public function actionEdit($id)
	{
		$data = $this->settingModel->get($id);
		if ($data === FALSE) {
			throw new BadRequestException();
		}
		$values = $data->toArray();
		$this->getComponent('editForm')->setDefaults($values);
		$this->template->data = $data;
	}

	protected function createComponentEditForm()
	{
		$form = $this->prepareForm();
		$form->addSubmit('send', 'Update setting');
		$form->onSuccess[] = $this->editFormSucceeded;
		return $form;
	}

	public function editFormSucceeded(Form $form, $values)
	{
		unset($values['name']);
		$values['updated'] = new DateTime();
		try {
			$this->settingModel->update($this->getParameter('id'), $values);
		} catch (PDOException $ex) {
			throw $ex;
		}
		$this->flashMessage('Setting has been successfully updated.', 'success');
		$this->redirect('default');
	}

	private function prepareForm()
	{
		$form = new Form();
		$form->addGroup('General');
		$form->addText('name', 'Name:')
			->setAttribute('readonly');
		$form->addText('ratio', 'Ratio:')
			->setRequired()
			->addRule(Form::INTEGER, 'Ratio must an integer.')
			->addRule(Form::RANGE, 'Ratio must be non-negative.', [0, PHP_INT_MAX])
			->setOption('description', 'A portion of new users assigned this model.');

		$form->setCurrentGroup(null);
		return $form;
	}

	public function createComponentSettingList()
	{
		$grid = new Datagrid();
		$grid->setRowPrimaryKey('id_setting');
		$grid->setDatasourceCallback(function ($filter, $order) {
			return $this->settingModel->getAll();
		});

		$grid->addCellsTemplate(__DIR__ . '/../grids/setting-list.latte');
		$grid->addColumn('id_setting', 'ID');
		$grid->addColumn('name', 'Name');
		$grid->addColumn('ratio', 'Ratio');
		$grid->addColumn('inserted', 'Inserted');
		$grid->addColumn('updated', 'Last updated');

		return $grid;
	}

}
