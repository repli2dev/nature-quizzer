<?php

namespace NatureQuizzer\Presenters;


use Exception;
use NatureQuizzer\Processors\AnswerProcessor;
use NatureQuizzer\Processors\ConceptsProcessor;
use NatureQuizzer\Processors\FeedbackProcessor;
use NatureQuizzer\Processors\QuestionsProcessor;
use NatureQuizzer\Processors\UserProcessor;
use NatureQuizzer\RequestProcessorException;
use Nette\Application\AbortException;
use Tracy\Debugger;

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

	public function injectBase(AnswerProcessor $answerProcessor, ConceptsProcessor $conceptsProcessor,
							   QuestionsProcessor $questionsProcessor, UserProcessor $userProcessor,
							   FeedbackProcessor $feedbackProcessor)
	{
		$this->answerProcessor = $answerProcessor;
		$this->conceptsProcessor = $conceptsProcessor;
		$this->questionsProcessor = $questionsProcessor;
		$this->userProcessor = $userProcessor;
		$this->feedbackProcessor = $feedbackProcessor;
	}

	public function actionConcept($conceptId)
	{
		try {
			$output = $this->conceptsProcessor->get($conceptId);
			$this->sendJson($output);
		} catch (RequestProcessorException $ex) {
			$this->sendErrorJSON($ex->getCode(), $ex->getMessage());
		} catch (AbortException $ex) {
			throw $ex;
		} catch (Exception $ex) {
			Debugger::log($ex, Debugger::EXCEPTION);
			$this->sendErrorJSON(0, 'Unknown error');
		}
	}

	public function actionConcepts()
	{
		try {
			$output = $this->conceptsProcessor->getAll();
			$this->sendJson($output);
		} catch (RequestProcessorException $ex) {
			$this->sendErrorJSON($ex->getCode(), $ex->getMessage());
		} catch (AbortException $ex) {
			throw $ex;
		} catch (Exception $ex) {
			Debugger::log($ex, Debugger::EXCEPTION);
			$this->sendErrorJSON(0, 'Unknown error');
		}
	}
	public function actionQuick()
	{
		try {
			$output = $this->conceptsProcessor->getQuick();
			$this->sendJson($output);
		} catch (RequestProcessorException $ex) {
			$this->sendErrorJSON($ex->getCode(), $ex->getMessage());
		} catch (AbortException $ex) {
			throw $ex;
		} catch (Exception $ex) {
			Debugger::log($ex, Debugger::EXCEPTION);
			$this->sendErrorJSON(0, 'Unknown error');
		}
	}

	public function actionQuestions($conceptId, $count = 12)
	{
		try {
			$output = $this->questionsProcessor->get($conceptId, $count);
			$this->sendJson($output);
		} catch (RequestProcessorException $ex) {
			$this->sendErrorJSON($ex->getCode(), $ex->getMessage());
		} catch (AbortException $ex) {
			throw $ex;
		} catch (Exception $ex) {
			Debugger::log($ex, Debugger::EXCEPTION);
			$this->sendErrorJSON(0, 'Unknown error');
		}
	}

	public function actionAnswer()
	{
		$data = $this->request->getPost();
		$this->answerProcessor->save($data);
		$this->terminate();
	}

	public function actionFeedback()
	{
		$data = $this->request->getPost();
		$output = $this->feedbackProcessor->send($data);
		$this->sendJson($output);
		$this->terminate();
	}

	public function actionUserProfile()
	{
		$this->sendJson($this->userProcessor->profile());
		$this->terminate();
	}

	public function actionUserLogin()
	{
		$data = $this->request->getPost();
		$this->sendJson($this->userProcessor->login($data));
		$this->terminate();
	}

	public function actionUserRegister()
	{
		$data = $this->request->getPost();
		$this->sendJson($this->userProcessor->register($data));
		$this->terminate();
	}

	public function actionUserLogout()
	{
		$this->sendJson($this->userProcessor->logout());
		$this->terminate();
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
