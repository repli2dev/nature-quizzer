<?php

namespace NatureQuizzer\Presenters;

use NatureQuizzer\Database\Model\Answer;
use NatureQuizzer\Database\Model\Model;
use NatureQuizzer\Database\Model\Round;
use NatureQuizzer\Database\Utils\LanguageLookup;
use NatureQuizzer\Utils\Helpers;
use Nette\Application\UI\Form;
use Nette\Utils\DateTime;


class StatisticPresenter extends BasePresenter
{
	const LANG = 'cs';

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

	public function injectBase(Round $round, Answer $answer, LanguageLookup $languageLookup, Model $model)
	{
		$this->round = $round;
		$this->answer = $answer;
		$this->languageLookup = $languageLookup;
		$this->model = $model;
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

		$this->template->roundStats = $this->round->getStats();
		$this->template->organismDistribution = $this->answer->getOrganismDistribution($this->languageLookup->getId(self::LANG), $this->selectedModel);
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

}
