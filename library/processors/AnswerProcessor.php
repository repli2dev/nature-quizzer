<?php
namespace NatureQuizzer\Processors;

use NatureQuizzer\Database\Model\Answer;
use NatureQuizzer\Model\IModelFacade;
use NatureQuizzer\Model\ModelFacadeFactory;
use NatureQuizzer\Model\Utils\UserAnswerFactory;
use NatureQuizzer\Runtime\CurrentClient;
use NatureQuizzer\Runtime\CurrentRound;
use NatureQuizzer\Runtime\CurrentUser;
use Nette\Object;
use Tracy\Debugger;

class AnswerProcessor extends Object
{
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
	/** @var IModelFacade */
	private $modelFacade;

	public function __construct(CurrentUser $currentUser, CurrentClient $currentClient, CurrentRound $currentRound,
								Answer $answer, UserAnswerFactory $userAnswerFactory, ModelFacadeFactory $modelFacadeFactory)
	{
		$this->currentUser = $currentUser;
		$this->currentClient = $currentClient;
		$this->currentRound = $currentRound;
		$this->answer = $answer;
		$this->userAnswerFactory = $userAnswerFactory;
		$this->modelFacade = $modelFacadeFactory->get($this->currentUser->get());
	}

	public function save($data)
	{
		$roundHash = $data['round'];

		$userId = $this->currentUser->get();
		$roundId = $this->currentRound->get($roundHash, $userId, $this->prepareClientInfo($data));

		$userAnswer = $this->userAnswerFactory->create($this->modelFacade->getId(), $roundId, $data);
		if (!$userAnswer->isValid()) { // Data are invalid, log it and go away
			Debugger::log(sprintf('User answer is not valid. Stopping.'), Debugger::EXCEPTION);
			Debugger::log($userAnswer, Debugger::EXCEPTION);
			return;
		}

		$this->answer->insert($userAnswer->toRows());
		$this->modelFacade->answer($userId, $userAnswer);
	}

	public function prepareClientInfo($data)
	{
		$output = $this->currentClient->get();
		$output['screenWidth'] = $data['screenWidth'];
		$output['screenHeight'] = $data['screenHeight'];
		return $output;
	}
}