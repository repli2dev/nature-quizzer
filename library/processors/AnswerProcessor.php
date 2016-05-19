<?php
namespace NatureQuizzer\Processors;

use NatureQuizzer\Database\Model\Answer;
use NatureQuizzer\Model\IModelFacade;
use NatureQuizzer\Model\ModelFacadeFactory;
use NatureQuizzer\Model\Utils\UserAnswerFactory;
use NatureQuizzer\Runtime\CurrentClient;
use NatureQuizzer\Runtime\CurrentRound;
use NatureQuizzer\Runtime\CurrentUser;
use Nette\Database\UniqueConstraintViolationException;
use Nette\InvalidArgumentException;
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
		// Prevent empty requests to proceed
		if (count($data) == 0 || !isset($data['round']) || !isset($data['conceptId'])) {
			throw new InvalidArgumentException();
		}
		$roundHash = $data['round'];
		$conceptId = $data['conceptId'];

		$userId = $this->currentUser->get();
		$roundId = $this->currentRound->get($roundHash, $userId, $this->prepareClientInfo($data), $conceptId);

		$userAnswer = $this->userAnswerFactory->create($this->modelFacade->getId(), $this->modelFacade->getPersistenceId(), $roundId, $data);
		if (!$userAnswer->isValid()) { // Data are invalid, log it and go away
			Debugger::log(sprintf('User answer is not valid. Stopping.'), Debugger::EXCEPTION);
			Debugger::log($userAnswer, Debugger::EXCEPTION);
			return;
		}
		try {
			$this->answer->insert($userAnswer->toRows());
		} catch (UniqueConstraintViolationException $ex) {
			Debugger::log('Attempted to insert duplicite answer.', Debugger::WARNING);
			Debugger::log($userAnswer, Debugger::WARNING);
		}
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