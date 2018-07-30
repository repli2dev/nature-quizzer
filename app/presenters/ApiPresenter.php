<?php

namespace NatureQuizzer\Presenters;


use Exception;
use NatureQuizzer\Processors\AnswerProcessor;
use NatureQuizzer\Processors\ConceptsProcessor;
use NatureQuizzer\Processors\FeedbackProcessor;
use NatureQuizzer\Processors\QuestionsProcessor;
use NatureQuizzer\Processors\SummaryProcessor;
use NatureQuizzer\Processors\UserProcessor;
use NatureQuizzer\RequestProcessorException;
use Nette\Application\AbortException;
use Nette\InvalidArgumentException;
use Tracy\Debugger;
use Tracy\ILogger;

class ApiPresenter extends BasePresenter
{

	/** @var AnswerProcessor */
	private $answerProcessor;
	/** @var ConceptsProcessor */
	private $conceptsProcessor;
	/** @var QuestionsProcessor */
	private $questionsProcessor;
	/** @var UserProcessor */
	private $userProcessor;
	/** @var FeedbackProcessor */
	private $feedbackProcessor;
	/** @var SummaryProcessor */
	private $summaryProcessor;

	public function injectBase(AnswerProcessor $answerProcessor, ConceptsProcessor $conceptsProcessor,
							   QuestionsProcessor $questionsProcessor, UserProcessor $userProcessor,
							   FeedbackProcessor $feedbackProcessor, SummaryProcessor $summaryProcessor)
	{
		$this->answerProcessor = $answerProcessor;
		$this->conceptsProcessor = $conceptsProcessor;
		$this->questionsProcessor = $questionsProcessor;
		$this->userProcessor = $userProcessor;
		$this->feedbackProcessor = $feedbackProcessor;
		$this->summaryProcessor = $summaryProcessor;
	}

	public function actionConcept($conceptId)
	{
		try {
			$output = $this->conceptsProcessor->get($conceptId);
			$this->sendJson($output);
		} catch (RequestProcessorException $ex) {
			Debugger::log($ex, ILogger::WARNING); // These exceptions are due to malformed requests
			$this->sendErrorJSON($ex->getCode(), $ex->getMessage());
		} catch (AbortException $ex) {
			throw $ex; // This is Nette application stuff, needs to be rethrowed
		} catch (Exception $ex) {
			Debugger::log($ex, Debugger::CRITICAL);
			$this->sendErrorJSON(0, 'Unknown error');
		}
	}

	public function actionConceptDetail($conceptId)
	{
		try {
			$output = $this->conceptsProcessor->getDetail($conceptId);
			$this->sendJson($output);
		} catch (RequestProcessorException $ex) {
			Debugger::log($ex, ILogger::WARNING); // These exceptions are due to malformed requests
			$this->sendErrorJSON($ex->getCode(), $ex->getMessage());
		} catch (AbortException $ex) {
			throw $ex; // This is Nette application stuff, needs to be rethrowed
		} catch (Exception $ex) {
			Debugger::log($ex, Debugger::CRITICAL);
			$this->sendErrorJSON(0, 'Unknown error');
		}
	}

	public function actionConcepts()
	{
		try {
			$output = $this->conceptsProcessor->getAll();
			$this->sendJson($output);
		} catch (RequestProcessorException $ex) {
			Debugger::log($ex, ILogger::WARNING); // These exceptions are due to malformed requests
			$this->sendErrorJSON($ex->getCode(), $ex->getMessage());
		} catch (AbortException $ex) {
			throw $ex; // This is Nette application stuff, needs to be rethrowed
		} catch (Exception $ex) {
			Debugger::log($ex, Debugger::CRITICAL);
			$this->sendErrorJSON(0, 'Unknown error');
		}
	}
	public function actionQuick()
	{
		try {
			$output = $this->conceptsProcessor->getQuick();
			$this->sendJson($output);
		} catch (RequestProcessorException $ex) {
			Debugger::log($ex, ILogger::WARNING); // These exceptions are due to malformed requests
			$this->sendErrorJSON($ex->getCode(), $ex->getMessage());
		} catch (AbortException $ex) {
			throw $ex; // This is Nette application stuff, needs to be rethrowed
		} catch (Exception $ex) {
			Debugger::log($ex, Debugger::CRITICAL);
			$this->sendErrorJSON(0, 'Unknown error');
		}
	}

