<?php

namespace NatureQuizzer\Presenters;

use NatureQuizzer\Database\Model\Model;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Utils\DateTime;
use Nextras\Datagrid\Datagrid;
use PDOException;


class ModelPresenter extends BasePresenter
{
	protected $resource = 'content';

	/** @var Model */
	private $model;

	public function injectBase(Model $model)
	{
		$this->model = $model;
	}

	public function startup()
	{
		$this->perm();
		parent::startup();
	}

	public function actionEdit($id)
	{
		$data = $this->model->get($id);
		if ($data === NULL) {
			throw new BadRequestException();
		}
		$values = $data->toArray();
		/** @var Form $form */
		$form = $this->getComponent('editForm');
		$form->setDefaults($values);
		$this->template->data = $data;
	}

	protected function createComponentEditForm()
	{
		$form = $this->prepareForm();
		$form->addSubmit('send', 'Update model setting');
		$form->onSuccess[] = [$this, 'editFormSucceeded'];
		return $form;
	}

	public function editFormSucceeded(Form $form, $values)
	{
		unset($values['name']);
		$values['updated'] = new DateTime();
		try {
			$this->model->update($this->getParameter('id'), $values);
		} catch (PDOException $ex) {
			throw $ex;
		}
		$this->flashMessage('Model settings has been successfully updated.', 'success');
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
		$grid->setRowPrimaryKey('id_model');
		$grid->setDatasourceCallback(function ($filter, $order) {
			return $this->model->getAll();
		});

		$grid->addCellsTemplate(__DIR__ . '/../grids/model-list.latte');
		$grid->addColumn('id_model', 'ID');
		$grid->addColumn('name', 'Name');
		$grid->addColumn('ratio', 'Ratio');
		$grid->addColumn('inserted', 'Inserted');
		$grid->addColumn('updated', 'Last updated');

		return $grid;
	}

}
