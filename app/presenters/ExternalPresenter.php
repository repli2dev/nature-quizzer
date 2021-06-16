<?php

namespace NatureQuizzer\Presenters;

use Exception;
use NatureQuizzer\Processors\UserProcessor;
use NatureQuizzer\Utils\Facebook;
use NatureQuizzer\Utils\Google;
use Nette\Application\AbortException;
use Tracy\Debugger;
use Tracy\ILogger;

class ExternalPresenter extends BasePresenter
{

	/** @var Facebook */
	private $facebook;
	/** @var Google */
	private $google;

	/** @var UserProcessor */
	private $userProcessor;

	public function injectExternalDependencies(Facebook $facebook, Google $google, UserProcessor $userProcessor)
	{
		$this->facebook = $facebook;
		$this->google = $google;
		$this->userProcessor = $userProcessor;
	}

	public function actionFb()
	{
		// Force session to start in order to make direct access (without any previous interactions with page work)
		$this->session->start();

		// Suppress Tracy bar as it is causing problems with redirect
		Debugger::enable(Debugger::PRODUCTION);
		$result = NULL;
		try {
			$result = $this->facebook->authenticate($this->link('https://this'));
		} catch (Exception $ex) {
			if ($ex instanceof AbortException) {
				throw $ex;
			}
			Debugger::log($ex, ILogger::EXCEPTION);
			if ($ex->getCode() === Facebook::NOT_AVAILABLE) {
				$this->redirect('Homepage:facebookLoginProblem', 4);
			} else {
				$this->redirect('Homepage:facebookLoginProblem', 1);
			}
			$this->terminate();
		}
		if ($result == NULL) {
			Debugger::log('Obtaining of Facebook session failed for unknown reason.', ILogger::EXCEPTION);
			$this->redirect('Homepage:facebookLoginProblem', 2);
			$this->terminate();
		}
		try {
			$this->userProcessor->loginViaFacebook($result);
		} catch (Exception $ex) {
			Debugger::log($ex, ILogger::EXCEPTION);
			$this->redirect('Homepage:facebookLoginProblem', 3);
			$this->terminate();
		}
		$this->redirect('Homepage:default');
		$this->terminate();
	}

	public function actionGoogle()
	{
		$result = NULL;
		try {
			$result = $this->google->authenticate($this->link('//this'));
		} catch (Exception $ex) {
			if ($ex instanceof AbortException) {
				throw $ex;
			}
			Debugger::log($ex, ILogger::EXCEPTION);
			if ($ex->getCode() === Google::NOT_AVAILABLE) {
				$this->redirect('Homepage:googleLoginProblem', 4);
			} else {
				$this->redirect('Homepage:googleLoginProblem', 1);
			}
			$this->terminate();
		}
		if ($result == NULL) {
			Debugger::log('Obtaining of Google session failed for unknown reason.', ILogger::EXCEPTION);
			$this->redirect('Homepage:googleLoginProblem', 2);
			$this->terminate();
		}
		try {
			$this->userProcessor->loginViaGoogle($result);
		} catch (Exception $ex) {
			Debugger::log($ex, ILogger::EXCEPTION);
			$this->redirect('Homepage:googleLoginProblem', 3);
			$this->terminate();
		}
		$this->redirect('Homepage:default');
		$this->terminate();
	}

}
