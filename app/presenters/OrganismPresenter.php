<?php

namespace NatureQuizzer\Presenters;

use NatureQuizzer\Database\Model\Language;
use NatureQuizzer\Database\Model\Organism;
use NatureQuizzer\Database\Model\OrganismCommonness;
use NatureQuizzer\Database\Utils\LanguageLookup;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\DateTime;
use Nextras\Datagrid\Datagrid;
use PDOException;


class OrganismPresenter extends BasePresenter
{
	protected $resource = 'content';

	/** @var Organism */
	private $organismModel;
	/** @var OrganismCommonness */
	private $organismCommonness;
	/** @var Language */
	private $languageModel;
	/** @var LanguageLookup */
	private $languageLookup;

	public function injectBase(Organism $organismModel, OrganismCommonness $organismCommonness, Language $languageModel, LanguageLookup $languageLookup)
	{
		$this->organismModel = $organismModel;
		$this->organismCommonness = $organismCommonness;
		$this->languageModel = $languageModel;
		$this->languageLookup = $languageLookup;
	}

	public function startup()
	{
		$this->perm();
		parent::startup();
	}

	public function actionEdit($id)
	{
		$data = $this->organismModel->get($id);
		if ($data === NULL) {
			throw new BadRequestException();
		}
		$values = $data->toArray();
		$values['infos'] = $this->organismModel->getInfos($id);
		$this->getComponent('editForm')->setDefaults($values);
		$this->template->data = $data;

		$representations = $this->organismModel->getRepresentations($id);
		$this->template->representations = $representations;
	}

	public function actionDelete($id)
	{
		$data = $this->organismModel->get($id);
		if ($data === NULL) {
			throw new BadRequestException();
		}
		$this->template->data = $data;
	}

	protected function createComponentAddForm()
	{
		$form = $this->prepareForm();
		$form->addSubmit('send', 'Add organism');
		$form->onSuccess[] = [$this, 'addFormSucceeded'];
		return $form;
	}

	public function addFormSucceeded(Form $form, $values)
	{
		$infos = $values['infos'];
		unset($values['infos']);
		try {
			$this->organismModel->insert($values, $infos);
		} catch (PDOException $ex) {
			throw $ex;
			return;
		}
		$this->flashMessage('Organism has been successfully added.', 'success');
		$this->redirect('default');
	}

	protected function createComponentEditForm()
	{
		$form = $this->prepareForm();
		$form->addSubmit('send', 'Update organism');
		$form->onSuccess[] = [$this, 'editFormSucceeded'];
		return $form;
	}

	public function editFormSucceeded(Form $form, $values)
	{
		$infos = $values['infos'];
		unset($values['infos']);
		try {
			$this->organismModel->update($this->getParameter('id'), $values, $infos);
		} catch (PDOException $ex) {
			throw $ex;
			return;
		}
		$this->flashMessage('Organism has been successfully updated.', 'success');
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
			$this->organismModel->delete($this->getParameter('id'));
			$this->flashMessage('Organism has been successfully deleted.', 'success');
		}
		$this->redirect('default');
	}

	private function prepareForm()
	{
		$form = new Form();
		$form->addGroup('General');
		$form->addText('latin_name', 'Latin name:')
			->setRequired('Please enter latin name of organism.');

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

	public function createComponentAddRepresentationForm()
	{
		$form = new Form();
		$form->addGroup('New representation');
		$form->addUpload('file', 'Image')
			->setRequired('Please choose file.')
			->addRule($form::IMAGE, 'File has to be an image.');

		$form->addGroup('Meta information');
		$form->addText('source', 'Source');
		$form->addText('rights_holder', 'Držitel práv');
		$form->addText('license', 'Licence');

		$form->setCurrentGroup(null);
		$form->addSubmit('send', 'Add');
		$form->onSuccess[] = [$this, 'addRepresentationFormSucceeded'];
		return $form;
	}

	public function addRepresentationFormSucceeded($form, $values)
	{
		$file = $values['file'];
		unset($values['file']);
		if ($file->isOk()) {
			$values['hash'] = $hash = hash_file('sha512', $file->getTemporaryFile());
			if ($this->organismModel->representationExists($hash)) {
				$form->addError('THis representation is already inserted in the database.');
				return;
			}
			$representation = $this->organismModel->addRepresentation($this->getParameter('id'), $values);
			$file->move(__DIR__ . '/../../www/images/organisms/' . $representation->id_representation);

			$this->flashMessage('Representation has been successfully added.');
			$this->redirect('this');
		} else {
			$form->addError('Representation was not added due to upload problem.');
		}

	}
	public function handledeleteRepresentation($key)
	{
		$fileName = __DIR__ . '/../../www/images/organisms/' . $key;
		if (file_exists($fileName)) {
			@unlink($fileName);
		}
		$this->organismModel->deleteRepresentation($key);
		$this->flashMessage('Representation has been successfully deleted.');
		$this->redirect('this');
	}

	public function createComponentOrganismList()
	{
		$grid = new Datagrid();
		$grid->setRowPrimaryKey('id_organism');
		$grid->setDatasourceCallback(function ($filter, $order) {
			return $this->organismModel->getAllWithName($this->languageLookup->getId(AdminPresenter::LANG));
		});

		$grid->addCellsTemplate(__DIR__ . '/../grids/organism-list.latte');
		$grid->addColumn('id_organism', 'ID');
		$grid->addColumn('name', 'Name');
		$grid->addColumn('latin_name', 'Latin name');
		$grid->addColumn('value', 'Preferability');

		return $grid;
	}

	public function actionChangePreferability($organism, $value)
	{
		// This method is quite hack as the Datagrid has limited support for ajax from inside cells
		if (!is_numeric($organism) || !is_numeric($value)) {
			return;
		}
		$this->organismCommonness->setValue($organism, $value);
		$this->setView('default');
		$this->getComponent('organismList')->redrawRow($organism);
	}

}
