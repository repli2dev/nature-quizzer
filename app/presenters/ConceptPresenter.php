<?php

namespace NatureQuizzer\Presenters;

use NatureQuizzer\Database\Model\Concept;
use NatureQuizzer\Database\Model\Group;
use NatureQuizzer\Database\Model\Language;
use NatureQuizzer\Database\Model\Organism;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nextras\Datagrid\Datagrid;
use PDOException;


class ConceptPresenter extends BasePresenter
{
	protected $resource = 'content';

	/** @var Concept */
	private $conceptModel;
	/** @var Language */
	private $languageModel;
	/** @var Organism */
	private $organismModel;
	/** @var Group */
	private $groupModel;

	public function injectBase(Concept $conceptModel, Language $languageModel, Organism $organismModel, Group $groupModel)
	{
		$this->conceptModel = $conceptModel;
		$this->languageModel = $languageModel;
		$this->organismModel = $organismModel;
		$this->groupModel = $groupModel;
	}

	public function startup()
	{
		$this->perm();
		parent::startup();
	}

	public function actionEdit($id)
	{
		$data = $this->conceptModel->get($id);
		if ($data === NULL) {
			throw new BadRequestException();
		}
		$values = $data->toArray();
		$values['infos'] = $this->conceptModel->getInfos($id);
		$this->getComponent('editForm')->setDefaults($values);
		$this->template->data = $data;
	}

	public function actionManage($id)
	{
		$data = $this->conceptModel->get($id);
		if ($data === NULL) {
			throw new BadRequestException();
		}
		$this->template->data = $data;
		$conceptOrganisms = $this->organismModel->getFromConcept($this->getParameter('id'));
		$checked = [];
		foreach ($conceptOrganisms as $row) {
			$checked[$row->id_organism] = true;
		}
		$this->getComponent('organisms')->setDefaults(['organisms' => $checked]);
	}

	public function actionDelete($id)
	{
		$data = $this->conceptModel->get($id);
		if ($data === NULL) {
			throw new BadRequestException();
		}
		$this->template->data = $data;
	}

	protected function createComponentAddForm()
	{
		$form = $this->prepareForm();
		$form->addSubmit('send', 'Add concept');
		$form->onSuccess[] = [$this, 'addFormSucceeded'];
		return $form;
	}

	public function addFormSucceeded(Form $form, $values)
	{
		$infos = $values['infos'];
		unset($values['infos']);
		$values['quick'] = $values['quick'] ? 'true' : 'false';
		$values['warning'] = $values['warning'] ? 'true' : 'false';
		try {
			$this->conceptModel->insert($values, $infos);
		} catch (PDOException $ex) {
			if ($ex->errorInfo[0] != 23505) {
				throw $ex;
			} else {
				$form->addError('This code name is already used. Please select different one.');
			}
			return;
		}
		$this->flashMessage('Concept has been successfully added.', 'success');
		$this->redirect('default');
	}

	protected function createComponentEditForm()
	{
		$form = $this->prepareForm();
		$form->addSubmit('send', 'Update concept');
		$form->onSuccess[] = [$this, 'editFormSucceeded'];
		return $form;
	}

	public function editFormSucceeded(Form $form, $values)
	{
		$infos = $values['infos'];
		unset($values['infos']);
		$values['quick'] = $values['quick'] ? 'true' : 'false';
		$values['warning'] = $values['warning'] ? 'true' : 'false';
		try {
			$this->conceptModel->update($this->getParameter('id'), $values, $infos);
		} catch (PDOException $ex) {
			if ($ex->errorInfo[0] != 23505) {
				throw $ex;
			} else {
				$form->addError('This code name is already used. Please select different one.');
			}
			return;
		}
		$this->flashMessage('Concept has been successfully updated.', 'success');
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
			$this->conceptModel->delete($this->getParameter('id'));
			$this->flashMessage('Concept has been successfully deleted.', 'success');
		}
		$this->redirect('default');
	}

	private function prepareForm()
	{
		$form = new Form();
		$form->addGroup('General');
		$form->addText('code_name', 'Code name:')
			->setRequired('Please enter concept code name.');
		$form->addSelect('id_group', 'Group', $this->groupModel->getPairs())
			->setPrompt('No group');
		$form->addCheckbox('quick', 'Favourite');
		$form->addCheckbox('warning', 'Warning');

		$form->setCurrentGroup(null);

		$languagePairs = $this->languageModel->getLanguagePairs();
		$infos = $form->addContainer('infos');
		foreach ($languagePairs as $langId => $langName) {
			$inner = $infos->addContainer($langId);
			$group = $form->addGroup($langName);
			$inner->setCurrentGroup($group);
			$inner->addText('name', 'Name');
			$inner->addText('description', 'Description');
		}

		$form->setCurrentGroup(null);
		return $form;
	}

	public function createComponentConceptList()
	{
		$grid = new Datagrid();
		$grid->setRowPrimaryKey('id_concept');
		$grid->setDatasourceCallback(function ($filter, $order) {
			return $this->conceptModel->getAll();
		});

		$grid->addCellsTemplate(__DIR__ . '/../grids/concepts-list.latte');
		$grid->addColumn('id_concept', 'ID');
		$grid->addColumn('code_name', 'Code name');
		$grid->addColumn('quick', 'Favourite');
		$grid->addColumn('warning', 'Warning');

		return $grid;
	}

	public function createComponentOrganisms()
	{
		$form = new Form();
		$container = $form->addContainer('organisms');
		$organisms = $this->organismModel->getAll();
		foreach ($organisms as $row) {
			$container->addCheckbox($row->id_organism, $row->latin_name);
		}
		$form->addSubmit('send', 'Update');
		$form->onSuccess[] = [$this, 'organismsSucceeded'];
		return $form;
	}

	public function organismsSucceeded($form, $values)
	{
		$organisms = iterator_to_array($values['organisms']);
		$this->organismModel->syncBelonging($this->getParameter('id'), $organisms);
		$this->flashMessage('Matching of organisms to concept has been successfully updated.');
		//$this->redirect('this');
	}

}
