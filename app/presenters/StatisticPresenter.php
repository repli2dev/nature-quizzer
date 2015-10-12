<?php

namespace NatureQuizzer\Presenters;

use NatureQuizzer\Database\Model\Answer;
use NatureQuizzer\Database\Model\Round;
use NatureQuizzer\Utils\Helpers;
use Nette\Utils\DateTime;


class StatisticPresenter extends BasePresenter
{
	protected $resource = 'content';

	/** @var Round */
	private $round;
	/** @var Answer */
	private $answer;

	public function injectBase(Round $round, Answer $answer)
	{
		$this->round = $round;
		$this->answer = $answer;
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
		$this->template->organismDistribution = $this->answer->getOrganismDistribution();
		$this->template->userStats = $this->round->getUserStats();
		$this->template->answerStats = $this->answer->getStats();
	}

}
