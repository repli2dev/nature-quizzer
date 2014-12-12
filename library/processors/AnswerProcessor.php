<?php
namespace NatureQuizzer\Processors;

use NatureQuizzer\Database\Model\Answer;
use NatureQuizzer\Database\Model\CurrentKnowledge;
use NatureQuizzer\Database\Model\Organism;
use NatureQuizzer\Database\Model\OrganismDifficulty;
use NatureQuizzer\Database\Model\PriorKnowledge;
use NatureQuizzer\Model\Utils\PostAnswerUpdate;
use NatureQuizzer\Model\Utils\UserAnswerFactory;
use NatureQuizzer\Runtime\CurrentClient;
use NatureQuizzer\Runtime\CurrentRound;
use NatureQuizzer\Runtime\CurrentUser;
use Nette\Object;
use Nette\Utils\Json;
use Tracy\Debugger;

class AnswerProcessor extends Object
{

	const A = 1; // TBD: find proper one
	const B = 0.05; // TBD: find proper one

	/** @var CurrentUser */
	private $currentUser;
	/** @var CurrentClient */
	private $currentClient;
	/** @var CurrentRound */
	private $currentRound;

	/** @var Answer */
	private $answer;
	/** @var UserAnswerFactory */
	private $userAnswerFactory;
	/** @var PostAnswerUpdate */
	private $postAnswerUpdate;

	public function __construct(CurrentUser $currentUser, CurrentClient $currentClient, CurrentRound $currentRound,
								Answer $answer, UserAnswerFactory $userAnswerFactory, PostAnswerUpdate $postAnswerUpdate)
	{
		list (
			$this->currentUser,
			$this->currentClient,
			$this->currentRound,
			$this->answer,
			$this->userAnswerFactory,
			$this->postAnswerUpdate
		) = func_get_args();
	}

	public function save($data)
	{
		$roundHash = $data['round'];

		$userId = $this->currentUser->get();
		$roundId = $this->currentRound->get($roundHash, $userId, $this->prepareClientInfo($data));

		$userAnswer = $this->userAnswerFactory->create($roundId, $data);
		if (!$userAnswer->isValid()) { // Data are invalid, log it and go away
			Debugger::log(sprintf('User answer is not valid. Stopping.'), Debugger::ERROR);
			Debugger::log($userAnswer, Debugger::ERROR);
			return;
		}

		$this->answer->insert($userAnswer->toRows());
		$this->postAnswerUpdate->perform($userId, $userAnswer);
	}

	public function prepareClientInfo($data)
	{
		$output = $this->currentClient->get();
		$output['screenWidth'] = $data['screenWidth'];
		$output['screenHeight'] = $data['screenHeight'];
		return $output;
	}
}