	public function actionQuestions($conceptId, $count = 10)
	{
		try {
			$output = $this->questionsProcessor->get($conceptId, $count);
			$this->sendJson($output);
		} catch (RequestProcessorException $ex) {
			Debugger::log($ex, ILogger::WARNING); // These exceptions are due to malformed requests
			$this->sendErrorJSON($ex->getCode(), $ex->getMessage());
		} catch (AbortException $ex) {
			throw $ex; // This is Nette application stuff, needs to be rethrowed
		} catch (Exception $ex) {
			Debugger::log($ex, Debugger::CRITICAL);
			$this->sendErrorJSON(0, 'Unknown error');
		}
	}

	public function actionAnswer()
	{
		try {
			$data = $this->request->getPost();
			$this->answerProcessor->save($data);
			$this->terminate();
		} catch (AbortException $ex) {
			throw $ex; // This is Nette application stuff, needs to be rethrowed
		} catch (InvalidArgumentException $ex) {
			$this->sendErrorJSON(1, 'Empty request');
		} catch (Exception $ex) {
			Debugger::log($ex, Debugger::CRITICAL);
		}
	}

	public function actionFeedback()
	{
		try {
			$data = $this->request->getPost();
			$output = $this->feedbackProcessor->send($data);
			$this->sendJson($output);
			$this->terminate();
		} catch (AbortException $ex) {
			throw $ex; // This is Nette application stuff, needs to be rethrowed
		} catch (Exception $ex) {
			Debugger::log($ex, Debugger::CRITICAL);
		}
	}

	public function actionRoundSummary()
	{
		try {
			$this->sendJson($this->summaryProcessor->get());
			$this->terminate();
		} catch (AbortException $ex) {
			throw $ex; // This is Nette application stuff, needs to be rethrowed
		} catch (Exception $ex) {
			Debugger::log($ex, Debugger::CRITICAL);
		}
	}

	public function actionUserProfile()
	{
		try {
			$this->sendJson($this->userProcessor->profile());
			$this->terminate();
		} catch (AbortException $ex) {
			throw $ex; // This is Nette application stuff, needs to be rethrowed
		} catch (Exception $ex) {
			Debugger::log($ex, Debugger::CRITICAL);
		}
	}

	public function actionUserLogin()
	{
		try {
			$data = $this->request->getPost();
			$this->sendJson($this->userProcessor->login($data));
			$this->terminate();
		} catch (AbortException $ex) {
			throw $ex; // This is Nette application stuff, needs to be rethrowed
		} catch (Exception $ex) {
			Debugger::log($ex, Debugger::CRITICAL);
		}
	}

	public function actionUserRegister()
	{
		try {
			$data = $this->request->getPost();
			$this->sendJson($this->userProcessor->register($data));
			$this->terminate();
		} catch (AbortException $ex) {
			throw $ex; // This is Nette application stuff, needs to be rethrowed
		} catch (Exception $ex) {
			Debugger::log($ex, Debugger::CRITICAL);
		}
	}

	public function actionUserLogout()
	{
		try {
			$this->sendJson($this->userProcessor->logout());
			$this->terminate();
		} catch (AbortException $ex) {
			throw $ex; // This is Nette application stuff, needs to be rethrowed
		} catch (Exception $ex) {
			Debugger::log($ex, Debugger::CRITICAL);
		}
	}

	private function sendErrorJSON($code, $message)
	{
		$this->sendJson([
			'error' => [
				'code' => $code,
				'message' => $message
			]
		]);
		$this->terminate();
	}

}
