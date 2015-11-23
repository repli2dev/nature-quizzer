<?php

namespace NatureQuizzer\Presenters;

use NatureQuizzer\Database\Model\Answer;
use NatureQuizzer\Database\Model\Model;
use NatureQuizzer\Database\Model\Organism;
use NatureQuizzer\Database\Model\Round;
use NatureQuizzer\Database\Utils\LanguageLookup;
use NatureQuizzer\Utils\Helpers;
use Nette\Application\UI\Form;
use Nette\Utils\DateTime;


class DiagnosticPresenter extends BasePresenter
{
	protected $resource = 'content';

	/** @persistent */
	public $selectedModel = NULL;

	/** @var Round */
	private $round;
	/** @var Answer */
	private $answer;
	/** @var LanguageLookup */
	private $languageLookup;
	/** @var Model */
	private $model;
	/** @var Organism */
	private $organism;

	public function injectBase(Round $round, Answer $answer, LanguageLookup $languageLookup, Model $model, Organism $organism)
	{
		$this->round = $round;
		$this->answer = $answer;
		$this->languageLookup = $languageLookup;
		$this->model = $model;
		$this->organism = $organism;
	}

	public function startup()
	{
		$this->perm();
		parent::startup();
	}

	public function actionDefault()
	{
		$range = Helpers::getDatePeriod(DateTime::from('-14 days'), DateTime::from('tomorrow'));
		$this->template->range = $range;

		$this->template->globalStats = $this->organism->getGlobalStats();
		$this->template->roundStats = $this->round->getStats();
		$this->template->organismDistribution = $this->answer->getOrganismDistribution($this->languageLookup->getId(AdminPresenter::LANG), $this->selectedModel);
		$this->template->userStats = $this->round->getUserStats();
		$this->template->answerStats = $this->answer->getStats();
	}

	public function createComponentModelSelection()
	{
		$form = new Form();
		$form->addSelect('model', 'Model', $this->model->getPairs())->setPrompt('Select model')->setDefaultValue($this->selectedModel);
		$form->addSubmit('submitted', 'Select');
		$form->onSubmit[] = function(Form $form) {
			$values = $form->getValues();
			$this->selectedModel = $values['model'];
			$this->redirect('this');
		};
		return $form;
	}

	public function createComponentRepresentationSearch()
	{
		$form = new Form();
		$form->addText('query', 'To search');
		$form->addSubmit('submitted', 'Search');
		$form->onSubmit[] = function(Form $form) {
			$values = $form->getValues();
			$temp = trim(str_replace('[', '', str_replace(']', '', $values['query'])));
			$temp = explode(' ', $temp);
			if (count($temp) > 0 && reset($temp) != '') {
				$this->template->queryResult = $this->organism->getRepresentationsByIds($temp);
			}
		};
		return $form;
	}

	public function createComponentOrganismSearch()
	{
		$form = new Form();
		$form->addText('query', 'To search');
		$form->addSubmit('submitted', 'Search');
		$form->onSubmit[] = function(Form $form) {
			$values = $form->getValues();
			$temp = trim(str_replace('[', '', str_replace(']', '', $values['query'])));
			$temp = explode(' ', $temp);
			if (count($temp) > 0 && reset($temp) != '') {
				$this->template->queryResult = $this->organism->getRepresentationsWithInfoByOrganisms($this->languageLookup->getId(AdminPresenter::LANG), $temp);
			}
		};
		return $form;
	}

}
